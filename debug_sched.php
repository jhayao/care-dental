<?php
require_once 'db_connect.php';

echo "<h2>Debug Scheduled Payments</h2>";

// 1. Check if any scheduled payments exist
$res = $conn->query("SELECT * FROM payments WHERE status = 'scheduled'");
if ($res->num_rows > 0) {
    echo "Found " . $res->num_rows . " scheduled payments:<br>";
    echo "<table border='1'><tr><th>ID</th><th>Booking ID</th><th>Amount</th><th>Due Date</th><th>Desc</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['booking_id']}</td><td>{$row['total_price']}</td><td>{$row['due_date']}</td><td>{$row['description']}</td></tr>";
    }
    echo "</table><br>";
} else {
    echo "No scheduled payments found in the database.<br>";
}

// 2. Check Package/Service configurations
echo "<h3>Packages with Installments > 0</h3>";
$res = $conn->query("SELECT id, package_name, down_payment, installment_months FROM packages WHERE installment_months > 0");
while ($row = $res->fetch_assoc()) {
    echo "Pkg #{$row['id']}: {$row['package_name']} - Down: {$row['down_payment']} - Months: {$row['installment_months']}<br>";
}

echo "<h3>Services with Installments > 0</h3>";
$res = $conn->query("SELECT id, service_name, down_payment, installment_months FROM services WHERE installment_months > 0");
while ($row = $res->fetch_assoc()) {
    echo "Svc #{$row['id']}: {$row['service_name']} - Down: {$row['down_payment']} - Months: {$row['installment_months']}<br>";
}
?>
