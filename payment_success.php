<?php
// payment_success.php

if (!isset($_GET['id'])) {
    header("Location: appointments.php");
    exit;
}

// This ID is ONLY for display/reference
$booking_id = (int) $_GET['id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 80px;
        }
        .success {
            color: green;
            font-size: 22px;
        }
        .note {
            margin-top: 10px;
            color: #555;
        }
    </style>
</head>
<body>

<h2 class="success">✅ Payment Successful</h2>
<p>Your payment was received.</p>

<p class="note">
    Your booking will be confirmed shortly.<br>
    Please wait for email confirmation.
</p>

<a href="appointments.php">Back to Appointments</a>

</body>
</html>
