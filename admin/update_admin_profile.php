<?php
session_start();
require_once '../db_connect.php';

// Ensure admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get POST data
$user_id = $_POST['id'];
$first_name = trim($_POST['first_name']);
$last_name  = trim($_POST['last_name']);
$address    = trim($_POST['address_']);
$email      = trim($_POST['email']);
$gender     = $_POST['gender'];

// Basic validation
if (empty($first_name) || empty($last_name) || empty($address) || empty($email) || empty($gender)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: admin_profile.php");
    exit;
}

// Check if email is already used by another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email is already in use by another account.";
    header("Location: profile.php");
    exit;
}
$stmt->close();

// Update user details
$stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, address_=?, email=?, gender=?, updated_at=NOW() WHERE id=? AND user_type='admin'");
$stmt->bind_param("sssssi", $first_name, $last_name, $address, $email, $gender, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Profile updated successfully.";
} else {
    $_SESSION['error'] = "Error updating profile: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to profile page
header("Location: profile.php");
exit;
?>
