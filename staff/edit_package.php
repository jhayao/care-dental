<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if (!isset($_GET['id'])) {
    header("Location: manage_packages.php");
    exit;
}

$package_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: manage_packages.php");
    exit;
}

$package = $result->fetch_assoc();
$stmt->close();


// -------------------------
// UPDATE PACKAGE LOGIC
// -------------------------
if (isset($_POST['update_package'])) {
    $package_name = $_POST['package_name'];
    $description  = $_POST['description'];
    $status       = $_POST['status'];
    $price        = $_POST['price'] ?: NULL;

    // Convert textarea lines into array
    $inclusions = array_filter(array_map('trim', preg_split("/\r\n|\n|,/", $_POST['inclusions'])));
    $inclusions_json = json_encode($inclusions);

    $stmt = $conn->prepare("
        UPDATE packages 
        SET package_name = ?, description = ?, inclusions = ?, status = ?, price = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssssdi", $package_name, $description, $inclusions_json, $status, $price, $package_id);

    if ($stmt->execute()) {
        $success = "Package updated successfully!";

        // Refresh displayed data
        $package['package_name'] = $package_name;
        $package['description']  = $description;
        $package['inclusions']   = $inclusions_json;
        $package['status']       = $status;
        $package['price']        = $price;
    } else {
        $error = "Error updating package: " . $conn->error;
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
<title>Edit Package</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>

</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">


    <aside class="w-64 bg-white shadow-lg sticky top-0 h-screen">
        <?php include 'sidebar.php'; ?>
    </aside>


    <main class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-blue-700 mb-6 text-center">Edit Package</h1>

            <?php if($success): ?>
                <div id="successModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 shadow-lg w-96 relative">
                        <button onclick="document.getElementById('successModal').style.display='none'" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-xl font-bold">&times;</button>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                            <span class="text-green-700 font-medium text-lg"><?php echo $success; ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-6 text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-lg rounded-lg p-6 space-y-4">

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Package Name</label>
                    <input type="text" name="package_name" required 
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600"
                        value="<?php echo htmlspecialchars($package['package_name']); ?>">
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Inclusions (one per line)</label>

                    <textarea name="inclusions" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600"><?php 

                        // Decode JSON inclusions
                        $incs = json_decode($package['inclusions'], true);

                        // If not JSON, fallback to comma/newline string
                        if (!is_array($incs)) {
                            $incs = preg_split("/\r\n|\n|,/", $package['inclusions']);
                        }

                        $incs = array_map('trim', $incs);
                        $incs = array_filter($incs);

                        echo implode("\n", $incs); // Show one per line

                    ?></textarea>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" required 
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600"><?php 
                        echo htmlspecialchars($package['description']); ?></textarea>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Price</label>
                    <input type="number" name="price" step="0.01"
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600"
                        value="<?php echo htmlspecialchars($package['price']); ?>">
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600">
                        <option value="Active"   <?= $package['status']=='Active'?'selected':''; ?>>Active</option>
                        <option value="Inactive" <?= $package['status']=='Inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="submit" name="update_package" 
                        class="bg-gray-800 hover:bg-blue-700 text-white px-6 py-3 rounded font-semibold inline-flex items-center space-x-2">
                        <i class="fas fa-save"></i><span>Save changes</span>
                    </button>

                    <a href="manage_packages.php" 
                        class="bg-red-500 hover:bg-gray-300 text-black px-6 py-3 rounded font-semibold inline-flex items-center space-x-2">
                        <i class="fas fa-times"></i><span>Cancel</span>
                    </a>
                </div>

            </form>
        </div>
    </main>

</body>
</html>
