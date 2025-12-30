<?php
require_once 'db_connect.php';

$conn->query("ALTER TABLE users ADD COLUMN dob DATE NULL AFTER gender");

if ($conn->error) {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "Column 'dob' already exists.";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Successfully added 'dob' column.";
}
?>
