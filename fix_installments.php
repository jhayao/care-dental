<?php
// fix_installments.php
// Manually run this for booking #49 (or any booking that missed installment generation)
require_once 'db_connect.php';
require_once 'config.php';
require_once 'QStashService.php';

$booking_id = 49; // HARDCODED for the user's failed booking
echo "Fixing installments for Booking #$booking_id ...<br>";

// 1. Get Booking & Payment details
$b_res = $conn->query("SELECT total_amount, user_id FROM bookings WHERE id=$booking_id")->fetch_assoc();
$total_booking_val = $b_res['total_amount'];

// Calculate already paid
$p_res = $conn->query("SELECT SUM(total_price) as paid FROM payments WHERE booking_id=$booking_id AND status IN ('approved', 'paid', 'completed')");
$paid_row = $p_res->fetch_assoc();
$amount_paid_now = $paid_row['paid'] ?? 0;
$remaining = $total_booking_val - $amount_paid_now;

echo "Total: $total_booking_val, Paid: $amount_paid_now, Remaining: $remaining <br>";

if ($remaining > 100) {
     // Find max months
     $max_months = 0;
     $q_pkg = $conn->query("SELECT p.installment_months FROM booking_items bi JOIN packages p ON bi.item_id = p.id WHERE bi.booking_id = $booking_id AND bi.item_type='package'");
     while($row = $q_pkg->fetch_assoc()) if($row['installment_months'] > $max_months) $max_months = $row['installment_months'];
     
     $q_svc = $conn->query("SELECT s.installment_months FROM booking_items bi JOIN services s ON bi.item_id = s.id WHERE bi.booking_id = $booking_id AND bi.item_type='service'");
     while($row = $q_svc->fetch_assoc()) if($row['installment_months'] > $max_months) $max_months = $row['installment_months'];

     echo "Max Months: $max_months <br>";

     if ($max_months > 0) {
         $monthly_amount = $remaining / $max_months;
         
         $stmt_sched = $conn->prepare("INSERT INTO payments (booking_id, total_price, payment_method, status, due_date, installment_number, description) VALUES (?, ?, 'Link', 'scheduled', ?, ?, ?)");
         
         for ($i = 1; $i <= $max_months; $i++) {
             $due_date = date('Y-m-d', strtotime("+$i month")); 
             $desc = "Installment #$i of $max_months";
             
             // Check if already exists to avoid duplicates
             $check = $conn->query("SELECT id FROM payments WHERE booking_id=$booking_id AND installment_number=$i");
             if ($check->num_rows > 0) {
                 echo "Installment #$i already exists. Skipping.<br>";
                 continue;
             }
             
             $stmt_sched->bind_param("idsis", $booking_id, $monthly_amount, $due_date, $i, $desc);
             if ($stmt_sched->execute()) {
                 $sched_pay_id = $stmt_sched->insert_id;
                 echo "Created Installment #$i (ID: $sched_pay_id) - Due: $due_date <br>";
                 
                 // QStash logic removed.
                 echo " -> No QStash scheduled (On-demand mode)<br>";
             } else {
                 echo "Error creating installment #$i: " . $stmt_sched->error . "<br>";
             }
         }
         $stmt_sched->close();
     } else {
         echo "No installment term found for this booking.<br>";
     }
} else {
    echo "No significant remaining balance.<br>";
}
echo "Done.";
?>
