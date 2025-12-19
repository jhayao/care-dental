<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

$available_date = $_POST['available_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';

if($available_date && $start_time && $end_time){
    $stmt = $conn->prepare("INSERT INTO dentist_calendar (available_date, start_time, end_time) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $available_date, $start_time, $end_time);

    if($stmt->execute()){
        echo json_encode(['status'=>'success','message'=>'Slot added successfully!']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Failed to add slot.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status'=>'error','message'=>'Invalid input.']);
}
