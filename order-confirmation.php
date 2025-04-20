<?php
require_once 'db.php';

// Redirect to home if no order information
if (!isset($_SESSION['order'])) {
    header('Location: index.php');
    exit();
}

$order = $_SESSION['order'];

// Count items in cart
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Fresh Market</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="img/logo.png" alt="Logo">
                    <h1>Fresh Market</h1>
                </a>
            </div>
            <nav>
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                    <span class="count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>
    </header>

    <main>
        <div class="order-confirmation">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="confirmation-title">Order Completed!</h2>
            <p class="confirmation-message">Order details have been sent to your email.</p>
            
            <div class="order-details">
                <div class="detail-row">
                    <div class="detail-label">Order Number:</div>
                    <div class="detail-value"><?php echo $order['order_id']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Order Date:</div>
                    <div class="detail-value"><?php echo date('F d, Y H:i', strtotime($order['date'])); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Amount:</div>
                    <div class="detail-value">$<?php echo number_format($order['total'], 2); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Shipping Address:</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($order['customer']['address']); ?>, 
                        <?php echo htmlspecialchars($order['customer']['city']); ?>, 
                        <?php echo htmlspecialchars($order['customer']['state']); ?>, 
                        <?php echo htmlspecialchars($order['customer']['zipcode']); ?>
                    </div>
                </div>
            </div>
            
            <div class="order-items">
                <h3>Ordered Items</h3>
                <div class="order-items-list">
                    <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></div>
                        <div class="item-price">$<?php echo number_format($item['subtotal'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <a href="index.php" class="button button-primary home-button">Return to Home</a>
        </div>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Fresh Market</h3>
                <p>Providing fresh food at reasonable prices.</p>
                <p>Customer Service: 1-800-000-0000</p>
                <p>Email: help@freshmarket.com</p>
            </div>
            <div class="footer-section">
                <h3>Shopping Guide</h3>
                <a href="#">Shipping Information</a>
                <a href="#">Returns & Refunds</a>
                <a href="#">FAQ</a>
            </div>
            <div class="footer-section">
                <h3>Company Info</h3>
                <a href="#">About Us</a>
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            &copy; 2024 Fresh Market. All rights reserved.
        </div>
    </footer>

    <script>
    // Delete order information from session after confirmation (prevent refresh)
    window.addEventListener('beforeunload', function() {
        fetch('clear-order.php');
    });
    </script>
</body>
</html> 