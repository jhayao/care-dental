<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Dental Care - Home</title>
    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</head>
<body class="bg-gray-50 font-poppins">

<?php include 'header.php'; ?>


<section class="text-center py-20 bg-gradient-to-br from-blue-50 to-blue-200">
    <img src="img/logo.webp" class="w-40 h-40 mx-auto rounded-full border-4 border-white shadow-lg mb-6">
    <h1 class="text-4xl md:text-5xl font-bold text-blue-700 mb-4">
        Welcome to B-Dental Care
    </h1>
    <p class="max-w-2xl mx-auto text-gray-700 text-lg mb-6">
        Providing quality dental care to keep your smile healthy, bright, and confident.
    </p>
   
</section>


<section class="py-16 max-w-6xl mx-auto px-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">


        <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-semibold mb-2 text-blue-700">Why Choose Us</h3>
            <p class="text-gray-600">Expert dentists, state-of-the-art equipment, and a friendly environment for all patients.</p>
        </div>


        <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-semibold mb-2 text-blue-700">Packages</h3>
            <p class="text-gray-600">Affordable dental packages for regular check-ups, cleanings, and more.</p>
        </div>

      
        <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-semibold mb-2 text-blue-700">Testimonials</h3>
            <p class="text-gray-600">Hear from our happy patients and see why they trust B-Dental Care with their smiles.</p>
        </div>

    </div>
</section>

<?php include 'footer.php'; ?>

</body>
</html>
