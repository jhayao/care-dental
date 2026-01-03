<?php
// QStash Webhook Receiver - TRANSACTIONAL EMAILS
// Receives: { booking_id: 123, type: 'approved'|'cancelled'|'rescheduled'|'refunded' }

require_once 'db_connect.php';
require_once 'phpmailer2.php';

// Raw input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400); die("Invalid Payload");
}

$booking_id = $data['booking_id'] ?? null;
$type = $data['type'] ?? '';

if (!$booking_id || !$type) {
    http_response_code(400); die("Missing parameters");
}

// Fetch Booking
$stmt = $conn->prepare("
    SELECT b.*, u.email, u.first_name, u.last_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found");
}

$fullName = $booking['first_name'] . ' ' . $booking['last_name'];
$date = date('F j, Y', strtotime($booking['appointment_date']));
$time = date('g:i A', strtotime($booking['time_slot']));
$subject = "";
$message = "";

switch ($type) {
    case 'approved':
        $subject = "Booking Confirmed - B Dental Care";
        $message = "
        Hi $fullName,

        Your appointment has been successfully confirmed!

        Date: $date
        Time: $time
        At B Dental Care Clinic

        Thank you for choosing us!
        ";
        break;

    case 'cancelled':
        $subject = "Booking Cancelled - B Dental Care";
        $message = "
        Hi $fullName,

        Your appointment on $date at $time has been cancelled.

        If this was a mistake, please contact us or book a new appointment.
        ";
        break;

    case 'refunded':
        $subject = "Booking Refunded - B Dental Care";
        $message = "
        Hi $fullName,

        Your appointment on $date at $time has been cancelled and a refund has been processed.

        Please allow 5-10 business days for the amount to reflect in your account depending on your bank's policy.
        ";
        break;

    case 'rescheduled':
        $subject = "Booking Rescheduled - B Dental Care";
        $message = "
        Hi $fullName,

        Your appointment has been successfully rescheduled.

        New Date: $date
        New Time: $time
        At B Dental Care Clinic

        See you then!
        ";
        break;

    default:
        die("Unknown notification type");
}

if ($subject && $message) {
    if (sendEmail($booking['email'], $subject, $message)) {
        echo "Email ($type) sent to " . $booking['email'];
    } else {
        http_response_code(500); echo "Failed to send email";
    }
}
?>
