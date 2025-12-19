<?php
session_start();
require_once '../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['error' => 'Booking ID missing']);
    exit;
}

$bookingId = intval($data['id']);
$status = $data['status'] ?? null;
$bookingDate = $data['booking_date'] ?? null;
$timeSlot = $data['time_slot'] ?? null;

// If rescheduling
if ($bookingDate && $timeSlot) {
    $stmt = $conn->prepare("UPDATE bookings 
        SET booking_date = ?, time_slot = ?, status = 'rescheduled', updated_at = NOW()
        WHERE id = ?");
    $stmt->bind_param("ssi", $bookingDate, $timeSlot, $bookingId);
} elseif ($status) {
    // If updating status only
    $stmt = $conn->prepare("UPDATE bookings 
        SET status = ?, updated_at = NOW()
        WHERE id = ?");
    $stmt->bind_param("si", $status, $bookingId);
} else {
    echo json_encode(['error' => 'Nothing to update']);
    exit;
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to update booking']);
}
$stmt->close();
?>
