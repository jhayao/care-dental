<?php
session_start();
require_once 'db_connect.php';
require_once './phpmailer2.php';

header('Content-Type: application/json');

/* ------------------ AUTH CHECK ------------------ */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$user_id    = $_SESSION['user_id'];
$action     = $_POST['action'] ?? '';
$booking_id = $_POST['booking_id'] ?? '';

if (empty($booking_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Booking ID is required.']);
    exit;
}

/* ------------------ FETCH BOOKING & USER ------------------ */
$stmt = $conn->prepare("
    SELECT b.*, u.email, u.first_name, u.last_name
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo json_encode(['status' => 'error', 'message' => 'Booking not found.']);
    exit;
}

$full_name    = $booking['first_name'] . ' ' . $booking['last_name'];
$booking_date = date('F j, Y', strtotime($booking['booking_date']));
$booking_time = date('g:i A', strtotime($booking['time_slot']));

/* ------------------ FETCH PAYMENT ------------------ */
$stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo json_encode(['status' => 'error', 'message' => 'Payment record not found.']);
    exit;
}

$xendit_payment_id = $payment['xendit_payment_id'];
$total_amount     = (float) $payment['total_price'];

/* ------------------ FETCH STAFF & ADMIN ------------------ */
$staff = $conn->query("
    SELECT email FROM users 
    WHERE user_type = 'staff' AND status_ = 'Active' 
    LIMIT 1
")->fetch_assoc();

$admin = $conn->query("
    SELECT email FROM users 
    WHERE user_type = 'admin' AND status_ = 'Active' 
    LIMIT 1
")->fetch_assoc();

/* ==========================================================
   CANCEL BOOKING + REFUND
   ========================================================== */
if ($action === 'cancel') {

    if (empty($xendit_payment_id) || $total_amount <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No valid payment found for refund.'
        ]);
        exit;
    }

    /* ---------- XENDIT REFUND ---------- */
    $apiKey = 'xnd_production_XXXXXXXXXXXXXXXXXXXX'; // MOVE TO ENV

    $refundData = [
        'payment_request_id' => $xendit_payment_id,
        'reason' => 'REQUESTED_BY_CUSTOMER',
        'amount' => $total_amount,
        'currency' => 'PHP'
    ];

    $ch = curl_init('https://api.xendit.co/refunds');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($refundData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ':')
        ]
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $refundResponse = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Refund failed.',
            'xendit_error' => $refundResponse
        ]);
        exit;
    }

    /* ---------- UPDATE BOOKING ---------- */
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'cancelled', cancelled_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $stmt->close();

    /* ---------- UPDATE PAYMENT ---------- */
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = 'refunded', refunded_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("i", $payment['id']);
    $stmt->execute();
    $stmt->close();

    /* ---------- EMAILS ---------- */
    $customerMessage = "
Hello {$full_name},

Your appointment on {$booking_date} at {$booking_time} has been cancelled.
Your payment of PHP " . number_format($total_amount, 2) . " has been refunded.

Thank you,
B-Dental Care
";

    $adminMessage = "
Booking Cancelled & Refunded

Patient: {$full_name}
Date: {$booking_date}
Time: {$booking_time}
Booking ID: {$booking_id}
Refunded Amount: PHP " . number_format($total_amount, 2) . "
";

    sendEmail($booking['email'], 'Booking Cancelled & Refunded', $customerMessage);
    if (!empty($staff['email'])) sendEmail($staff['email'], 'Cancelled Booking (Refunded)', $adminMessage);
    if (!empty($admin['email'])) sendEmail($admin['email'], 'Cancelled Booking (Refunded)', $adminMessage);

    echo json_encode([
        'status' => 'success',
        'message' => 'Booking cancelled and payment refunded successfully.'
    ]);
    exit;
}

/* ==========================================================
   RESCHEDULE BOOKING
   ========================================================== */
if ($action === 'reschedule') {

    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';

    if (empty($new_date) || empty($new_time)) {
        echo json_encode(['status' => 'error', 'message' => 'New date and time are required.']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE bookings
        SET appointment_date = ?, appointment_time = ?, status = 'rescheduled'
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ssii", $new_date, $new_time, $booking_id, $user_id);

    if ($stmt->execute()) {

        $customerMessage = "
Hello {$full_name},

Your appointment has been rescheduled to {$new_date} at {$new_time}.

Thank you,
B-Dental Care
";

        $adminMessage = "
Booking Rescheduled

Patient: {$full_name}
New Date: {$new_date}
New Time: {$new_time}
Booking ID: {$booking_id}
";

        sendEmail($booking['email'], 'Booking Rescheduled', $customerMessage);
        if (!empty($staff['email'])) sendEmail($staff['email'], 'Rescheduled Booking', $adminMessage);
        if (!empty($admin['email'])) sendEmail($admin['email'], 'Rescheduled Booking', $adminMessage);

        echo json_encode(['status' => 'success', 'message' => 'Booking rescheduled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reschedule booking.']);
    }

    $stmt->close();
    exit;
}

/* ------------------ INVALID ACTION ------------------ */
echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
