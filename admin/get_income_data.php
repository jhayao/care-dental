<?php
session_start();
require_once '../db_connect.php';

// Disable error reporting for cleaner JSON
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Return empty JSON if query fails logic or connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$whereClause = "WHERE status = 'approved'"; // Only count approved payments
$params = [];
$types = "";

if ($startDate && $endDate) {
    $whereClause .= " AND DATE(payment_date) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types = "ss";
} elseif ($startDate) {
    $whereClause .= " AND DATE(payment_date) >= ?";
    $params[] = $startDate;
    $types = "s";
}

$query = "
    SELECT DATE(payment_date) as p_date, SUM(total_price) as total_income 
    FROM payments 
    $whereClause
    GROUP BY DATE(payment_date) 
    ORDER BY p_date ASC
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    // Return error as JSON so frontend doesn't crash with SyntaxError
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

if($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = date('M d', strtotime($row['p_date']));
    $values[] = (float)$row['total_income'];
}

echo json_encode(['labels' => $labels, 'values' => $values]);
?>
