<?php
// pay_installment.php
// Handles on-demand payment link generation for installments.

session_start();
require_once 'db_connect.php';
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_id = $_GET['id'] ?? null;

if (!$payment_id) {
    die("Error: No payment ID provided.");
}

// 2. Fetch Payment & Verify Ownership
$stmt = $conn->prepare("
    SELECT p.*, b.user_id, u.email, u.first_name, u.last_name 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE p.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $payment_id, $user_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die("Error: Payment not found or access denied.");
}

// Check if already paid
if (in_array($payment['status'], ['paid', 'approved', 'completed'])) {
    die("Error: This payment is already completed.");
}

// Check if valid URL exists (and not expired - simplistic check)
if (!empty($payment['payment_url']) && $payment['status'] === 'pending') {
    // If we already have a link, just redirect to it.
    // OPTIONAL: We could check Xendit API to see if it's expired. For now, assume valid.
    header("Location: " . $payment['payment_url']);
    exit;
}

// 3. Generate Xendit Invoice
Configuration::setXenditKey(XENDIT_API_KEY);
$invoiceApi = new InvoiceApi();

$external_id = 'INST_MANUAL_' . $payment['booking_id'] . '_' . $payment['installment_number'] . '_' . time();
$amount = (float)$payment['total_price'];
$desc = $payment['description'] . " (Booking #" . $payment['booking_id'] . ")";

$invoiceRequest = new CreateInvoiceRequest([
    'external_id' => $external_id,
    'amount' => $amount,
    'payer_email' => $payment['email'],
    'currency' => 'PHP',
    'invoice_duration' => 86400, // Valid for 24 hours
    'description' => $desc,
    'success_redirect_url' => APP_URL . '/payment_success.php?id=' . $payment['booking_id'], 
    'failure_redirect_url' => APP_URL . '/payment_fail.php?id=' . $payment['booking_id'],
    'payment_methods' => ['GCASH', 'CREDIT_CARD', 'PAYMAYA', 'GRABPAY'] 
]);

try {
    $invoice = $invoiceApi->createInvoice($invoiceRequest);
    $invoice_url = $invoice['invoice_url'];
    $xendit_id = $invoice['id'];

    // 4. Update Payment Record
    $stmt = $conn->prepare("
        UPDATE payments
        SET status = 'pending', xendit_invoice_id = ?, payment_url = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $xendit_id, $invoice_url, $payment_id);
    $stmt->execute();
    $stmt->close();

    // 5. Redirect User
    header("Location: " . $invoice_url);
    exit;

} catch (Exception $e) {
    die("Error creating invoice: " . $e->getMessage());
}
?>
