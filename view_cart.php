<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ================= USER CATEGORY ================= */
$stmt = $conn->prepare("SELECT category FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$category = $user['category'] ?? 'None';
$stmt->close();

/* ================= CART INIT ================= */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ================= ITEM DETAILS ================= */
function get_item_details($conn, $type, $id) {
    if ($type === 'package') {
        $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $item;
}

/* ================= TOTAL COMPUTATION ================= */
$subtotal = 0;
$total_minutes = 0;
$booking_fee = 50;

foreach ($_SESSION['cart'] as $item) {
    $details = get_item_details($conn, $item['type'], $item['id']);
    if ($details) {
        $subtotal += $details['price'] ?? 0;
        $total_minutes += (int)($details['duration_minutes'] ?? 0);
    }
}

$discount = ($category === 'Senior' || $category === 'PWD') ? $subtotal * 0.20 : 0;
$total = $subtotal - $discount + $booking_fee;

/* Convert total minutes to hours + minutes */
$hours = floor($total_minutes / 60);
$minutes = $total_minutes % 60;

/* ================= FETCH DENTIST AVAILABILITY ================= */
$available_slots = [];
$stmt = $conn->prepare("SELECT available_date, start_time, end_time FROM dentist_calendar ORDER BY available_date, start_time");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $available_slots[$row['available_date']][] = [
        'start' => $row['start_time'],
        'end'   => $row['end_time']
    ];
}
$stmt->close();

/* ================= FETCH EXISTING BOOKINGS ================= */
$existing_bookings = [];
$stmt = $conn->prepare("SELECT appointment_date, appointment_time, duration_minutes FROM bookings");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $date = $row['appointment_date'];
    $start = strtotime($row['appointment_time']);
    $end = $start + ($row['duration_minutes'] * 60);
    $existing_bookings[$date][] = [
        'start' => date("H:i", $start),
        'end'   => date("H:i", $end)
    ];
}
$stmt->close();

/* ================= GENERATE AVAILABLE TIMES ================= */
$available_times = [];
foreach ($available_slots as $date => $slots) {
    foreach ($slots as $slot) {
        $slot_start = strtotime($date . ' ' . $slot['start']);
        $slot_end = strtotime($date . ' ' . $slot['end']);
        $current = $slot_start;

        while (($current + $total_minutes * 60) <= $slot_end) {
            $current_end = $current + $total_minutes * 60;

            // Check overlap with bookings
            $overlap = false;
            if (isset($existing_bookings[$date])) {
                foreach ($existing_bookings[$date] as $b) {
                    $b_start = strtotime($date . ' ' . $b['start']);
                    $b_end = strtotime($date . ' ' . $b['end']);
                    // block if any overlap
                    if ($current < $b_end && $current_end > $b_start) {
                        $overlap = true;
                        break;
                    }
                }
            }

            if (!$overlap) {
                $available_times[$date][] = [
                    'value' => date('H:i', $current),
                    'text'  => date('h:i A', $current)
                ];
            }

            $current += 15 * 60; // increment 15 minutes
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart - B-Dental Care</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<script>
tailwind.config = { theme: { extend: { fontFamily: { poppins: ['Poppins', 'sans-serif'] } } } }
</script>
</head>
<body class="bg-gray-100 font-poppins min-h-screen flex flex-col">
<?php include 'header.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-3 gap-8">

<!-- ================= CART ================= -->
<div class="md:col-span-2 bg-white shadow rounded-lg p-6">
    <h2 class="text-3xl font-bold mb-6 border-b pb-2">Your Cart</h2>
    <?php if (!empty($_SESSION['cart'])): ?>
        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
            <?php $details = get_item_details($conn, $item['type'], $item['id']); if (!$details) continue; ?>
            <div class="flex justify-between border p-4 rounded mb-3">
                <div>
                    <h3 class="font-semibold"><?= htmlspecialchars($details['package_name'] ?? $details['service_name']) ?></h3>
                    <p class="text-gray-600 text-sm"><?= ucfirst($item['type']) ?></p>
                    <p class="font-semibold mt-1">₱<?= number_format($details['price'], 2) ?></p>
                    <?php if (!empty($details['duration_minutes']) && $details['duration_minutes'] > 0): ?>
                        <p class="flex items-center gap-2 text-gray-700 mt-1 text-sm">
                            Duration: <span class="font-semibold"><?= (int)$details['duration_minutes'] ?> minutes</span>
                        </p>
                    <?php endif; ?>
                </div>
                <a href="remove_cart.php?index=<?= $index ?>" class="text-red-600 font-semibold">Remove</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-gray-500">Your cart is empty.</p>
    <?php endif; ?>
</div>

<!-- ================= BOOKING ================= -->
<form method="POST" action="confirm.php" class="bg-white shadow rounded-lg p-6 flex flex-col gap-5">
<h2 class="text-2xl font-bold border-b pb-2 text-center">Appointment Summary</h2>

<div>
    <div class="flex justify-between"><span>Subtotal</span><span>₱<?= number_format($subtotal,2) ?></span></div>
    <?php if ($discount > 0): ?>
        <div class="flex justify-between text-yellow-600"><span>Discount (<?= $category ?>)</span><span>-₱<?= number_format($discount,2) ?></span></div>
    <?php endif; ?>
    <div class="flex justify-between"><span>Booking Fee</span><span>₱<?= number_format($booking_fee,2) ?></span></div>
    <div class="flex justify-between font-bold text-lg border-t pt-2"><span>Total</span><span>₱<?= number_format($total,2) ?></span></div>
    <div class="flex justify-between mt-2"><span>Duration Time:</span>
        <span class="font-semibold text-red-600"><?= $hours>0 ? "$hours hour".($hours>1?"s":"") : "" ?> <?= $minutes>0 ? "$minutes minutes" : "" ?></span>
    </div>
</div>

<!-- ================= AVAILABILITY ================= -->
<div class="space-y-4">
    <div>
        <label class="font-semibold block mb-1">Available Appointment Dates</label>
        <select name="appointment_date" id="appointment_date" class="w-full border rounded px-3 py-2" required onchange="updateTimes()">
            <option value="">-- Select Date --</option>
            <?php foreach ($available_times as $date => $times): ?>
                <option value="<?= $date ?>"><?= date('F d, Y', strtotime($date)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="font-semibold block mb-1 mt-4">Available Time</label>
        <select name="appointment_time" id="appointment_time" class="w-full border rounded px-3 py-2" required>
            <option value="">-- Select Time --</option>
        </select>
        <p class="text-gray-700 mt-2">Estimated end time: <?= $hours>0 ? "$hours hour".($hours>1?"s":"") : "" ?> <?= $minutes>0 ? "$minutes minutes" : "" ?></p>
    </div>

    <?php foreach ($_SESSION['cart'] as $item): ?>
        <input type="hidden" name="cart_items[]" value="<?= $item['type'] . ':' . $item['id'] ?>">
    <?php endforeach; ?>

    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded mt-auto w-full">
        Confirm Booking
    </button>
</div>
</form>
</main>

<?php include 'footer.php'; ?>

<script>
const availableTimes = <?= json_encode($available_times); ?>;

function updateTimes() {
    const dateSelect = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const date = dateSelect.value;

    timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
    if (!date || !availableTimes[date]) return;

    availableTimes[date].forEach(t => {
        const option = document.createElement('option');
        option.value = t.value;
        option.textContent = t.text;
        timeSelect.appendChild(option);
    });
}

// Initialize first date if exists
document.addEventListener('DOMContentLoaded', () => {
    const firstDate = document.getElementById('appointment_date').value;
    if (firstDate) updateTimes();
});
</script>
</body>
</html>
