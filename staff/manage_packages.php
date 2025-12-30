<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Package deleted successfully!";
    } else {
        $error = "Error deleting package: " . $conn->error;
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM packages ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Packages</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>

<script>
tailwind.config = {
    theme: { extend: { fontFamily: { poppins: ['Poppins', 'sans-serif'] } } }
}

$(document).ready(function() {
    $('#packagesTable').DataTable();

    $('.modal-close').click(function() {
        $(this).closest('.modal').hide();
    });
});
</script>
</head>

<body class="bg-gray-50 font-poppins min-h-screen flex">

<aside class="w-64 bg-white shadow-lg sticky top-0 h-screen">
    <?php include 'sidebar.php'; ?>
</aside>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">Manage Packages</h1>
            <a href="add_packages.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Package
            </a>
        </div>

        <?php if($success): ?>
            <div class="modal fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 shadow-lg w-96 relative">
                    <button class="modal-close absolute top-2 right-2 text-gray-500 hover:text-gray-800">&times;</button>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                        <span class="text-green-700 font-medium"><?= $success; ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="modal fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 shadow-lg w-96 relative">
                    <button class="modal-close absolute top-2 right-2 text-gray-500 hover:text-gray-800">&times;</button>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
                        <span class="text-red-700 font-medium"><?= $error; ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <table id="packagesTable" class="display stripe hover w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th>Package Name</th>
                    <th>Description</th>
                    <th>Inclusions</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['package_name']); ?></td>
                    <td><?= htmlspecialchars($row['description']); ?></td>

                    <td>
                        <?php
                            // Try to decode JSON
                            $incs = json_decode($row['inclusions'], true);

                            // If not JSON array, treat as plain text
                            if (!is_array($incs)) {
                                $incs = preg_split("/\r\n|\n|,/", $row['inclusions']);
                            }

                            $incs = array_filter(array_map('trim', $incs));

                            if (!empty($incs)) {
                                echo "<ul class='list-disc pl-5'>";
                                foreach ($incs as $inc) {
                                    echo "<li>" . htmlspecialchars($inc) . "</li>";
                                }
                                echo "</ul>";
                            } else {
                                echo "-";
                            }
                        ?>
                    </td>

                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td><?= $row['price'] ? "₱" . number_format($row['price'],2) : '-'; ?></td>
                    <td><?= htmlspecialchars($row['created_at']); ?></td>
                    <td><?= htmlspecialchars($row['updated_at']); ?></td>

                    <td class="space-x-2">
                        <a href="edit_package.php?id=<?= $row['id']; ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete_id=<?= $row['id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this package?');"
                           class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>

        </table>
    </div>
</main>

</body>
</html>
