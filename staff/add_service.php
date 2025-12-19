<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$posted_by = $_SESSION['user_id'];
$service_name = $_POST['service_name'];
$description = $_POST['description'];
$price = $_POST['price'];
$duration_minutes = $_POST['duration_minutes']; // <-- new field

$service_image = null;

if (!empty($_FILES['service_image']['name'])) {

    $targetDir = "../uploads/services/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid() . "_" . basename($_FILES["service_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["service_image"]["tmp_name"], $targetFilePath)) {
        $service_image = "uploads/services/" . $fileName;
    }
}

$stmt = $conn->prepare("
    INSERT INTO services (posted_by, service_name, description, service_image, price, duration_minutes, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param("isssdi", $posted_by, $service_name, $description, $service_image, $price, $duration_minutes);

if ($stmt->execute()) {
    header("Location: services.php?success=1");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
