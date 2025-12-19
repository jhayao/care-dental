<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['appointment_date'], $_POST['appointment_time'], $_SESSION['cart']) || empty($_SESSION['cart'])) {
    die("Invalid booking.");
}

$appointment_date = $_POST['appointment_date'];
$appointment_time = $_POST['appointment_time'];
$total_minutes = 0;
$subtotal = 0;
$booking_fee = 50;

/* Calculate totals and duration */
foreach ($_SESSION['cart'] as $item) {
    if ($item['type'] === 'package') {
        $stmt = $conn->prepare("SELECT price, duration_minutes FROM packages WHERE id=?");
    } else {
        $stmt = $conn->prepare("SELECT price, duration_minutes FROM services WHERE id=?");
    }
    $stmt->bind_param("i", $item['id']);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($details) {
        $subtotal += $details['price'];
        $total_minutes += $details['duration_minutes'];
    }
}

$category = ''; // Fetch user category if needed
$stmt = $conn->prepare("SELECT category FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$category = $user['category'] ?? '';
$stmt->close();

$discount = ($category === 'Senior' || $category === 'PWD') ? $subtotal * 0.2 : 0;
$total = $subtotal - $discount + $booking_fee;

/* Check overlap with existing bookings */
$stmt = $conn->prepare("SELECT appointment_time, duration_minutes FROM bookings WHERE appointment_date=?");
$stmt->bind_param("s", $appointment_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $b_start = strtotime($appointment_date . ' ' . $row['appointment_time']);
    $b_end = $b_start + ($row['duration_minutes']*60);
    $new_start = strtotime($appointment_date . ' ' . $appointment_time);
    $new_end = $new_start + ($total_minutes*60);

    if ($new_start < $b_end && $new_end > $b_start) {
        die("Selected time is already booked. Please choose another time.");
    }
}
$stmt->close();

/* Insert booking */
$stmt = $conn->prepare("INSERT INTO bookings (user_id, appointment_date, appointment_time, duration_minutes, booking_fee, discount, total_amount, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
$stmt->bind_param("issiidd", $_SESSION['user_id'], $appointment_date, $appointment_time, $total_minutes, $booking_fee, $discount, $total);
$stmt->execute();
$stmt->close();

/* Clear cart */
unset($_SESSION['cart']);

header("Location: booking_success.php");
exit;
