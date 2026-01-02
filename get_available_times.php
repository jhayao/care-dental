<?php
session_start();
require_once 'db_connect.php';
date_default_timezone_set("Asia/Manila");

header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Date is required']);
    exit;
}

$date = $_GET['date'];

/* ================= CALCULATE DURATION FROM CART ================= */
$total_minutes = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $table = ($item['type'] === 'package') ? 'packages' : 'services';
        $stmt = $conn->prepare("SELECT duration_minutes FROM {$table} WHERE id = ?");
        $stmt->bind_param("i", $item['id']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) {
            $total_minutes += (int)$res['duration_minutes'];
        }
        $stmt->close();
    }
}

// Fallback duration
if ($total_minutes <= 0) $total_minutes = 60;

/* ================= FETCH DENTIST AVAILABILITY ================= */
$available_slots = [];
$stmt = $conn->prepare("SELECT start_time, end_time FROM dentist_calendar WHERE available_date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $available_slots[] = [
        'start' => $row['start_time'], // e.g., '09:00:00'
        'end'   => $row['end_time']
    ];
}
$stmt->close();

if (empty($available_slots)) {
    echo json_encode([]); // No availability defined for this date
    exit;
}

/* ================= FETCH EXISTING BOOKINGS ================= */
$existing_bookings = [];
$stmt = $conn->prepare("
    SELECT appointment_time, duration_minutes 
    FROM bookings 
    WHERE appointment_date = ? 
    AND status NOT IN ('cancelled', 'refunded', 'rejected', 'failed')
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $start = strtotime("$date " . $row['appointment_time']);
    $duration = ($row['duration_minutes'] > 0) ? $row['duration_minutes'] : 60;
    $end = $start + ($duration * 60);
    
    $existing_bookings[] = [
        'start' => $start,
        'end'   => $end
    ];
}
$stmt->close();

/* ================= GENERATE TIMES ================= */
$final_times = [];

foreach ($available_slots as $slot) {
    $slot_start = strtotime("$date " . $slot['start']);
    $slot_end   = strtotime("$date " . $slot['end']);
    
    $current = $slot_start;

    // We step in 15-minute increments
    while (($current + $total_minutes * 60) <= $slot_end) {
        $current_end = $current + ($total_minutes * 60);
        
        $overlap = false;
        foreach ($existing_bookings as $b) {
            // Standard overlap check: (StartA < EndB) and (EndA > StartB)
            if ($current < $b['end'] && $current_end > $b['start']) {
                $overlap = true;
                break;
            }
        }

        if (!$overlap) {
            $final_times[] = [
                'value' => date('H:i', $current),
                'text'  => date('h:i A', $current)
            ];
        }

        $current += 15 * 60; // Increment by 15 mins
    }
}

echo json_encode($final_times);
?>
