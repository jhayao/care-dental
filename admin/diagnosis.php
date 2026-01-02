<?php
require_once '../db_connect.php';
header('Content-Type: text/plain');

echo "DIAGNOSIS START\n";

// 1. Show Table Definition
$res = $conn->query("SHOW CREATE TABLE packages");
$row = $res->fetch_assoc();
echo "TABLE DEF:\n" . $row['Create Table'] . "\n\n";

// 2. Test Direct Insert with Bind Param
echo "TEST 1: Bind Param\n";
try {
    $stmt = $conn->prepare("INSERT INTO packages (posted_by, package_name, description, inclusions, status, price, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $p = 1; $n = 'TestBind'; $d = 'Desc'; $i = '[]'; $s = 'Archived'; $pr = 100; $du = 60;
    $stmt->bind_param("isssidi", $p, $n, $d, $i, $s, $pr, $du);
    if ($stmt->execute()) {
        echo "SUCCESS Bind Param\n";
        $conn->query("DELETE FROM packages WHERE id = " . $stmt->insert_id);
    } else {
        echo "FAIL Bind Param: " . $stmt->error . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION Bind Param: " . $e->getMessage() . "\n";
}

// 3. Test Direct Insert with Literal
echo "\nTEST 2: Literal\n";
try {
    $sql = "INSERT INTO packages (posted_by, package_name, description, inclusions, status, price, duration_minutes) VALUES (1, 'TestLit', 'Desc', '[]', 'Archived', 100, 60)";
    if ($conn->query($sql) === TRUE) {
        echo "SUCCESS Literal\n";
        $conn->query("DELETE FROM packages WHERE package_name = 'TestLit'");
    } else {
        echo "FAIL Literal: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION Literal: " . $e->getMessage() . "\n";
}

echo "DIAGNOSIS END\n";
?>
