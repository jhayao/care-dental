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
    
    // FETCH OLD DETAILS
    $stmt = $conn->prepare("SELECT appointment_date, time_slot FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $oldBooking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $old_date = $oldBooking['appointment_date'] ?? null;
    $old_time = $oldBooking['time_slot'] ?? null;

    $stmt = $conn->prepare("UPDATE bookings 
        SET appointment_date = ?, appointment_time = ?, time_slot = ?, status = 'rescheduled', updated_at = NOW()
        WHERE id = ?");
    $stmt->bind_param("sssi", $bookingDate, $timeSlot, $timeSlot, $bookingId);
    
    if ($stmt->execute()) {
        require_once '../config.php';
        require_once '../QStashService.php';
        
        QStashService::schedule(
            APP_URL . "/webhook_notification.php",
            [
                'booking_id' => $bookingId, 
                'type' => 'rescheduled',
                'old_date' => $old_date,
                'old_time' => $old_time,
                'initiator' => 'staff'
            ],
            0
        );
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update booking']);
    }
    $stmt->close();
    exit;

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
