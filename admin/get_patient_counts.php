<?php
require_once '../db_connect.php';

// Check for date filter
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Function to build query
function getQuery($conn, $baseQuery, $startDate, $endDate) {
    if ($startDate && $endDate) {
        $baseQuery .= " WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
    } elseif ($startDate) {
         $baseQuery .= " WHERE DATE(created_at) >= '$startDate'";
    } elseif ($endDate) {
         $baseQuery .= " WHERE DATE(created_at) <= '$endDate'";
    }
    return $conn->query($baseQuery);
}

// Count total patients
$sqlTotal = "SELECT COUNT(*) as total FROM users";
if ($startDate && $endDate) {
    $sqlTotal .= " WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
} elseif ($startDate) {
    $sqlTotal .= " WHERE DATE(created_at) >= '$startDate'";
} elseif ($endDate) {
    $sqlTotal .= " WHERE DATE(created_at) <= '$endDate'";
}
$totalPatients = $conn->query($sqlTotal)->fetch_assoc()['total'];

// Count patients by category
$categories = [];
$sqlCat = "SELECT category, COUNT(*) as total FROM users";
if ($startDate && $endDate) {
    $sqlCat .= " WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
} elseif ($startDate) {
    $sqlCat .= " WHERE DATE(created_at) >= '$startDate'";
} elseif ($endDate) {
    $sqlCat .= " WHERE DATE(created_at) <= '$endDate'";
}
$sqlCat .= " GROUP BY category";

$result = $conn->query($sqlCat);
while($row = $result->fetch_assoc()) {
    $categories[$row['category']] = (int)$row['total'];
}

// Count patients by gender
$genders = [];
$sqlGen = "SELECT gender, COUNT(*) as total FROM users";
if ($startDate && $endDate) {
    $sqlGen .= " WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
} elseif ($startDate) {
    $sqlGen .= " WHERE DATE(created_at) >= '$startDate'";
} elseif ($endDate) {
    $sqlGen .= " WHERE DATE(created_at) <= '$endDate'";
}
$sqlGen .= " GROUP BY gender";

$result = $conn->query($sqlGen);
while($row = $result->fetch_assoc()) {
    $genders[$row['gender']] = (int)$row['total'];
}

echo json_encode([
    'totalPatients' => (int)$totalPatients,
    'categories' => $categories,
    'genders' => $genders
]);
