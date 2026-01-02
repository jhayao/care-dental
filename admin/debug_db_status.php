<?php
require_once '../db_connect.php';

$tables = ['packages', 'services'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $result = $conn->query("SHOW CREATE TABLE $table");
    if ($result) {
        $row = $result->fetch_assoc();
        echo $row['Create Table'] . "\n\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>
