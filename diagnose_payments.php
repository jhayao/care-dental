<?php
require 'db_connect.php';

$orphaned_bookings = $conn->query("SELECT COUNT(*) FROM payments p LEFT JOIN bookings b ON p.booking_id = b.id WHERE b.id IS NULL")->fetch_row()[0];
$orphaned_users = $conn->query("SELECT COUNT(*) FROM payments p JOIN bookings b ON p.booking_id = b.id LEFT JOIN users u ON b.user_id = u.id WHERE u.id IS NULL")->fetch_row()[0];

echo "Orphaned Bookings (Payment -> X): $orphaned_bookings\n";
echo "Orphaned Users (Payment -> Booking -> X): $orphaned_users\n";
?>
