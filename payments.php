<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch Payments for the logged-in patient
$sql = "
    SELECT 
        p.id, 
        p.payment_date, 
        p.total_price, 
        p.payment_method, 
        p.status,
        p.booking_id
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY p.payment_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payments - B Dental Care</title>
    <!-- Tailwind & Fonts -->
    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex flex-col">

    <?php include 'header.php'; ?>

    <main class="flex-grow max-w-7xl mx-auto py-12 px-6 w-full">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-blue-700">My Payments</h1>
            <p class="text-gray-500">View your transaction history.</p>
        </header>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
            <div class="overflow-x-auto">
                <table id="paymentsTable" class="w-full text-left border-collapse display stripe hover">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase text-sm leading-normal border-b">
                            <th class="py-3 px-4 rounded-tl-lg">ID</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Booking ID</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Method</th>
                            <th class="py-3 px-4 rounded-tr-lg">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-medium">
                        <?php if ($payments_result && $payments_result->num_rows > 0): ?>
                            <?php while ($row = $payments_result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors">
                                    <td class="py-3 px-4 font-bold text-blue-600">#<?= $row['id'] ?></td>
                                    <td class="py-3 px-4"><?= date('M d, Y h:i A', strtotime($row['payment_date'])) ?></td>
                                    <td class="py-3 px-4"><span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">#<?= $row['booking_id'] ?></span></td>
                                    <td class="py-3 px-4 font-bold text-green-600">₱<?= number_format($row['total_price'], 2) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($row['payment_method']) ?></td>
                                    <td class="py-3 px-4">
                                        <?php 
                                            $status = strtolower($row['status']);
                                            $statusColor = match($status) {
                                                'paid', 'approved', 'completed' => 'bg-green-100 text-green-700 border border-green-200',
                                                'pending' => 'bg-yellow-100 text-yellow-700 border border-yellow-200',
                                                'failed', 'declined', 'cancelled' => 'bg-red-100 text-red-700 border border-red-200',
                                                default => 'bg-gray-100 text-gray-700 border border-gray-200'
                                            };
                                        ?>
                                        <span class="<?= $statusColor ?> py-1 px-3 rounded-full text-xs font-bold capitalize">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- DataTables handles empty tables decently, but explicit row is fine too -->
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    
    <script>
        $(document).ready(function() {
             $('#paymentsTable').DataTable({
                responsive: true,
                order: [[0, 'desc']], // Sort by ID descending
                language: {
                    search: "Search Payments:",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No payment records found."
                }
            });
        });
    </script>
</body>
</html>
