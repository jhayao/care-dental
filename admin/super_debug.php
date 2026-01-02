<?php
require_once '../db_connect.php';

header('Content-Type: text/plain');

echo "Connected Database: " . $database . "\n";
echo "Host: " . $host . "\n";

echo "\n--- CHECKING PACKAGES TABLE ---\n";
$result = $conn->query("DESCRIBE packages status");
if ($result) {
    print_r($result->fetch_assoc());
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n--- CHECKING POST DATA (Dummy) ---\n";
echo "Testing 'Archived' insertion...\n";

// Try a dry run insertion to see if MySQL complains specifically about the value
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO packages (posted_by, package_name, description, inclusions, status, price, duration_minutes) VALUES (1, 'Test', 'Test', '[]', 'Archived', 100, 60)");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    echo "SUCCESS: 'Archived' value accepted!\n";
    $conn->rollback();
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}

?>
