<?php
require_once '../db_connect.php';

// Count total patients
$totalPatients = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

// Count patients by category
$categories = [];
$result = $conn->query("SELECT category, COUNT(*) as total FROM users GROUP BY category");
while($row = $result->fetch_assoc()) {
    $categories[$row['category']] = (int)$row['total'];
}

// Count patients by gender
$genders = [];
$result = $conn->query("SELECT gender, COUNT(*) as total FROM users GROUP BY gender");
while($row = $result->fetch_assoc()) {
    $genders[$row['gender']] = (int)$row['total'];
}

echo json_encode([
    'totalPatients' => (int)$totalPatients,
    'categories' => $categories,
    'genders' => $genders
]);
