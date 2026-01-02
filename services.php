<?php
session_start();
require_once 'db_connect.php';

// Fetch active services
$stmt = $conn->prepare("SELECT * FROM services WHERE status='Active' ORDER BY created_at DESC");
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Flash message
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
    <title>Services - B-Dental Care</title>

    <link href="./assets/css/main.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>


</head>

<body class="bg-gray-50 font-poppins min-h-screen flex flex-col">

<?php include 'header.php'; ?>

<main class="py-16 px-2 max-w-6xl mx-auto flex-1">
    <h1 class="text-3xl font-bold text-blue-700 mb-8 text-center">Our Services</h1>

    <?php if (count($services) > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">
            <?php foreach ($services as $service): ?>
                <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 text-center w-[330px] mx-auto">

                    <img
                        src="<?php echo htmlspecialchars($service['service_image']); ?>"
                        alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                        class="w-full h-56 object-cover"
                    >

                    <div class="p-6">
                        <h2 class="text-2xl font-semibold text-blue-600 mb-2">
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </h2>

                        <p class="text-gray-600 mt-2 mb-4">
                            <?php echo htmlspecialchars($service['description']); ?>
                        </p>

                        <?php if (!empty($service['price'])): ?>
                            <p class="font-bold text-green-600 mb-2">
                                ₱<?php echo number_format($service['price'], 2); ?>
                            </p>
                        <?php endif; ?>

                    

                        <span class="badge bg-info text-dark mb-3">
                            <i class="fas fa-clock me-1"></i>Duration time of this service takes:
                            <?php echo (int)$service['duration_minutes']; ?> minutes
                        </span>


                        <p class="text-gray-400 text-sm mb-4">
                            Posted on: <?php echo date('M d, Y', strtotime($service['created_at'])); ?>
                        </p>

                        <a href="cart.php?service_id=<?php echo $service['id']; ?>"
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded flex items-center justify-center space-x-2">
                            <i class="fas fa-cart-plus"></i>
                            <span>Add to Cart</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-500">No services found.</p>
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
