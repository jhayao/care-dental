<?php
session_start();

// Check if cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if index is provided
if (isset($_GET['index'])) {
    $index = intval($_GET['index']);

    // Remove item from cart if exists
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);

        // Re-index the array to avoid gaps
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}

// Redirect back to cart page
header("Location: view_cart.php");
exit;
?>
