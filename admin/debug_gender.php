<?php
require_once '../db_connect.php';
$result = $conn->query("SELECT id, first_name, gender FROM users WHERE user_type='patient'");
echo "<pre>";
while($row = $result->fetch_assoc()) {
    var_dump($row);
}
echo "</pre>";
?>
