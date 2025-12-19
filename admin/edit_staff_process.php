<?php
session_start();
require_once '../db_connect.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address_ = trim($_POST['address_']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $status_ = $_POST['status_'];

 
    if (empty($first_name) || empty($last_name) || empty($address_) || empty($email) || empty($gender) || empty($status_)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: edit_staff.php?id=$id");
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Email already in use by another staff.";
        $stmt->close();
        header("Location: edit_staff.php?id=$id");
        exit;
    }
    $stmt->close();

  
    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, address_=?, email=?, gender=?, status_=? WHERE id=?");
    $stmt->bind_param("ssssssi", $first_name, $last_name, $address_, $email, $gender, $status_, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Staff updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating staff: " . $stmt->error;
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
