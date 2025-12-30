<?php
session_start();
require_once '../db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $address_   = trim($_POST['address_'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $pword      = trim($_POST['pword'] ?? ''); // fix undefined key
    $gender     = $_POST['gender'] ?? '';

    // Auto fields
    $user_type = "staff";
    $status_ = "Active";

    if ($first_name === '' || $last_name === '' || $address_ === '' || $email === '' || $pword === '' || $gender === '') {
        $error = "All fields are required.";
    
    } else {
        // Check duplicate email
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO users 
                (first_name, last_name, gender, address_, email, pword, user_type, status_, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $hashed = password_hash($pword, PASSWORD_DEFAULT);

            $stmt->bind_param(
                "ssssssss",
                $first_name,
                $last_name,
                $gender,
                $address_,
                $email,
                $hashed,
                $user_type,
                $status_
            );

            if ($stmt->execute()) {
                $success = "Staff registration successful! You may now log in.";
            } else {
                $error = "Error saving data. Try again.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration - B-Dental Care</title>
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-poppins">

<div class="flex-grow flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white p-8 rounded-xl shadow-xl relative">
        <div class="absolute -top-14 left-1/2 transform -translate-x-1/2">
            <img src="img/logo.webp" class="w-24 h-24 rounded-full border-4 border-white shadow-md object-cover">
        </div>

        <div class="mt-12 text-center">
            <h2 class="text-2xl font-bold mb-6 text-gray-700">Staff Registration</h2>

            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-md"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-3 mb-4 rounded-md"><?= $success ?></div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-600">First Name</label>
                        <input type="text" name="first_name" required class="w-full mt-1 p-2 border rounded-md">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Last Name</label>
                        <input type="text" name="last_name" required class="w-full mt-1 p-2 border rounded-md">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-600">Gender</label>
                        <select name="gender" required class="w-full mt-1 p-2 border rounded-md">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Address</label>
                        <input type="text" name="address_" required class="w-full mt-1 p-2 border rounded-md">
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-600">Email</label>
                    <input type="email" name="email" required class="w-full mt-1 p-2 border rounded-md">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Password</label>
                    <input type="password" name="pword" required class="w-full mt-1 p-2 border rounded-md">
                </div>

                <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Register</button>

                <p class="text-center text-sm mt-2">
                    Already have an account? <a href="login.php" class="text-blue-600 hover:underline">Login here</a>
                </p>
            </form>
        </div>
    </div>
</div>

<footer class="bg-blue-600 text-white text-center py-4">
    © <?php echo date('Y'); ?> B-Dental Care. All rights reserved.
</footer>

</body>
</html>
