<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("Asia/Manila"); // Ensure correct timezone

session_start();
require_once 'config.php';
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

// Validate Past Time
if (strtotime("$appointment_date $appointment_time") < time()) {
    $_SESSION['booking_error'] = "❌ Cannot book past time slots.";
    header("Location: view_cart.php");
    exit;
}

$total_minutes = 0;
$subtotal = 0;

/* ---------------- CALCULATE CART TOTALS ---------------- */
$down_payment_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $table = $item['type'] === 'package' ? 'packages' : 'services';
    // Fetch price, duration, AND down_payment
    $stmt = mysqli_prepare($conn, "SELECT price, duration_minutes, down_payment FROM {$table} WHERE id=?");
    if (!$stmt) die("❌ Prepare failed: " . mysqli_error($conn));

    mysqli_stmt_bind_param($stmt, "i", $item['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $price, $duration, $dp);

    if (mysqli_stmt_fetch($stmt)) {
        $subtotal += $price;
        $total_minutes += $duration;
        // If down payment is set and > 0, use it. Otherwise, assume full price is due.
        // Wait, if down_payment is > 0, we charge that. If it's 0 (default), we charge full price.
        // BUT, if user mixes items (one with DP, one without), we should sum up 'Due Now'.
        
        $item_due_now = ($dp > 0 && $dp < $price) ? $dp : $price;
        $down_payment_total += $item_due_now;
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
// Discount applies to total price usually.
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

// Calculate actual amount to pay NOW
// If there was a discount, we might need to adjust the down payment?
// Complexity: Usually discounts apply to the total. Down payment is fixed.
// Let's assume Down Payment is fixed and NOT subject to discount unless it covers the full price.
// If down_payment_total < subtotal, it means we have installment items.
// Let's keep it simple: Pay Now = down_payment_total + booking_fee (if any).
// NOTE: If global discount reduces total below down payment? Unlikely for braces.
// Let's stick to: Pay Now = Sum of (DownPayment OR Price) - Discount?
// Actually, standard practice: Discount reduces the Total Price. Installment setup usually has fixed down payment.
// Let's just use $down_payment_total IF it varies from $subtotal.
// If $down_payment_total == $subtotal (meaning no items have special DP), then apply discount to it.
// If $down_payment_total < $subtotal, we interpret $down_payment_total as the strict amount to pay now.
// However, if we have a senior citizen discount on a 30k package (20% off -> 24k), 
// and DP is 5k. Do we still charge 5k? Yes.
// So, PayNow = $down_payment_total (plus booking fee).
// Discount applies to the 'total_amount' recorded in DB, reducing the remaining balance.

$amountToPayNow = $down_payment_total + $booking_fee;

// Safety check: PayNow shouldn't exceed TotalAmount (in case of huge discounts)
if ($amountToPayNow > $totalAmount) {
    $amountToPayNow = $totalAmount;
}

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
require_once 'config.php'; // Ensure config is loaded if not already (it usually is via db_connect or similar, but let's be safe or just rely on global if included)
// Actually db_connect doesn't include config.php usually, let's check.
// `confirm.php` includes `db_connect.php`. `db_connect.php` usually just does DB stuff.
// `config.php` has the dotenv loading.
// Let's assume `config.php` needs to be required if not present.
// Looking at file, `confirm.php` requires `db_connect.php` and `vendor/autoload.php`.
// It DOES NOT require `config.php`.
// So I should replace the prompt to import config.php too or just add the loading logic?
// Better to require 'config.php' at the top.

Configuration::setXenditKey(XENDIT_API_KEY);
// Configuration::setXenditKey('xnd_development_NUCDa05e0ZnIklrBuGxCPDleszx1JWlq2khKSc97CkLreQ4I8k7eyLfspzff3');
$invoiceApi = new InvoiceApi();

$paymentDesc = 'Payment for Booking #' . $booking_id;
if ($amountToPayNow < $totalAmount) {
     $paymentDesc = 'Down Payment for Booking #' . $booking_id;
}

$invoiceRequest = new CreateInvoiceRequest([
    'external_id' => 'B-Dental Booking_' . $booking_id,
    'amount' => (float)$amountToPayNow,
    'payer_email' => $email,
    'currency' => 'PHP',
    'invoice_duration' => 86400,
    'description' => $paymentDesc,
    'success_redirect_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment_success.php?id=' . $booking_id,
    'failure_redirect_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment_fail.php?id=' . $booking_id,
    'payment_methods' => ['GCASH'], // Added more methods
]);

try {
    $invoice = $invoiceApi->createInvoice($invoiceRequest);

    /* ---------------- INSERT PAYMENT ---------------- */
    $stmt = mysqli_prepare($conn, "
        INSERT INTO payments
        (booking_id, total_price, payment_method, status, xendit_invoice_id, payment_date, payment_url, description)
        VALUES (?, ?, 'Link', 'pending', ?, NOW(), ?, ?)
    ");
    
    // We store the amountToPayNow in the payment record
    $payment_url = $invoice['invoice_url'];
    $inv_id = $invoice['id'];
    
    mysqli_stmt_bind_param($stmt, "idsss", $booking_id, $amountToPayNow, $inv_id, $payment_url, $paymentDesc);
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
