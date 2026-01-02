<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

/* ---------------- AUTH CHECK ---------------- */
if (!isset($_SESSION['user_id'])) {
    die("❌ Not logged in");
}

/* ---------------- INPUT CHECK ---------------- */
if (!isset($_POST['appointment_date'], $_POST['appointment_time']) || empty($_SESSION['cart'])) {
    die("❌ Invalid booking / empty cart");
}

$appointment_date = $_POST['appointment_date'];
$appointment_time = $_POST['appointment_time'];

$total_minutes = 0;
$subtotal = 0;

/* ---------------- CALCULATE CART TOTALS ---------------- */
foreach ($_SESSION['cart'] as $item) {
    $table = $item['type'] === 'package' ? 'packages' : 'services';
    $stmt = mysqli_prepare($conn, "SELECT price, duration_minutes FROM {$table} WHERE id=?");
    if (!$stmt) die("❌ Prepare failed: " . mysqli_error($conn));

    mysqli_stmt_bind_param($stmt, "i", $item['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $price, $duration);

    if (mysqli_stmt_fetch($stmt)) {
        $subtotal += $price;
        $total_minutes += $duration;
    } else {
        die("❌ Item not found");
    }

    mysqli_stmt_close($stmt);
}

/* ---------------- GET USER INFO ---------------- */
$stmt = mysqli_prepare($conn, "SELECT category, email, discount FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $category, $email, $user_discount_percent);
if (!mysqli_stmt_fetch($stmt)) die("❌ User not found");
mysqli_stmt_close($stmt);

/* ---------------- DISCOUNT & TOTAL ---------------- */
$discount = 0;
// Note: $user_discount_percent comes from DB as decimal/int (e.g. 20.00)
if ($user_discount_percent > 0) {
    $discount = $subtotal * ($user_discount_percent / 100);
} elseif (in_array($category, ['Senior','PWD'])) {
    $discount = $subtotal * 0.20;
}
$booking_fee = 0;
// Fetch Booking Fee
$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'booking_fee'");
$stmt->execute();
$stmt->bind_result($booking_fee_val);
if ($stmt->fetch()) {
    $booking_fee = floatval($booking_fee_val);
}
$stmt->close();

$totalAmount = ($subtotal - $discount) + $booking_fee;

/* ---------------- OVERLAP CHECK ---------------- */
$stmt = mysqli_prepare($conn, "
    SELECT appointment_time, duration_minutes
    FROM bookings
    WHERE appointment_date=?
      AND status NOT IN ('cancelled', 'refunded', 'rejected', 'failed')
");
mysqli_stmt_bind_param($stmt, "s", $appointment_date);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $b_time, $b_duration);

$new_start = strtotime("$appointment_date $appointment_time");
$new_end = $new_start + ($total_minutes * 60);

while (mysqli_stmt_fetch($stmt)) {
    $b_start = strtotime("$appointment_date $b_time");
    $b_end = $b_start + ($b_duration * 60);
    if ($new_start < $b_end && $new_end > $b_start) {
      $_SESSION['booking_error'] = "❌ Selected time is already booked";
header("Location: view_cart.php");
exit;

    }
}
mysqli_stmt_close($stmt);

/* ---------------- INSERT BOOKING (NO BOOKING FEE) ---------------- */
$stmt = mysqli_prepare($conn, "
    INSERT INTO bookings
    (user_id, appointment_date, appointment_time, time_slot, duration_minutes, discount, total_amount, booking_fee, status, booking_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");
mysqli_stmt_bind_param(
    $stmt,
    "isssiddi",
    $_SESSION['user_id'],  
    $appointment_date,     
    $appointment_time,
    $appointment_time, // time_slot value     
    $total_minutes,        
    $discount,             
    $totalAmount,
    $booking_fee
);


if (!mysqli_stmt_execute($stmt)) {
    die("❌ Booking insert failed: " . mysqli_error($conn));
}
$booking_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

/* ---------------- INSERT BOOKING ITEMS ---------------- */
$stmtItem = mysqli_prepare($conn, "INSERT INTO booking_items (booking_id, item_id, item_type) VALUES (?, ?, ?)");
foreach ($_SESSION['cart'] as $item) {
    mysqli_stmt_bind_param($stmtItem, "iis", $booking_id, $item['id'], $item['type']);
    if (!mysqli_stmt_execute($stmtItem)) {
         die("❌ Booking Item insert failed: " . mysqli_error($conn));
    }
}
mysqli_stmt_close($stmtItem);

/* ---------------- XENDIT INVOICE ---------------- */
// Configuration::setXenditKey('xnd_production_A2pv3BkrsjtoJNWAmhkcKL93KtGiaXZp6ohf7Umc4u55bly2nHTxshpN4kTrmc');
Configuration::setXenditKey('xnd_development_NUCDa05e0ZnIklrBuGxCPDleszx1JWlq2khKSc97CkLreQ4I8k7eyLfspzff3');
$invoiceApi = new InvoiceApi();

$invoiceRequest = new CreateInvoiceRequest([
    'external_id' => 'B-Dental Booking_' . $booking_id,
    'amount' => (float)$totalAmount,
    'payer_email' => $email,
    'currency' => 'PHP',
    'invoice_duration' => 86400,
    'description' => 'Payment for Booking #' . $booking_id,
    'success_redirect_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment_success.php?id=' . $booking_id,
    'failure_redirect_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment_fail.php?id=' . $booking_id,
    'payment_methods' => ['GCASH'],
]);

try {
    $invoice = $invoiceApi->createInvoice($invoiceRequest);

    /* ---------------- INSERT PAYMENT ---------------- */
    $stmt = mysqli_prepare($conn, "
        INSERT INTO payments
        (booking_id, total_price, payment_method, status, xendit_invoice_id, payment_date)
        VALUES (?, ?, 'GCASH', 'pending', ?, NOW())
    ");
    mysqli_stmt_bind_param($stmt, "ids", $booking_id, $totalAmount, $invoice['id']);
    if (!mysqli_stmt_execute($stmt)) {
        die("❌ Payment insert failed: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    /* ------------------------------------------------- */

    unset($_SESSION['cart']);

    header("Location: " . $invoice['invoice_url']);
    exit;

} catch (Exception $e) {
    die("❌ Xendit Error: " . $e->getMessage());
}
