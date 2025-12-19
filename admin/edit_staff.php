<?php
session_start();
require_once '../db_connect.php';

// Ensure admin/staff is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check staff ID
if (!isset($_GET['id'])) {
    header("Location: staff_list.php");
    exit;
}

$staff_id = intval($_GET['id']);

// Fetch staff info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'staff'");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if (!$staff) {
    $_SESSION['error'] = "Staff not found.";
    header("Location: staff_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Staff</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex">

    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex justify-center items-start p-8">
        <div class="bg-white rounded shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-center">Edit Staff</h2>
            <form action="edit_staff_process.php" method="POST" class="space-y-4">
                <input type="hidden" name="id" value="<?= $staff['id']; ?>">

                <input type="text" name="first_name" value="<?= htmlspecialchars($staff['first_name']); ?>" placeholder="First Name" class="w-full border px-3 py-2 rounded" required>
                <input type="text" name="last_name" value="<?= htmlspecialchars($staff['last_name']); ?>" placeholder="Last Name" class="w-full border px-3 py-2 rounded" required>
                <input type="text" name="address_" value="<?= htmlspecialchars($staff['address_']); ?>" placeholder="Address" class="w-full border px-3 py-2 rounded" required>
                <input type="email" name="email" value="<?= htmlspecialchars($staff['email']); ?>" placeholder="Email" class="w-full border px-3 py-2 rounded" required>

                <select name="gender" class="w-full border px-3 py-2 rounded" required>
                    <option value="Male" <?= $staff['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?= $staff['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>

                <select name="status_" class="w-full border px-3 py-2 rounded" required>
                    <option value="Active" <?= $staff['status_'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?= $staff['status_'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Archived" <?= $staff['status_'] == 'Archived' ? 'selected' : ''; ?>>Archived</option>
                </select>

                <div class="flex justify-end space-x-2">
                    <a href="staff_list.php" class="bg-gray-400 px-4 py-2 rounded hover:bg-gray-500 text-white">Cancel</a>
                    <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 text-white">Update</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
