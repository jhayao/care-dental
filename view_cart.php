<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ================= USER CATEGORY & CHECK DISCOUNT ================= */
$stmt = $conn->prepare("SELECT category, discount FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$category = $user['category'] ?? 'None';
$user_discount_percent = $user['discount'] ?? 0;
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

/* ================= TOTAL COMPUTATION (NO BOOKING FEE) ================= */
$subtotal = 0;
$total_minutes = 0;

foreach ($_SESSION['cart'] as $item) {
    $details = get_item_details($conn, $item['type'], $item['id']);
    if ($details) {
        $subtotal += $details['price'] ?? 0;
        $total_minutes += (int)($details['duration_minutes'] ?? 0);
    }
}

// Discount Logic: Priority to specific user discount % -> Then Senior/PWD standard 20%
$discount = 0;
if ($user_discount_percent > 0) {
    $discount = $subtotal * ($user_discount_percent / 100);
} elseif ($category === 'Senior' || $category === 'PWD') {
    $discount = $subtotal * 0.20;
}
$total = $subtotal - $discount;

// Ensure minimum duration of 30 mins to prevent logic errors
if ($total_minutes <= 0) $total_minutes = 30;

/* Convert total minutes to hours + minutes */
$hours = floor($total_minutes / 60);
$minutes = $total_minutes % 60;

/* ================= FETCH AVAILABLE DATES FOR CALENDAR HIGHLIGHTING ================= */
// We only need the dates to color the calendar green. The times are fetched via AJAX.
$available_times = [];
$stmt = $conn->prepare("SELECT DISTINCT available_date FROM dentist_calendar WHERE available_date >= CURDATE()");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // We set value to true or empty array just to indicate existence
    $available_times[$row['available_date']] = []; 
}
$stmt->close();


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart - B-Dental Care</title>
<link href="./assets/css/main.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

</head>
<body class="bg-gray-100 font-poppins min-h-screen flex flex-col">
<?php include 'header.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-3 gap-8 flex-1">

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
                    <?php if (!empty($details['duration_minutes'])): ?>
                        <p class="text-sm mt-1">Duration: <span class="font-semibold"><?= (int)$details['duration_minutes'] ?> minutes</span></p>
                    <?php endif; ?>
                </div>
                <a href="remove_cart.php?index=<?= $index ?>" class="text-red-600 font-semibold">Remove</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-gray-500">Your cart is empty.</p>
    <?php endif; ?>
</div>


<?php if (isset($_SESSION['booking_error'])): ?>
<div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full text-center">
        <h3 class="text-xl font-bold text-red-600 mb-4"></h3>
        <p class="text-gray-700 mb-6">
            <?= htmlspecialchars($_SESSION['booking_error']) ?>
        </p>
        <button onclick="closeModal()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded">
            OK
        </button>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('errorModal').remove();
}
</script>

<?php unset($_SESSION['booking_error']); ?>
<?php endif; ?>

<!-- ================= BOOKING ================= -->
<form method="POST" action="confirm.php" class="bg-white shadow rounded-lg p-6 flex flex-col gap-5">
<h2 class="text-2xl font-bold border-b pb-2 text-center">Appointment Summary</h2>

<div>
    <div class="flex justify-between"><span>Subtotal</span><span>₱<?= number_format($subtotal,2) ?></span></div>
    <?php if ($discount > 0): ?>
        <div class="flex justify-between text-yellow-600">
            <span>Discount (<?= $category ?>)</span>
            <span>-₱<?= number_format($discount,2) ?></span>
        </div>
    <?php endif; ?>
    <div class="flex justify-between font-bold text-lg border-t pt-2">
        <span>Total</span>
        <span>₱<?= number_format($total,2) ?></span>
    </div>
    <div class="flex justify-between mt-2">
        <span>Duration Time:</span>
        <span class="font-semibold text-red-600">
            <?= $hours>0 ? "$hours hour".($hours>1?"s":"") : "" ?>
            <?= $minutes>0 ? "$minutes minutes" : "" ?>
        </span>
    </div>
</div>

<div class="space-y-4">
    <label class="font-semibold text-lg mb-2 block">Select Appointment Date</label>
    <div class="border rounded-lg p-3 mb-4 bg-white relative z-0">
        <div id="bookingCalendar"></div>
    </div>
    <input type="hidden" name="appointment_date" id="appointment_date" required>

    <div id="timeSelectionSection" class="hidden transition-all duration-300">
        <label class="font-semibold block mb-1">Select Available Time</label>
        <?php if(empty($available_times)): ?>
             <p class="text-sm text-red-500 mb-2">No available dates found. Please check back later.</p>
        <?php endif; ?>

    <select name="appointment_time" id="appointment_time" class="w-full border rounded px-3 py-2" required>
        <option value="">-- Select Date First --</option>
    </select>
    </div>

    <?php foreach ($_SESSION['cart'] as $item): ?>
        <input type="hidden" name="cart_items[]" value="<?= $item['type'] . ':' . $item['id'] ?>">
    <?php endforeach; ?>

    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded w-full">
        Confirm Booking
    </button>
</div>
</form>
</main>

<?php include 'footer.php'; ?>

<!-- FullCalendar -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<style>
    .fc-toolbar-title { font-size: 1.25rem !important; }
    .fc-button { font-size: 0.75rem !important; }
</style>
<script>
const availableTimes = <?= json_encode($available_times); ?>; // Populated with dates only

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('bookingCalendar');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev',
            center: 'title',
            right: 'next'
        },
        height: 'auto',
        validRange: { start: '<?= date('Y-m-d'); ?>' },
        dayCellDidMount: function(arg) {
            const date = arg.date;
            const dateStr = date.getFullYear() + "-" + 
                            String(date.getMonth() + 1).padStart(2, '0') + "-" + 
                            String(date.getDate()).padStart(2, '0');
            const cell = arg.el;
            
            // Check if date is in availableTimes (keys are 'YYYY-MM-DD')
            // Note: availableTimes is an object/array from PHP
            // Use Object.prototype.hasOwnProperty in case it's an array with string keys
            if (availableTimes.hasOwnProperty(dateStr) || availableTimes[dateStr] !== undefined) {
                cell.style.backgroundColor = '#ecfdf5'; // Green-50
                cell.style.cursor = 'pointer';
            } else {
                cell.style.backgroundColor = '#f3f4f6'; // Gray-100
                cell.style.pointerEvents = 'none';
                cell.style.color = '#9ca3af';
            }
        },
        dateClick: function(info) {
             // Check availability before doing anything
             if (!availableTimes.hasOwnProperty(info.dateStr) && availableTimes[info.dateStr] === undefined) {
                 return;
             }

            // Reset styles
            document.querySelectorAll('.fc-daygrid-day').forEach(el => {
                 const dStr = el.dataset.date;
                 // Re-apply green if available
                 if (availableTimes.hasOwnProperty(dStr) || availableTimes[dStr] !== undefined) {
                     el.style.backgroundColor = '#ecfdf5'; // Green-50
                     el.style.color = '';
                 } else {
                     el.style.backgroundColor = '#f3f4f6'; // Gray-100
                     el.style.color = '#9ca3af';
                 }
            });
            
            // Highlight selected
            info.dayEl.style.backgroundColor = '#3b82f6'; 
            info.dayEl.style.color = 'white';

            const dateInput = document.getElementById('appointment_date');
            dateInput.value = info.dateStr;

            updateTimes(info.dateStr);

            document.getElementById('timeSelectionSection').classList.remove('hidden');
        }
    });
    calendar.render();
});

function updateTimes(date) {
    const timeSelect = document.getElementById('appointment_time');
    // Show loading or clear
    timeSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`get_available_times.php?date=${date}`)
        .then(response => response.json())
        .then(data => {
            timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
            if (data.length === 0) {
                 timeSelect.innerHTML += '<option value="" disabled>No available slots</option>';
            } else {
                data.forEach(t => {
                    timeSelect.innerHTML += `<option value="${t.value}">${t.text}</option>`;
                });
            }
        })
        .catch(err => {
            console.error(err);
            timeSelect.innerHTML = '<option value="">Error fetching times</option>';
        });
}
</script>
</body>
</html>
