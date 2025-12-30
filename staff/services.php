<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle delete request
$deleted = false;
if (isset($_GET['delete_id'])) {
   $delete_id = intval($_GET['delete_id']);

$stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
$stmt->bind_param("i", $delete_id);
if ($stmt->execute()) {
    $deleted = true;
}
$stmt->close();

}

// Fetch services posted by logged-in user
// Fetch all services
$services = [];
$stmt = $conn->prepare("SELECT * FROM services ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Services - B-Dental</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- DATATABLES -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- ALPINE.JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>

<style>
body { font-family: 'Poppins', sans-serif; }
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #ccc;
    border-radius: 0.25rem;
    padding: 0.25rem 0.5rem;
}
.dataTables_wrapper .dataTables_length select {
    padding: 4px;
}
</style>
</head>
<body class="bg-gray-100 min-h-screen flex" x-data="serviceModals()">

<?php include 'sidebar.php'; ?>

<div class="flex-1 p-6">

   
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Services</h1>
        <button @click="addOpen = true" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Add Service
        </button>
    </div>

  
    <?php if ($deleted): ?>
    <div x-show="deleteSuccess" x-init="deleteSuccess=true; setTimeout(()=>deleteSuccess=false,3000)" x-transition.opacity class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded shadow-lg text-center">
            <div class="text-green-600 text-6xl mb-3">&#10004;</div>
            <p class="text-lg font-semibold">Service deleted successfully!</p>
        </div>
    </div>
    <?php endif; ?>

   
    <div class="overflow-x-auto bg-white rounded shadow p-4">
        <table id="servicesTable" class="min-w-full divide-y divide-gray-200 display">
            <thead class="bg-gray-50">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $index => $service): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($service['service_name']) ?></td>
                    <td><?= htmlspecialchars($service['description']) ?></td>
                    <td><?= $service['price'] !== NULL ? '$' . number_format($service['price'], 2) : 'N/A' ?></td>
                    <td><?= htmlspecialchars($service['status']) ?></td>
                    <td>
                        <a href="manage_services.php?id=<?= $service['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                        <a href="?delete_id=<?= $service['id'] ?>" @click.prevent="if(confirm('Are you sure?')) { window.location='?delete_id=<?= $service['id'] ?>' }" class="text-red-600 hover:underline">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

   <div x-show="addOpen" x-cloak x-transition.opacity class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div @click.away="addOpen=false" class="bg-white p-6 rounded shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Add New Service</h2>
        <form action="add_service.php" method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="text" name="service_name" placeholder="Service Name" required class="w-full p-2 border rounded-md">
            <textarea name="description" placeholder="Description" required class="w-full p-2 border rounded-md"></textarea>
            <input type="number" step="0.01" name="price" placeholder="Price" required class="w-full p-2 border rounded-md">
            
            <!-- NEW FIELD: Service Duration -->
        <!-- NEW FIELD: Service Duration -->
<input type="number" name="duration_minutes" placeholder="Duration (minutes)" min="1" required class="w-full p-2 border rounded-md">

            <select name="status" required class="w-full p-2 border rounded-md">
                <option value="">Select Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <input type="file" name="service_image" class="w-full">
            <div class="flex justify-end space-x-2">
                <button type="button" @click="addOpen=false" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Add</button>
            </div>
        </form>
    </div>
</div>


</div> 

<script>
function serviceModals() {
    return {
        addOpen: false,
        deleteSuccess: <?php echo $deleted ? 'true' : 'false'; ?>
    }
}


$(document).ready(function () {
    $('#servicesTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 20, 50],
        responsive: true,
        columnDefs: [{ orderable: false, targets: 5 }]
    });
});
</script>

</body>
</html>
