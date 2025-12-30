<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Tailwind CSS (Ensure available if not present in parent) -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Custom Scrollbar for Sidebar */
    aside::-webkit-scrollbar { width: 5px; }
    aside::-webkit-scrollbar-track { background: #1f2937; } /* gray-800 */
    aside::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 5px; } /* gray-600 */
</style>

<aside class="bg-gray-800 text-white w-64 h-screen flex flex-col font-poppins shadow-2xl transition-all duration-300 ease-in-out flex-shrink-0 overflow-y-auto sticky top-0">
    <!-- Header -->
    <div class="p-6 flex items-center justify-center border-b border-gray-700">
        <div class="text-center">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-2 text-blue-600 shadow-md">
                <i class="fas fa-tooth text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold tracking-wide">Care Dental</h2>
            <p class="text-xs text-gray-400 uppercase tracking-widest mt-1">Staff Panel</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1">
        
        <!-- Section: Overview -->
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-2">Overview</p>
        
        <a href="report.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-gray-700 group <?= ($current_page == 'report.php') ? 'bg-gray-700 shadow-inner' : '' ?>">
            <i class="fas fa-chart-pie w-6 text-center mr-3 text-gray-400 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="reports.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-gray-700 group <?= ($current_page == 'reports.php') ? 'bg-gray-700 shadow-inner' : '' ?>">
            <i class="fas fa-file-invoice-dollar w-6 text-center mr-3 text-gray-400 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Reports</span>
        </a>

        <!-- Section: Clinical -->
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Clinical</p>

        <a href="bookings.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-gray-700 group <?= ($current_page == 'bookings.php') ? 'bg-gray-700 shadow-inner' : '' ?>">
            <i class="fas fa-calendar-check w-6 text-center mr-3 text-gray-400 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Bookings</span>
        </a>

        <a href="services.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-gray-700 group <?= ($current_page == 'services.php') ? 'bg-gray-700 shadow-inner' : '' ?>">
            <i class="fas fa-tooth w-6 text-center mr-3 text-gray-400 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Services</span>
        </a>

        <!-- Section: Management -->
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Management</p>

        <a href="manage_packages.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-gray-700 group <?= ($current_page == 'manage_packages.php' || $current_page == 'add_packages.php' || $current_page == 'edit_package.php') ? 'bg-gray-700 shadow-inner' : '' ?>">
            <i class="fas fa-box-open w-6 text-center mr-3 text-gray-400 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Packages</span>
        </a>

        <!-- Section: System -->
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">System</p>

        <a href="profile.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 hover:bg-gray-700 group <?= ($current_page == 'profile.php') ? 'bg-gray-700 shadow-inner' : '' ?>">
            <i class="fas fa-user-cog w-6 text-center mr-3 text-gray-400 group-hover:text-white transition-colors"></i>
            <span class="font-medium">Profile</span>
        </a>

    </nav>

    <!-- Footer / Logout -->
    <div class="p-4 border-t border-gray-700 mt-auto">
        <a href="../logout.php" class="flex items-center px-4 py-3 rounded-lg bg-gray-700 hover:bg-red-600 transition-colors duration-300 group text-white">
            <i class="fas fa-sign-out-alt w-6 text-center mr-3 text-gray-400 group-hover:text-white"></i>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>
