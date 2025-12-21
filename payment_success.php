<?php
// require_once 'db_connect.php';

// print_r($conn);
// exit;
// require_once './phpmailer2.php';

// sendEmail('tare.kristian@gmail.com', 'Admin Notification', 'wow testing');




if (!isset($_GET['id'])) {
    header("Location: appointments.php");
    exit;
}

$booking_id = (int)$_GET['id'];

/* ---------------- GET BOOKING ---------------- */
$sql = "
    SELECT b.*, u.email, u.first_name, u.last_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = $booking_id
";

echo $sql;
exit;
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$booking = mysqli_fetch_assoc($result);
if (!$booking) {
    die("Booking not found.");
}



$user_email = $booking['email'];
$full_name = $booking['first_name'] . ' ' . $booking['last_name'];

/* ---------------- GET PAYMENT ---------------- */
$sql = "SELECT * FROM payments WHERE booking_id = $booking_id";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Payment query failed: " . mysqli_error($conn));
}

$payment = mysqli_fetch_assoc($result);
if (!$payment) {
    die("Payment record not found.");
}




/* ---------------- GET STAFF & ADMIN ---------------- */
$queryStaff = "SELECT email FROM users
    WHERE user_type='staff' AND status_='Active'
    LIMIT 1";

$resultStaff = mysqli_query($conn,$queryStaff);
$staff = mysqli_fetch_assoc($resultStaff);



$admin = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT email FROM users
    WHERE user_type='admin' AND status_='Active'
    LIMIT 1
"));

/* ---------------- UPDATE PAYMENT & BOOKING ---------------- */
$update_payment = "UPDATE payments SET status='approved' WHERE booking_id=$booking_id";
if (!mysqli_query($conn, $update_payment)) {
    die("Update payment failed: " . mysqli_error($conn));
}

$update_booking = "UPDATE bookings SET status='confirmed' WHERE id=$booking_id";
if (!mysqli_query($conn, $update_booking)) {
    die("Update booking failed: " . mysqli_error($conn));
}

/* ---------------- FORMAT DATES ---------------- */
$booking_date = date('F j, Y', strtotime($booking['appointment_date']));
$booking_time = date('g:i a', strtotime($booking['appointment_time']));

/* ---------------- EMAIL CONTENT ---------------- */
$customerMessage = "
Your booking on {$booking_date} at {$booking_time}
has been approved and reserved.
";

$staffMessage = "
{$full_name} has booked an appointment on
{$booking_date} at {$booking_time}.
";


sendEmail($user_email, 'Approved Booking', $customerMessage);
// sendEmail($staff['email'], 'Customer Booking', $staffMessage);
sendEmail($admin['email'], 'Admin Notification', $staffMessage);

/* ---------------- REDIRECT ---------------- */
header("Location: appointments.php?success=Payment+Success");
exit;
