<?php
require_once '../db_connect.php';

echo "<h1>Fixing Database Schema</h1>";

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Fix Packages Table
    echo "<p>Updating 'packages' table...</p>";
    $sql_packages = "ALTER TABLE packages MODIFY COLUMN status ENUM('Active','Inactive','Archived') NOT NULL";
    if ($conn->query($sql_packages) === TRUE) {
        echo "<p style='color:green'>Successfully updated 'status' column in 'packages' table.</p>";
    } else {
        echo "<p style='color:red'>Error updating 'packages': " . $conn->error . "</p>";
    }

    // Fix Services Table
    echo "<p>Updating 'services' table...</p>";
    $sql_services = "ALTER TABLE services MODIFY COLUMN status ENUM('Active','Inactive','Archived') NOT NULL";
    if ($conn->query($sql_services) === TRUE) {
        echo "<p style='color:green'>Successfully updated 'status' column in 'services' table.</p>";
    } else {
        echo "<p style='color:red'>Error updating 'services': " . $conn->error . "</p>";
    }

    echo "<p style='color:blue'><strong>Done! You can now use the application.</strong></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Critical Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='package.php'>Go back to Packages</a> | <a href='services.php'>Go back to Services</a></p>";
?>
