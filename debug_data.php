<?php
require_once 'db_connect.php';

echo "=== Dentist Availability (Next 7 Days) ===\n";
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$stmt = $conn->prepare("SELECT * FROM dentist_calendar WHERE available_date BETWEEN ? AND ? ORDER BY available_date");
$stmt->bind_param("ss", $today, $nextWeek);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    echo "Date: {$row['available_date']} | Time: {$row['start_time']} - {$row['end_time']}\n";
}

echo "\n=== Services ===\n";
$res = $conn->query("SELECT service_name, duration_minutes FROM services");
while ($row = $res->fetch_assoc()) {
    echo "{$row['service_name']}: {$row['duration_minutes']} mins\n";
}

echo "\n=== Packages ===\n";
$res = $conn->query("SELECT package_name, duration_minutes FROM packages");
while ($row = $res->fetch_assoc()) {
    echo "{$row['package_name']}: {$row['duration_minutes']} mins\n";
}
?>
