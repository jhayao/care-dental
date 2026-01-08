<?php
require_once 'db_connect.php';

echo "<h2>Debug Latest Booking</h2>";

$res = $conn->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 1");
$booking = $res->fetch_assoc();

if ($booking) {
    echo "Latest Booking ID: " . $booking['id'] . "<br>";
    echo "Total Amount: " . $booking['total_amount'] . "<br>";
    echo "User ID: " . $booking['user_id'] . "<br>";
    
    // Check Payments
    $pres = $conn->query("SELECT * FROM payments WHERE booking_id = " . $booking['id']);
    $total_paid = 0;
    echo "<h3>Payments</h3>";
    echo "<table border='1'><tr><th>ID</th><th>Amount</th><th>Status</th><th>Type</th></tr>";
    while ($p = $pres->fetch_assoc()) {
        echo "<tr><td>{$p['id']}</td><td>{$p['total_price']}</td><td>{$p['status']}</td><td>{$p['payment_method']}</td></tr>";
        if ($p['status'] == 'approved' || $p['status'] == 'paid' || $p['status'] == 'completed') {
            $total_paid += $p['total_price'];
        }
    }
    echo "</table>";
    echo "Total Paid (Approved): " . $total_paid . "<br>";
    
    $remaining = $booking['total_amount'] - $total_paid;
    echo "Calculated Remaining: " . $remaining . "<br>";

    // Check Items
    echo "<h3>Booking Items</h3>";
    $ires = $conn->query("SELECT * FROM booking_items WHERE booking_id = " . $booking['id']);
    while ($item = $ires->fetch_assoc()) {
        echo "Item: {$item['item_type']} ID: {$item['item_id']} <br>";
        
        if ($item['item_type'] == 'package') {
            $pkg = $conn->query("SELECT * FROM packages WHERE id = " . $item['item_id'])->fetch_assoc();
            echo " -> Package Config: Down: {$pkg['down_payment']}, Months: {$pkg['installment_months']} <br>";
        } elseif ($item['item_type'] == 'service') {
            $svc = $conn->query("SELECT * FROM services WHERE id = " . $item['item_id'])->fetch_assoc();
            echo " -> Service Config: Down: {$svc['down_payment']}, Months: {$svc['installment_months']} <br>";
        }
    }

} else {
    echo "No bookings found.";
}
?>
