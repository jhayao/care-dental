<?php
require_once '../db_connect.php';

// Add 'completed' to ENUM
$sql = "ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'rescheduled', 'refunded', 'completed') DEFAULT 'pending'";

if ($conn->query($sql) === TRUE) {
    echo "Database updated successfully: Added 'completed' status.";
} else {
    echo "Error updating database: " . $conn->error;
}
?>
