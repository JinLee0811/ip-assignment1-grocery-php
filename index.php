<?php
require_once 'db.php';

// Database connection
$host = 'localhost';
$dbname = 'grocery_shop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch categories and subcategories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$subcategories = $pdo->query("SELECT * FROM subcategories ORDER BY category_id, name")->fetchAll();

// Fetch products with their category and subcategory information
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, s.name as subcategory_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    ORDER BY p.product_name
");
$products = $stmt->fetchAll();

// Handle cart operations
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    // Calculate total quantity including current cart items
    $current_cart_quantity = 0;
    if (isset($_SESSION['cart'][$product_id])) {
        $current_cart_quantity = $_SESSION['cart'][$product_id];
    }
    $total_quantity = $current_cart_quantity + $quantity;
    
    // Check if total quantity exceeds available stock
    if ($total_quantity <= $product['in_stock']) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $_SESSION['message'] = "Product added to cart successfully!";
    } else {
        $_SESSION['error'] = "Insufficient stock. Current stock: " . $product['in_stock'] . " units";
    }
    
    // Redirect back to the same product section
    header('Location: ' . $_SERVER['REQUEST_URI'] . '#product-' . $product_id);
    exit();
}

// Search functionality
$search_results = [];
$is_search = false;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, s.name as subcategory_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        WHERE p.product_name LIKE ? OR p.unit_quantity LIKE ? 
        ORDER BY p.product_name
    ");
    $stmt->execute([$search, $search]);
    $search_results = $stmt->fetchAll();
    $is_search = true;
}

// Count items in cart
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}

// Display stock error message if exists
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Grocery Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="js/script.js"></script>
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
            <div class="search-box">
                <form method="get" action="index.php">
                    <input type="text" name="search" placeholder="Search products by name or description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <button type="button" class="clear-search"><i class="fas fa-times"></i></button>
                    <?php endif; ?>
                    <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
                </form>
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

    <section class="categories">
        <div class="category-nav">
            <div class="categories">
                <button class="category-item active" data-category="all">All Products</button>
                <?php foreach ($categories as $category): ?>
                    <button class="category-item" data-category="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="subcategories">
                <?php foreach ($categories as $category): ?>
                    <div class="subcategory-group" data-category="<?php echo $category['id']; ?>" style="display: none;">
                        <?php
                        $category_subcategories = array_filter($subcategories, function($sub) use ($category) {
                            return $sub['category_id'] == $category['id'];
                        });
                        foreach ($category_subcategories as $subcategory):
                        ?>
                            <button class="subcategory-item" data-category="<?php echo $category['id']; ?>" 
                                    data-subcategory="<?php echo $subcategory['id']; ?>">
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <main>
        <?php if ($is_search): ?>
            <h2 class="page-title">Search Results for '<?php echo htmlspecialchars($_GET['search']); ?>'</h2>
            
            <?php if (empty($search_results)): ?>
                <p>No results found. Please try a different search term.</p>
            <?php else: ?>
                <div class="products-container">
                    <div class="products-grid">
                        <?php foreach ($search_results as $product): ?>
                            <div class="product-card" data-category="<?php echo $product['category_id']; ?>" data-subcategory="<?php echo $product['subcategory_id']; ?>" id="product-<?php echo $product['id']; ?>">
                                <div class="product-image">
                                    <img src="img/products/<?php echo $product['id']; ?>.jpg" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='img/no-image.png'">
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    <p class="price">$<?php echo number_format($product['unit_price'], 2); ?></p>
                                    <p class="unit"><?php echo htmlspecialchars($product['unit_quantity']); ?></p>
                                    
                                    <?php if ($product['in_stock'] > 0): ?>
                                        <p class="stock in-stock">In Stock: <?php echo $product['in_stock']; ?> units</p>
                                        <form method="post" class="add-to-cart-form">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="quantity-input">
                                                <label for="quantity-<?php echo $product['id']; ?>">Quantity:</label>
                                                <input type="number" id="quantity-<?php echo $product['id']; ?>" 
                                                    name="quantity" value="1" min="1" max="<?php echo $product['in_stock']; ?>">
                                            </div>
                                            <button type="submit" name="add_to_cart">Add to Cart</button>
                                        </form>
                                    <?php else: ?>
                                        <p class="stock out-of-stock">Out of Stock</p>
                                        <button disabled>Add to Cart</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <h2 class="page-title">Fresh Products for You</h2>
            
            <div class="products-container">
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-category="<?php echo $product['category_id']; ?>" data-subcategory="<?php echo $product['subcategory_id']; ?>" id="product-<?php echo $product['id']; ?>">
                            <div class="product-image">
                                <img src="img/products/<?php echo $product['id']; ?>.jpg" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='img/no-image.png'">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="price">$<?php echo number_format($product['unit_price'], 2); ?></p>
                                <p class="unit"><?php echo htmlspecialchars($product['unit_quantity']); ?></p>
                                
                                <?php if ($product['in_stock'] > 0): ?>
                                    <p class="stock in-stock">In Stock: <?php echo $product['in_stock']; ?> units</p>
                                    <form method="post" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="quantity-input">
                                            <label for="quantity-<?php echo $product['id']; ?>">Quantity:</label>
                                            <input type="number" id="quantity-<?php echo $product['id']; ?>" 
                                                name="quantity" value="1" min="1" max="<?php echo $product['in_stock']; ?>">
                                        </div>
                                        <button type="submit" name="add_to_cart">Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <p class="stock out-of-stock">Out of Stock</p>
                                    <button disabled>Add to Cart</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Cart Floating Popup -->
    <div class="cart-popup">
        <div class="cart-popup-header">
            <div class="cart-popup-title">Shopping Cart</div>
            <button class="close-cart"><i class="fas fa-times"></i></button>
        </div>
        <div class="cart-popup-items">
            <!-- Will be loaded dynamically with JavaScript -->
        </div>
        <div class="cart-popup-footer">
            <div class="cart-popup-total">
                <span>Total:</span>
                <span class="total-amount">$0.00</span>
            </div>
            <div class="cart-popup-buttons">
                <a href="cart.php" class="button button-primary">View Cart</a>
                <button class="button button-secondary clear-cart">Clear</button>
            </div>
        </div>
    </div>

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
        // Category filtering
        const categoryButtons = document.querySelectorAll('.category-item');
        const productCards = document.querySelectorAll('.product-card');
        const subcategoryGroups = document.querySelectorAll('.subcategory-group');
        
        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Update active state
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const selectedCategory = this.dataset.category;
                
                // Show/hide subcategories
                subcategoryGroups.forEach(group => {
                    if (selectedCategory === 'all' || group.dataset.category === selectedCategory) {
                        group.style.display = 'flex';
                    } else {
                        group.style.display = 'none';
                    }
                });
                
                // Filter products
                productCards.forEach(card => {
                    if (selectedCategory === 'all' || card.dataset.category === selectedCategory) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        // Subcategory filtering
        const subcategoryButtons = document.querySelectorAll('.subcategory-item');
        
        subcategoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                const selectedCategory = this.dataset.category;
                const selectedSubcategory = this.dataset.subcategory;
                
                // Update active state
                subcategoryButtons.forEach(btn => {
                    if (btn.dataset.category === selectedCategory) {
                        btn.classList.remove('active');
                    }
                });
                this.classList.add('active');
                
                // Filter products
                productCards.forEach(card => {
                    if (card.dataset.category === selectedCategory && 
                        card.dataset.subcategory === selectedSubcategory) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        // Cart popup
        const cartIcon = document.querySelector('.cart-icon');
        const cartPopup = document.querySelector('.cart-popup');
        const closeCart = document.querySelector('.close-cart');
        
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            cartPopup.classList.toggle('active');
            
            // Load cart items
            loadCartItems();
        });
        
        closeCart.addEventListener('click', function() {
            cartPopup.classList.remove('active');
        });
        
        // Load cart popup items
        function loadCartItems() {
            const cartItemsContainer = document.querySelector('.cart-popup-items');
            const totalAmountElement = document.querySelector('.total-amount');
            
            // Get cart data using AJAX
            fetch('cart.php?action=get_cart_data')
                .then(response => response.json())
                .then(data => {
                    cartItemsContainer.innerHTML = '';
                    let totalAmount = 0;
                    
                    if (data.items.length === 0) {
                        cartItemsContainer.innerHTML = '<p>Your cart is empty.</p>';
                    } else {
                        data.items.forEach(item => {
                            const itemElement = document.createElement('div');
                            itemElement.className = 'cart-popup-item';
                            itemElement.innerHTML = `
                                <img src="img/products/${item.product_id}.jpg" alt="${item.name}" onerror="this.src='img/no-image.png'">
                                <div class="cart-popup-details">
                                    <h4>${item.name}</h4>
                                    <p class="cart-popup-price">$${item.price.toFixed(2)}</p>
                                    <p class="cart-popup-quantity">Qty: ${item.quantity}</p>
                                </div>
                            `;
                            cartItemsContainer.appendChild(itemElement);
                            
                            totalAmount += item.price * item.quantity;
                        });
                    }
                    
                    totalAmountElement.textContent = `$${totalAmount.toFixed(2)}`;
                })
                .catch(error => {
                    console.error('Error loading cart data:', error);
                    cartItemsContainer.innerHTML = '<p>Failed to load cart information.</p>';
                });
        }
        
        // Clear cart
        const clearCartButton = document.querySelector('.clear-cart');
        clearCartButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                fetch('cart.php?action=clear_cart')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadCartItems();
                            // Update cart icon count
                            const cartCount = document.querySelector('.cart-icon .count');
                            if (cartCount) {
                                cartCount.remove();
                            }
                        }
                    });
            }
        });
    });
    </script>
</body>
</html> 