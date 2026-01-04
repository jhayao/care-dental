<?php
// Test script to verify past booking prevention
require_once 'db_connect.php';
date_default_timezone_set("Asia/Manila");

$today = date('Y-m-d');
$now = time();

echo "Testing Date: $today\n";
echo "Current Time: " . date("H:i:s", $now) . "\n";

// Emulate get_available_times.php logic fetch
$url = "http://localhost/care-dental/care-dental/get_available_times.php?date=$today";

// Use curl to fetch content
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$slots = json_decode($response, true);

if (!is_array($slots)) {
    echo "Error fetching slots or no slots available.\n";
    echo "Response: $response\n";
    exit;
}

$failed = false;
foreach ($slots as $slot) {
    if (isset($slot['value'])) {
        $slotTime = strtotime("$today " . $slot['value']);
        if ($slotTime < $now) {
            echo "FAIL: Found past slot " . $slot['value'] . "\n";
            $failed = true;
        } else {
            // echo "PASS: Slot " . $slot['value'] . " is valid.\n";
        }
    }
}

if (!$failed) {
    echo "SUCCESS: No past slots found.\n";
}

?>
