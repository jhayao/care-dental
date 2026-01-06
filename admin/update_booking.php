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

    // FETCH OLD DETAILS FOR NOTIFICATION
    $stmt = $conn->prepare("SELECT appointment_date, time_slot FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $oldBooking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $old_date = $oldBooking['appointment_date'] ?? null;
    $old_time = $oldBooking['time_slot'] ?? null;

    // Correctly update appointment_date and appointment_time (and time_slot for legacy)
    // Also set status to 'rescheduled' automatically if desired, or keep previous status. 
    // Usually rescheduling implies 'rescheduled' status, but the original code didn't force it.
    // However, to be consistent with booking_actions.php, let's update status to rescheduled if not specified.
    // But here we just update date/time. The prompt says "When Admin/staff reschedule appointments...".
    // I will add status update to be safe and consistent.
    
    $stmt = $conn->prepare("UPDATE bookings SET appointment_date = ?, appointment_time = ?, time_slot = ?, status = 'rescheduled', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $data['booking_date'], $data['time_slot'], $data['time_slot'], $id);
    
    if ($stmt->execute()) {
        require_once '../config.php';
        require_once '../QStashService.php';
        
        QStashService::schedule(
            APP_URL . "/webhook_notification.php",
            [
                'booking_id' => $id, 
                'type' => 'rescheduled',
                'old_date' => $old_date,
                'old_time' => $old_time,
                'initiator' => 'admin'
            ],
            0
        );
        echo json_encode(['success' => true]);
    } else {
         echo json_encode(['error' => 'Failed to update booking']);
    }
    $stmt->close();
    exit;

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
