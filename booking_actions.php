<?php
session_start();
require_once 'db_connect.php';
require_once './phpmailer2.php';

header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

/* ------------------ AUTH CHECK ------------------ */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$user_id    = $_SESSION['user_id'];
$action     = $_POST['action'] ?? '';
$booking_id = $_POST['booking_id'] ?? '';

if (empty($booking_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Booking ID is required.']);
    exit;
}

/* ------------------ FETCH BOOKING & USER ------------------ */
$stmt = $conn->prepare("
    SELECT b.*, u.email, u.first_name, u.last_name
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    error_log("Booking not found for ID: " . $booking_id); // Debug Log
    echo json_encode(['status' => 'error', 'message' => "Booking not found. (ID: $booking_id)"]);
    exit;
}

$full_name    = $booking['first_name'] . ' ' . $booking['last_name'];
$booking_date = date('F j, Y', strtotime($booking['booking_date']));
$booking_time = date('g:i A', strtotime($booking['time_slot']));

/* ------------------ FETCH PAYMENT ------------------ */
$stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo json_encode(['status' => 'error', 'message' => 'Payment record not found.']);
    exit;
}

$xendit_payment_id = $payment['xendit_payment_id'];
$total_amount     = (float) $payment['total_price'];

/* ------------------ FETCH STAFF & ADMIN ------------------ */
$staff = $conn->query("
    SELECT email FROM users 
    WHERE user_type = 'staff' AND status_ = 'Active' 
    LIMIT 1
")->fetch_assoc();

$admin = $conn->query("
    SELECT email FROM users 
    WHERE user_type = 'admin' AND status_ = 'Active' 
    LIMIT 1
")->fetch_assoc();

/* ==========================================================
   CANCEL BOOKING + REFUND
   ========================================================== */
if ($action === 'cancel') {

    $refundProcessed = false;
    $refundMessage = "";

    // 1. Attempt Refund ONLY if we have a valid payment ID and amount
    if (!empty($xendit_payment_id) && $total_amount > 0 && $payment['status'] === 'approved') {
        
        // Calculate Refund Amount based on Cancellation Policy
        $appointmentTimestamp = strtotime($booking['appointment_date'] . ' ' . $booking['appointment_time']);
        $currentTimestamp = time();
        $secondsUntilAppointment = $appointmentTimestamp - $currentTimestamp;
        $hoursUntilAppointment = $secondsUntilAppointment / 3600;

        $booking_fee = floatval($booking['booking_fee'] ?? 0);
        $refundAmount = $total_amount;
        $deduction = 0;
        $messageDetail = "";

        if ($hoursUntilAppointment > 24) {
            // More than 24h before: Deduct 50% of booking fee
            $deduction = $booking_fee * 0.50;
            $refundAmount = $total_amount - $deduction;
            $messageDetail = "A deduction of PHP " . number_format($deduction, 2) . " (50% of Booking Fee) was applied as per cancellation policy (> 24 hours).";
        } else {
            // Less than or equal to 24h: Forfeit 100% of booking fee
            $deduction = $booking_fee;
            $refundAmount = $total_amount - $deduction;
            $messageDetail = "A deduction of PHP " . number_format($deduction, 2) . " (100% of Booking Fee) was applied as per cancellation policy (<= 24 hours).";
        }

        // Ensure refund amount is not negative
        if ($refundAmount < 0) $refundAmount = 0;

        if ($refundAmount > 0) {
            $apiKey = 'xnd_development_NUCDa05e0ZnIklrBuGxCPDleszx1JWlq2khKSc97CkLreQ4I8k7eyLfspzff3'; 

            $refundData = [
                'invoice_id' => $xendit_payment_id,
                'reason' => 'REQUESTED_BY_CUSTOMER',
                'amount' => $refundAmount,
                'currency' => 'PHP'
            ];

            $ch = curl_init('https://api.xendit.co/refunds');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($refundData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($apiKey . ':')
                ]
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $refundResponse = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                $refundProcessed = true;
                $refundMessage = "Your payment check has been processed. Refund Amount: PHP " . number_format($refundAmount, 2) . ". " . $messageDetail;
            } else {
                 echo json_encode([
                    'status' => 'error',
                    'message' => 'Refund failed: ' . ($refundResponse['message'] ?? 'Unknown error'),
                    'xendit_error' => $refundResponse
                ]);
                exit;
            }
        } else {
             $refundProcessed = true; // Treated as processed even if 0 refund to cancel booking
             $refundMessage = "No refund was issued as the deduction (PHP " . number_format($deduction, 2) . ") equals or exceeds the total amount. " . $messageDetail;
        }
    }

    /* ---------- UPDATE BOOKING ---------- */
    // If we processed a refund, marks as 'refunded', otherwise 'cancelled'
    $finalStatus = $refundProcessed ? 'refunded' : 'cancelled';

    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = ?, cancelled_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("sii", $finalStatus, $booking_id, $user_id);
    $stmt->execute();
    $stmt->close();

    /* ---------- UPDATE PAYMENT ---------- */
    // Also cancel the payment record so it doesn't stay 'pending'
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $finalStatus, $payment['id']);
    $stmt->execute();
    $stmt->close();

    /* ---------- EMAILS ---------- */
    $emailBody = "Your appointment on {$booking_date} at {$booking_time} has been cancelled.\n";
    if ($refundProcessed) {
        $emailBody .= $refundMessage . "\n";
    }

    $customerMessage = "
Hello {$full_name},

{$emailBody}

Thank you,
B-Dental Care
";

    $adminMessage = "
Booking Cancelled
Patient: {$full_name}
Date: {$booking_date}
Time: {$booking_time}
Booking ID: {$booking_id}
";
    if ($refundProcessed) {
         $adminMessage .= "Refunded Amount: PHP " . number_format($total_amount, 2);
    }

    sendEmail($booking['email'], 'Booking Cancelled', $customerMessage);
    if (!empty($staff['email'])) sendEmail($staff['email'], 'Cancelled Booking', $adminMessage);
    if (!empty($admin['email'])) sendEmail($admin['email'], 'Cancelled Booking', $adminMessage);

    echo json_encode([
        'status' => 'success',
        'message' => $refundProcessed ? 'Booking cancelled and payment refunded.' : 'Booking cancelled successfully.'
    ]);
    exit;
}

/* ==========================================================
   RESCHEDULE BOOKING
   ========================================================== */
if ($action === 'reschedule') {

    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';

    if (empty($new_date) || empty($new_time)) {
        echo json_encode(['status' => 'error', 'message' => 'New date and time are required.']);
        exit;
    }

    // Validate that the new date is not in the past
    if (strtotime($new_date) < strtotime(date('Y-m-d'))) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot reschedule to a past date.']);
        exit;
    }

    // NEW: Check Availability in dentist_calendar
    $checkStmt = $conn->prepare("SELECT id FROM dentist_calendar WHERE available_date = ?");
    $checkStmt->bind_param("s", $new_date);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows === 0) {
        $checkStmt->close();
        echo json_encode(['status' => 'error', 'message' => 'The selected date is not available. Please choose another date.']);
        exit;
    }
    $checkStmt->close();

    $stmt = $conn->prepare("
        UPDATE bookings
        SET appointment_date = ?, appointment_time = ?, time_slot = ?, status = 'rescheduled'
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("sssii", $new_date, $new_time, $new_time, $booking_id, $user_id);

    if ($stmt->execute()) {

        $customerMessage = "
Hello {$full_name},

Your appointment has been rescheduled to {$new_date} at {$new_time}.

Thank you,
B-Dental Care
";

        $adminMessage = "
Booking Rescheduled

Patient: {$full_name}
New Date: {$new_date}
New Time: {$new_time}
Booking ID: {$booking_id}
";

        sendEmail($booking['email'], 'Booking Rescheduled', $customerMessage);
        if (!empty($staff['email'])) sendEmail($staff['email'], 'Rescheduled Booking', $adminMessage);
        if (!empty($admin['email'])) sendEmail($admin['email'], 'Rescheduled Booking', $adminMessage);

        echo json_encode(['status' => 'success', 'message' => 'Booking rescheduled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reschedule booking.']);
    }

    $stmt->close();
    exit;
}

/* ------------------ INVALID ACTION ------------------ */
echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
