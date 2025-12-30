<?php
session_start();
require_once '../db_connect.php';

// Ensure staff is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default first day of month
$end_date = $_GET['end_date'] ?? date('Y-m-t');     // Default last day of month
$status = $_GET['status'] ?? 'all';

// Build Query
$query = "
    SELECT 
        b.id, 
        b.appointment_date, 
        b.appointment_time, 
        b.status,
        b.total_amount,
        u.first_name, 
        u.last_name,
        u.email
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.appointment_date BETWEEN ? AND ?
";

if ($status !== 'all') {
    $query .= " AND b.status = ?";
}

$query .= " ORDER BY b.appointment_date ASC, b.appointment_time ASC";

$stmt = $conn->prepare($query);

if ($status !== 'all') {
    $stmt->bind_param("sss", $start_date, $end_date, $status);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
$total_revenue = 0;
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
    if ($row['status'] == 'completed' || $row['status'] == 'confirmed') {
        $total_revenue += $row['total_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff - Reports</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<style>
    @media print {
        aside, .no-print { display: none !important; }
        .print-container { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        body { background: white !important; }
    }
</style>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<?php include 'sidebar.php'; ?>

<main class="flex-1 p-8 print-container">
    <div class="flex justify-between items-center mb-8 no-print">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Reports</h1>
            <p class="text-gray-500 mt-1">Generate booking reports and revenue summaries.</p>
        </div>
        <button onclick="window.print()" class="bg-gray-800 text-white px-5 py-2 rounded-lg hover:bg-gray-700 transition flex items-center">
            <i class="fas fa-print mr-2"></i> Print Report
        </button>
    </div>

    <!-- Filter Section (No Print) -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8 no-print">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= $start_date ?>" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= $end_date ?>" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" class="border px-3 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none min-w-[150px]">
                    <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="confirmed" <?= $status == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                Generate
            </button>
        </form>
    </div>

    <!-- Report Content -->
    <div class="bg-white p-8 rounded-xl shadow-lg">
        <div class="text-center mb-8 border-b pb-6">
            <h2 class="text-2xl font-bold text-gray-800">Booking Summary Report</h2>
            <p class="text-gray-500 mt-1">From <span class="font-semibold text-gray-700"><?= date("F j, Y", strtotime($start_date)) ?></span> to <span class="font-semibold text-gray-700"><?= date("F j, Y", strtotime($end_date)) ?></span></p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider border-y">
                        <th class="py-3 px-4 font-semibold">Date</th>
                        <th class="py-3 px-4 font-semibold">Time</th>
                        <th class="py-3 px-4 font-semibold">Patient</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="py-3 px-4"><?= date("M j, Y", strtotime($b['appointment_date'])) ?></td>
                                <td class="py-3 px-4"><?= date("h:i A", strtotime($b['appointment_time'])) ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($b['email']) ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <?php 
                                        $badges = [
                                            'confirmed' => 'bg-green-100 text-green-700',
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'completed' => 'bg-blue-100 text-blue-700',
                                            'cancelled' => 'bg-red-100 text-red-700'
                                        ];
                                        $badgeClass = $badges[$b['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $badgeClass ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-medium">
                                    ₱<?= number_format($b['total_amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">No bookings found for this period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-bold text-gray-800">
                        <td colspan="4" class="py-4 px-4 text-right uppercase text-xs tracking-wider">Total Revenue (Confirmed/Completed)</td>
                        <td class="py-4 px-4 text-right text-lg text-green-700">₱<?= number_format($total_revenue, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="mt-8 text-xs text-gray-400 text-center">
            Report generated on <?= date("F j, Y h:i A") ?> | Care Dental Staff Panel
        </div>
    </div>
</main>

</body>
</html>
