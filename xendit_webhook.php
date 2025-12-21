<?php
require_once 'db_connect.php';
require_once './phpmailer2.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Read payload
$payload = json_decode(file_get_contents('php://input'), true);

// Validate
if (!isset($payload['id'], $payload['status'])) {
    http_response_code(400);
    exit;
}

$xenditInvoiceId = $payload['id'];
$paymentStatus   = $payload['status'];
$paymentId       = $payload['payment_id'] ?? null;

// Find payment
$stmt = $conn->prepare("
    SELECT p.*, b.appointment_date, b.appointment_time,
           u.email, u.first_name, u.last_name
    FROM payments p
    JOIN bookings b ON b.id = p.booking_id
    JOIN users u ON u.id = b.user_id
    WHERE p.xendit_invoice_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $xenditInvoiceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit;
}

$data = $result->fetch_assoc();

// ✅ PAID
if ($paymentStatus === 'PAID') {

    // Prevent double processing
    if ($data['status'] === 'approved') {
        http_response_code(200);
        exit;
    }

    // Update payment
    $stmt = $conn->prepare("
        UPDATE payments
        SET status='approved', xendit_payment_id=?
        WHERE id=?
    ");
    $stmt->bind_param("si", $paymentId, $data['id']);
    $stmt->execute();

    // Update booking
    $stmt = $conn->prepare("
        UPDATE bookings
        SET status='confirmed'
        WHERE id=?
    ");
    $stmt->bind_param("i", $data['booking_id']);
    $stmt->execute();

    // Email
    $date = date('F j, Y', strtotime($data['appointment_date']));
    $time = date('g:i a', strtotime($data['appointment_time']));
    $name = $data['first_name'].' '.$data['last_name'];

    $message = "
Hello $name,

Your appointment on $date at $time has been confirmed.

Thank you.
";

    sendEmail($data['email'], 'Booking Confirmed', $message);
}

// ❌ EXPIRED
if ($paymentStatus === 'EXPIRED') {

    $stmt = $conn->prepare("
        UPDATE payments SET status='failed' WHERE id=?
    ");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();

    $stmt = $conn->prepare("
        UPDATE bookings SET status='canceled' WHERE id=?
    ");
    $stmt->bind_param("i", $data['booking_id']);
    $stmt->execute();
}

http_response_code(200);
echo json_encode(['message' => 'Webhook processed']);
