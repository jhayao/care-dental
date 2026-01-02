<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>

<header class="font-poppins">
    <nav class="bg-white shadow-md py-4 px-6 flex items-center justify-between">

        <div class="flex items-center space-x-3">
            <a href="home.php" class="text-xl font-bold text-blue-700 flex items-center space-x-2 no-underline hover:no-underline">
             
                <span>B-Dental Care</span>
            </a>
        </div>

   
<div class="hidden md:flex space-x-8 text-gray-700 font-medium text-lg mx-auto">
    <a href="home.php" class="hover:text-blue-600">Home</a>
    <a href="services.php" class="hover:text-blue-600">Services</a>
    <a href="packages.php" class="hover:text-blue-600">Packages</a>
    <a href="about.php" class="hover:text-blue-600">About</a>
</div>

<div class="hidden md:flex items-center space-x-5 text-lg">
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="appointments.php" class="hover:text-blue-600 flex items-center space-x-1">
             Appointments
        </a>

        <a href="view_cart.php" class="relative hover:text-blue-600 flex items-center space-x-1">
            <i class="fas fa-shopping-cart text-xl"></i>
            <?php if($cart_count > 0): ?>
                <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
                    <?php echo $cart_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="profile.php" class="hover:text-blue-600 flex items-center space-x-1">
            <i class="fas fa-user-circle text-xl"></i>
        </a>
        <a href="logout.php" class="text-decoration-none text-primary" style="text-decoration: underline;">
            Logout
        </a>

    <?php else: ?>
        <a href="login.php" class="text-blue-600 font-semibold hover:underline flex items-center space-x-1 text-lg">
            <i class="fas fa-sign-in-alt text-xl"></i> <span>Login</span>
        </a>
        <a href="register.php" class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 flex items-center space-x-2 text-lg">
            <i class="fas fa-user-plus text-xl"></i> <span>Register</span>
        </a>
    <?php endif; ?>
</div>

    </nav>


   
</header>
