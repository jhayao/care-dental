<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch only patients with category PWD or Senior
$stmt = $conn->prepare("
    SELECT 
        id,
        first_name,
        last_name,
        address_,
        email,
        email_verified_at,
        pword,
        user_type,
        status_,
        proof_file,
        remember_token,
        created_at,
        updated_at,
        gender,
        reference_no,
        category,
        discount
    FROM users
    WHERE user_type = 'patient' AND category IN ('PWD', 'Senior')
    ORDER BY last_name ASC, first_name ASC
");
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PWD & Senior Patients List</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<link href="../assets/css/main.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">


</head>
<body class="flex bg-gray-50 font-poppins min-h-screen">

<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <h1 class="text-2xl font-bold mb-6 text-center">PWD & Senior Patients List</h1>

    <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-4">
        <table id="patientsTable" class="display table-auto w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Category</th>
                    <th>Discount</th>
                    <th>Status</th>
                    <th>Proof</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($patients as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['first_name']); ?></td>
                        <td><?= htmlspecialchars($p['last_name']); ?></td>
                        <td><?= htmlspecialchars($p['address_']); ?></td>
                        <td><?= htmlspecialchars($p['email']); ?></td>
                        <td><?= htmlspecialchars($p['gender']); ?></td>
                        <td><?= htmlspecialchars($p['category']); ?></td>
                        <td>₱<?= number_format($p['discount'], 2); ?></td>
                        <td>
                            <?php if($p['status_'] == 'Active'): ?>
                                <span class="text-green-600 font-semibold">Active</span>
                            <?php elseif($p['status_'] == 'Inactive'): ?>
                                <span class="text-yellow-600 font-semibold">Inactive</span>
                            <?php else: ?>
                                <span class="text-red-600 font-semibold">Archived</span>
                            <?php endif; ?>
                        </td>
                        <td>
    <?php if(!empty($p['proof_file'])): ?>
        <a href="../uploads/proofs/<?= htmlspecialchars($p['proof_file']); ?>" target="_blank">
            <img src="../uploads/proofs/<?= htmlspecialchars($p['proof_file']); ?>" 
                 alt="Proof" class="w-16 h-16 object-cover rounded border">
        </a>
    <?php else: ?>
        N/A
    <?php endif; ?>
</td>

                        <td><?= date('M d, Y', strtotime($p['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#patientsTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        responsive: true
    });
});
</script>

</body>
</html>
