<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - B Dental Care</title>
    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex flex-col">


    <?php include 'header.php'; ?>


    <section class="max-w-4xl mx-auto px-6 py-16 text-center flex-1">
        <h1 class="text-4xl font-bold text-blue-700 mb-4">About Us</h1>
        <p class="text-gray-700 mb-4 text-justify">
        At B Dental Care Clinic your smile is our top priority. Located in the heart of Tudela, we are proud to provide gentle, personalized, and high-quality dental care for patients of all ages.
        </p>

        <p class="text-gray-700 mb-4 text-justify">
            With a team of experienced dentists, hygienists, and friendly staff, we combine modern dental technology with a compassionate approach to ensure every visit is comfortable, effective, and tailored to your needs.
        </p>

        <p class="text-gray-700 mb-6 text-justify">
            Whether you need a routine check-up, a cosmetic enhancement, or advanced restorative care, we'll help you achieve and maintain a healthy, confident smile.
        </p>

        <img src="img/dental.webp" alt="Dental Clinic" class="mx-auto rounded-lg shadow-lg mb-6">
    </section>
    <?php include 'footer.php'; ?>

</body>
</html>
