<?php
require_once 'db_connect.php';

echo "<h2>Debug Package/Service Configuration</h2>";

echo "<h3>All Packages</h3>";
$res = $conn->query("SELECT id, package_name, down_payment, installment_months FROM packages");
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Down Payment</th><th>Months</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['package_name']}</td><td>{$row['down_payment']}</td><td>{$row['installment_months']}</td></tr>";
}
echo "</table>";

echo "<h3>All Services</h3>";
$res = $conn->query("SELECT id, service_name, down_payment, installment_months FROM services");
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Down Payment</th><th>Months</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['service_name']}</td><td>{$row['down_payment']}</td><td>{$row['installment_months']}</td></tr>";
}
echo "</table>";
?>
