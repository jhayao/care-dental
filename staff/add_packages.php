<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if (isset($_POST['add_package'])) {
    $package_name = $_POST['service_name']; 
    $description = $_POST['description'];
    $price = $_POST['price'] ?: NULL;
    $status = $_POST['status'];
    $duration_minutes = intval($_POST['duration_minutes']); // NEW FIELD
    $posted_by = $_SESSION['user_id']; 

    $inclusions = array_filter(array_map('trim', explode("\n", $_POST['inclusions'])));
    $inclusions_json = json_encode($inclusions);

    $stmt = $conn->prepare("
        INSERT INTO packages 
            (posted_by, package_name, description, inclusions, status, price, duration_minutes, created_at, updated_at)
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param("isssisi", $posted_by, $package_name, $description, $inclusions_json, $status, $price, $duration_minutes);

    if ($stmt->execute()) {
        $success = "Package added successfully!";
    } else {
        $error = "Error adding package: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Package - Staff</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script>
tailwind.config = {
    theme: { extend: { fontFamily: { poppins: ['Poppins', 'sans-serif'] } } }
}
</script>
</head>
<body class="bg-gray-50 font-poppins h-screen flex">

    <aside class="w-64 bg-white shadow-lg sticky top-0 h-screen">
        <?php include 'sidebar.php'; ?>
    </aside>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-blue-700 mb-8 text-center">Add New Package</h1>

            <?php if($success): ?>
                <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full relative">
                        <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
                        <div class="flex flex-col items-center space-y-4">
                            <i class="fas fa-check-circle text-green-500 text-6xl"></i>
                            <p class="text-green-700 text-center font-semibold"><?php echo $success; ?></p>
                            <button onclick="closeModal()" class="mt-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded font-medium">Close</button>
                        </div>
                    </div>
                </div>
                <script>
                function closeModal() {
                    document.getElementById('successModal').style.display = 'none';
                }
                </script>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-6 text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-lg rounded-lg p-6 space-y-4">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Package Name</label>
                    <input type="text" name="service_name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600">
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Inclusions (one per line)</label>
                    <textarea name="inclusions" rows="4" placeholder="Inclusion 1&#10;Inclusion 2&#10;Inclusion 3" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600"></textarea>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" required class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600"></textarea>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Price</label>
                    <input type="number" name="price" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600">
                </div>

                <!-- NEW FIELD: Duration -->
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" min="1" required class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600">
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="text-center">
                    <button type="submit" name="add_package" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded font-semibold inline-flex items-center space-x-2">
                        <i class="fas fa-plus"></i><span>Add Package</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
