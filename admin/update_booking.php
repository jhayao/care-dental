<?php
session_start();
require_once '../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$id = intval($data['id']);

if (isset($data['booking_date']) && isset($data['time_slot'])) {
    // Correctly update appointment_date and appointment_time (and time_slot for legacy)
    $stmt = $conn->prepare("UPDATE bookings SET appointment_date = ?, appointment_time = ?, time_slot = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $data['booking_date'], $data['time_slot'], $data['time_slot'], $id);
} elseif (isset($data['status'])) {
    $stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $data['status'], $id);
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
