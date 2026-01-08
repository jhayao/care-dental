<?php
require_once 'db_connect.php';

echo "<h1>Updating Schema...</h1>";

function executeQuery($conn, $sql, $successMsg) {
    try {
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green'>✅ $successMsg</p>";
        } else {
            // Check for duplicate column error or similar harmless errors
            if (strpos($conn->error, "Duplicate column") !== false) {
                 echo "<p style='color:orange'>⚠️ Column already exists (Skipped)</p>";
            } else {
                 echo "<p style='color:red'>❌ Error: " . $conn->error . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Exception: " . $e->getMessage() . "</p>";
    }
}

// 1. Update bookings table status enum
// Existing: 'pending', 'confirmed', 'cancelled', 'rescheduled', 'refunded', 'completed'
// Needed: 'rejected', 'failed'
echo "<h3>1. Updating 'bookings' table...</h3>";
$sql = "ALTER TABLE bookings MODIFY COLUMN status 
        ENUM('pending', 'confirmed', 'cancelled', 'rescheduled', 'refunded', 'completed', 'rejected', 'failed') 
        DEFAULT 'pending'";
executeQuery($conn, $sql, "Updated 'bookings' status ENUM.");


// 2. Update payments table
echo "<h3>2. Updating 'payments' table...</h3>";
// Add payment_url TEXT
$check = $conn->query("SHOW COLUMNS FROM payments LIKE 'payment_url'");
if ($check->num_rows == 0) {
    executeQuery($conn, "ALTER TABLE payments ADD COLUMN payment_url TEXT NULL", "Added 'payment_url' column.");
} else {
    echo "<p style='color:orange'>⚠️ 'payment_url' already exists.</p>";
}

// Add description TEXT
$check = $conn->query("SHOW COLUMNS FROM payments LIKE 'description'");
if ($check->num_rows == 0) {
    executeQuery($conn, "ALTER TABLE payments ADD COLUMN description TEXT NULL", "Added 'description' column.");
} else {
    echo "<p style='color:orange'>⚠️ 'description' already exists.</p>";
}

// 3. Update packages and services tables
echo "<h3>3. Updating 'packages' & 'services' tables...</h3>";

foreach (['packages', 'services'] as $table) {
    echo "<strong>Table: $table</strong><br>";
    // Add down_payment
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE 'down_payment'");
    if ($check->num_rows == 0) {
        executeQuery($conn, "ALTER TABLE $table ADD COLUMN down_payment DECIMAL(10,2) DEFAULT 0.00", "Added 'down_payment' to $table.");
    } else {
        echo "<p style='color:orange'>⚠️ 'down_payment' already exists in $table.</p>";
    }

    // Add installment_months
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE 'installment_months'");
    if ($check->num_rows == 0) {
        executeQuery($conn, "ALTER TABLE $table ADD COLUMN installment_months INT DEFAULT 0", "Added 'installment_months' to $table.");
    } else {
         echo "<p style='color:orange'>⚠️ 'installment_months' already exists in $table.</p>";
    }
}

echo "<h2>🎉 Schema Update Complete!</h2>";
?>
