<?php
require_once '../db_connect.php';
$bookingId = 66; // I should pick a recent booking ID that I know exists. 
// Let's first fetch the latest booking ID.
$res = $conn->query("SELECT id FROM bookings ORDER BY id DESC LIMIT 1");
$row = $res->fetch_assoc();
$id = $row['id'];
echo "Checking Booking ID: $id \n";

$stmt = $conn->prepare("SELECT * FROM booking_items WHERE booking_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
print_r($items);
?>
