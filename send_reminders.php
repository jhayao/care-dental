<?php
// send_reminders.php
// Run this script via Cron Job every 5-10 minutes
// Example: */5 * * * * /usr/bin/php /path/to/send_reminders.php

require_once 'db_connect.php';
require_once 'phpmailer2.php';

// Set strict error reporting for debugging log (optional)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get current time
$now = date('Y-m-d H:i:s');

// We want to send reminders for appointments happening in the "next hour".
// Specifically: Appointment Time <= Now + 1 Hour AND Appointment Time > Now
// And reminder hasn't been sent yet.
// And status is 'confirmed'.

$query = "
    SELECT 
        b.id, 
        b.appointment_date, 
        b.appointment_time, 
        u.email, 
        u.first_name, 
        u.last_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE 
        b.status = 'confirmed' 
        AND b.reminder_sent = 0
        AND CONCAT(b.appointment_date, ' ', b.appointment_time) <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
        AND CONCAT(b.appointment_date, ' ', b.appointment_time) > NOW()
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " appointments to remind.\n";

    while ($row = $result->fetch_assoc()) {
        $booking_id = $row['id'];
        $email = $row['email'];
        $name = $row['first_name'] . ' ' . $row['last_name'];
        $time = date('h:i A', strtotime($row['appointment_time']));
        $date = date('F j, Y', strtotime($row['appointment_date']));

        $subject = "Appointment Reminder - B Dental Care";
        $message = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #2563eb;'>Appointment Reminder</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>This is a friendly reminder that you have an upcoming appointment with B Dental Care.</p>
                <div style='background: #f9fafb; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Date:</strong> $date</p>
                    <p><strong>Time:</strong> $time</p>
                </div>
                <p>Please arrive at least 15 minutes before your scheduled time.</p>
                <p>See you soon!</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #666; font-size: 12px;'>B Dental Care Team</p>
            </div>
        ";

        // Send Email
        sendEmail($email, $subject, $message);

        // Update database to mark as sent
        $update = $conn->prepare("UPDATE bookings SET reminder_sent = 1 WHERE id = ?");
        $update->bind_param("i", $booking_id);
        $update->execute();
        
        echo "Reminder sent to $email (Booking ID: $booking_id)\n";
    }
} else {
    echo "No upcoming appointments to remind at this time.\n";
}

$conn->close();
?>
