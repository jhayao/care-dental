<?php
require_once '../db_connect.php';

// Check for date filter
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Helper to build WHERE clause
function getWhereClause($startDate, $endDate) {
    $clauses = ["user_type = 'patient'"]; // Default filter
    if ($startDate && $endDate) {
        $clauses[] = "DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
    } elseif ($startDate) {
        $clauses[] = "DATE(created_at) >= '$startDate'";
    } elseif ($endDate) {
        $clauses[] = "DATE(created_at) <= '$endDate'";
    }
    return " WHERE " . implode(" AND ", $clauses);
}

$where = getWhereClause($startDate, $endDate);

// Count total patients
$sqlTotal = "SELECT COUNT(*) as total FROM users" . $where;
$totalPatients = $conn->query($sqlTotal)->fetch_assoc()['total'];

// Count patients by category
$categories = [];
$sqlCat = "SELECT category, COUNT(*) as total FROM users" . $where . " GROUP BY category";
$result = $conn->query($sqlCat);
while($row = $result->fetch_assoc()) {
    $categories[$row['category']] = (int)$row['total'];
}

// Count patients by gender
$genders = [];
$sqlGen = "SELECT gender, COUNT(*) as total FROM users" . $where . " GROUP BY gender";
$result = $conn->query($sqlGen);
while($row = $result->fetch_assoc()) {
    $genders[$row['gender']] = (int)$row['total'];
}

echo json_encode([
    'totalPatients' => (int)$totalPatients,
    'categories' => $categories,
    'genders' => $genders
]);
