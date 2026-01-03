<?php
// QStash Webhook Receiver - DAILY REMINDERS
// This script is intended to be called daily (e.g., at 8:00 AM) by QStash.
// It fetches all confirmed bookings for "Tomorrow" and sends reminder emails.

require_once 'db_connect.php';
require_once 'phpmailer2.php';

date_default_timezone_set('Asia/Manila');

// 1. Calculate Target Date (Tomorrow)
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$count = 0;

// 2. Fetch Bookings for Tomorrow
$stmt = $conn->prepare("
    SELECT b.*, u.email, u.first_name, u.last_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.appointment_date = ? 
      AND b.status = 'confirmed'
      AND (b.reminder_sent = 0 OR b.reminder_sent IS NULL)
");
$stmt->bind_param("s", $tomorrow);
$stmt->execute();
$result = $stmt->get_result();

echo "Processing reminders for: " . $tomorrow . "\n";

while ($booking = $result->fetch_assoc()) {
    $fullName = $booking['first_name'] . ' ' . $booking['last_name'];
    $date = date('F j, Y', strtotime($booking['appointment_date']));
    $time = date('g:i A', strtotime($booking['time_slot']));

    $subject = "Appointment Reminder - B-Dental Care";
    $message = "
    Hi $fullName,

    This is a friendly reminder about your upcoming appointment with B-Dental Care tomorrow.

    Date: $date
    Time: $time
    
    Location: B-Dental Care Clinic

    If you need to reschedule or cancel, please log in to your account.

    See you soon!
    ";

    $sent = sendEmail($booking['email'], $subject, $message);
    
    if ($sent) {
        $count++;
        // Update reminder_sent flag
        $updateStmt = $conn->prepare("UPDATE bookings SET reminder_sent = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $booking['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        error_log("Failed to send reminder to " . $booking['email']);
    }
}

echo "Sent $count reminders.";
?>
