<?php
session_start();
require_once '../db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['booking_fee'])) {
        $booking_fee = floatval($_POST['booking_fee']);
        
        // Update or Insert booking_fee
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('booking_fee', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $booking_fee, $booking_fee);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Settings updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update settings.";
        }
        $stmt->close();
    }
}

// Fetch current settings
$booking_fee = 100; // Default
$result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'booking_fee'");
if ($result && $row = $result->fetch_assoc()) {
    $booking_fee = floatval($row['setting_value']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Settings - B-Dental Care</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-blue-800 mb-6">System Settings</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 relative">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Payment Settings</h2>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="booking_fee" class="block text-gray-700 font-medium mb-2">Booking Fee (PHP)</label>
                    <p class="text-xs text-gray-500 mb-2">This non-refundable fee will be added to all appointment checkouts.</p>
                    <div class="relative max-w-xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-900 font-bold sm:text-sm">₱</span>
                        </div>
                        <input type="number" name="booking_fee" id="booking_fee" 
                            class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md py-2 border" 
                            placeholder="0.00" step="0.01" min="0" value="<?= number_format($booking_fee, 2, '.', '') ?>" required>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-medium transition duration-200">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
