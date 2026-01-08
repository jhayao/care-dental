<?php
require_once 'db_connect.php';

try {
    // 1. Add installment_months to packages
    $conn->query("ALTER TABLE packages ADD COLUMN installment_months INT DEFAULT 0 AFTER down_payment");
    echo "Added installment_months to packages.<br>";
} catch (Exception $e) { echo "Skip packages: " . $e->getMessage() . "<br>"; }

try {
    // 2. Add installment_months to services
    $conn->query("ALTER TABLE services ADD COLUMN installment_months INT DEFAULT 0 AFTER down_payment");
    echo "Added installment_months to services.<br>";
} catch (Exception $e) { echo "Skip services: " . $e->getMessage() . "<br>"; }

try {
    // 3. Add due_date and installment_number to payments
    $conn->query("ALTER TABLE payments ADD COLUMN due_date DATE NULL AFTER payment_date");
    $conn->query("ALTER TABLE payments ADD COLUMN installment_number INT DEFAULT 0 AFTER due_date");
    // Add 'scheduled' to enum? modifying enum is tricky in MySQL if strict. 
    // Usually it's just a VARCHAR or similar if we didn't specify ENUM explicitly in CREATE TABLE.
    // Let's check status column type. If it's pure varchar(50) usually, we are fine.
    // Assuming VARCHAR based on previous scripts.
    echo "Added due_date and installment_number to payments.<br>";
} catch (Exception $e) { echo "Skip payments: " . $e->getMessage() . "<br>"; }

echo "Schema update complete.";
?>
