<?php
require_once 'db_connect.php';

function getTableSchema($conn, $table) {
    echo "Schema for $table\n";
    $result = $conn->query("SHOW CREATE TABLE $table");
    if ($row = $result->fetch_assoc()) {
        echo $row['Create Table'] . "\n\n";
    }
}

getTableSchema($conn, 'bookings');
getTableSchema($conn, 'payments');
?>
