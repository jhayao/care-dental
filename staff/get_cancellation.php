<?php
session_start();
require_once '../db_connect.php';

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing booking ID']);
    exit;
}

$booking_id = intval($_GET['id']);

// Fetch the cancelled_at and booking_fee for the booking
$stmt = $conn->prepare("SELECT status, cancelled_at, booking_fee, total_amount FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

// Only proceed if the booking is cancelled
if ($result['status'] !== 'cancelled' || empty($result['cancelled_at'])) {
    echo json_encode(['error' => 'Booking is not cancelled']);
    exit;
}

// Calculate hours since cancellation
$cancelledAt = new DateTime($result['cancelled_at']);
$now = new DateTime();
$interval = $now->diff($cancelledAt);
$hoursSinceCancel = ($interval->days * 24) + $interval->h + ($interval->i / 60);

// Determine refund eligibility
$refundType = ($hoursSinceCancel <= 24) ? 'full' : 'booking_fee_only';

echo json_encode([
    'cancelled_at' => $result['cancelled_at'],
    'hours_since_cancel' => round($hoursSinceCancel, 2),
    'refund_type' => $refundType,
    'booking_fee' => $result['booking_fee'],
    'total_amount' => $result['total_amount']
]);
