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
    $duration_minutes = intval($_POST['duration_minutes']);

    if (empty($service_name) || empty($description) || empty($status) || $price < 0 || $duration_minutes < 0) {
        $_SESSION['error'] = "Please fill in all fields correctly.";
        header("Location: edit_service.php?id=" . $id);
        exit;
    }

    // Validate status
    $allowed_statuses = ['Active', 'Inactive', 'Archived'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'Inactive';
    }

    // Handle Image Upload
    $service_image_path = null;
    if (!empty($_FILES['service_image']['name'])) {
        $targetDir = "../uploads/services/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($_FILES["service_image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["service_image"]["tmp_name"], $targetFilePath)) {
            $service_image_path = "uploads/services/" . $fileName;
        }
    }

    if ($service_image_path) {
        // Update with image - literal status
        $stmt = $conn->prepare("
            UPDATE services 
            SET service_name = ?, description = ?, status = '$status', price = ?, duration_minutes = ?, service_image = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        // Removed status 's' from bind_param
        // Types: name(s), desc(s), price(d), dur(i), image(s), id(i) -> ssdisi
        $stmt->bind_param("ssdisi", $service_name, $description, $price, $duration_minutes, $service_image_path, $id);
    } else {
        // Update without image - literal status
        $stmt = $conn->prepare("
            UPDATE services 
            SET service_name = ?, description = ?, status = '$status', price = ?, duration_minutes = ?, updated_at = NOW() 
            WHERE id = ?
        ");
         // Types: name(s), desc(s), price(d), dur(i), id(i) -> ssdii
        $stmt->bind_param("ssdii", $service_name, $description, $price, $duration_minutes, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Service updated successfully!";
        header("Location: services.php?success=1");
    } else {
        $_SESSION['error'] = "Failed to update service: " . $stmt->error;
        header("Location: edit_service.php?id=" . $id);
    }

    $stmt->close();
    $conn->close();
    exit;
} else {
    header("Location: services.php");
    exit;
}
?>
