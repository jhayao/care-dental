<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Success or error message
$successMessage = '';
if (isset($_GET['success'])) {
    $successMessage = htmlspecialchars($_GET['success']);
}

// Fetch bookings
$stmt = $conn->prepare("
    SELECT * FROM bookings
    WHERE user_id = ?
    ORDER BY booking_date DESC, time_slot DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper functions
function get_booking_items($conn, $booking_id) {
    $stmt = $conn->prepare("SELECT * FROM booking_items WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $items;
}

function get_item_details($conn, $type, $id) {
    if ($type === 'package') {
        $stmt = $conn->prepare("SELECT id, package_name AS name, description, price, duration_minutes FROM packages WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, service_name AS name, description, price, duration_minutes FROM services WHERE id = ?");
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $item;
}

// Format time to 12-hour
function formatTime12($time) {
    if (!$time) return '';
    return date("h:i A", strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Appointments</title>
<link href="./assets/css/main.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body class="bg-gray-50 font-poppins min-h-screen flex flex-col">

<?php include 'header.php'; ?>

<div class="max-w-5xl mx-auto p-6 flex-1">
    <h1 class="text-3xl font-bold text-blue-700 mb-8 text-center">My Appointments</h1>

    <!-- Success message -->
    <?php if (!empty($successMessage)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-center">
            <?= $successMessage ?>
        </div>
    <?php endif; ?>

    <?php if (count($bookings) == 0): ?>
        <p class="text-center text-gray-600">You have no bookings yet.</p>
    <?php endif; ?>

    <?php foreach ($bookings as $booking): ?>
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-blue-600">
                    Booking #<?= $booking['id'] ?>
                </h2>

                <span class="px-3 py-1 rounded text-sm 
                <?php
                    if ($booking['status'] == 'confirmed') echo 'bg-green-200 text-green-800';
                    elseif ($booking['status'] == 'pending') echo 'bg-yellow-200 text-yellow-800';
                    elseif ($booking['status'] == 'rescheduled') echo 'bg-blue-200 text-blue-800';
                    elseif ($booking['status'] == 'refunded') echo 'bg-purple-200 text-purple-800'; // Purple for Refunded
                    else echo 'bg-red-200 text-red-800'; // Red for Cancelled
                ?>">
                <?= htmlspecialchars($booking['status']) ?>
                </span>
            </div>
<?php
$total_minutes = 0;
$items = get_booking_items($conn, $booking['id']);

// Sum up the duration of all items in the booking
foreach ($items as $item) {
    $details = get_item_details($conn, $item['item_type'], $item['item_id']);
    $total_minutes += (int)($details['duration_minutes'] ?? 0);
}

// Fallback: if total_minutes is 0, set a default duration (e.g., 30 minutes)
if ($total_minutes === 0) $total_minutes = 30;

// Calculate start and end time
$start_time = strtotime($booking['appointment_time']);
$end_time = $start_time + ($total_minutes * 60);
?>


<p class="text-gray-700 mt-2">
    <strong>Date:</strong> <?= date("M d, Y", strtotime($booking['appointment_date'])) ?>
    &nbsp;&nbsp; | &nbsp;&nbsp;
    <strong>Time:</strong> <?= formatTime12($booking['appointment_time']) ?> - <?= formatTime12(date('H:i:s', $end_time)) ?>
    <?php if (strtolower($booking['status']) === 'rescheduled'): ?>
        <span class="text-blue-600 font-semibold">(Rescheduled)</span>
    <?php endif; ?>
</p>



            <hr class="my-4">

            <h3 class="text-xl font-semibold text-gray-800 mb-3">Included Services</h3>
            <?php
            foreach ($items as $item):
                $details = get_item_details($conn, $item['item_type'], $item['item_id']);
            ?>
                <div class="bg-gray-50 p-3 rounded-lg mb-3 shadow-sm">
                    <p class="text-lg font-semibold text-blue-600"><?= $details['name'] ?></p>
                    <p class="text-gray-600 text-sm"><?= $details['description'] ?></p>
                    <p class="text-green-600 font-semibold mt-1">₱<?= number_format($details['price'], 2) ?></p>
                    <p class="text-gray-500 text-xs uppercase"><?= $item['item_type'] ?></p>
                </div>
            <?php endforeach; ?>
            
            <div class="mt-4 border-t pt-2">
                <!-- Subtotal calculation -->
                <?php
                $subtotal = 0;
                foreach ($items as $item) {
                     $details = get_item_details($conn, $item['item_type'], $item['item_id']);
                     $subtotal += $details['price'] ?? 0;
                }
                ?>
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>₱<?= number_format($subtotal, 2) ?></span>
                </div>
                
                <?php if (!empty($booking['discount']) && $booking['discount'] > 0): ?>
                <div class="flex justify-between text-sm text-yellow-600">
                    <span>Discount</span>
                    <span>-₱<?= number_format($booking['discount'], 2) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($booking['booking_fee']) && $booking['booking_fee'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span>Booking Fee</span>
                    <span>₱<?= number_format($booking['booking_fee'], 2) ?></span>
                </div>
                <?php endif; ?>

                <div class="flex justify-between font-bold text-lg mt-1 border-t pt-1">
                    <span>Total Amount</span>
                    <span>₱<?= number_format($booking['total_amount'], 2) ?></span>
                </div>
            </div>

            <div class="flex gap-3 mt-4">
                <?php 
                $statusLower = strtolower($booking['status']); 
                if ($statusLower !== 'cancelled' && $statusLower !== 'refunded'): ?>
                    <button onclick="cancelBooking(<?= $booking['id'] ?>)" 
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        Cancel
                    </button>
                    <button onclick="openReschedule(<?= $booking['id'] ?>, '<?= $booking['booking_date'] ?>', '<?= $booking['time_slot'] ?>')" 
                        class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                        Reschedule
                    </button>
                <?php else: ?>
                    <p class="<?= $statusLower === 'refunded' ? 'text-purple-600' : 'text-red-600' ?> italic">
                        <?= $statusLower === 'refunded' ? 'Refunded' : 'Cancelled' ?> at: 
                        <?= isset($booking['cancelled_at']) ? date("M d, Y h:i A", strtotime($booking['cancelled_at'])) : 'N/A' ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Reschedule Modal -->
<div id="resModal" class="fixed inset-0 hidden flex items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white p-6 rounded shadow-lg w-80 relative">
        <h2 class="text-xl font-bold mb-4">Reschedule Booking</h2>

        <label>New Date:</label>
        <input type="date" id="modal_date" class="w-full p-2 border rounded mb-3">

        <label>New Time:</label>
        <input type="time" id="modal_time" class="w-full p-2 border rounded mb-3" min="13:00" max="16:00">

        <div class="flex justify-end gap-2 mt-3">
            <button onclick="closeReschedule()" class="px-4 py-2 bg-gray-400 text-white rounded">Cancel</button>
            <button onclick="saveReschedule()" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div id="cancelModal" class="fixed inset-0 hidden flex items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white p-6 rounded shadow-lg w-96 relative text-center">
        <h2 class="text-xl font-bold mb-2 text-red-600">Cancel Booking</h2>
        <p class="text-gray-600 mb-6">Are you sure you want to cancel this booking? <br>
           <span class="text-sm font-semibold text-gray-800">Note: Refunds are subject to cancellation policy.</span>
        </p>

        <div class="flex justify-center gap-4">
            <button onclick="closeCancelModal()" class="px-5 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 font-medium">No, Keep</button>
            <button onclick="confirmCancelBooking()" class="px-5 py-2 bg-red-600 text-white rounded hover:bg-red-700 font-medium">Yes, Cancel</button>
        </div>
    </div>
</div>

<script>
let currentBookingId = null;

let cancelBookingId = null;

function cancelBooking(bookingId) {
    cancelBookingId = bookingId;
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    cancelBookingId = null;
    document.getElementById('cancelModal').classList.add('hidden');
}

function confirmCancelBooking() {
    if (!cancelBookingId) return;
    
    const idToCancel = cancelBookingId; // Store valid ID locally

    // Hide modal (this clears the global cancelBookingId)
    closeCancelModal();

    // Show loading
    document.getElementById('loadingOverlay').classList.remove('hidden');

    fetch('booking_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cancel&booking_id=${idToCancel}`
    })
    .then(res => res.json())
    .then(data => {
        // Hide loading
        document.getElementById('loadingOverlay').classList.add('hidden');
        
        if (data.status === 'success') {
            window.location.href = `appointments.php?success=${encodeURIComponent(data.message)}`;
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.log(error)
        document.getElementById('loadingOverlay').classList.add('hidden');
        alert("An error occurred. Please try again.");
    });
}

function openReschedule(id, date, time) {
    currentBookingId = id;
    document.getElementById('modal_date').value = date;
    
    // Prevent past dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('modal_date').setAttribute('min', today);

    const timeInput = document.getElementById('modal_time');
    timeInput.value = time;
    // Removed hardcoded min/max 13:00-16:00
    // timeInput.min = "09:00"; 
    // timeInput.max = "17:00";

    document.getElementById('resModal').classList.remove('hidden');
}

function closeReschedule() {
    document.getElementById('resModal').classList.add('hidden');
}

// Removed "Please select time between 1:00 PM and 4:00 PM only" validation
/*
document.getElementById('modal_time').addEventListener('change', function () {
    if (this.value < this.min || this.value > this.max) {
        alert("Please select time between 1:00 PM and 4:00 PM only.");
        this.value = '';
    }
});
*/

function saveReschedule() {
    const newDate = document.getElementById('modal_date').value;
    const newTime = document.getElementById('modal_time').value;

    // Show loading
    document.getElementById('loadingOverlay').classList.remove('hidden');

    fetch('booking_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=reschedule&booking_id=${currentBookingId}&new_date=${newDate}&new_time=${newTime}`
    })
    .then(res => res.json())
    .then(data => {
        // Hide loading
        document.getElementById('loadingOverlay').classList.add('hidden');

        if (data.status === 'success') {
            window.location.href = `appointments.php?success=${encodeURIComponent(data.message)}`;
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        document.getElementById('loadingOverlay').classList.add('hidden');
        alert("An error occurred. Please try again.");
    });
}
</script>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-xl flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
        <p class="text-gray-700 font-semibold text-lg">Processing request...</p>
        <p class="text-gray-500 text-sm mt-1">Please wait, do not close this window.</p>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
