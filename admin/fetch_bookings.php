<?php
require_once '../db_connect.php';

// Fetch confirmed bookings
$stmt = $conn->prepare("SELECT * FROM bookings WHERE status='confirmed'");
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while($row = $result->fetch_assoc()){
    $start = $row['booking_date'] . 'T' . $row['time_slot'];
    $end_time = date('H:i:s', strtotime($row['time_slot'] . ' +1 hour'));
    $end = $row['booking_date'] . 'T' . $end_time;

    // Remove the title; only send start and end times
    $events[] = [
        'start' => $start,
        'end' => $end
    ];
}

echo json_encode($events);
?>
