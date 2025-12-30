<?php
require 'db_connect.php';
$res = $conn->query('SHOW COLUMNS FROM booking_items');
while($row = $res->fetch_assoc()) { echo $row['Field'] . ' '; }
?>
