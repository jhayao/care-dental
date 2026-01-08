<?php
session_start();
require_once '../db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Adjust path to vendor

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

header('Content-Type: application/json');

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 2. Schema Check (Lazy Migration)
try {
    // Check if columns exist, if not add them. 
    // This is a rough way to do it but works for this context without CLI access.
    // Suppress errors if column exists
    $conn->query("ALTER TABLE payments ADD COLUMN payment_url VARCHAR(500) NULL");
    $conn->query("ALTER TABLE payments ADD COLUMN description VARCHAR(255) NULL");
} catch (Exception $e) {
    // Ignore error if column already exists
}

// 3. Input Validation
$booking_id = $_POST['booking_id'] ?? null;
$amount     = $_POST['amount'] ?? null;
$notes      = $_POST['notes'] ?? 'Installment Payment';

if (!$booking_id || !$amount || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid inputs. Valid Booking ID and Amount required.']);
    exit;
}

// 4. Fetch Booking & User Info
$stmt = $conn->prepare("
    SELECT b.id, b.user_id, u.email, u.first_name, u.last_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    exit;
}

// 5. Create Xendit Invoice
require_once '../config.php';
Configuration::setXenditKey(XENDIT_API_KEY);
$invoiceApi = new InvoiceApi();

$external_id = 'INSTALLMENT_' . $booking_id . '_' . time();

$invoiceRequest = new CreateInvoiceRequest([
    'external_id' => $external_id,
    'amount' => (float)$amount,
    'payer_email' => $booking['email'],
    'currency' => 'PHP',
    'invoice_duration' => 86400 * 7, // 7 Days validity
    'description' => $notes . ' (Booking #' . $booking_id . ')',
    'success_redirect_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . '/care-dental/care-dental/payment_success.php?id=' . $booking_id,
    'failure_redirect_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . '/care-dental/care-dental/payment_fail.php?id=' . $booking_id,
    'payment_methods' => ['GCASH'] // Added usage of common e-wallets
]);


// docker run -d --name oa-v11 -p 8000:8000 -v ${PWD}:/var/www/html --network official-alumni_my-network ghcr.io/hackazouk-inc/docker-hkz:multi-arch-8.1-alp-ngx-node16

try {
    $invoice = $invoiceApi->createInvoice($invoiceRequest);

    // 6. Save Payment Record
    $stmt = $conn->prepare("
        INSERT INTO payments 
        (booking_id, total_price, payment_method, status, xendit_invoice_id, payment_date, payment_url, description) 
        VALUES (?, ?, 'Link', 'pending', ?, NOW(), ?, ?)
    ");
    
    $payment_url = $invoice['invoice_url'];
    $invoice_id = $invoice['id'];
    
    $stmt->bind_param("idsss", $booking_id, $amount, $invoice_id, $payment_url, $notes);
    
    if($stmt->execute()) {
         echo json_encode(['success' => true, 'message' => 'Payment link created successfully.']);
    } else {
         echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Xendit Error: ' . $e->getMessage()]);
}
?>
