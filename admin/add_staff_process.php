<?php
session_start();
require_once '../db_connect.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $address_   = trim($_POST['address_']);
    $email      = trim($_POST['email']);
    $gender     = $_POST['gender'];
    $status_    = $_POST['status_'];
    $pword      = $_POST['pword'];
    $confirm_pword = $_POST['confirm_password'];


    if (empty($first_name) || empty($last_name) || empty($address_) || empty($email) || empty($gender) || empty($status_) || empty($pword) || empty($confirm_pword)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: staff_list.php");
        exit;
    }

    if ($pword !== $confirm_pword) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: staff_list.php");
        exit;
    }


    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Email already exists.";
        $stmt->close();
        header("Location: staff_list.php");
        exit;
    }
    $stmt->close();


    $hashed_pword = password_hash($pword, PASSWORD_DEFAULT);


    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, address_, email, gender, status_, pword, user_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'staff', NOW())
    ");
    $stmt->bind_param("sssssss", $first_name, $last_name, $address_, $email, $gender, $status_, $hashed_pword);

    if ($stmt->execute()) {
        $_SESSION['success'] = "New staff added successfully.";
    } else {
        $_SESSION['error'] = "Error adding staff: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: staff_list.php");
    exit;
} else {
    header("Location: staff_list.php");
    exit;
}
?>
