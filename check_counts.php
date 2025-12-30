<?php
require 'db_connect.php';
$counts = [];
foreach (['payments', 'bookings', 'users'] as $table) {
    if ($res = $conn->query("SELECT COUNT(*) FROM $table")) {
        $counts[$table] = $res->fetch_row()[0];
    } else {
        $counts[$table] = "Error: " . $conn->error;
    }
}
print_r($counts);
?>
