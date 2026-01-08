<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: patients.php");
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
<title>Patient Record - Staff</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex">

<aside class="w-64 bg-white shadow-lg sticky top-0 h-screen">
    <?php include 'sidebar.php'; ?>
</aside>

<main class="flex-1 p-8 overflow-y-auto h-screen">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Patient Record</h1>
            <a href="patients.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow transition">
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
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Paid</th>
                            <th class="px-6 py-3">Balance</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Pre-calculate payments per booking
                        $paid_map = [];
                        foreach ($payments as $p) {
                            if (in_array($p['status'], ['approved', 'paid', 'completed'])) {
                                if (!isset($paid_map[$p['booking_id']])) $paid_map[$p['booking_id']] = 0;
                                $paid_map[$p['booking_id']] += $p['total_price'];
                            }
                        }

                        foreach($bookings as $b): 
                            $paid = $paid_map[$b['id']] ?? 0;
                            $balance = $b['total_amount'] - $paid;
                            if ($balance < 0) $balance = 0; // Should not happen ideally
                        ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                #<?= $b['id'] ?>
                                <br>
                                <button onclick="viewBookingItems(<?= $b['id'] ?>)" class="text-xs text-blue-600 hover:underline">View Items</button>
                            </td>
                            <td class="px-6 py-4"><?= date('M d, Y', strtotime($b['appointment_date'])) ?></td>
                            <td class="px-6 py-4"><?= date('h:i A', strtotime($b['appointment_time'])) ?></td>
                            <td class="px-6 py-4 font-semibold text-gray-900">₱<?= number_format($b['total_amount'], 2) ?></td>
                            <td class="px-6 py-4 text-green-600">₱<?= number_format($paid, 2) ?></td>
                            <td class="px-6 py-4 font-bold <?= $balance > 0 ? 'text-red-600' : 'text-gray-500' ?>">
                                ₱<?= number_format($balance, 2) ?>
                            </td>
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
                 
                 <!-- Installment Button -->
                 <div class="mb-4 flex justify-end">
                     <button onclick="document.getElementById('paymentModal').classList.remove('hidden')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow flex items-center gap-2 transition">
                         <i class="fas fa-plus-circle"></i> Create Payment Request
                     </button>
                 </div>

                 <table id="paymentsTable" class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Payment ID</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Booking ID</th>
                            <th class="px-6 py-3">Description</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">#<?= $p['id'] ?></td>
                            <td class="px-6 py-4"><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                            <td class="px-6 py-4 text-blue-600">#<?= $p['booking_id'] ?></td>
                            <td class="px-6 py-4"><?= !empty($p['description']) ? htmlspecialchars($p['description']) : 'Standard' ?></td>
                            <td class="px-6 py-4 font-bold text-green-600">₱<?= number_format($p['total_price'], 2) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 font-semibold leading-tight rounded-full 
                                    <?= match(strtolower($p['status'])) {
                                        'paid', 'approved', 'completed' => 'text-green-700 bg-green-100',
                                        'pending' => 'text-yellow-700 bg-yellow-100',
                                        'failed' => 'text-red-700 bg-red-100',
                                        default => 'text-gray-700 bg-gray-100'
                                    } ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if(!empty($p['payment_url'])): ?>
                                    <a href="<?= htmlspecialchars($p['payment_url']) ?>" target="_blank" class="text-blue-500 underline text-xs">Link</a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">N/A</span>
                                <?php endif; ?>
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

<!-- Create Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-white rounded-lg p-6 shadow-xl max-w-sm w-full">
        <h3 class="text-xl font-bold mb-4 text-gray-800">Create Payment Request</h3>
        <form id="paymentForm" onsubmit="handleCreatePayment(event)">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Booking ID</label>
                <select name="booking_id" required class="w-full border rounded px-3 py-2 text-sm bg-gray-50">
                    <option value="">Select Booking...</option>
                    <?php foreach($bookings as $b): 
                        if(in_array($b['status'], ['cancelled', 'rejected'])) continue; // Skip cancelled
                    ?>
                        <option value="<?= $b['id'] ?>">#<?= $b['id'] ?> - <?= date('M d', strtotime($b['appointment_date'])) ?> (Total: ₱<?= $b['total_amount'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Amount (PHP)</label>
                <input type="number" name="amount" min="1" step="0.01" required class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description / Notes</label>
                <input type="text" name="notes" placeholder="e.g. Down Payment, Installment 1" class="w-full border rounded px-3 py-2">
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold" id="submitPayBtn">Generate Link</button>
            </div>
        </form>
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

function handleCreatePayment(e) {
    e.preventDefault();
    const btn = document.getElementById('submitPayBtn');
    const originalText = btn.innerText;
    btn.innerText = 'Processing...';
    btn.disabled = true;

    const formData = new FormData(e.target);

    fetch('create_payment_request.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('Payment link created successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('An network error occurred.');
    })
    .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    });
}
</script>

</body>
</html>
