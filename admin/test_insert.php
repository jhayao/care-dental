<?php
require_once '../db_connect.php';

echo "<h1>Testing Direct Insertion</h1>";

$posted_by = 1; // Assuming ID 1 exists (Admin from script.sql)
$package_name = "Test Archive Package";
$description = "This is a test";
$inclusions = "[]";
$status = "Archived";
$price = 100.00;
$duration = 60;

echo "<p>Attempting to insert package with status: <strong>$status</strong></p>";

try {
    $stmt = $conn->prepare("
        INSERT INTO packages
            (posted_by, package_name, description, inclusions, status, price, duration_minutes, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isssidi", $posted_by, $package_name, $description, $inclusions, $status, $price, $duration);

    if ($stmt->execute()) {
        echo "<h2 style='color:green'>SUCCESS: Inserted with status 'Archived'!</h2>";
        $id = $stmt->insert_id;
        echo "<p>New ID: $id</p>";
        
        // Clean up
        $conn->query("DELETE FROM packages WHERE id = $id");
        echo "<p>Test record deleted.</p>";
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

} catch (Exception $e) {
    echo "<h2 style='color:red'>FAILURE: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
