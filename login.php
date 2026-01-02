<?php
session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, email, pword, user_type FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
         
            if (password_verify($password, $row['pword'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_type'] = $row['user_type'];

                switch ($row['user_type']) {
                    case 'admin':
                        header("Location: admin/bookings.php");
                        exit;
                    case 'staff':
                        header("Location: staff/dashboard.php");
                        exit;
                    case 'patient':
                        header("Location: home.php");
                        exit;
                    default:
                        $error = "Unknown user type.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - B-Dental Care</title>
    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>

<body class="bg-gray-100 flex flex-col min-h-screen font-poppins">

    <div class="flex-grow flex items-center justify-center p-4 overflow-auto">
        <div class="w-full max-w-sm bg-white p-8 rounded-xl shadow-xl relative flex-shrink-0">
            

        <div class="mt-12 text-center">
    <h2 class="text-2xl font-bold mb-6 text-gray-700">
        User Login
    </h2>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-md text-center">
            <?= $error ?>
        </div>
    <?php endif; ?>
</div>


                <form method="post" class="space-y-4">

                    <div>
                        <label class="text-sm text-gray-600">Email</label>
                        <input type="email" name="email" required
                               class="w-full mt-1 p-2 border rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Password</label>
                        <div class="mt-1" style="position: relative;">
                            <input type="password" name="password" id="password" required
                                   class="w-full p-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 pr-10">
                            <button type="button" onclick="togglePassword()"
                                    style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); background: none; border: none; cursor: pointer;"
                                    class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <script>
                    function togglePassword() {
                        const passwordInput = document.getElementById('password');
                        const toggleIcon = document.getElementById('toggleIcon');
                        
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            toggleIcon.classList.remove('fa-eye');
                            toggleIcon.classList.add('fa-eye-slash');
                        } else {
                            passwordInput.type = 'password';
                            toggleIcon.classList.remove('fa-eye-slash');
                            toggleIcon.classList.add('fa-eye');
                        }
                    }
                    </script>

                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md">
                        Login
                    </button>

                    <p class="text-center text-sm mt-2">
                        Don’t have an account?
                        <a href="register.php" class="text-blue-600 hover:underline">
                            Register here
                        </a>
                    </p>

                </form>
            </div>

        </div>
    </div>

<footer class="bg-blue-700 text-white mt-auto pb-8">
    <div class="mx-auto px-4 text-center">

    

        <!-- Copyright -->
        <div class="border-t border-blue-600 pt-4 text-gray-200 text-sm">
            © <?php echo date('Y'); ?> B-Dental Care. All rights reserved.
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</footer>

</body>

</html>
