<footer class="bg-blue-700 text-white mt-16 py-8">
    <div class="max-w-6xl mx-auto px-4 text-center">

        <!-- Branding -->
        <div class="mb-6">
            <h3 class="text-xl font-bold">B-Dental Care</h3>
            <p class="text-gray-200 text-sm">
                Quality dental care for a healthy, confident smile.
            </p>
        </div>

        <!-- Quick Links -->
        <div class="mb-6">
            <ul class="flex flex-wrap justify-center gap-4 text-sm">
                <li><a href="services.php" class="hover:text-gray-300">Services</a></li>
                <li><a href="about.php" class="hover:text-gray-300">About</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="home.php" class="hover:text-gray-300">Home</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="hover:text-gray-300">Login</a></li>
                    <li><a href="register.php" class="hover:text-gray-300">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Contact Info -->
        <div class="mb-6">
            <h4 class="text-lg font-semibold mb-2">Contact Us</h4>
            <div class="flex justify-center gap-4 mb-2 text-2xl">
                <a href="#" class="hover:text-gray-300"><i class="fab fa-facebook"></i></a>
                <a href="mailto:bdcclinic@gmail.com" class="hover:text-gray-300"><i class="fas fa-envelope"></i></a>
            </div>
            <p class="text-gray-200 text-sm">
                Email: <a href="mailto:bdcclinic@gmail.com" class="hover:text-gray-300">bdcclinic@gmail.com</a><br>
                Phone: 0920-000-0000
            </p>
        </div>

        <!-- Copyright -->
        <div class="border-t border-blue-600 pt-4 text-gray-200 text-sm">
            © <?php echo date('Y'); ?> B-Dental Care. All rights reserved.
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</footer>
