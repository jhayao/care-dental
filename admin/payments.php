<?php
session_start();
require_once '../db_connect.php';

// Check Admin Access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch Payments with Booking Details
$sql = "
    SELECT 
        p.id, 
        p.payment_date, 
        p.total_price, 
        p.payment_method, 
        p.status,
        b.id AS booking_id, 
        u.first_name, 
        u.last_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    ORDER BY p.payment_date DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments - Admin Panel</title>
    <!-- Tailwind & Fonts -->
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

    <?php include 'admin_sidebar.php'; ?>

    <main class="flex-1 p-8">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Payments</h1>
                <p class="text-gray-500">View and track all transaction history.</p>
            </div>
            <!-- Optional: Export Button could go here -->
        </header>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="overflow-x-auto">
                <table id="paymentsTable" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal border-b">
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Patient Name</th>
                            <th class="py-3 px-4">Booking ID</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Method</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-800">#<?= $row['id'] ?></td>
                                    <td class="py-3 px-4"><?= date('M d, Y h:i A', strtotime($row['payment_date'])) ?></td>
                                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td class="py-3 px-4"><span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">#<?= $row['booking_id'] ?></span></td>
                                    <td class="py-3 px-4 font-bold text-green-600">₱<?= number_format($row['total_price'], 2) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($row['payment_method']) ?></td>
                                    <td class="py-3 px-4">
                                        <?php 
                                            $status = strtolower($row['status']);
                                            $statusColor = match($status) {
                                                'paid', 'approved', 'completed' => 'bg-green-100 text-green-700',
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'failed', 'declined', 'cancelled' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700'
                                            };
                                        ?>
                                        <span class="<?= $statusColor ?> py-1 px-3 rounded-full text-xs font-semibold capitalize">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500">No payment records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            $('#paymentsTable').DataTable({
                responsive: true,
                order: [[0, 'desc']], // Sort by ID descending
                language: {
                    search: "Filter payments:",
                    lengthMenu: "Show _MENU_ entries",
                }
            });
        });
    </script>
</body>
</html>
