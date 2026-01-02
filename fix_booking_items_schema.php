<?php
require_once 'db_connect.php';

echo "<h1>Fixing booking_items Schema...</h1>";

try {
    // 1. Check if 'booking_items' table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'booking_items'");
    if ($checkTable->num_rows == 0) {
        die("❌ Table 'booking_items' does not exist. Please run the full database setup.");
    }

    // 2. Check if 'id' column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM booking_items LIKE 'id'");
    if ($checkColumn->num_rows == 0) {
         // Create the column if it doesn't exist (unlikely given the error)
         $sql = "ALTER TABLE booking_items ADD COLUMN id BIGINT UNSIGNED NOT NULL FIRST";
         if ($conn->query($sql) === TRUE) {
             echo "✅ Added 'id' column.<br>";
         } else {
             throw new Exception("Error adding 'id' column: " . $conn->error);
         }
    }

    // 3. Make 'id' PRIMARY KEY and AUTO_INCREMENT
    // We need to drop the primary key if it exists (it might not be on ID)
    // But usually, if it's not AI, it might not be PK.
    // Let's try to modify the column directly.
    
    $sql = "ALTER TABLE booking_items MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Successfully updated 'booking_items' table: 'id' is now AUTO_INCREMENT PRIMARY KEY.<br>";
    } else {
        // If it fails, maybe there's already a primary key or data conflicts?
        // Let's print the error.
        throw new Exception("Error modifying table: " . $conn->error);
    }
    
    echo "<h3>🎉 Fix applied successfully! You can now try booking again.</h3>";

} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
