<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $address_ = trim($_POST['address_']);
    $email = trim($_POST['email']);
    $pword = trim($_POST['pword']);
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    // Category Logic
    $category_input = $_POST['category'] ?? 'None'; 
    $category = 'None'; 

    $discount = 0;
    $user_type = "patient";
    $status_ = "Active";
    $fileName = null;

    if ($first_name === '' || $last_name === '' || $address_ === '' ||
        $email === '' || $pword === '' || $gender === '' || $dob === '') {
        header("Location: patients.php?error=All fields are required");
        exit;
    }

    // Age Calc
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;

    if ($age >= 60) {
        $category = 'Senior';
    } else {
        if ($category_input === 'PWD') $category = 'PWD';
        else $category = 'None';
    }

    // Discount & Proof
    if ($category === 'Senior' || $category === 'PWD') {
        $discount = 20;

        // Proof Upload
        if (!isset($_FILES['proof']) || $_FILES['proof']['error'] != 0) {
            // Technically should require proof, but let's be loose if user insists or forgot. 
            // Ideally we fail here. Let's fail to enforce rule.
            header("Location: patients.php?error=Proof is required for Senior/PWD discount");
            exit;
        } else {
            $proofFile = $_FILES['proof'];
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($proofFile['type'], $allowedTypes)) {
               header("Location: patients.php?error=Invalid file type for proof");
               exit;
            }

            $fileName = uniqid() . '_' . basename($proofFile['name']);
            // Save to root/uploads/proofs/
            $uploadDir = '../uploads/proofs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (!move_uploaded_file($proofFile['tmp_name'], $uploadDir . $fileName)) {
                header("Location: patients.php?error=Failed to upload proof");
                exit;
            }
        }
    }

    // Check Duplicate Email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: patients.php?error=Email already registered");
        exit;
    }

    // Insert
    $stmt = $conn->prepare("
        INSERT INTO users 
        (first_name, last_name, address_, email, pword, gender, dob, category, discount, user_type, status_, proof_file, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $hashed = password_hash($pword, PASSWORD_DEFAULT);

    $stmt->bind_param(
        "ssssssssdsss",
        $first_name,
        $last_name,
        $address_,
        $email,
        $hashed,
        $gender,
        $dob,
        $category,
        $discount,
        $user_type,
        $status_,
        $fileName
    );

    if ($stmt->execute()) {
        header("Location: patients.php?success=1");
    } else {
        header("Location: patients.php?error=Database error");
    }
    $stmt->close();
}
?>
