<?php
// fix_status_enum.php
require_once 'db_connect.php';

try {
    echo "Attempting to update 'status' column in 'payments' table...<br>";
    
    // Explicitly set the new ENUM definition
    $sql = "ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'cancelled', 'refunded', 'scheduled') DEFAULT 'pending'";
    
    if ($conn->query($sql) === TRUE) {
        echo "<h2 style='color:green'>SUCCESS: 'scheduled' added to status enum.</h2>";
    } else {
        echo "<h2 style='color:red'>ERROR: " . $conn->error . "</h2>";
    }

    // Verify
    echo "<br>Verifying columns:<br>";
    $result = $conn->query("SHOW COLUMNS FROM payments LIKE 'status'");
    if ($row = $result->fetch_assoc()) {
        echo "Type: " . $row['Type'] . "<br>";
    }

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage();
}
?>
