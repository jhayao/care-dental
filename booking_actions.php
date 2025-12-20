<?php
session_start();
require_once 'db_connect.php';
require_once './phpmailer2.php'; 

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

/* ------------------ FETCH BOOKING & USER INFO ------------------ */
$stmt = $conn->prepare("SELECT b.*, u.email, u.first_name, u.last_name 
                        FROM bookings b 
                        JOIN users u ON u.id = b.user_id 
                        WHERE b.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo json_encode(['status' => 'error', 'message' => 'Booking not found.']);
    exit;
}

$full_name = $booking['first_name'] . ' ' . $booking['last_name'];
$booking_date = date('F j, Y', strtotime($booking['booking_date']));
$booking_time = date('g:i A', strtotime($booking['time_slot']));

/* ------------------ FETCH STAFF & ADMIN ------------------ */
$staff = $conn->query("SELECT email FROM users WHERE user_type='staff' AND status_='Active' LIMIT 1")->fetch_assoc();
$admin = $conn->query("SELECT email FROM users WHERE user_type='admin' AND status_='Active' LIMIT 1")->fetch_assoc();

/* ------------------ CANCEL BOOKING ------------------ */
if ($action === 'cancel') {
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled', cancelled_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);

    if ($stmt->execute()) {

        // Email to customer
        $customerMessage = "
Hello {$full_name},

Your appointment on {appointment_date} at {$appointment_time} has been successfully cancelled.

Thank you,
B-Dental Care
";

        // Email to staff/admin
        $adminMessage = "
Booking Cancelled Notice

Patient: {$full_name}
Date: {$appointment_date}
Time: {$appointment_time}
Booking ID: {$booking_id}

The slot is now available.
";

        sendEmail($booking['email'], 'Booking Cancellation Confirmation', $customerMessage);
        if (!empty($staff['email'])) sendEmail($staff['email'], 'Cancelled Booking Notification', $adminMessage);
        if (!empty($admin['email'])) sendEmail($admin['email'], 'Cancelled Booking Notification', $adminMessage);

        echo json_encode(['status' => 'success', 'message' => 'Booking cancelled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to cancel booking.']);
    }
    $stmt->close();
    exit;
}

/* ------------------ RESCHEDULE BOOKING ------------------ */
if ($action === 'reschedule') {
    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';

    if (empty($new_date) || empty($new_time)) {
        echo json_encode(['status' => 'error', 'message' => 'New date and time are required.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE bookings 
        SET booking_date = ?, time_slot = ?, status = 'Rescheduled' 
        WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $new_date, $new_time, $booking_id, $user_id);

    if ($stmt->execute()) {
        // Email to customer
        $customerMessage = "
Hello {$full_name},

Your appointment has been rescheduled to {$new_date} at {$new_time}.

Thank you,
B-Dental Care
";

        // Email to staff/admin
        $adminMessage = "
Booking Rescheduled Notice

Patient: {$full_name}
New Date: {$new_date}
New Time: {$new_time}
Booking ID: {$booking_id}
";

        sendEmail($booking['email'], 'Booking Rescheduled Confirmation', $customerMessage);
        if (!empty($staff['email'])) sendEmail($staff['email'], 'Rescheduled Booking Notification', $adminMessage);
        if (!empty($admin['email'])) sendEmail($admin['email'], 'Rescheduled Booking Notification', $adminMessage);

        echo json_encode(['status' => 'success', 'message' => 'Booking rescheduled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reschedule booking.']);
    }

    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
