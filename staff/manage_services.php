<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: services.php");
    exit;
}

$service_id = intval($_GET['id']);

// Fetch service
$stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    die("Service not found.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['service_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $duration_minutes = intval($_POST['duration_minutes']); // New field

    $service_image = $service['service_image']; 

    if (!empty($_FILES['service_image']['name'])) {
        $targetDir = "../uploads/services/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $newFile = uniqid() . "_" . basename($_FILES['service_image']['name']);
        $targetFile = $targetDir . $newFile;

        if (move_uploaded_file($_FILES['service_image']['tmp_name'], $targetFile)) {
            $service_image = "uploads/services/" . $newFile;
        } else {
            $error = "Failed to upload new image.";
        }
    }

    if (empty($error)) {
        $update = $conn->prepare("
            UPDATE services 
            SET service_name=?, description=?, price=?, duration_minutes=?, status=?, service_image=? 
            WHERE id=?
        ");

        $update->bind_param("ssdissi", $name, $description, $price, $duration_minutes, $status, $service_image, $service_id);

        if ($update->execute()) {
            $success = "Service updated successfully!";
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Failed to update service.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Service</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100 flex h-screen">

    <div class="w-64 bg-white shadow-md h-screen fixed">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="flex-1 ml-64 overflow-auto p-6">
        <div class="max-w-4xl mx-auto bg-white shadow-md rounded p-6">

            <h1 class="text-2xl font-bold mb-4">Edit Service</h1>

            <?php if (!empty($error)): ?>
                <div class="bg-red-200 text-red-800 p-2 rounded mb-3"><?= $error ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                    <div class="bg-white rounded-lg shadow-lg p-6 text-center relative">
                        <div class="text-green-600 mb-3 text-6xl">&#10004;</div>
                        <p class="text-lg font-semibold"><?= $success ?></p>
                        <button onclick="document.getElementById('successModal').style.display='none'" 
                            class="mt-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Close
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="font-semibold">Service Name:</label>
                    <input type="text" name="service_name" value="<?= htmlspecialchars($service['service_name']) ?>" required class="w-full p-2 border rounded">
                </div>

                <div>
                    <label class="font-semibold">Price:</label>
                    <input type="number" name="price" step="0.01" value="<?= $service['price'] ?>" required class="w-full p-2 border rounded">
                </div>

                <!-- NEW FIELD: Duration -->
                <div>
                    <label class="font-semibold">Duration (minutes):</label>
                    <input type="number" name="duration_minutes" min="1" value="<?= $service['duration_minutes'] ?>" required class="w-full p-2 border rounded">
                </div>

                <div class="md:col-span-2">
                    <label class="font-semibold">Description:</label>
                    <textarea name="description" required class="w-full p-2 border rounded"><?= htmlspecialchars($service['description']) ?></textarea>
                </div>

                <div>
                    <label class="font-semibold">Status:</label>
                    <select name="status" required class="w-full p-2 border rounded">
                        <option value="Active" <?= $service['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $service['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="font-semibold">Current Image:</label><br>
                    <?php if (!empty($service['service_image'])): ?>
                        <img src="../<?= htmlspecialchars($service['service_image']) ?>" class="w-32 rounded mb-2" alt="Service Image">
                    <?php else: ?>
                        <p class="text-gray-500">No image uploaded.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="font-semibold">Upload New Image:</label>
                    <input type="file" name="service_image" class="w-full">
                </div>

                <div class="md:col-span-2 flex justify-end gap-2 mt-4">
                    <a href="services.php" class="px-4 py-2 bg-red-400 text-white rounded hover:bg-red-500">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
                </div>

            </form>

        </div>
    </div>

</body>
</html>
