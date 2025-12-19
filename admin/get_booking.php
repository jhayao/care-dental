<?php
session_start();
require_once '../db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing booking ID']);
    exit;
}

$id = intval($_GET['id']);

// Fetch booking details
$stmt = $conn->prepare("
    SELECT 
        bookings.id,
        bookings.booking_date,
        bookings.time_slot,
        bookings.booking_fee,
        bookings.discount,
        bookings.total_amount,
        bookings.status,
        users.first_name,
        users.last_name,
        users.category
    FROM bookings
    INNER JOIN users ON users.id = bookings.user_id
    WHERE bookings.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

// Fetch booking items
$stmt = $conn->prepare("SELECT item_type, item_id FROM booking_items WHERE booking_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$items_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$items = [];
foreach ($items_result as $row) {
    $type = $row['item_type'];
    $item_id = $row['item_id'];

    if ($type === 'package') {
        $stmt = $conn->prepare("SELECT id, package_name, description, price, inclusions FROM packages WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, service_name, description, price FROM services WHERE id = ?");
    }

    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $itemData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($itemData) {
        $itemEntry = [
            'id' => $itemData['id'],
            'name' => $type === 'package' ? $itemData['package_name'] : $itemData['service_name'],
            'description' => $itemData['description'],
            'price' => $itemData['price'],
            'type' => $type
        ];

        // Include package inclusions if available
        if ($type === 'package' && !empty($itemData['inclusions'])) {
            $itemEntry['inclusions'] = json_decode($itemData['inclusions'], true); // returns array
        }

        $items[] = $itemEntry;
    }
}

$booking['discount'] = $booking['discount'] ?? 0;
$booking['category'] = $booking['category'] ?? 'None';

echo json_encode([
    'booking' => $booking,
    'items' => $items
]);
?>
