<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if(isset($_POST['id'])){
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid package ID']);
}

$conn->close();
?>
