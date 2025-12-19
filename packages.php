<?php
session_start();
require_once 'db_connect.php'; 

$result = $conn->query("SELECT * FROM packages WHERE status='Active' ORDER BY created_at DESC");

$flash_message = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages - B-Dental Care</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap (for badge) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { poppins: ['Poppins', 'sans-serif'] }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-poppins min-h-screen flex flex-col">

<?php include 'header.php'; ?>

<main class="max-w-6xl mx-auto p-6">
    <h1 class="text-3xl font-bold text-center text-blue-700 mb-6">Our Packages</h1>

    <?php if ($result->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="bg-white shadow-lg rounded-lg p-5 flex flex-col justify-between">

                    <div>
                        <h2 class="text-xl font-semibold mb-2">
                            <?php echo htmlspecialchars($row['package_name']); ?>
                        </h2>

                        <p class="text-gray-700 mb-2">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </p>

                        <?php 
                        // Decode inclusions
                        $incs = json_decode($row['inclusions'], true);
                        if (!is_array($incs)) {
                            $incs = preg_split("/\r\n|\n|,/", $row['inclusions']);
                        }

                        $incs = array_map('trim', $incs);
                        $incs = array_filter($incs);

                        if (!empty($incs)): ?>
                            <p class="font-semibold text-gray-800 mt-3 mb-1">Inclusions:</p>
                            <ul class="list-disc pl-5 mb-3 text-gray-700">
                                <?php foreach ($incs as $inc): ?>
                                    <li><?php echo htmlspecialchars($inc); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <p class="font-semibold text-lg">
                            Price:
                            <?php echo $row['price'] ? "₱" . number_format($row['price'], 2) : "Free"; ?>
                        </p>
                    </div>

                    <!-- Button + Duration -->
                    <div class="mt-4 text-center">

                        <a href="cart.php?package_id=<?php echo $row['id']; ?>"
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded flex items-center justify-center space-x-2 mb-2">
                            <i class="fas fa-cart-plus"></i>
                            <span>Add to Cart</span>
                                                </a>
                       <?php if (!empty($row['duration_minutes']) && $row['duration_minutes'] > 0): ?>
                        <p class="flex items-center gap-2 text-gray-700 mt-2 text-sm">
                            <i class="fas fa-clock"></i>
                            This package takes about:  <?php echo (int)$row['duration_minutes']; ?> minutes
                        </p>
                    <?php endif; ?>
                    </div>

                </div>
            <?php endwhile; ?>

        </div>
    <?php else: ?>
        <p class="text-center text-gray-500 mt-6">No packages available at the moment.</p>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

<?php if ($flash_message): ?>
<div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-80 relative">
        <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>

        <div class="flex items-center space-x-3">
            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            <p class="font-semibold text-gray-700">
                <?php echo htmlspecialchars($flash_message); ?>
            </p>
        </div>

        <div class="mt-4 flex justify-end">
            <button onclick="closeModal()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function closeModal() {
    const modal = document.getElementById('cartModal');
    if (modal) modal.remove();
}

setTimeout(() => {
    closeModal();
}, 3000);
</script>
<?php endif; ?>

</body>
</html>
