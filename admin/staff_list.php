<?php
session_start();
require_once '../db_connect.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// Filter Logic removed
$query = "
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
    WHERE user_type = 'staff'
";

// Removed exclusion of 'Archived'

$query .= " ORDER BY last_name ASC, first_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute();
$staff = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff List</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">


<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">
<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold">
            <i class="fa-solid fa-users mr-2 text-blue-600"></i>
            Staff List
        </h1>
        <!-- Filter Form Removed -->
    </div>
    <button id="addStaffBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        <i class="fa-solid fa-user-plus mr-2"></i>
        Add Staff
    </button>
</div>


    <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-4">
        <table id="staffTable" class="w-full text-sm border-collapse border border-gray-200">
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
        <?php foreach($staff as $s): ?>
            <tr class="hover:bg-gray-50">
                <td class="border px-4 py-2"><?= htmlspecialchars($s['first_name']); ?></td>
                <td class="border px-4 py-2"><?= htmlspecialchars($s['last_name']); ?></td>
                <td class="border px-4 py-2"><?= htmlspecialchars($s['address_']); ?></td>
                <td class="border px-4 py-2"><?= htmlspecialchars($s['email']); ?></td>
                <td class="border px-4 py-2"><?= htmlspecialchars($s['gender']); ?></td>
                <td class="border px-4 py-2">
                <?php 
                    $status = $s['status_'];
                    if ($status == 'Active') {
                        $dotColor = 'bg-green-500';
                    } elseif ($status == 'Inactive') {
                        $dotColor = 'bg-yellow-400';
                    } elseif ($status == 'Archived') {
                        $dotColor = 'bg-red-500';
                    } else {
                        $dotColor = 'bg-gray-400';
                    }
                ?>
                <div class="flex items-center space-x-2">
                    <span class="h-3 w-3 rounded-full <?= $dotColor; ?>"></span>
                    <span><?= htmlspecialchars($status); ?></span>
                </div>
            </td>
                <td class="border px-4 py-2"><?= date('M d, Y', strtotime($s['created_at'])); ?></td>
                <td class="border px-4 py-2">
                <div class="flex justify-center gap-2">
                    <a href="edit_staff.php?id=<?= $s['id']; ?>"
                    class="bg-blue-800 text-white px-3 py-1 rounded hover:bg-blue-900 text-sm flex items-center gap-1">
                        Edit
                    </a>
                    <?php if($s['status_'] !== 'Archived'): ?>
                    <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded flex items-center gap-1 text-sm" onclick="archiveStaff(<?= $s['id']; ?>)">
                        <i class="fas fa-archive"></i> Archive
                    </button>
                    <?php else: ?>
                    <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded flex items-center gap-1 text-sm" onclick="updateStaffStatus(<?= $s['id']; ?>, 'unarchive')">
                        <i class="fas fa-undo"></i> Unarchive
                    </button>
                    <?php endif; ?>
                </div>
            </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    </div>
</div>


<div id="addStaffModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6 relative">
        <h2 class="text-xl font-bold mb-4">Add New Staff</h2>
        <form id="addStaffForm" action="add_staff_process.php" method="POST" class="space-y-4">
            <input type="text" name="first_name" placeholder="First Name" class="w-full border px-3 py-2 rounded" required>
            <input type="text" name="last_name" placeholder="Last Name" class="w-full border px-3 py-2 rounded" required>
            <input type="text" name="address_" placeholder="Address" class="w-full border px-3 py-2 rounded" required>
            <input type="email" name="email" placeholder="Email" class="w-full border px-3 py-2 rounded" required>
            <select name="gender" class="w-full border px-3 py-2 rounded" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
            <select name="status_" class="w-full border px-3 py-2 rounded" required>
                <option value="">Select Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <input type="password" name="pword" placeholder="Password" id="password" class="w-full border px-3 py-2 rounded" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" id="confirm_password" class="w-full border px-3 py-2 rounded" required>
            <p id="passwordError" class="text-red-500 text-sm hidden">Passwords do not match.</p>
            <div class="flex justify-end space-x-2">
                <button type="button" id="closeModal" class="bg-gray-400 px-4 py-2 rounded hover:bg-gray-500 text-white">Cancel</button>
                <button type="submit" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 text-white">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-sm p-6 relative shadow-2xl text-center">
        <div class="mb-4">
            <i class="fas fa-question-circle text-blue-500 text-4xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Confirm Action</h3>
        <p class="text-gray-600 mb-6" id="confirmMessage">Are you sure?</p>
        <div class="flex justify-center gap-3">
            <button onclick="closeConfirmModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cancel</button>
            <button id="confirmBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Confirm</button>
        </div>
    </div>
</div>

<!-- Generic Alert Modal -->
<div id="alertModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-sm p-6 relative shadow-2xl text-center">
        <div class="mb-4" id="alertIcon"></div>
        <h3 class="text-lg font-bold text-gray-800 mb-2" id="alertTitle">Notification</h3>
        <p class="text-gray-600 mb-6" id="alertMessage"></p>
        <button onclick="closeAlertModal()" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">OK</button>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#staffTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        responsive: true
    });
  
    $('#addStaffBtn').click(function () {
        $('#addStaffModal').removeClass('hidden').addClass('flex');
    });

    $('#closeModal').click(function () {
        $('#addStaffModal').removeClass('flex').addClass('hidden');
        $('#passwordError').addClass('hidden');
        $('#addStaffForm')[0].reset();
    });

    $('#addStaffForm').submit(function(e){
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        if(password !== confirmPassword){
            e.preventDefault();
            $('#passwordError').removeClass('hidden');
        } else {
            $('#passwordError').addClass('hidden');
        }
    });
});

// Modal Logic
const confirmModal = document.getElementById('confirmModal');
const alertModal = document.getElementById('alertModal');
let confirmCallback = null;

function showConfirmModal(message, callback) {
    document.getElementById('confirmMessage').textContent = message;
    confirmCallback = callback;
    confirmModal.classList.remove('hidden');
    confirmModal.classList.add('flex');
}

function closeConfirmModal() {
    confirmModal.classList.add('hidden');
    confirmModal.classList.remove('flex');
    confirmCallback = null;
}

document.getElementById('confirmBtn').addEventListener('click', function() {
    if (confirmCallback) confirmCallback();
    closeConfirmModal();
});

function showAlert(message, type = 'info') {
    const titleEl = document.getElementById('alertTitle');
    const msgEl = document.getElementById('alertMessage');
    const iconEl = document.getElementById('alertIcon');

    msgEl.textContent = message;
    
    if (type === 'success') {
        titleEl.textContent = 'Success';
        iconEl.innerHTML = '<i class="fas fa-check-circle text-green-500 text-4xl"></i>';
    } else if (type === 'error') {
            titleEl.textContent = 'Error';
            iconEl.innerHTML = '<i class="fas fa-times-circle text-red-500 text-4xl"></i>';
    } else {
            titleEl.textContent = 'Notification';
            iconEl.innerHTML = '<i class="fas fa-info-circle text-blue-500 text-4xl"></i>';
    }

    alertModal.classList.remove('hidden');
    alertModal.classList.add('flex');
}
function closeAlertModal() { 
    alertModal.classList.add('hidden'); 
    alertModal.classList.remove('flex'); 
}

function updateStaffStatus(id, action) {
    const actionText = action === 'archive' ? 'archive' : 'unarchive';
    showConfirmModal(`Are you sure you want to ${actionText} this staff member?`, function() {
        fetch('archive_user_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&action=' + action
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showAlert(`Staff ${actionText}d successfully`, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert('Error: ' + data.message, 'error');
            }
        })
        .catch(err => console.error(err));
    });
}
function archiveStaff(id) { updateStaffStatus(id, 'archive'); } 
</script>

</body>
</html>
