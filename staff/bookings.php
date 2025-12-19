<?php
session_start();
require_once '../db_connect.php';

// Ensure staff is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch all bookings with user info
$stmt = $conn->prepare("
    SELECT 
        bookings.id,
        bookings.booking_date,
        bookings.time_slot,
        bookings.booking_fee,
        bookings.total_amount,
        bookings.status,
        users.first_name,
        users.last_name
    FROM bookings
    INNER JOIN users ON users.id = bookings.user_id
    ORDER BY bookings.booking_date DESC, bookings.time_slot DESC
");

$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Bookings</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">


<script>
tailwind.config = {
    theme: {
        extend: { fontFamily: { poppins: ['Poppins', 'sans-serif'] } }
    }
};

$(document).ready(function() {
    $('#appointmentsTable').DataTable({
        pageLength: 10,          
        lengthMenu: [5, 10, 25, 50, 100], 
        paging: true,            
        info: true,              
        searching: true,         
        ordering: true,         
        autoWidth: false,
        responsive: true
    });
});


</script>

</head>

<body class="bg-gray-50 font-poppins min-h-screen flex">

<aside class="w-64 bg-white shadow-lg sticky top-0 h-screen">
    <?php include 'sidebar.php'; ?>
</aside>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-6xl mx-auto">

        <h1 class="text-3xl font-bold text-blue-700 mb-6 text-center">
            Bookings
        </h1>

        <div class="bg-white p-6 rounded-xl shadow-lg">

            <table id="appointmentsTable" class="display stripe hover w-full text-sm">
                <thead>
                <tr class="bg-gray-100">
                    <th>Firstname</th>
                    <th>Lastname</th>
                    <th>Booking Date</th>
                    <th>Time Slot</th>
                    <th>Booking Fee</th>
                    <th>Total Amount</th> 
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['first_name']); ?></td>
                            <td><?= htmlspecialchars($a['last_name']); ?></td>
                            <td><?= date('M d, Y', strtotime($a['booking_date'])); ?></td>
                            <td><?= date('h:i A', strtotime($a['time_slot'])); ?></td>
                            <td>₱<?= number_format($a['booking_fee'], 2); ?></td>
                            <td>₱<?= number_format($a['total_amount'], 2); ?></td> 
                            <td>
                       <span class="px-3 py-1 rounded-full text-sm font-semibold
                        <?= $a['status'] === 'pending' ? 'text-warning' : 
                            ($a['status'] === 'confirmed' ? 'text-success' : 
                            ($a['status'] === 'cancelled' ? 'text-danger' : 
                            ($a['status'] === 'rescheduled' ? 'text-primary' : 'text-secondary'))); ?>">
                        <?= ucfirst($a['status']); ?>
                    </span>
                            </td>
                               <td class="d-flex flex-column gap-2">
                            <button 
                                onclick="openModal(<?= $a['id']; ?>)" 
                                class="btn btn-primary btn-sm">
                                View
                            </button>

                            <button 
                                onclick="openRescheduleModal('<?= $a['id']; ?>', '<?= $a['booking_date']; ?>', '<?= $a['time_slot']; ?>')" 
                                class="btn btn-warning btn-sm text-white">
                                Reschedule
                            </button>

                            <?php if ($a['status'] === 'cancelled'): ?>
                                <button 
                                    onclick="viewCancellation(<?= $a['id']; ?>)" 
                                    class="btn btn-danger btn-sm">
                                    View Cancellation
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>
</main>

<script>
function viewCancellation(bookingId) {
    fetch(`get_cancellation.php?id=${bookingId}`)
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }

        const cancelledAt = new Date(data.cancelled_at);
        const now = new Date();
        let diff = Math.floor((now - cancelledAt) / 1000); 

        const days = Math.floor(diff / 86400); 
        diff %= 86400;
        const hours = Math.floor(diff / 3600);
        diff %= 3600;
        const minutes = Math.floor(diff / 60);
        const seconds = diff % 60;

        alert(
            `Booking Cancelled At: ${data.cancelled_at}\n` +
            `Time Since Cancellation: ${days}d ${hours}h ${minutes}m ${seconds}s`
            
        );
    })
    .catch(err => {
        console.error(err);
        alert("Failed to fetch cancellation info.");
    });
}
</script>

<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl w-full relative overflow-y-auto max-h-[80vh]">
        <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-2xl font-bold">&times;</button>
        <h2 class="text-2xl font-bold mb-4">Booking Details</h2>

        <div id="modalContent" class="space-y-2">
            <p><strong>Booking Date:</strong> <span id="modalDate"></span></p>
            <p><strong>Time Slot:</strong> <span id="modalTime"></span></p>
            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
            <p><strong>Booking Fee:</strong> ₱<span id="modalFee"></span></p>

            <div id="modalDiscount" class="text-yellow-600 font-semibold" style="display:none;">
                <p><strong>Category:</strong> <span id="modalCategory"></span></p>
                <p><strong>Discount:</strong> ₱<span id="modalDiscountAmount"></span></p>
            </div>

            <div class="mt-4">
                <h3 class="font-semibold text-lg mb-2">Booked Items</h3>
                <div id="modalItems" class="space-y-3 text-gray-700"></div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button id="confirmBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold">Confirm</button>
                <button id="completeBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold">Completed</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBookingId = null;
function openModal(bookingId) {
    currentBookingId = bookingId;

    fetch(`get_booking.php?id=${bookingId}`)
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }

        const b = data.booking;

        document.getElementById('modalDate').textContent = b.booking_date;
        document.getElementById('modalTime').textContent = b.time_slot;
        document.getElementById('modalStatus').textContent = b.status;
        document.getElementById('modalFee').textContent = parseFloat(b.booking_fee).toFixed(2);

        // Items
        const itemsDiv = document.getElementById('modalItems');
        itemsDiv.innerHTML = '';

       if (data.items.length > 0) {
    data.items.forEach(item => {
        const container = document.createElement('div');
        container.classList.add('border', 'p-3', 'rounded', 'bg-gray-50');

        let html = `<p><strong>Name:</strong> ${item.name}</p>`;
        if (item.description) html += `<p><strong>Description:</strong> ${item.description}</p>`;
        if (item.duration) html += `<p><strong>Duration:</strong> ${item.duration} mins</p>`;
        html += `<p><strong>Type:</strong> ${item.type}</p>`;
        html += `<p><strong>Price:</strong> ₱${parseFloat(item.price).toFixed(2)}</p>`;

        // Add inclusions for packages
        if (item.type === 'package' && item.inclusions && item.inclusions.length > 0) {
            html += `<p><strong>Inclusions:</strong></p>`;
            html += `<ul class="list-disc ml-5 mt-1">`;
            item.inclusions.forEach(inclusion => {
                html += `<li>${inclusion}</li>`;
            });
            html += `</ul>`;
        }

        container.innerHTML = html;
        itemsDiv.appendChild(container);
    });
} else {
    itemsDiv.textContent = 'No items booked.';
}

        // Discount
        const discountDiv = document.getElementById('modalDiscount');
        if (b.discount > 0) {
            discountDiv.style.display = 'block';
            document.getElementById('modalCategory').textContent = b.category;
            document.getElementById('modalDiscountAmount').textContent = parseFloat(b.discount).toFixed(2);
        } else {
            discountDiv.style.display = 'none';
        }

        document.getElementById('viewModal').classList.remove('hidden');
    })
    .catch(err => console.error(err));
}

// Close modal
function closeModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

// Confirm booking
document.getElementById('confirmBtn').addEventListener('click', () => {
    if (!confirm("Mark this booking as confirmed?")) return;

    fetch('update_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentBookingId, status: 'confirmed' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Booking confirmed!");
            closeModal();
            location.reload();
        } else {
            alert(data.error || "Failed to confirm booking.");
        }
    });
});

// Complete booking
document.getElementById('completeBtn').addEventListener('click', () => {
    if (!confirm("Mark this booking as completed?")) return;

    fetch('update_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentBookingId, status: 'completed' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Booking marked as completed!");
            closeModal();
            location.reload();
        } else {
            alert(data.error || "Failed to update status.");
        }
    });
});
</script>


<!-- Reschedule Modal -->
<div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full relative">
        <button onclick="closeRescheduleModal()" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-2xl font-bold">&times;</button>
        <h2 class="text-2xl font-bold mb-4">Reschedule Booking</h2>

        <form id="rescheduleForm" class="space-y-4">
            <input type="hidden" id="rescheduleBookingId">

            <div>
                <label for="newDate" class="block font-semibold mb-1">New Date</label>
                <input type="date" id="newDate" class="w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label for="newTime" class="block font-semibold mb-1">New Time</label>
                <input type="time" id="newTime" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRescheduleModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">Reschedule</button>
            </div>
        </form>
    </div>
</div>
<script>
let currentRescheduleId = null;

// Open Reschedule Modal
function openRescheduleModal(bookingId, currentDate = '', currentTime = '') {
    currentRescheduleId = bookingId;
    document.getElementById('rescheduleBookingId').value = bookingId;

    // Optional: pre-fill current date & time
    if (currentDate) document.getElementById('newDate').value = currentDate;
    if (currentTime) document.getElementById('newTime').value = currentTime;

    document.getElementById('rescheduleModal').classList.remove('hidden');
}

// Close Reschedule Modal
function closeRescheduleModal() {
    document.getElementById('rescheduleModal').classList.add('hidden');
    document.getElementById('rescheduleForm').reset();
    currentRescheduleId = null;
}

// Handle Reschedule Form submit
document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (!currentRescheduleId) {
        alert("Booking ID not set.");
        return;
    }

    const newDate = document.getElementById('newDate').value;
    const newTime = document.getElementById('newTime').value;

    if (!newDate || !newTime) {
        alert("Please select both date and time.");
        return;
    }

    fetch('update_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: currentRescheduleId,
            booking_date: newDate,
            time_slot: newTime
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Booking rescheduled successfully!");
            closeRescheduleModal();
            location.reload();
        } else {
            alert(data.error || "Failed to reschedule booking.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error connecting to server.");
    });
});
</script>

</body>
</html>
