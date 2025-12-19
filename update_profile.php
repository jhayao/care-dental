<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address_ = trim($_POST['address_']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // May be empty

    // Basic validation (you can expand)
    if (empty($first_name) || empty($last_name) || empty($email)) {
        die("First name, last name, and email are required.");
    }

    // Start building query
    $params = [];
    $types = "";
    $sql = "UPDATE users SET first_name = ?, last_name = ?, address_ = ?, email = ?";
    $params[] = &$first_name;
    $params[] = &$last_name;
    $params[] = &$address_;
    $params[] = &$email;
    $types .= "ssss";

    // If password is provided, hash and add to query
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", pword = ?";
        $params[] = &$hashed_password;
        $types .= "s";
    }

    $sql .= " WHERE id = ? AND user_type = 'patient'";
    $params[] = &$user_id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update profile: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: profile.php");
    exit;
} else {
    header("Location: profile.php");
    exit;
}
