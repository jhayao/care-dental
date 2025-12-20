<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Missing booking ID']);
    exit;
}

$bookingId = intval($_GET['id']);

// Fetch booking info (without booking_fee)
$stmt = $conn->prepare("
    SELECT b.id, b.appointment_date, b.appointment_time, b.discount, b.total_amount, b.status,
           u.first_name, u.last_name, u.category
    FROM bookings b
    INNER JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

// Fetch booked items
$stmt = $conn->prepare("SELECT * FROM booking_items WHERE booking_id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$items_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$items = [];
$total_minutes = 0;

foreach ($items_result as $row) {
    $type = strtolower($row['item_type']);
    $item_id = $row['item_id'];

    if ($type === 'service') {
        $query = "SELECT id, service_name AS name, description, price, duration_minutes FROM services WHERE id = ?";
    } elseif ($type === 'package') {
        $query = "SELECT id, package_name AS name, description, price, duration_minutes, inclusions FROM packages WHERE id = ?";
    } else {
        continue;
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $itemData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($itemData) {
        $duration = intval($itemData['duration_minutes'] ?? 0);
        $total_minutes += $duration;

        $itemEntry = [
            'id' => $itemData['id'],
            'name' => $itemData['name'],
            'description' => $itemData['description'],
            'price' => floatval($itemData['price']),
            'type' => $type,
            'duration' => $duration
        ];

        if ($type === 'package' && !empty($itemData['inclusions'])) {
            $itemEntry['inclusions'] = json_decode($itemData['inclusions'], true);
        }

        $items[] = $itemEntry;
    }
}

// Format time in 12-hour format
$start_time = strtotime($booking['appointment_time']);
$end_time = $start_time + ($total_minutes * 60);
$booking['appointment_time_12h'] = date('h:i A', $start_time);
$booking['time_slot'] = date('h:i A', $start_time) . ' – ' . date('h:i A', $end_time);

// Ensure discount/category fields
$booking['discount'] = $booking['discount'] ?? 0;
$booking['category'] = $booking['category'] ?? 'None';

echo json_encode([
    'booking' => $booking,
    'items' => $items
]);
