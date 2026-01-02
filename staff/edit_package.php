<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage_packages.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch package details
$stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) {
    $_SESSION['error'] = "Package not found.";
    header("Location: manage_packages.php");
    exit;
}

// Decode inclusions to array for comparison
$current_inclusions = json_decode($package['inclusions'], true);
if (!is_array($current_inclusions)) {
    // Fallback for old CSV format if any
    $current_inclusions = array_map('trim', preg_split("/\r\n|\n|,/", $package['inclusions']));
}

// Fetch active services for the checklist
$stmt = $conn->prepare("SELECT id, service_name, duration_minutes, price FROM services WHERE status = 'Active' ORDER BY service_name ASC");
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff - Edit Package</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">

</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<?php include 'sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-edit mr-2 text-blue-600"></i> Edit Package
        </h1>
        <a href="manage_packages.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6 max-w-3xl mx-auto">
        <form action="update_package.php" method="POST">
            <input type="hidden" name="id" value="<?= $package['id'] ?>">
            
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Package Name</label>
                <input type="text" name="package_name" value="<?= htmlspecialchars($package['package_name']) ?>" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Description</label>
                <textarea name="description" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" required><?= htmlspecialchars($package['description']) ?></textarea>
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-semibold">Select Services (Inclusions)</label>
                <div class="border rounded p-4 max-h-60 overflow-y-auto bg-gray-50">
                    <?php if (empty($services)): ?>
                        <p class="text-gray-500">No active services found.</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <?php foreach($services as $s): ?>
                                <?php 
                                    $isChecked = in_array($s['service_name'], $current_inclusions) ? 'checked' : '';
                                ?>
                                <label class="flex items-center space-x-2 cursor-pointer p-2 hover:bg-gray-100 rounded">
                                    <input type="checkbox" 
                                           name="service_ids[]" 
                                           value="<?= $s['id']; ?>" 
                                           data-duration="<?= $s['duration_minutes']; ?>" 
                                           class="service-checkbox w-4 h-4 text-blue-600"
                                           <?= $isChecked ?>>
                                    <span class="text-sm">
                                        <?= htmlspecialchars($s['service_name']); ?> 
                                        <span class="text-gray-500 text-xs">(<?= $s['duration_minutes']; ?> mins)</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1">Select the services included in this package. Total duration will be calculated automatically.</p>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block mb-1 font-semibold">Total Duration (minutes)</label>
                    <input type="number" name="duration_minutes" id="duration_minutes" value="<?= $package['duration_minutes'] ?>" class="w-full border px-3 py-2 rounded bg-gray-100 cursor-not-allowed" readonly required>
                    <div class="text-xs text-blue-600 mt-1 font-medium" id="duration_display">0 minutes</div>
                </div>
                <div>
                     <label class="block mb-1 font-semibold">Price</label>
                     <input type="number" step="0.01" name="price" value="<?= $package['price'] ?>" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block mb-1 font-semibold">Status</label>
                <select name="status" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="Active" <?= $package['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $package['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold transition">Update Package</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.service-checkbox');
    const durationInput = document.getElementById('duration_minutes');
    const durationDisplay = document.getElementById('duration_display');

    function calculateTotalDuration() {
        let total = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                total += parseInt(cb.getAttribute('data-duration')) || 0;
            }
        });
        durationInput.value = total;
        
        // Format display (e.g., 1h 30m)
        const hours = Math.floor(total / 60);
        const minutes = total % 60;
        let displayText = total + " minutes";
        if (total > 60) {
            displayText += ` (${hours}h ${minutes}m)`;
        }
        durationDisplay.textContent = displayText;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', calculateTotalDuration);
    });
    
    // Initial calculation
    calculateTotalDuration();
});
</script>

</body>
</html>
