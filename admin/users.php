<?php
session_start();
require_once '../db_connect.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}



// Filter logic removed - Showing all including Archived by default
$query = "
    SELECT 
        id,
        first_name,
        last_name,
        address_,
        email,
        gender,
        status_,
        category,
        created_at
    FROM users
    WHERE user_type = 'patient'
";

// Removed exclusion of 'Archived'

$query .= " ORDER BY last_name ASC, first_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patients List</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">
<?php include 'admin_sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-center">Patients List</h1>
        <!-- Filter Form Removed -->
    </div>

    <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-4">
        <table id="patientsTable" class="w-full text-sm border-collapse border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2">First Name</th>
                    <th class="border px-4 py-2">Last Name</th>
                    <th class="border px-4 py-2">Address</th>
                    <th class="border px-4 py-2">Email</th>
                    <th class="border px-4 py-2">Gender</th>
                    <th class="border px-4 py-2">Category</th>
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
                             <?php
                                $cat = $p['category'] ?? 'None';
                                $badgeClass = 'bg-gray-100 text-gray-600';
                                if ($cat === 'Senior') {
                                    $badgeClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                                } elseif ($cat === 'PWD') {
                                    $badgeClass = 'bg-blue-100 text-blue-800 border border-blue-200';
                                }
                             ?>
                             <span class="px-2 py-1 rounded text-xs font-semibold <?= $badgeClass ?>"><?= $cat ?></span>
                        </td>
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
                        <td class="border px-4 py-2">
                            <?php if($p['status_'] !== 'Archived'): ?>
                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded flex items-center gap-1 archive-btn"
                                onclick="updateUserStatus(<?= $p['id']; ?>, 'archive')">
                                <i class="fas fa-archive"></i> Archive
                            </button>
                            <?php else: ?>
                            <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded flex items-center gap-1 unarchive-btn"
                                onclick="updateUserStatus(<?= $p['id']; ?>, 'unarchive')">
                                <i class="fas fa-box-open"></i> Unarchive
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-white rounded-lg p-6 shadow-xl max-w-sm w-full transform transition-all scale-100">
        <h3 class="text-lg font-bold mb-2 text-gray-800">Confirm Action</h3>
        <p id="confirmMessage" class="text-gray-600 mb-6">Are you sure you want to proceed?</p>
        <div class="flex justify-end gap-3">
            <button onclick="closeConfirmModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cancel</button>
            <button id="confirmBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition font-semibold">Confirm</button>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div id="alertModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-white rounded-lg p-6 shadow-xl max-w-sm w-full text-center">
        <div id="alertIcon" class="mb-4"></div>
        <h3 id="alertTitle" class="text-xl font-bold mb-2">Notification</h3>
        <p id="alertMessage" class="text-gray-600 mb-6">Message goes here.</p>
        <button onclick="closeAlertModal()" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition font-semibold">Okay</button>
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


function updateUserStatus(id, action) {
    const actionText = action === 'archive' ? 'archive' : 'unarchive';
    showConfirmModal(`Are you sure you want to ${actionText} this user?`, function() {
        fetch('archive_user_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&action=' + action
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                showAlert(`User ${actionText}d successfully`, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('Error: ' + data.message, 'error');
            }
        })
        .catch(err => console.error(err));
    });
}
function archiveUser(id) { updateUserStatus(id, 'archive'); } // Backward compatibility just in case
</script>

</body>
</html>
