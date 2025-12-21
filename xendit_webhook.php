<?php
// xendit_webhook.php

require_once 'db_connect.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

// Get raw payload
$payload = json_decode(file_get_contents('php://input'), true);

// Validate payload
if (!isset($payload['id']) || !isset($payload['status'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid payload']);
    exit;
}

$xenditInvoiceId = $payload['id'];
$paymentStatus   = $payload['status'];
$paymentId       = $payload['payment_id'] ?? null;

// Find payment
$stmt = $conn->prepare("
    SELECT * FROM payments
    WHERE xendit_invoice_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $xenditInvoiceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['message' => 'Payment not found']);
    exit;
}

$payment = $result->fetch_assoc();

// Handle payment status
if ($paymentStatus === 'PAID') {

    $stmt = $conn->prepare("
        UPDATE payments
        SET status = 'approved',
            xendit_payment_id = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $paymentId, $payment['id']);
    $stmt->execute();

} elseif ($paymentStatus === 'EXPIRED') {

    // Update payment
    $stmt = $conn->prepare("
        UPDATE payments
        SET status = 'failed'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $payment['id']);
    $stmt->execute();

    // Cancel booking
    $stmt = $conn->prepare("
        UPDATE bookings
        SET status = 'canceled'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $payment['booking_id']);
    $stmt->execute();
}

http_response_code(200);
echo json_encode(['message' => 'Webhook processed']);
