<?php
session_start();
require_once '../db_connect.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


$stmt = $conn->prepare("
    SELECT 
        id,
        first_name,
        last_name,
        address_,
        email,
        gender,
        status_,
        created_at
    FROM users
    WHERE user_type = 'patient'
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
<title>Patients List</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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
<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <h1 class="text-2xl font-bold mb-6 text-center">Patients List</h1>

    <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-4">
        <table id="patientsTable" class="w-full text-sm border-collapse border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2">First Name</th>
                    <th class="border px-4 py-2">Last Name</th>
                    <th class="border px-4 py-2">Address</th>
                    <th class="border px-4 py-2">Email</th>
                    <th class="border px-4 py-2">Gender</th>
                    <th class="border px-4 py-2">Status</th>
                    <th class="border px-4 py-2">Created At</th>
                    <th class="border px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($patients as $p): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border px-4 py-2"><?= htmlspecialchars($p['first_name']); ?></td>
                        <td class="border px-4 py-2"><?= htmlspecialchars($p['last_name']); ?></td>
                        <td class="border px-4 py-2"><?= htmlspecialchars($p['address_']); ?></td>
                        <td class="border px-4 py-2"><?= htmlspecialchars($p['email']); ?></td>
                        <td class="border px-4 py-2"><?= htmlspecialchars($p['gender']); ?></td>
                       <td class="border px-4 py-2">
                            <?php if($p['status_'] == 'Active'): ?>
                                <span class="text-green-600 font-semibold flex items-center">
                                    <i class="fas fa-circle text-green-500 mr-1 text-xs"></i> Active
                                </span>
                            <?php elseif($p['status_'] == 'Inactive'): ?>
                                <span class="text-yellow-600 font-semibold flex items-center">
                                    <i class="fas fa-circle text-yellow-500 mr-1 text-xs"></i> Inactive
                                </span>
                            <?php elseif($p['status_'] == 'Archived'): ?>
                                <span class="text-red-600 font-semibold flex items-center">
                                    <i class="fas fa-circle text-red-500 mr-1 text-xs"></i> Archived
                                </span>
                            <?php else: ?>
                                <span class="text-gray-600 font-semibold flex items-center">
                                    <i class="fas fa-circle text-gray-400 mr-1 text-xs"></i> Unknown
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="border px-4 py-2"><?= date('M d, Y', strtotime($p['created_at'])); ?></td>
                        <td class="border px-4 py-2 flex justify-center gap-2">
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded flex items-center gap-1 edit-btn"
                                data-id="<?= $p['id']; ?>"
                                data-first="<?= htmlspecialchars($p['first_name']); ?>"
                                data-last="<?= htmlspecialchars($p['last_name']); ?>"
                                data-address="<?= htmlspecialchars($p['address_']); ?>"
                                data-email="<?= htmlspecialchars($p['email']); ?>"
                                data-gender="<?= $p['gender']; ?>"
                                data-status="<?= $p['status_']; ?>">
                                <i class="fas fa-pen"></i> Edit
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<div id="editPatientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-1/3 p-6 relative">
        <h2 class="text-xl font-bold mb-4">Edit Patient</h2>
        <form id="editPatientForm" action="update_patient.php" method="POST">
            <input type="hidden" name="id" id="editPatientId">

            <div class="mb-4">
                <label class="block mb-1 font-semibold">First Name</label>
                <input type="text" name="first_name" id="editFirstName" class="w-full border px-3 py-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Last Name</label>
                <input type="text" name="last_name" id="editLastName" class="w-full border px-3 py-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Address</label>
                <input type="text" name="address" id="editAddress" class="w-full border px-3 py-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Email</label>
                <input type="email" name="email" id="editEmail" class="w-full border px-3 py-2 rounded" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Gender</label>
                <select name="gender" id="editGender" class="w-full border px-3 py-2 rounded" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Status</label>
                <select name="status" id="editStatus" class="w-full border px-3 py-2 rounded" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" id="closeEditPatientModal" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#patientsTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        responsive: true
    });

  
    $('.edit-btn').click(function () {
        $('#editPatientId').val($(this).data('id'));
        $('#editFirstName').val($(this).data('first'));
        $('#editLastName').val($(this).data('last'));
        $('#editAddress').val($(this).data('address'));
        $('#editEmail').val($(this).data('email'));
        $('#editGender').val($(this).data('gender'));
        $('#editStatus').val($(this).data('status'));
        $('#editPatientModal').removeClass('hidden').addClass('flex');
    });


    $('#closeEditPatientModal').click(function () {
        $('#editPatientModal').removeClass('flex').addClass('hidden');
    });
});
</script>

</body>
</html>
