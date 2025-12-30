<?php
session_start();
require_once '../db_connect.php';

// Ensure staff is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

// Get current staff user details
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, first_name, last_name, address_, email, gender, created_at FROM users WHERE id=? AND user_type='staff' LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "Staff user not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          poppins: ['Poppins', 'sans-serif'],
        }
      }
    }
  }
</script>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 p-8">
    <div class="bg-white p-8 rounded shadow-md max-w-md mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Staff Profile</h2>
            <button id="editProfileBtn" class="text-blue-600 hover:text-blue-800 text-xl">
                <i class="fas fa-pen"></i>
            </button>
        </div>

        <div class="space-y-3">
            <div><span class="font-semibold">First Name:</span> <?= htmlspecialchars($user['first_name']); ?></div>
            <div><span class="font-semibold">Last Name:</span> <?= htmlspecialchars($user['last_name']); ?></div>
            <div><span class="font-semibold">Address:</span> <?= htmlspecialchars($user['address_']); ?></div>
            <div><span class="font-semibold">Email:</span> <?= htmlspecialchars($user['email']); ?></div>
            <div><span class="font-semibold">Gender:</span> <?= htmlspecialchars($user['gender']); ?></div>
            <div><span class="font-semibold">Created At:</span> <?= date('M d, Y', strtotime($user['created_at'])); ?></div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6 relative">
        <h2 class="text-xl font-bold mb-4">Edit Profile</h2>
        <form action="update_staff_profile.php" method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= $user['id']; ?>">
            <input type="text" name="first_name" placeholder="First Name" value="<?= htmlspecialchars($user['first_name']); ?>" class="w-full border px-3 py-2 rounded" required>
            <input type="text" name="last_name" placeholder="Last Name" value="<?= htmlspecialchars($user['last_name']); ?>" class="w-full border px-3 py-2 rounded" required>
            <input type="text" name="address_" placeholder="Address" value="<?= htmlspecialchars($user['address_']); ?>" class="w-full border px-3 py-2 rounded" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email']); ?>" class="w-full border px-3 py-2 rounded" required>
            <select name="gender" class="w-full border px-3 py-2 rounded" required>
                <option value="">Select Gender</option>
                <option value="Male" <?= $user['gender']=='Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?= $user['gender']=='Female' ? 'selected' : ''; ?>>Female</option>
            </select>
            <div class="flex justify-end space-x-2">
                <button type="button" id="closeModal" class="bg-gray-400 px-4 py-2 rounded hover:bg-gray-500 text-white">Cancel</button>
                <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 text-white">Save</button>
            </div>
        </form>
    </div>
</div>
<?php if(isset($_SESSION['success'])): ?>
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96 text-center relative">
        <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">&times;</button>
        <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
        <h2 class="text-xl font-bold mb-2">Success!</h2>
        <p class="mb-4"><?= $_SESSION['success']; ?></p>
        <button onclick="closeModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">OK</button>
    </div>
</div>
<script>
    function closeModal(){
        document.getElementById('successModal').style.display = 'none';
        // Remove query param via JS history (optional polish)
    }
</script>
<?php 
// Clear success message after showing
unset($_SESSION['success']); 
endif; 
?>

<script>
const editBtn = document.getElementById('editProfileBtn');
const modal = document.getElementById('editProfileModal');
const closeBtn = document.getElementById('closeModal');

editBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
});

closeBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
});
</script>

</body>
</html>
