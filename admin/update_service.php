<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id']);
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    $price = floatval($_POST['price']);
    $duration_minutes = intval($_POST['duration_minutes']); // NEW FIELD

    if (empty($service_name) || empty($description) || empty($status) || $price < 0 || $duration_minutes < 0) {
        $_SESSION['error'] = "Please fill in all fields correctly.";
        header("Location: services.php");
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE services 
        SET service_name = ?, description = ?, status = ?, price = ?, duration_minutes = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("sssdii", $service_name, $description, $status, $price, $duration_minutes, $id); // UPDATED

    if ($stmt->execute()) {
        $_SESSION['success'] = "Service updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update service. Please try again.";
    }

    $stmt->close();
    $conn->close();

    header("Location: services.php");
    exit;
} else {
    header("Location: services.php");
    exit;
}
?>
