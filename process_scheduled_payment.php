<?php
// process_scheduled_payment.php
// Triggered by QStash when a scheduled installment is due.

require_once 'db_connect.php';
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

header('Content-Type: application/json');

// 1. Get Input
$input = json_decode(file_get_contents('php://input'), true);
$payment_id = $input['payment_id'] ?? null;

if (!$payment_id) {
    echo json_encode(['status' => 'error', 'message' => 'No payment_id provided']);
    exit;
}

// 2. Fetch Scheduled Payment
$stmt = $conn->prepare("
    SELECT p.*, b.user_id, u.email, u.first_name, u.last_name 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE p.id = ? AND p.status = 'scheduled'
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo json_encode(['status' => 'error', 'message' => 'Payment not found or already processed']);
    exit;
}

// 3. Generate Xendit Invoice
Configuration::setXenditKey(XENDIT_API_KEY);
$invoiceApi = new InvoiceApi();

$external_id = 'INST_AUTO_' . $payment['booking_id'] . '_' . $payment['installment_number'] . '_' . time();
$amount = (float)$payment['total_price'];
$desc = $payment['description'] . " (Booking #" . $payment['booking_id'] . ")";

$invoiceRequest = new CreateInvoiceRequest([
    'external_id' => $external_id,
    'amount' => $amount,
    'payer_email' => $payment['email'],
    'currency' => 'PHP',
    'invoice_duration' => 86400 * 30, // Valid for 30 days
    'description' => $desc,
    'success_redirect_url' => APP_URL . '/payment_success.php?id=' . $payment['booking_id'], // Reuse success page logic to mark as paid
    'failure_redirect_url' => APP_URL . '/payment_fail.php?id=' . $payment['booking_id'],
    'payment_methods' => ['GCASH'] 
]);

try {
    $invoice = $invoiceApi->createInvoice($invoiceRequest);
    $invoice_url = $invoice['invoice_url'];
    $xendit_id = $invoice['id'];

    // 4. Update Payment Record to 'pending' (Available for payment)
    $stmt = $conn->prepare("
        UPDATE payments
        SET status = 'pending', xendit_invoice_id = ?, payment_url = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $xendit_id, $invoice_url, $payment_id);
    $stmt->execute();
    $stmt->close();

    // 5. Send Email Notification
    require_once './phpmailer2.php'; 
    // Assuming sendEmail function exists and works.
    
    $subject = "Installment Invoice Due: " . $desc;
    $body = "
        <p>Dear {$payment['first_name']},</p>
        <p>Your installment payment of <strong>₱" . number_format($amount, 2) . "</strong> is now due.</p>
        <p>Please pay using the link below:</p>
        <p><a href='$invoice_url' style='padding:10px 20px; background-color:blue; color:white; text-decoration:none; border-radius:5px;'>Pay Now</a></p>
        <p>Due Date: " . date('F j, Y', strtotime($payment['due_date'])) . "</p>
        <p>Thank you!</p>
    ";
    
    sendEmail([$payment['email']], $subject, $body);

    echo json_encode(['status' => 'success', 'message' => 'Invoice created and emailed']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Xendit Error: ' . $e->getMessage()]);
}
?>
