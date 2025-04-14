<?php
require_once 'db.php';

// Cart clear API
if (isset($_GET['action']) && $_GET['action'] === 'clear_cart') {
    $_SESSION['cart'] = [];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// Cart data API
if (isset($_GET['action']) && $_GET['action'] === 'get_cart_data') {
    $cart_items = [];
    $total_amount = 0;
    
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $cart_items[] = [
                    'product_id' => $product_id,
                    'name' => $product['product_name'],
                    'price' => $product['unit_price'],
                    'quantity' => $quantity,
                    'subtotal' => $product['unit_price'] * $quantity
                ];
                $total_amount += $product['unit_price'] * $quantity;
            }
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'items' => $cart_items,
        'total' => $total_amount
    ]);
    exit();
}

// Remove item from cart
if (isset($_POST['remove_item'])) {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        // Save cart state before deletion for potential recovery
        $_SESSION['previous_cart'] = $_SESSION['cart'];
        $_SESSION['cart_restore_time'] = time();
        
        unset($_SESSION['cart'][$product_id]);
    }
    header('Location: cart.php');
    exit();
}

// Update cart item quantity
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = max(1, intval($_POST['quantity']));
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    header('Location: cart.php');
    exit();
}

// Restore cart
if (isset($_POST['restore_cart']) && isset($_SESSION['previous_cart']) && isset($_SESSION['cart_restore_time'])) {
    // Only allow recovery within 10 minutes
    if (time() - $_SESSION['cart_restore_time'] <= 600) {
        $_SESSION['cart'] = $_SESSION['previous_cart'];
        unset($_SESSION['previous_cart']);
        unset($_SESSION['cart_restore_time']);
    }
    header('Location: cart.php');
    exit();
}

// Clear cart
if (isset($_POST['clear_cart'])) {
    // Save cart state before clearing for potential recovery
    $_SESSION['previous_cart'] = $_SESSION['cart'];
    $_SESSION['cart_restore_time'] = time();
    
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit();
}

// Get cart item information
$cart_items = [];
$total_amount = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            $cart_items[] = [
                'product_id' => $product_id,
                'name' => $product['product_name'],
                'price' => $product['unit_price'],
                'quantity' => $quantity,
                'unit' => $product['unit_quantity'],
                'subtotal' => $product['unit_price'] * $quantity,
                'in_stock' => $product['in_stock']
            ];
            $total_amount += $product['unit_price'] * $quantity;
        }
    }
}

// Count items in cart
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}

// Database connection
$host = 'localhost';
$dbname = 'grocery_shop';
$username = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    } elseif (isset($_POST['remove'])) {
        $product_id = $_POST['product_id'];
        unset($_SESSION['cart'][$product_id]);
    }
    
    // Redirect to prevent form resubmission
    header('Location: cart.php');
    exit();
}

// Calculate cart totals
$total_items = 0;
$total_price = 0;
$cart_items = [];

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            $subtotal = $product['price'] * $quantity;
            $total_items += $quantity;
            $total_price += $subtotal;
            
            $cart_items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Fresh Market</title>
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
                <a href="cart.php" class="cart-icon active">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                    <span class="count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>
    </header>

    <main>
        <h2 class="page-title">Shopping Cart</h2>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-4x"></i>
                <p>Your cart is empty.</p>
                <a href="index.php" class="button button-primary">Continue Shopping</a>
                
                <?php if (isset($_SESSION['previous_cart']) && !empty($_SESSION['previous_cart']) && isset($_SESSION['cart_restore_time'])): ?>
                    <?php $time_left = 600 - (time() - $_SESSION['cart_restore_time']); ?>
                    <?php if ($time_left > 0): ?>
                        <div class="restore-cart-section">
                            <p>You can restore your previous cart. (Time remaining: <?php echo floor($time_left / 60); ?> min <?php echo $time_left % 60; ?> sec)</p>
                            <form method="post">
                                <button type="submit" name="restore_cart" class="button button-secondary">Restore Cart</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="img/products/<?php echo $item['product_id']; ?>.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='img/no-image.png'">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                <p class="unit"><?php echo htmlspecialchars($item['unit']); ?></p>
                                <p class="item-subtotal">Subtotal: $<?php echo number_format($item['subtotal'], 2); ?></p>
                            </div>
                            <div class="cart-actions">
                                <form method="post" class="quantity-form">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['in_stock']; ?>">
                                    <button type="submit" name="update_quantity">Update</button>
                                </form>
                                <form method="post" class="remove-form">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" name="remove_item"><i class="fas fa-trash-alt"></i> Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Items:</span>
                        <span><?php echo $cart_count; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    
                    <div class="cart-buttons">
                        <form method="post" class="clear-cart-form">
                            <button type="submit" name="clear_cart" class="button button-danger">Clear Cart</button>
                        </form>
                        <a href="index.php" class="button button-secondary">Continue Shopping</a>
                        <a href="checkout.php" class="button button-primary">Checkout</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Fresh Market</h3>
                <p>Providing fresh products at reasonable prices.</p>
                <p>Customer Service: 1-800-FRESH</p>
                <p>Email: help@freshmarket.com</p>
            </div>
            <div class="footer-section">
                <h3>Shopping Guide</h3>
                <a href="#">Delivery Information</a>
                <a href="#">Returns & Exchanges</a>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Timer setup (for cart recovery)
        <?php if (isset($_SESSION['cart_restore_time']) && !empty($_SESSION['previous_cart'])): ?>
        const restoreSection = document.querySelector('.restore-cart-section');
        if (restoreSection) {
            let timeLeft = <?php echo 600 - (time() - $_SESSION['cart_restore_time']); ?>;
            
            const updateTimer = () => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                const messageElement = restoreSection.querySelector('p');
                messageElement.textContent = `You can restore your previous cart. (Time remaining: ${minutes} min ${seconds} sec)`;
                
                if (timeLeft <= 0) {
                    restoreSection.remove();
                    clearInterval(timerInterval);
                }
                
                timeLeft--;
            };
            
            const timerInterval = setInterval(updateTimer, 1000);
        }
        <?php endif; ?>
    });
    </script>
</body>
</html> 