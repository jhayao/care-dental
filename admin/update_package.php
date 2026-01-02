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
    $service_ids = $_POST['service_ids'] ?? []; // Array of selected service IDs

    // Validate required fields
    if (empty($package_name) || empty($description) || empty($service_ids) || empty($status) || !is_numeric($price)) {
         $_SESSION['error'] = "Please fill all required fields and select at least one service.";
         header("Location: edit_package.php?id=" . $id);
         exit;
    }
    
    // Calculate total duration and get service names for inclusions
    $total_duration = 0;
    $inclusions_names = [];

    if (!empty($service_ids)) {
        // Prepare statement to fetch service details
        // Dynamically create placeholders based on count
        $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
        $types = str_repeat('i', count($service_ids));
        
        $stmt_services = $conn->prepare("SELECT service_name, duration_minutes FROM services WHERE id IN ($placeholders)");
        $stmt_services->bind_param($types, ...$service_ids);
        $stmt_services->execute();
        $result = $stmt_services->get_result();

        while ($row = $result->fetch_assoc()) {
            $total_duration += (int)$row['duration_minutes'];
            $inclusions_names[] = $row['service_name'];
        }
        $stmt_services->close();
    }
    
    // Encode inclusions as JSON
    $inclusions_json = json_encode($inclusions_names);

    // Validate status against allowed values
    $allowed_statuses = ['Active', 'Inactive', 'Archived'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'Inactive';
    }

    $stmt = $conn->prepare("
        UPDATE packages
        SET package_name = ?, description = ?, inclusions = ?, status = '$status', price = ?, duration_minutes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    // Removed $status from bind_param, type string changed from 'ssssdii' to 'sssdii'
    $stmt->bind_param("sssdii", $package_name, $description, $inclusions_json, $price, $total_duration, $id);

    if ($stmt->execute()) {
        // Redirect back to packages.php with success
        header("Location: package.php?success=1");
        exit;
    } else {
        $_SESSION['error'] = "Error updating package: " . $stmt->error;
        header("Location: edit_package.php?id=" . $id);
        exit;
    }

    $stmt->close();
}
$conn->close();
?>
