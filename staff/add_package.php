<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize input
    $package_name = trim($_POST['package_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Inactive';
    $status = trim($status); // Ensure it is trimmed!
    // DEBUG:
    // var_dump($status); die();
    $price = $_POST['price'] ?? 0;
    $service_ids = $_POST['service_ids'] ?? []; // Array of selected service IDs
    $posted_by = $_SESSION['user_id'];

    if (!$package_name || !$description || empty($service_ids)) {
        $_SESSION['error'] = "Please fill all required fields and select at least one service.";
        header("Location: manage_packages.php");
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

    // Validate status against allowed values to be safe for literal insertion
    $allowed_statuses = ['Active', 'Inactive', 'Archived'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'Inactive';
    }

    // Prepare insert - Interpolating status directly to bypass potential bind_param issue with ENUM
    $stmt = $conn->prepare("
        INSERT INTO packages
            (posted_by, package_name, description, inclusions, status, price, duration_minutes, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, '$status', ?, ?, NOW(), NOW())
    ");
    
    // Type string changed from 'isssidi' to 'issidi' (removed one 's' for status)
    // removed $status from bind_param arguments

    if ($stmt) {
        $stmt->bind_param(
            "issidi",
            $posted_by,
            $package_name,
            $description,
            $inclusions_json,
            $price,
            $total_duration
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Package added successfully!";
        } else {
            $_SESSION['error'] = "Database error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare statement: " . $conn->error;
    }

    $conn->close();

    // Redirect back to packages.php
    header("Location: manage_packages.php");
    exit;
} else {
    header("Location: manage_packages.php");
    exit;
}
