<?php
require_once 'db_connect.php';

try {
    $conn->query("ALTER TABLE payments ADD COLUMN payment_url VARCHAR(500) NULL");
    echo "SUCCESS: payment_url column added.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

try {
    // Also check if payment_method is specific ENUM or VARCHAR
    // If it's short, let's make it longer to support notes like 'Installment 1'
    // But usually payment_method is 'GCASH', 'CREDIT_CARD'.
    // Maybe we add 'description' or 'notes'?
    $conn->query("ALTER TABLE payments ADD COLUMN description VARCHAR(255) NULL");
    echo "SUCCESS: description column added.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
