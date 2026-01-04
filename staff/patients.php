<?php
session_start();
require_once '../db_connect.php';

// Ensure staff is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check for success/error messages
$msg = '';
$msgType = '';
if (isset($_GET['success'])) {
    $msg = "Patient added successfully.";
    $msgType = 'success';
} elseif (isset($_GET['error'])) {
    $msg = htmlspecialchars($_GET['error']);
    $msgType = 'error';
}


// Fetch Patients
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
    ORDER BY last_name ASC, first_name ASC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Patients</title>
<link href="../assets/css/main.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 font-poppins min-h-screen flex">

<aside class="w-64 bg-white shadow-lg sticky top-0 h-screen">
    <?php include 'sidebar.php'; ?>
</aside>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-blue-700">Patients</h1>
            <button onclick="openAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-semibold shadow flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Patient
            </button>
        </div>

        <?php if ($msg): ?>
            <div class="mb-4 p-4 rounded-lg <?= $msgType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-xl shadow-lg">
            <table id="patientsTable" class="display stripe hover w-full text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th>Name</th>
                        <th>Address</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Member Since</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $p): ?>
                        <tr>
                            <td class="font-semibold"><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?></td>
                            <td><?= htmlspecialchars($p['address_']); ?></td>
                            <td><?= htmlspecialchars($p['email']); ?></td>
                            <td><?= htmlspecialchars($p['gender']); ?></td>
                            <td>
                                <?php
                                $cat = $p['category'] ?? 'None';
                                $badgeClass = 'bg-gray-100 text-gray-600';
                                if ($cat === 'Senior') $badgeClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                                elseif ($cat === 'PWD') $badgeClass = 'bg-blue-100 text-blue-800 border border-blue-200';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-semibold <?= $badgeClass ?>"><?= $cat ?></span>
                            </td>
                            <td>
                                <?php if($p['status_'] == 'Active'): ?>
                                    <span class="text-green-600 font-semibold text-xs"><i class="fas fa-circle text-[8px] mr-1"></i>Active</span>
                                <?php elseif($p['status_'] == 'Inactive'): ?>
                                    <span class="text-yellow-600 font-semibold text-xs"><i class="fas fa-circle text-[8px] mr-1"></i>Inactive</span>
                                <?php elseif($p['status_'] == 'Archived'): ?>
                                    <span class="text-red-600 font-semibold text-xs"><i class="fas fa-circle text-[8px] mr-1"></i>Archived</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($p['created_at'])); ?></td>
                            <td>
                                <?php if($p['status_'] !== 'Archived'): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="updateUserStatus(<?= $p['id']; ?>, 'archive')">
                                    <i class="fas fa-archive"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-success" onclick="updateUserStatus(<?= $p['id']; ?>, 'unarchive')">
                                    <i class="fas fa-box-open"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Patient Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeAddModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Add New Patient</h2>
        
        <form action="add_patient.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">First Name</label>
                    <input type="text" name="first_name" required class="w-full border rounded px-3 py-2 mt-1">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Last Name</label>
                    <input type="text" name="last_name" required class="w-full border rounded px-3 py-2 mt-1">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Date of Birth</label>
                    <input type="date" name="dob" id="dob" required onchange="checkAge()" class="w-full border rounded px-3 py-2 mt-1">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Gender</label>
                    <select name="gender" required class="w-full border rounded px-3 py-2 mt-1">
                        <option value="">Select...</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Category</label>
                <div class="flex gap-4 items-center">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="category" value="None" checked onclick="toggleProof(false)" id="catNone">
                        None
                    </label>
                    
                    <span id="seniorBadge" class="hidden bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-0.5 rounded border border-yellow-200">
                        Senior Citizen (Auto)
                    </span>

                    <label class="flex items-center gap-2" id="pwdOption">
                        <input type="radio" name="category" value="PWD" onclick="toggleProof(true)" id="catPWD">
                        PWD
                    </label>
                </div>
            </div>

            <div id="proofDiv" class="hidden p-3 bg-gray-50 rounded border border-dashed border-gray-300">
                <label class="block text-sm font-semibold text-gray-700" id="proofLabel">Upload ID Proof</label>
                <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full mt-1 text-sm text-gray-500">
                <p class="text-xs text-gray-400 mt-1">Accepted: JPG, PNG, PDF</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700">Address</label>
                <input type="text" name="address_" required class="w-full border rounded px-3 py-2 mt-1">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Email</label>
                    <input type="email" name="email" required class="w-full border rounded px-3 py-2 mt-1">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Password</label>
                    <input type="password" name="pword" required class="w-full border rounded px-3 py-2 mt-1">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold">Add Patient</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#patientsTable').DataTable({
        responsive: true,
        pageLength: 10
    });
});

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function checkAge() {
    const dobInput = document.getElementById('dob').value;
    if (!dobInput) return;

    const dob = new Date(dobInput);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }

    const seniorBadge = document.getElementById('seniorBadge');
    const catNone = document.getElementById('catNone');
    const catPWD = document.getElementById('catPWD');

    if (age >= 60) {
        seniorBadge.classList.replace('hidden', 'inline-block');
        catNone.checked = false;
        catPWD.checked = false;
        catNone.disabled = true;
        catPWD.disabled = true;
        toggleProof(true, 'Senior Citizen ID');
    } else {
        seniorBadge.classList.replace('inline-block', 'hidden');
        catNone.disabled = false;
        catPWD.disabled = false;
        if (!catPWD.checked) {
             catNone.checked = true;
             toggleProof(false);
        } else {
             toggleProof(true, 'PWD ID Proof');
        }
    }
}

function toggleProof(show, labelText = 'Upload ID Proof') {
    const proofDiv = document.getElementById('proofDiv');
    proofDiv.style.display = show ? 'block' : 'none';
    if (show) document.getElementById('proofLabel').innerText = labelText;
}

function updateUserStatus(id, action) {
    if(!confirm(`Are you sure you want to ${action} this user?`)) return;

    fetch('archive_user_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&action=' + action
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) location.reload();
        else alert('Error: ' + data.message);
    })
    .catch(err => console.error(err));
}
</script>

</body>
</html>
