<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address_ = trim($_POST['address_']);
    $email = trim($_POST['email']);
    $pword = trim($_POST['pword']);
    $gender = $_POST['gender'] ?? '';
    $category = $_POST['category'] ?? 'None'; 
    $discount = 0;
    $user_type = "patient";
    $status_ = "Active";

    // Validate required fields
    if ($first_name === '' || $last_name === '' || $address_ === '' ||
        $email === '' || $pword === '' || $gender === '' || $category === '') {
        $error = "All fields are required.";
    } else {

        // Discount logic
        if ($category === 'Senior' || $category === 'PWD') {
            $discount = 20;

            // Validate file upload
            if (!isset($_FILES['proof']) || $_FILES['proof']['error'] != 0) {
                $error = "Please upload a valid ID as proof for your category.";
            } else {
                $proofFile = $_FILES['proof'];
                $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!in_array($proofFile['type'], $allowedTypes)) {
                    $error = "Only JPG, PNG, or PDF files are allowed for proof.";
                } else {
                    // Generate unique file name
                    $fileName = uniqid() . '_' . basename($proofFile['name']);
                    $uploadDir = 'uploads/proofs/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $uploadPath = $uploadDir . $fileName;

                    if (!move_uploaded_file($proofFile['tmp_name'], $uploadPath)) {
                        $error = "Failed to upload proof file.";
                    }
                }
            }
        }

        // Continue registration if no errors
        if ($error === '') {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $error = "This email is already registered.";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO users 
                    (first_name, last_name, address_, email, pword, gender, category, discount, user_type, status_, proof_file, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");

                $hashed = password_hash($pword, PASSWORD_DEFAULT);

                $stmt->bind_param(
                    "sssssssssss",
                    $first_name,
                    $last_name,
                    $address_,
                    $email,
                    $hashed,
                    $gender,
                    $category,
                    $discount,
                    $user_type,
                    $status_,
                    $fileName
                );

                if ($stmt->execute()) {
                    header('Location: login.php');
                    exit;
                } else {
                    $error = "Error saving data. Try again.";
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Registration - B-Dental Care</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>
tailwind.config = {
    theme: {
        extend: { fontFamily: { poppins: ['Poppins','sans-serif'] } }
    }
}
</script>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-poppins">

<div class="flex-grow flex items-center justify-center p-4 relative">

    <div class="w-full max-w-lg bg-white p-8 pt-16 rounded-xl shadow-xl relative">
        <div class="mt-2">
            <h2 class="text-2xl font-bold text-center mb-6 text-gray-700">Patient Registration</h2>

            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-md text-center">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="space-y-4">
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
                    <label class="text-sm text-gray-600 mb-1 block">Category</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="category" value="None" checked onclick="toggleProof(false)">
                            None
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="category" value="Senior" onclick="toggleProof(true)">
                            Senior
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="category" value="PWD" onclick="toggleProof(true)">
                            PWD
                        </label>
                    </div>
                </div>

                <div id="proofDiv" class="hidden">
                    <label class="text-sm text-gray-600">Upload ID Proof</label>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full mt-1 p-2 border rounded-md">
                    <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, PDF</p>
                </div>

                <div>
                    <label class="text-sm text-gray-600">Address</label>
                    <input type="text" name="address_" required class="w-full mt-1 p-2 border rounded-md">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Email</label>
                    <input type="email" name="email" required class="w-full mt-1 p-2 border rounded-md">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Password</label>
                    <input type="password" name="pword" required class="w-full mt-1 p-2 border rounded-md">
                </div>

                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md">
                    Register
                </button>

                <p class="text-center text-sm mt-2">
                    Already have an account? 
                    <a href="login.php" class="text-blue-600 hover:underline">Login here</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
function toggleProof(show) {
    document.getElementById('proofDiv').style.display = show ? 'block' : 'none';
}
</script>

<footer class="bg-blue-700 text-white mt-auto py-8">
    <div class="max-w-6xl mx-auto px-4 text-center">
        <div class="mb-6">
            <h3 class="text-xl font-bold">B-Dental Care</h3>
            <p class="text-gray-200 text-sm">Quality dental care for a healthy, confident smile.</p>
        </div>
        <div class="mb-6">
            <h4 class="text-lg font-semibold mb-2">Contact Us</h4>
            <div class="flex justify-center gap-4 mb-2 text-2xl">
                <a href="#" class="hover:text-gray-300"><i class="fab fa-facebook"></i></a>
                <a href="mailto:bdcclinic@gmail.com" class="hover:text-gray-300"><i class="fas fa-envelope"></i></a>
            </div>
            <p class="text-gray-200 text-sm">
                Email: <a href="mailto:bdcclinic@gmail.com" class="hover:text-gray-300">bdcclinic@gmail.com</a><br>
                Phone: 0920-000-0000
            </p>
        </div>
        <div class="border-t border-blue-600 pt-4 text-gray-200 text-sm">
            © <?php echo date('Y'); ?> B-Dental Care. All rights reserved.
        </div>
    </div>
</footer>

</body>
</html>
