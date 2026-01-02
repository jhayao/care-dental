<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Dental Care - Home</title>
    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">


</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <nav class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <span class="text-xl font-bold text-blue-700">B-Dental Care</span>
        </div>

        <div class="space-x-6 text-gray-700 font-medium hidden md:flex">
            <a href="#services" class="hover:text-blue-600">Services</a>
            <a href="#about" class="hover:text-blue-600">About</a>
            <a href="#contact" class="hover:text-blue-600">Contact</a>
            <a href="login.php" class="text-blue-600 font-semibold hover:underline">Login</a>
            <a href="register.php" class="text-blue-600 font-semibold hover:underline">
                Register
            </a>
        </div>

        <div class="md:hidden">
            <button id="menuBtn" class="text-blue-600 text-2xl">&#9776;</button>
        </div>
    </nav>
    <div id="mobileMenu" class="hidden bg-white shadow-md p-4 space-y-3 text-gray-700 md:hidden">
        <a href="#services" class="block">Services</a>
        <a href="#about" class="block">About</a>
        <a href="#contact" class="block">Contact</a>
        <a href="login.php" class="block text-blue-600 font-semibold">Login</a>
        <a href="register.php" class="block bg-blue-600 text-white px-3 py-1 rounded-md text-center">
            Register
        </a>
    </div>
    <section class="text-center py-20 bg-gradient-to-br from-blue-50 to-blue-200">
        <img src="img/logo.webp"
             class="w-40 h-40 mx-auto rounded-full border-4 border-white shadow-lg mb-6">

        <h1 class="text-4xl md:text-5xl font-bold text-blue-700 mb-4">
            Welcome to B-Dental Care
        </h1>
        <p class="max-w-2xl mx-auto text-gray-700 text-lg mb-6">
            Providing quality dental care to keep your smile healthy, bright, and confident.
        </p>
    </section>

   
    <!-- <section id="services" class="py-16 max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Our Services</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h3 class="text-xl font-semibold mb-2 text-blue-600">Dental Check-up</h3>
                <p class="text-gray-600">Routine exams to ensure your teeth and gums are healthy.</p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h3 class="text-xl font-semibold mb-2 text-blue-600">Tooth Extraction</h3>
                <p class="text-gray-600">Safe and gentle removal of damaged or decayed teeth.</p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                <h3 class="text-xl font-semibold mb-2 text-blue-600">Teeth Cleaning</h3>
                <p class="text-gray-600">Professional cleaning to remove plaque and prevent cavities.</p>
            </div>

        </div>
    </section> -->

  
    <!-- <section id="about" class="py-16 bg-gray-100">
        <div class="max-w-5xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">About Our Clinic</h2>
            <p class="text-gray-700 text-lg leading-relaxed">
                B-Dental Care is a trusted dental clinic dedicated to providing safe, affordable,
                and high-quality dental services. Our skilled team ensures every patient feels
                comfortable and satisfied with their dental care.
            </p>
        </div>
    </section> -->


    <!-- <section id="contact" class="py-16 max-w-4xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Contact Us</h2>

        <div class="p-6 rounded-lg text-center">
            <p class="text-gray-700"><strong>Address:</strong> P-4 Basirang, Tudela, Misamis Occidental</p>
            <p class="text-gray-700"><strong>Email:</strong> bdcclinic@gmail.com</p>
            <p class="text-gray-700"><strong>Phone:</strong> 0920-000-0000</p>
        </div>
    </section> -->


    <footer class="bg-blue-600 text-white text-center py-4 mt-auto">
        © <?php echo date('Y'); ?> B-Dental Care. All rights reserved.
    </footer>

    <script>
    document.getElementById("menuBtn").onclick = function() {
        document.getElementById("mobileMenu").classList.toggle("hidden");
    };
    </script>

</body>
</html>
