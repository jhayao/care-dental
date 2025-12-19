<?php
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add item to cart
if (isset($_GET['service_id']) || isset($_GET['package_id'])) {

    if (isset($_GET['service_id'])) {
        $id = intval($_GET['service_id']);
        $type = 'service';
        $redirect = 'services.php'; // redirect for services
    } else {
        $id = intval($_GET['package_id']);
        $type = 'package';
        $redirect = 'packages.php'; // redirect for packages
    }

    // Avoid duplicates
    $exists = false;
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item) && $item['type'] === $type && $item['id'] === $id) {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $_SESSION['cart'][] = ['type' => $type, 'id' => $id];
        // Set flash message
        $_SESSION['flash_message'] = ucfirst($type) . " added to cart!";
    } else {
        $_SESSION['flash_message'] = ucfirst($type) . " is already in your cart.";
    }

    // Redirect to the appropriate page
    header("Location: $redirect");
    exit;
}
?>
