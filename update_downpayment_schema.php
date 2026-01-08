<?php
require_once 'db_connect.php';

function addColumn($conn, $table, $column, $type) {
    echo "Checking $table for $column...\n";
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE $table ADD COLUMN $column $type";
        if ($conn->query($sql)) {
            echo "SUCCESS: Added '$column' to '$table'.\n";
        } else {
            echo "ERROR: " . $conn->error . "\n";
        }
    } else {
        echo "INFO: '$column' already exists in '$table'.\n";
    }
}

echo "Running Down Payment Schema Update...\n";
addColumn($conn, 'packages', 'down_payment', 'DECIMAL(10,2) DEFAULT 0.00');
addColumn($conn, 'services', 'down_payment', 'DECIMAL(10,2) DEFAULT 0.00');
echo "Done.\n";
?>
