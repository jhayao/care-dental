<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.service_name,
        s.description,
        s.status,
        s.price,
        s.duration_minutes,
        s.created_at
    FROM services s
    ORDER BY s.created_at DESC
");
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Services</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-tooth mr-2 text-blue-600"></i> Services List
        </h1>
        <button id="addServiceBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Service
        </button>
    </div>

    <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-4">
        <table id="servicesTable" class="w-full text-sm border-collapse border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2">Service Name</th>
                    <th class="border px-4 py-2">Description</th>
                    <th class="border px-4 py-2">Duration (minutes)</th>
                    <th class="border px-4 py-2">Status</th>
                    <th class="border px-4 py-2">Price</th>
                    <th class="border px-4 py-2">Created At</th>
                    <th class="border px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($services as $s): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border px-4 py-2"><?= htmlspecialchars($s['service_name']); ?></td>
                    <td class="border px-4 py-2"><?= htmlspecialchars($s['description']); ?></td>
                    <td class="border px-4 py-2"><?= htmlspecialchars($s['duration_minutes']); ?></td>
                    <td class="border px-4 py-2">
                        <?php if($s['status'] == 'Active'): ?>
                            <span class="text-success fw-semibold">Active</span>
                        <?php elseif($s['status'] == 'Inactive'): ?>
                            <span class="text-warning fw-semibold">Inactive</span>
                        <?php else: ?>
                            <span class="text-danger fw-semibold">Archived</span>
                        <?php endif; ?>
                    </td>
                    <td class="border px-4 py-2">$<?= number_format($s['price'], 2); ?></td>
                    <td class="border px-4 py-2"><?= date('M d, Y', strtotime($s['created_at'])); ?></td>
                    <td class="border px-4 py-2 flex justify-center gap-2">
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded flex items-center gap-1 edit-btn"
                            data-id="<?= $s['id']; ?>"
                            data-name="<?= htmlspecialchars($s['service_name'], ENT_QUOTES); ?>"
                            data-desc="<?= htmlspecialchars($s['description'], ENT_QUOTES); ?>"
                            data-status="<?= htmlspecialchars($s['status'], ENT_QUOTES); ?>"
                            data-price="<?= htmlspecialchars($s['price'], ENT_QUOTES); ?>"
                            data-duration="<?= htmlspecialchars($s['duration_minutes'], ENT_QUOTES); ?>">
                          Edit
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addServiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-1/3 p-6 relative">
        <h2 class="text-xl font-bold mb-4">Add Service</h2>
        <form id="addServiceForm" action="add_service_action.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Service Name</label>
                <input type="text" name="service_name" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Description</label>
                <textarea name="description" class="w-full border px-3 py-2 rounded" required></textarea>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Duration (minutes)</label>
                <input type="number" name="duration_minutes" min="1" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Status</label>
                <select name="status" class="w-full border px-3 py-2 rounded" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Price</label>
                <input type="number" step="0.01" name="price" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Service Image</label>
                <input type="file" name="service_image" accept="image/*" class="w-full border px-3 py-2 rounded">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="closeAddModal" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
    <div class="bg-white rounded-lg w-1/3 p-6 relative">
        <h2 class="text-xl font-bold mb-4">Edit Service</h2>
        <form id="editForm" action="update_service.php" method="POST">
            <input type="hidden" name="id" id="serviceId">
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Service Name</label>
                <input type="text" name="service_name" id="serviceName" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Description</label>
                <textarea name="description" id="serviceDesc" class="w-full border px-3 py-2 rounded" required></textarea>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Duration (minutes)</label>
                <input type="number" name="duration_minutes" id="serviceDuration" min="1" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Status</label>
                <select name="status" id="serviceStatus" class="w-full border px-3 py-2 rounded" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Price</label>
                <input type="number" step="0.01" name="price" id="servicePrice" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="closeModal" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<?php if(isset($_SESSION['success'])): ?>
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white rounded-lg w-1/4 p-6 text-center relative">
        <h2 class="text-xl font-bold mb-4">Success</h2>
        <p class="mb-4"><?= $_SESSION['success']; ?></p>
        <button id="closeSuccessModal" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">OK</button>
    </div>
</div>
<?php 
unset($_SESSION['success']); 
endif; ?>

<script>
$(document).ready(function(){
    $('#servicesTable').DataTable({
        pageLength: 10,
        lengthMenu: [5,10,25,50],
        responsive: true
    });

    $('.edit-btn').click(function(){
        $('#serviceId').val($(this).data('id'));
        $('#serviceName').val($(this).data('name'));
        $('#serviceDesc').val($(this).data('desc'));
        $('#serviceStatus').val($(this).data('status'));
        $('#servicePrice').val($(this).data('price'));
        $('#serviceDuration').val($(this).data('duration'));
        $('#editModal').removeClass('hidden').addClass('flex');
    });

    $('#closeModal').click(function(){
        $('#editModal').removeClass('flex').addClass('hidden');
    });

    $('#addServiceBtn').click(function(){
        $('#addServiceModal').removeClass('hidden').addClass('flex');
    });

    $('#closeAddModal').click(function(){
        $('#addServiceModal').removeClass('flex').addClass('hidden');
    });

    $('#closeSuccessModal').click(function(){
        $('#successModal').fadeOut();
    });
});
</script>

</body>
</html>
