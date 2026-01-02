<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_connect.php';

// Fetch Booking Counts (kept from original)
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

// Get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Tailwind CSS (Ensure available if not present in parent) -->
<link href="../assets/css/main.css" rel="stylesheet">
<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Custom Scrollbar for Sidebar */
    aside::-webkit-scrollbar { width: 5px; }
    aside::-webkit-scrollbar-track { background: #1e3a8a; }
    aside::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 5px; }
</style>

<aside class="bg-blue-900 text-white w-64 h-screen flex flex-col font-poppins shadow-2xl transition-all duration-300 ease-in-out flex-shrink-0 overflow-y-auto sticky top-0">
    <!-- Header -->
    <div class="p-6 flex items-center justify-center border-b border-blue-800">
        <div class="text-center">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-2 text-blue-900 shadow-md">
                <i class="fas fa-tooth text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold tracking-wide">Care Dental</h2>
            <p class="text-xs text-blue-300 uppercase tracking-widest mt-1">Admin Panel</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1">
        
        <!-- Section: Overview -->
        <p class="px-4 text-xs font-semibold text-blue-400 uppercase tracking-wider mb-2 mt-2">Overview</p>
        
        <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'dashboard.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-chart-pie w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <!-- Section: Clinical -->
        <p class="px-4 text-xs font-semibold text-blue-400 uppercase tracking-wider mb-2 mt-6">Clinical</p>

        <a href="dentist_calendar.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'dentist_calendar.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-calendar-check w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Calendar</span>
        </a>

        <a href="bookings.php" class="flex items-center justify-between px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'bookings.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <div class="flex items-center">
                <i class="fas fa-clipboard-list w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
                <span class="font-medium">Bookings</span>
            </div>
            <?php if($bookings_count['pending'] > 0): ?>
                <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm animate-pulse">
                    <?= $bookings_count['pending'] ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="users.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'users.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-users w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Patients</span>
        </a>

        <!-- Section: Management -->
        <p class="px-4 text-xs font-semibold text-blue-400 uppercase tracking-wider mb-2 mt-6">Management</p>

        <a href="staff_list.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'staff_list.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-user-nurse w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Staff</span>
        </a>

        <a href="services.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'services.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-tooth w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Services</span>
        </a>

        <a href="package.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'package.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-box-open w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Packages</span>
        </a>
        
        <!-- Section: System -->
        <p class="px-4 text-xs font-semibold text-blue-400 uppercase tracking-wider mb-2 mt-6">System</p>

        <a href="reports.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'reports.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-file-invoice-dollar w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Reports</span>
        </a>

        <a href="payments.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'payments.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-money-bill-wave w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Payments</span>
        </a>

        <a href="settings.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'settings.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-cogs w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Booking Fee</span>
        </a>

        <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-blue-800 group <?= $current_page == 'profile.php' ? 'bg-blue-700 shadow-inner' : '' ?>">
            <i class="fas fa-user-cog w-6 text-center mr-3 text-blue-300 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Profile</span>
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="p-4 border-t border-blue-800 mt-auto">
        <a href="../logout.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-800 hover:bg-red-600 transition-colors duration-300 group text-white">
            <i class="fas fa-sign-out-alt w-6 text-center mr-3 text-blue-300 group-hover:text-white"></i>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>
