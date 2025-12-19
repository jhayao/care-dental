<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$booking_id = $_POST['booking_id'] ?? '';

if (empty($booking_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Booking ID is required.']);
    exit;
}

if ($action === 'cancel') {
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled', cancelled_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Booking cancelled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to cancel booking.']);
    }
    $stmt->close();
    exit;
}

if ($action === 'reschedule') {
    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';

    if (empty($new_date) || empty($new_time)) {
        echo json_encode(['status' => 'error', 'message' => 'New date and time are required.']);
        exit;
    }

    // UPDATED: Set status = 'Rescheduled'
    $stmt = $conn->prepare("UPDATE bookings 
        SET booking_date = ?, time_slot = ?, status = 'Rescheduled' 
        WHERE id = ? AND user_id = ?");
        
    $stmt->bind_param("ssii", $new_date, $new_time, $booking_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Booking rescheduled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reschedule booking.']);
    }

    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
