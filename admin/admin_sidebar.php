<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_connect.php';

$bookings_count = [
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$result = $conn->query("SELECT status, COUNT(*) as total FROM bookings GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings_count[$row['status']] = $row['total'];
    }
}
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<aside class="bg-primary text-white vh-100 p-3" style="width: 250px; font-family: 'Poppins', sans-serif;">
    <div class="mb-4">
        <h2 class="h4 fw-bold">Admin Dashboard</h2>
    </div>
    <nav class="nav flex-column">
        <a href="profile.php" class="nav-link text-white">Profile</a>
        <a href="staff_list.php" class="nav-link text-white">Staff</a>
        <a href="dentist_calendar.php" class="nav-link text-white">Calendar</a>
        <a href="users.php" class="nav-link text-white">Patients</a>
        <a href="list_of_category.php" class="nav-link text-white">PWD and Senior Citizens</a>
        <a href="bookings.php" class="nav-link text-white d-flex justify-content-between align-items-center">
            <span>Bookings</span>
            <span class="badge bg-danger"><?= $bookings_count['pending']; ?></span>
        </a>
        <a href="services.php" class="nav-link text-white">Services</a>
        <a href="report.php" class="nav-link text-white">Report</a>
        <a href="package.php" class="nav-link text-white">Packages</a>
        <a href="../logout.php" class="nav-link text-white">Logout</a>
    </nav>
</aside>

<!-- Optional Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
