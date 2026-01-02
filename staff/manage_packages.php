<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


$stmt = $conn->prepare("
    SELECT 
        p.id,
        u.first_name AS posted_by,
        p.package_name,
        p.description,
        p.inclusions,
        p.status,
        p.price,
        p.created_at,
        p.duration_minutes,
        p.updated_at
    FROM packages p
    LEFT JOIN users u ON p.posted_by = u.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$packages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff - Packages</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/main.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>


</head>
<body class="bg-gray-50 font-poppins min-h-screen flex">

<?php include 'sidebar.php'; ?>

<div class="flex-1 p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-boxes mr-2 text-blue-600"></i> Packages List
        </h1>
        <a href="create_package.php" id="openAddPackageBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center">
    <i class="fas fa-plus mr-2"></i> Add Package
</a>

    </div>

    <div class="overflow-x-auto bg-white shadow-lg rounded-lg p-4">
<table id="packagesTable" class="w-full text-sm border-collapse border border-gray-200">
    <thead class="bg-gray-100">
        <tr>
            <th class="border px-4 py-2">Package Name</th>
            <th class="border px-4 py-2">Description</th>
            <th class="border px-4 py-2">Inclusions</th>
            <th class="border px-4 py-2">Status</th>
            <th class="border px-4 py-2">Price</th>
            <th class="border px-4 py-2">Duration (minutes)</th>
            <th class="border px-4 py-2">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($packages as $p): ?>
        <tr class="hover:bg-gray-50">
            <td class="border px-4 py-2"><?= htmlspecialchars($p['package_name']); ?></td>
            <td class="border px-4 py-2"><?= htmlspecialchars($p['description']); ?></td>
            <td class="border px-4 py-2">
                <?php
                    $incs = json_decode($p['inclusions'], true);
                    if (!is_array($incs)) {
                        $incs = preg_split("/\r\n|\n|,/", $p['inclusions']);
                    }
                    $incs = array_map('trim', $incs);
                    $incs = array_filter($incs);
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
            <td class="border px-4 py-2">
                <?php if($p['status'] == 'Active'): ?>
                    <span class="text-green-600 font-semibold flex items-center">
                        <i class="fas fa-circle text-green-500 mr-1 text-xs"></i> Active
                    </span>
                <?php elseif($p['status'] == 'Inactive'): ?>
                    <span class="text-yellow-600 font-semibold flex items-center">
                        <i class="fas fa-circle text-yellow-500 mr-1 text-xs"></i> Inactive
                    </span>
                <?php else: ?>
                    <span class="text-red-600 font-semibold flex items-center">
                        <i class="fas fa-circle text-red-500 mr-1 text-xs"></i> Archived
                    </span>
                <?php endif; ?>
            </td>
            <td class="border px-4 py-2">₱<?= number_format($p['price'], 2); ?></td>
            <td class="border px-4 py-2"><?= intval($p['duration_minutes']); ?> Minutes</td>
            <td class="border px-4 py-2 flex justify-center gap-2">
                <a href="edit_package.php?id=<?= $p['id']; ?>" 
   class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded flex items-center gap-1">
    Edit
</a>

                <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded flex items-center gap-1 delete-btn"
                        data-id="<?= $p['id']; ?>">
                        Delete
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    </div>
</div>





<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-lg w-1/3 p-6 relative text-center">
        <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
        <h2 class="text-xl font-bold mb-2">Success!</h2>
        <p class="mb-4">Package updated successfully.</p>
        <button id="closeSuccessModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">OK</button>
    </div>
</div>





<script>
$(document).ready(function(){
    $('#packagesTable').DataTable({
        pageLength: 10,
        lengthMenu: [5,10,25,50],
        responsive: true
    });

  


   
    $('.delete-btn').click(function(){
        const id = $(this).data('id');
        if(confirm('Are you sure you want to delete this package?')){
            // alert('Delete package ID: ' + id);
           
        }
    });

    $('#openAddPackageModal').click(function(){
        // alert('Open Add Package Modal');
        
    });
});

$(document).ready(function(){
    
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('success') === '1'){
        $('#successModal').removeClass('hidden').addClass('flex');
     
        window.history.replaceState({}, document.title, window.location.pathname);
    }

 
    $('#closeSuccessModal').click(function(){
        $('#successModal').removeClass('flex').addClass('hidden');
    });
});


$('.delete-btn').click(function(){
    const id = $(this).data('id');
    if(confirm('Are you sure you want to delete this package?')){
        $.ajax({
            url: 'delete_package.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(response){
                if(response.status === 'success'){
                    alert('Package deleted successfully!');
                    
                    const table = $('#packagesTable').DataTable();
                    table.row($(this).parents('tr')).remove().draw();
                } else {
                    alert('Error: ' + response.message);
                }
            }.bind(this),
            error: function(){
                alert('An error occurred while deleting the package.');
            }
        });
    }
});


</script>


</body>
</html>
