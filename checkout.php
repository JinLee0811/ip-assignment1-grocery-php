<?php
require_once 'db.php';

// Redirect to home if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Get cart item information
$cart_items = [];
$total_amount = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Check stock
        if ($product['in_stock'] < $quantity) {
            $_SESSION['stock_error'] = "Not enough stock for '{$product['product_name']}'. Current stock: {$product['in_stock']}";
            header('Location: cart.php');
            exit();
        }
        
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

// Process shipping information form
$name = $email = $phone = $address = $city = $state = $zipcode = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $errors['name'] = 'Please enter your name.';
    }
    
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    $phone = trim($_POST['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = 'Please enter your phone number.';
    } elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $errors['phone'] = 'Please enter a valid Australian phone number (10 digits).';
    }
    
    $address = trim($_POST['address'] ?? '');
    if (empty($address)) {
        $errors['address'] = 'Please enter your address.';
    }
    
    $city = trim($_POST['city'] ?? '');
    if (empty($city)) {
        $errors['city'] = 'Please enter your city.';
    }
    
    $state = trim($_POST['state'] ?? '');
    if (empty($state)) {
        $errors['state'] = 'Please select your state/province.';
    }
    
    $zipcode = trim($_POST['zipcode'] ?? '');
    if (empty($zipcode)) {
        $errors['zipcode'] = 'Please enter your ZIP code.';
    }
    
    // Check stock again
    $stock_error = false;
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product && $product['in_stock'] < $quantity) {
            $errors['stock'] = "Not enough stock for '{$product['product_name']}'. Current stock: {$product['in_stock']}";
            $stock_error = true;
            break;
        }
    }
    
    // Process order if no errors
    if (empty($errors)) {
        // 1. Save order information (would be stored in DB in production)
        $order_id = 'ORD' . time() . rand(1000, 9999);
        
        // 2. Update stock
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $pdo->prepare("UPDATE products SET in_stock = in_stock - ? WHERE product_id = ?");
            $stmt->execute([$quantity, $product_id]);
        }
        
        // 3. Save order information in session (would be stored in DB in production)
        $_SESSION['order'] = [
            'order_id' => $order_id,
            'items' => $cart_items,
            'total' => $total_amount,
            'customer' => [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zipcode' => $zipcode
            ],
            'date' => date('Y-m-d H:i:s')
        ];
        
        // 4. Clear cart
        $_SESSION['cart'] = [];
        
        // 5. Redirect to order confirmation page
        header('Location: order-confirmation.php');
        exit();
    }
}

// Calculate total items in cart
$cart_count = 0;
foreach ($_SESSION['cart'] as $quantity) {
    $cart_count += $quantity;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Fresh Market</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">
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
        <h2 class="page-title">Shipping Information</h2>
        
        <div class="checkout-container">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></div>
                        <div class="item-price">$<?php echo number_format($item['subtotal'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-total">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($total_amount, 2); ?></span>
                </div>
            </div>
            
            <div class="delivery-form">
                <h3 class="form-title">Shipping Details</h3>
                
                <?php if (isset($errors['stock'])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors['stock']; ?>
                    <a href="cart.php" class="button button-primary">Return to Cart</a>
                </div>
                <?php endif; ?>
                
                <form method="post" action="checkout.php" id="checkout-form">
                    <div class="form-group">
                        <label for="name" class="required">Name</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'input-error' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
                        <?php if (isset($errors['name'])): ?>
                        <div class="error-message"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="required">Email</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'input-error' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <?php if (isset($errors['email'])): ?>
                            <div class="error-message"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="required">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'input-error' : ''; ?>" id="phone" name="phone" placeholder="0400 000 000" value="<?php echo htmlspecialchars($phone); ?>">
                            <?php if (isset($errors['phone'])): ?>
                            <div class="error-message"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="required">Address</label>
                        <input type="text" class="form-control <?php echo isset($errors['address']) ? 'input-error' : ''; ?>" id="address" name="address" placeholder="Street address" value="<?php echo htmlspecialchars($address); ?>">
                        <?php if (isset($errors['address'])): ?>
                        <div class="error-message"><?php echo $errors['address']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city" class="required">City</label>
                            <input type="text" class="form-control <?php echo isset($errors['city']) ? 'input-error' : ''; ?>" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>">
                            <?php if (isset($errors['city'])): ?>
                            <div class="error-message"><?php echo $errors['city']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="state" class="required">State</label>
                            <select class="form-control <?php echo isset($errors['state']) ? 'input-error' : ''; ?>" id="state" name="state">
                                <option value="">Select</option>
                                <option value="NSW" <?php echo $state === 'NSW' ? 'selected' : ''; ?>>New South Wales</option>
                                <option value="VIC" <?php echo $state === 'VIC' ? 'selected' : ''; ?>>Victoria</option>
                                <option value="QLD" <?php echo $state === 'QLD' ? 'selected' : ''; ?>>Queensland</option>
                                <option value="WA" <?php echo $state === 'WA' ? 'selected' : ''; ?>>Western Australia</option>
                                <option value="SA" <?php echo $state === 'SA' ? 'selected' : ''; ?>>South Australia</option>
                                <option value="TAS" <?php echo $state === 'TAS' ? 'selected' : ''; ?>>Tasmania</option>
                                <option value="ACT" <?php echo $state === 'ACT' ? 'selected' : ''; ?>>Australian Capital Territory</option>
                                <option value="NT" <?php echo $state === 'NT' ? 'selected' : ''; ?>>Northern Territory</option>
                            </select>
                            <?php if (isset($errors['state'])): ?>
                            <div class="error-message"><?php echo $errors['state']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="zipcode" class="required">Postcode</label>
                            <input type="text" class="form-control <?php echo isset($errors['zipcode']) ? 'input-error' : ''; ?>" id="zipcode" name="zipcode" placeholder="2000" value="<?php echo htmlspecialchars($zipcode); ?>">
                            <?php if (isset($errors['zipcode'])): ?>
                            <div class="error-message"><?php echo $errors['zipcode']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Delivery Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="cart.php" class="button button-secondary">Return to Cart</a>
                        <button type="submit" class="button button-primary" id="submit-order">Place Order</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Fresh Market</h3>
                <p>We provide fresh food at reasonable prices.</p>
                <p>Customer Service: 1588-0000</p>
                <p>Email: help@freshmarket.com</p>
            </div>
            <div class="footer-section">
                <h3>Shopping Guide</h3>
                <a href="#">Delivery Information</a>
                <a href="#">Exchange and Refunds</a>
                <a href="#">FAQ</a>
            </div>
            <div class="footer-section">
                <h3>Company Information</h3>
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
        const form = document.getElementById('checkout-form');
        const submitButton = document.getElementById('submit-order');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Form field validation
            const required = form.querySelectorAll('[required]');
            required.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('input-error');
                } else {
                    field.classList.remove('input-error');
                }
            });
            
            // Email validation
            const email = document.getElementById('email');
            if (email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                isValid = false;
                email.classList.add('input-error');
            }
            
            // Phone number validation
            const phone = document.getElementById('phone');
            if (phone.value.trim() && !/^[0-9]{10}$/.test(phone.value.trim())) {
                isValid = false;
                phone.classList.add('input-error');
            }
            
            if (!isValid) {
                e.preventDefault();
                window.scrollTo(0, 0);
            } else {
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';
            }
        });
    });
    </script>
</body>
</html> 