<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$patient_id = intval($_GET['id']);

// Fetch Patient Details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'patient'");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Patient not found.");
}

// Fetch Bookings
$bookings_query = "SELECT * FROM bookings WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC";
$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Payments
$payments_query = "
    SELECT p.*, b.appointment_date 
    FROM payments p 
    JOIN bookings b ON p.booking_id = b.id 
    WHERE b.user_id = ? 
    ORDER BY p.payment_date DESC
";
$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Record</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex">

<?php include 'admin_sidebar.php'; ?>

<main class="flex-1 p-8 overflow-y-auto h-screen">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Patient Record</h1>
            <a href="users.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to List
            </a>
        </div>

        <!-- Profile Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h2>
                    <p class="text-gray-500 text-sm mb-4">Patient ID: #<?= $patient['id'] ?></p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-2 text-sm">
                        <p><span class="font-semibold text-gray-600">Email:</span> <?= htmlspecialchars($patient['email']) ?></p>
                        <p><span class="font-semibold text-gray-600">Phone:</span> <?= htmlspecialchars($patient['contact_number'] ?? 'N/A') ?></p>
                        <p><span class="font-semibold text-gray-600">Gender:</span> <?= htmlspecialchars($patient['gender']) ?></p>
                        <p><span class="font-semibold text-gray-600">DOB:</span> <?= htmlspecialchars($patient['dob'] ?? 'N/A') ?></p>
                        <p><span class="font-semibold text-gray-600">Address:</span> <?= htmlspecialchars($patient['address_']) ?></p>
                        <p><span class="font-semibold text-gray-600">Registered:</span> <?= date('M d, Y', strtotime($patient['created_at'])) ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold 
                        <?= $patient['status_'] == 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= htmlspecialchars($patient['status_']) ?>
                    </span>
                    <div class="mt-2">
                         <span class="inline-block px-2 py-1 rounded text-xs border bg-gray-50">
                            Category: <strong><?= $patient['category'] ?? 'None' ?></strong>
                         </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-4 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 active-tab border-blue-600 text-blue-600" id="bookings-tab" data-tabs-target="#bookings" type="button" role="tab" aria-controls="bookings" aria-selected="true" onclick="switchTab('bookings')">Bookings History</button>
                </li>
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="payments-tab" data-tabs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false" onclick="switchTab('payments')">Payment History</button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div id="myTabContent">
            
            <!-- Bookings Tab -->
            <div class="bg-white rounded-lg shadow-md p-6" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                <table id="bookingsTable" class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Time</th>
                            <th class="px-6 py-3">Service Type</th> <!-- Assuming simple display or logic needed -->
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $b): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">#<?= $b['id'] ?></td>
                            <td class="px-6 py-4"><?= date('M d, Y', strtotime($b['appointment_date'])) ?></td>
                            <td class="px-6 py-4"><?= date('h:i A', strtotime($b['appointment_time'])) ?></td>
                            <td class="px-6 py-4">
                                <!-- Logic to fetch items or just generic -->
                                <button onclick="viewBookingItems(<?= $b['id'] ?>)" class="text-blue-600 hover:underline">View Items</button>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">₱<?= number_format($b['total_amount'], 2) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 font-semibold leading-tight rounded-full 
                                    <?= match(strtolower($b['status'])) {
                                        'confirmed', 'completed' => 'text-green-700 bg-green-100',
                                        'pending' => 'text-yellow-700 bg-yellow-100',
                                        'cancelled' => 'text-red-700 bg-red-100',
                                        default => 'text-gray-700 bg-gray-100'
                                    } ?>">
                                    <?= ucfirst($b['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Payments Tab -->
            <div class="hidden bg-white rounded-lg shadow-md p-6" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                 <table id="paymentsTable" class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Payment ID</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Booking ID</th>
                            <th class="px-6 py-3">Method</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">#<?= $p['id'] ?></td>
                            <td class="px-6 py-4"><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                            <td class="px-6 py-4 text-blue-600">#<?= $p['booking_id'] ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($p['payment_method']) ?></td>
                            <td class="px-6 py-4 font-bold text-green-600">₱<?= number_format($p['total_price'], 2) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 font-semibold leading-tight rounded-full 
                                    <?= match(strtolower($p['status'])) {
                                        'paid', 'completed' => 'text-green-700 bg-green-100',
                                        'pending' => 'text-yellow-700 bg-yellow-100',
                                        'failed' => 'text-red-700 bg-red-100',
                                        default => 'text-gray-700 bg-gray-100'
                                    } ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Simple Items Modal -->
<div id="itemsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded p-6 max-w-md w-full">
        <h3 class="font-bold text-lg mb-4">Booking Items</h3>
        <div id="itemsContent" class="mb-4 text-sm max-h-60 overflow-y-auto">Loading...</div>
        <button onclick="document.getElementById('itemsModal').classList.add('hidden')" class="bg-gray-500 text-white px-4 py-2 rounded">Close</button>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#bookingsTable').DataTable({ responsive: true, order: [[1, 'desc']] });
    $('#paymentsTable').DataTable({ responsive: true, order: [[1, 'desc']] });
});

function switchTab(tabId) {
    // Hide all
    document.getElementById('bookings').classList.add('hidden');
    document.getElementById('payments').classList.add('hidden');
    
    // Reset buttons
    document.getElementById('bookings-tab').className = "inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300";
    document.getElementById('payments-tab').className = "inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300";

    // Show selected
    document.getElementById(tabId).classList.remove('hidden');
    
    // Highlight button
    document.getElementById(tabId + '-tab').className = "inline-block p-4 border-b-2 rounded-t-lg text-blue-600 border-blue-600 active";
}

function viewBookingItems(bookingId) {
    const modal = document.getElementById('itemsModal');
    const content = document.getElementById('itemsContent');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    content.innerHTML = 'Loading...';

    // Reuse existing API or create simple logic
    fetch(`get_booking.php?id=${bookingId}`)
    .then(res => res.json())
    .then(data => {
        if(data.items && data.items.length > 0) {
            let html = '<ul class="list-disc pl-5">';
            data.items.forEach(item => {
                html += `<li>${item.name} (${item.type}) - ₱${item.price}</li>`;
            });
            html += '</ul>';
            content.innerHTML = html;
        } else {
            content.innerHTML = 'No items found.';
        }
    })
    .catch(err => {
        content.innerHTML = 'Error fetching items.';
        console.error(err);
    });
}
</script>

</body>
</html>
