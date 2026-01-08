<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;

if (!isset($_GET['id'])) {
    header("Location: appointments.php");
    exit;
}

$booking_id = (int) $_GET['id'];

// Fetch Xendit Invoice ID from DB
$stmt = $conn->prepare("SELECT xendit_invoice_id, status, total_price FROM payments WHERE booking_id = ? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die("Payment record not found.");
}

// Check Xendit Status (Fallback if Webhook failed)
// Use the same key as in confirm.php
// Check Xendit Status (Fallback if Webhook failed)
// Use the same key as in confirm.php
Configuration::setXenditKey(XENDIT_API_KEY);

$apiInstance = new InvoiceApi();

try {
    $invoice = $apiInstance->getInvoiceById($payment['xendit_invoice_id']);
    
    // If Xendit says PAID/SETTLED but DB is still 'pending', update it now
    if (($invoice['status'] === 'PAID' || $invoice['status'] === 'SETTLED') && $payment['status'] === 'pending') {
        
        // Update Payment
        $stmt = $conn->prepare("UPDATE payments SET status='approved', xendit_payment_id=?, payment_date=NOW() WHERE booking_id=?");
        $paidStatus = $invoice['id']; // or internal ID logic
        $stmt->bind_param("si", $paidStatus, $booking_id);
        $stmt->execute();
        $stmt->close();

        // Update Booking
        $stmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        
        // Refresh local status for display
        $payment['status'] = 'approved';

        // NEW: Queue 'Approved' Email
        require_once 'config.php';
        require_once 'QStashService.php';
        
        // ----------------- AUTOMATED INSTALLMENTS -----------------
        // 1. Check if booking includes items with installment_months > 0
        // Calculate remaining balance to be split
        
        // Fetch Booking Total & Down Payment paid
        // We know 'payment_id' (this transaction) covered the Down Payment (amountToPayNow)
        // We need to see if there is a 'remaining balance'.
        // Actually, 'total_amount' in bookings table is the FULL price.
        // 'total_price' in payments table is what was just paid.
        
        $b_query = $conn->prepare("SELECT total_amount FROM bookings WHERE id=?");
        $b_query->bind_param("i", $booking_id);
        $b_query->execute();
        $b_res = $b_query->get_result()->fetch_assoc();
        $b_query->close();
        
        $total_booking_val = $b_res['total_amount'] ?? 0;
        $amount_paid_now = $payment['total_price'];
        $remaining = $total_booking_val - $amount_paid_now;
        
        if ($remaining > 100) { // If significant remaining balance > 100 PHP
             // Find the MAX installment term from the bought items
             // If multiple items, we usually just take the max term or average? 
             // Simplest: Check if any item has installment_months > 0.
             // We need to join booking_items -> packages/services
             
             $max_months = 0;
             
             // Check Packages
             $q_pkg = $conn->query("
                SELECT p.installment_months 
                FROM booking_items bi 
                JOIN packages p ON bi.item_id = p.id 
                WHERE bi.booking_id = $booking_id AND bi.item_type='package'
             ");
             while($row = $q_pkg->fetch_assoc()) {
                 if($row['installment_months'] > $max_months) $max_months = $row['installment_months'];
             }
             
             // Check Services
             $q_svc = $conn->query("
                SELECT s.installment_months 
                FROM booking_items bi 
                JOIN services s ON bi.item_id = s.id 
                WHERE bi.booking_id = $booking_id AND bi.item_type='service'
             ");
             while($row = $q_svc->fetch_assoc()) {
                 if($row['installment_months'] > $max_months) $max_months = $row['installment_months'];
             }
             
             if ($max_months > 0) {
                 $monthly_amount = $remaining / $max_months;
                 
                 // Create Scheduled Payments
                 $stmt_sched = $conn->prepare("
                    INSERT INTO payments (booking_id, total_price, payment_method, status, due_date, installment_number, description, payment_date)
                    VALUES (?, ?, 'Link', 'scheduled', ?, ?, ?, NULL)
                 ");
                 
                 for ($i = 1; $i <= $max_months; $i++) {
                     $due_date = date('Y-m-d', strtotime("+$i month")); // Due in +i months
                     $desc = "Installment #$i of $max_months";
                     
                     // We use status='scheduled'. 
                     // payment_date is NULL because not paid yet.
                     
                     $stmt_sched->bind_param("idsis", $booking_id, $monthly_amount, $due_date, $i, $desc);
                     $stmt_sched->execute();
                     $sched_pay_id = $stmt_sched->insert_id;
                     
                     // QStash setup removed.
                 }
                 $stmt_sched->close();
             }
        }

        QStashService::schedule(
            APP_URL . "/webhook_notification.php", 
            ['booking_id' => $booking_id, 'type' => 'approved'], 
            0 // Immediate
        );
    }

} catch (Exception $e) {
    // Ignore API error, just show page
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Success</title>
    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center">
    <div class="mb-4">
        <svg class="w-20 h-20 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
    </div>

    <h2 class="text-2xl font-bold text-gray-800 mb-2">Payment Successful!</h2>
    <p class="text-gray-600 mb-6">Thank you for your booking.</p>

    <?php if ($payment['status'] === 'approved'): ?>
         <div class="bg-green-50 text-green-700 p-3 rounded mb-4 text-sm font-semibold">
            Status: Confirmed & Paid
        </div>
    <?php else: ?>
        <div class="bg-yellow-50 text-yellow-700 p-3 rounded mb-4 text-sm">
            Status: Processing (Please wait...)
        </div>
    <?php endif; ?>

    <p class="text-sm text-gray-500 mb-6">
        A confirmation email will be sent to you shortly.
    </p>

    <a href="appointments.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-200">
        Go to My Appointments
    </a>
</div>

</body>
</html>
