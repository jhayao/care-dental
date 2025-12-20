<?php
require_once '../db_connect.php';


$stmt = $conn->prepare("SELECT * FROM bookings WHERE status='confirmed'");
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while($row = $result->fetch_assoc()){
    $start = $row['appointment_date'] . 'T' . $row['appointment_time'];
    $end_time = date('H:i:s', strtotime($row['appointment_time'] . ' +1 hour'));
    $end = $row['appointment_date'] . 'T' . $end_time;

    
    $events[] = [
        'start' => $start,
        'end' => $end
    ];
}

echo json_encode($events);
?>
