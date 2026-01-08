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

<?php
try {
    $conn->query("ALTER TABLE payments ADD COLUMN due_date DATE NULL");
    echo "SUCCESS: due_date column added.<br>";
} catch (Exception $e) {
    // It might already exist, checks are good but simple try-catch works for quick fix script
    echo "NOTE due_date: " . $e->getMessage() . "<br>";
}
?>

<?php
try {
    $conn->query("ALTER TABLE payments ADD COLUMN installment_number INT DEFAULT 0 NULL");
    echo "SUCCESS: installment_number column added.<br>";
} catch (Exception $e) {
    echo "NOTE installment_number: " . $e->getMessage() . "<br>";
}
?>

<?php
try {
    $conn->query("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'cancelled', 'refunded', 'scheduled') DEFAULT 'pending'");
    echo "SUCCESS: 'scheduled' added to status enum.<br>";
} catch (Exception $e) {
    echo "NOTE status enum: " . $e->getMessage() . "<br>";
}
?>
