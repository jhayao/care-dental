<?php
require_once 'db_connect.php';

function addColumn($conn, $table, $column, $type) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($check->num_rows == 0) {
            $sql = "ALTER TABLE $table ADD COLUMN $column $type";
            if ($conn->query($sql)) {
                echo "Added column '$column' to '$table'.\n";
            } else {
                echo "Error adding '$column': " . $conn->error . "\n";
            }
        } else {
            echo "Column '$column' already exists in '$table'.\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

echo "Running schema update...\n";
addColumn($conn, 'payments', 'payment_url', 'VARCHAR(500) DEFAULT NULL');
addColumn($conn, 'payments', 'description', 'VARCHAR(255) DEFAULT NULL');
echo "Done.\n";
?>
