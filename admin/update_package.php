<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $package_name = trim($_POST['package_name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $status = $_POST['status'] ?? 'Inactive'; // Default to Inactive if not set
    $duration_minutes = $_POST['duration_minutes']; // new field

    // Process inclusions as JSON array
    $inclusions_raw = trim($_POST['inclusions']);
    $inclusions_array = array_filter(array_map('trim', preg_split("/\r\n|\n|,/", $inclusions_raw)));
    $inclusions_json = json_encode($inclusions_array);

    // Validate required fields
    if (empty($package_name) || empty($description) || empty($inclusions_array) || empty($status) || !is_numeric($price) || !is_numeric($duration_minutes)) {
        die("Invalid input. Please fill all required fields and provide a valid number for price and duration.");
    }

    $stmt = $conn->prepare("
        UPDATE packages
        SET package_name = ?, description = ?, inclusions = ?, status = ?, price = ?, duration_minutes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssssdii", $package_name, $description, $inclusions_json, $status, $price, $duration_minutes, $id);

    if ($stmt->execute()) {
        // Redirect back to packages.php with success
        header("Location: package.php?success=1");
        exit;
    } else {
        echo "Error updating package: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
