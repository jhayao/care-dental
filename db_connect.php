<?php
// Database configuration
$host     = "localhost";   // e.g. "127.0.0.1"
$username = "root";        // your DB username
$password = "";            // your DB password
$database = "dentist"; // your DB name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8mb4");
?>