<?php
/**
 * Database Setup Script
 * 
 * This script creates the 'dentist' database and imports the schema 
 * from 'dentist_project.sql'.
 */

// Database credentials (matching db_connect.php)
$host     = "localhost";
$username = "root";
$password = "";
$dbname   = "dentist"; // Target database name
$sqlFile  = "script.sql";

echo "<h1>Database Setup</h1>";

// Enable exception reporting for mysqli (default in PHP 8+)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Connect to MySQL server (without selecting DB)
    $conn = new mysqli($host, $username, $password);
    echo "<p>Connected to MySQL server successfully.</p>";

    // 2. Create Database
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->query($sql);
    echo "<p>Database '<strong>$dbname</strong>' created or already exists.</p>";

    // 3. Select Database
    $conn->select_db($dbname);

    // 4. Read SQL file
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file '$sqlFile' not found.");
    }

    $sqlContent = file_get_contents($sqlFile);

    // 5. Execute SQL (using multi_query)
    set_time_limit(300);
    echo "<p>Importing schema from '$sqlFile'...</p>";

    if ($conn->multi_query($sqlContent)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
            // Check if there are more results
        } while ($conn->more_results() && $conn->next_result());
        
        echo "<p style='color:green'><strong>Database setup completed successfully!</strong></p>";
    }

    $conn->close();

} catch (mysqli_sql_exception $e) {
    die("<p style='color:red'>MySQL Error: " . $e->getMessage() . "</p>");
} catch (Exception $e) {
    die("<p style='color:red'>Error: " . $e->getMessage() . "</p>");
}
?>
