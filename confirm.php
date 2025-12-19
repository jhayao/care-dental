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
$booking_fee = 50;

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
        echo "<br>Item: {$item['id']} price: $price, duration: $duration";
    } else {
        echo "<br>Item ID {$item['id']} not found";
    }

    mysqli_stmt_close($stmt);
}


/* ---------------- GET USER INFO ---------------- */
$stmt = mysqli_prepare($conn, "SELECT category, email FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $category, $email);
if (!mysqli_stmt_fetch($stmt)) die("❌ User not found");
mysqli_stmt_close($stmt);

$discount = in_array($category, ['Senior','PWD']) ? $subtotal * 0.2 : 0;
$totalAmount = $subtotal - $discount + $booking_fee;


/* ---------------- OVERLAP CHECK ---------------- */
$stmt = mysqli_prepare($conn, "SELECT appointment_time, duration_minutes FROM bookings WHERE appointment_date=?");
mysqli_stmt_bind_param($stmt, "s", $appointment_date);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $b_time, $b_duration);

$new_start = strtotime("$appointment_date $appointment_time");
$new_end = $new_start + ($total_minutes * 60);

while(mysqli_stmt_fetch($stmt)) {
    $b_start = strtotime("$appointment_date $b_time");
    $b_end = $b_start + ($b_duration * 60);
    if ($new_start < $b_end && $new_end > $b_start) {
        die("❌ Selected time is already booked.");
    }
}
mysqli_stmt_close($stmt);

/* ---------------- INSERT BOOKING ---------------- */
$stmt = mysqli_prepare($conn, "
    INSERT INTO bookings
    (user_id,appointment_date, appointment_time, duration_minutes, booking_fee, discount, total_amount, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
");
mysqli_stmt_bind_param($stmt, "issiidd", $_SESSION['user_id'],$appointment_date, $appointment_time, $total_minutes, $booking_fee, $discount, $totalAmount);
if(!mysqli_stmt_execute($stmt)) die("❌ Booking insert failed: " . mysqli_error($conn));
$booking_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);


/* ---------------- XENDIT INVOICE ---------------- */
Configuration::setXenditKey('xnd_production_A2pv3BkrsjtoJNWAmhkcKL93KtGiaXZp6ohf7Umc4u55bly2nHTxshpN4kTrmc');
$invoiceApi = new InvoiceApi();

$invoiceRequest = new CreateInvoiceRequest([
    'external_id' => 'Booking_' . $booking_id,
    'amount' => (float)1,
    'payer_email' => $email,
    'currency' => 'PHP',
    'invoice_duration' => 86400,
    'description' => 'Payment for Booking #' . $booking_id,
    'success_redirect_url' => 'http://localhost/care-dental/payment_success.php?id=' . $booking_id,
    'failure_redirect_url' => 'http://localhost/care-dental/payment_fail.php?id=' . $booking_id,
    'payment_methods' => ['GCASH'],
]);

try {
    $invoice = $invoiceApi->createInvoice($invoiceRequest);
    echo "<br>✅ Xendit invoice created: " . $invoice['id'];

    /* ---------------- INSERT PAYMENT ---------------- */
    $stmt = mysqli_prepare($conn, "
        INSERT INTO payments
        (booking_id, total_price, payment_method, status, xendit_invoice_id, payment_date)
        VALUES (?, ?, 'GCASH', 'pending', ?, NOW())
    ");
    mysqli_stmt_bind_param($stmt, "ids", $booking_id, $totalAmount, $invoice['id']);
    if(!mysqli_stmt_execute($stmt)) die("❌ Payment insert failed: " . mysqli_error($conn));
    mysqli_stmt_close($stmt);

    echo "<br>✅ Payment record inserted";

    unset($_SESSION['cart']);
    echo "<br>✅ Cart cleared";

    // echo "<br>Redirecting to Xendit payment page... <a href='{$invoice['invoice_url']}'>Pay Now</a>";

    header("Location:".$invoice['invoice_url']);

} catch (Exception $e) {
    die("❌ Xendit Error: " . $e->getMessage());
}
