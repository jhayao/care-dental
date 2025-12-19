<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

if (isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address_ = $_POST['address_'];
    $email = $_POST['email'];
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, address_=?, email=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("ssssi", $first_name, $last_name, $address_, $email, $user_id);
    if ($stmt->execute()) {
        $success = "Profile updated successfully!";
    } else {
        $error = "Error updating profile.";
    }
    $stmt->close();

    if (!empty($password)) {
        $new_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET pword=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        $stmt->execute();
        $stmt->close();
        $success .= "<br>Password updated successfully!";
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - B-Dental Care</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: { poppins: ['Poppins', 'sans-serif'] }
        }
    }
}
</script>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<?php include 'sidebar.php'; ?>

<main class="flex-1 py-16 px-6 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-blue-700 mb-8 text-center">Your Profile</h1>

    <div class="bg-white shadow rounded-lg p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p><span class="font-semibold text-gray-700">First Name:</span> <?php echo htmlspecialchars($user['first_name']); ?></p>
        </div>
        <div>
            <p><span class="font-semibold text-gray-700">Last Name:</span> <?php echo htmlspecialchars($user['last_name']); ?></p>
        </div>
        <div>
            <p><span class="font-semibold text-gray-700">Gender:</span> <?php echo htmlspecialchars($user['gender']); ?></p>
        </div>
        <div>
            <p><span class="font-semibold text-gray-700">Address:</span> <?php echo htmlspecialchars($user['address_']); ?></p>
        </div>
        <div class="md:col-span-2">
            <p><span class="font-semibold text-gray-700">Email:</span> <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div class="md:col-span-2 flex justify-center mt-4">
            <button id="editBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center space-x-2">
                <i class="fas fa-edit"></i><span>Edit Profile</span>
            </button>
        </div>
    </div>
</main>

<!-- Edit Profile Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <h2 class="text-xl font-bold mb-4 text-blue-700">Edit Profile</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

            <div>
                <label class="block text-gray-700 font-medium mb-1">First Name</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600" required>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-1">Last Name</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600" required>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-1">Gender</label>
                <input type="text" value="<?php echo htmlspecialchars($user['gender']); ?>" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed" readonly>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-1">Address</label>
                <input type="text" name="address_" value="<?php echo htmlspecialchars($user['address_']); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600">
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600" required>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-1">Password (optional)</label>
                <input type="password" name="password" placeholder="Enter new password to change" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-600">
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" id="closeBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" name="update_profile" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 relative text-center">
        <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
        <h3 class="text-xl font-bold mb-2 text-green-700">Success!</h3>
        <p id="successMessage" class="text-gray-700 mb-4"></p>
        <button id="successCloseBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Close</button>
    </div>
</div>

<script>
const editBtn = document.getElementById('editBtn');
const editModal = document.getElementById('editModal');
const closeBtn = document.getElementById('closeBtn');

editBtn.addEventListener('click', () => {
    editModal.classList.remove('hidden');
    editModal.classList.add('flex');
});

closeBtn.addEventListener('click', () => {
    editModal.classList.add('hidden');
    editModal.classList.remove('flex');
});

editModal.addEventListener('click', (e) => {
    if(e.target === editModal){
        editModal.classList.add('hidden');
        editModal.classList.remove('flex');
    }
});

// Success Modal
const successModal = document.getElementById('successModal');
const successCloseBtn = document.getElementById('successCloseBtn');
const successMessage = document.getElementById('successMessage');

<?php if(!empty($success)): ?>
    successMessage.innerHTML = `<?php echo $success; ?>`;
    successModal.classList.remove('hidden');
    successModal.classList.add('flex');
<?php endif; ?>

successCloseBtn.addEventListener('click', () => {
    successModal.classList.add('hidden');
    successModal.classList.remove('flex');
});

successModal.addEventListener('click', (e) => {
    if(e.target === successModal){
        successModal.classList.add('hidden');
        successModal.classList.remove('flex');
    }
});
</script>

</body>
</html>
