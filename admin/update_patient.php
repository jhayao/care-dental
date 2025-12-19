<?php
session_start();
require_once '../db_connect.php';

// Ensure staff/admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $status = $_POST['status'];

    // Validate inputs (basic example, expand as needed)
    if (empty($first_name) || empty($last_name) || empty($address) || empty($email)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: patients.php");
        exit;
    }

    // Update patient in database
    $stmt = $conn->prepare("
        UPDATE users SET
            first_name = ?,
            last_name = ?,
            address_ = ?,
            email = ?,
            gender = ?,
            status_ = ?
        WHERE id = ? AND user_type = 'patient'
    ");
    $stmt->bind_param("ssssssi", $first_name, $last_name, $address, $email, $gender, $status, $id);

    if ($stmt->execute()) {
        $stmt->close();
        // Redirect back with success flag
        header("Location: users.php?success=1");
        exit;
    } else {
        $stmt->close();
        $_SESSION['error'] = "Failed to update patient. Please try again.";
        header("Location: users.php");
        exit;
    }
} else {
    // Invalid access
    header("Location: users.php");
    exit;
}
