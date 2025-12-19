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
    $inclusions = trim($_POST['inclusions'] ?? '');
    $status = $_POST['status'] ?? 'Inactive';
    $price = $_POST['price'] ?? 0;
    $duration_minutes = $_POST['duration_minutes'] ?? 0;
    $posted_by = $_SESSION['user_id'];

    if (!$package_name || !$description || !$duration_minutes) {
        $_SESSION['error'] = "Please fill all required fields.";
        header("Location: packages.php");
        exit;
    }

    // Encode inclusions as JSON (comma-separated input)
    $inclusions_array = array_filter(array_map('trim', explode(',', $inclusions)));
    $inclusions_json = json_encode($inclusions_array);

    // Prepare insert
    $stmt = $conn->prepare("
        INSERT INTO packages
            (posted_by, package_name, description, inclusions, status, price, duration_minutes, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    if ($stmt) {
        $stmt->bind_param(
            "isssidi",
            $posted_by,
            $package_name,
            $description,
            $inclusions_json,
            $status,
            $price,
            $duration_minutes
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
    header("Location: package.php");
    exit;
} else {
    header("Location: package.php");
    exit;
}
