<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Add Service</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">

</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-plus-circle mr-2 text-blue-600"></i> Add Service
        </h1>
        <a href="services.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6 max-w-2xl mx-auto">
        <form action="add_service_action.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Service Name</label>
                <input type="text" name="service_name" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Description</label>
                <textarea name="description" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4" required></textarea>
            </div>
            <div class="mb-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 font-semibold">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" min="1" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                     <label class="block mb-1 font-semibold">Price</label>
                     <input type="number" step="0.01" name="price" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Status</label>
                <select name="status" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block mb-1 font-semibold">Service Image</label>
                <input type="file" name="service_image" accept="image/*" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition">Save Service</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
