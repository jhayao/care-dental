<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Staff';


$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Staff Member';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<aside
  class="bg-gray-800 text-white w-64 min-h-screen p-6 hidden md:flex flex-col font-poppins"
  style="font-family: 'Poppins', sans-serif;"
>

  <div class="flex items-center mb-10">
    <i class="fas fa-tooth text-3xl text-blue-400 mr-3"></i>
    <h2 class="text-2xl font-bold">Staff Dashboard</h2>
  </div>

  <nav class="flex flex-col space-y-3">


    <a href="services.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors duration-200">
      Services
    </a>

    <a href="add_packages.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors duration-200">
    Add Packages
    </a>

    <a href="manage_packages.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors duration-200">
     Manage Packages
    </a>

    <a href="bookings.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors duration-200">
     Bookings
    </a>
    <a href="report.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors duration-200">
       Report
    </a>


     <a href="../logout.php" class="flex items-center py-2 px-4 rounded hover:bg-gray-700 transition-colors duration-200">
       Logout
    </a>
  </nav>
</aside>

