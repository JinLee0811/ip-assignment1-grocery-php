<?php
require_once 'db.php';

// Categories and subcategories data (should be from database in real implementation)
$categories = [
    ['id' => 1, 'name' => 'Vegetables/Fruits'],
    ['id' => 2, 'name' => 'Meat/Seafood'],
    ['id' => 3, 'name' => 'Frozen Foods'],
    ['id' => 4, 'name' => 'Beverages/Snacks'],
    ['id' => 5, 'name' => 'Household Items']
];

$subcategories = [
    1 => [
        ['id' => 101, 'name' => 'Vegetables'],
        ['id' => 102, 'name' => 'Fruits'],
        ['id' => 103, 'name' => 'Salads']
    ],
    2 => [
        ['id' => 201, 'name' => 'Beef'],
        ['id' => 202, 'name' => 'Pork'],
        ['id' => 203, 'name' => 'Fish']
    ],
    3 => [
        ['id' => 301, 'name' => 'Frozen Meals'],
        ['id' => 302, 'name' => 'Ice Cream']
    ],
    4 => [
        ['id' => 401, 'name' => 'Drinks'],
        ['id' => 402, 'name' => 'Snacks'],
        ['id' => 403, 'name' => 'Bakery']
    ],
    5 => [
        ['id' => 501, 'name' => 'Cleaning Products'],
        ['id' => 502, 'name' => 'Paper Products']
    ]
];

// Add category_id to each product for filtering
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll();

// Add category_id to products based on name/type (in a real application, this would be in the database)
foreach ($products as $key => $product) {
    // Assign category based on product name/description
    $name = strtolower($product['product_name']);
    
    if (strpos($name, 'apple') !== false || strpos($name, 'banana') !== false || 
        strpos($name, 'orange') !== false || strpos($name, 'fruit') !== false ||
        strpos($name, 'vegetable') !== false) {
        $products[$key]['category_id'] = 1;
    } elseif (strpos($name, 'beef') !== false || strpos($name, 'chicken') !== false || 
              strpos($name, 'pork') !== false || strpos($name, 'fish') !== false) {
        $products[$key]['category_id'] = 2;
    } elseif (strpos($name, 'frozen') !== false || strpos($name, 'ice cream') !== false) {
        $products[$key]['category_id'] = 3;
    } elseif (strpos($name, 'drink') !== false || strpos($name, 'soda') !== false || 
              strpos($name, 'snack') !== false || strpos($name, 'cookie') !== false) {
        $products[$key]['category_id'] = 4;
    } else {
        $products[$key]['category_id'] = 5; // Default to household items
    }
}

// Search functionality
$search_results = [];
$is_search = false;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_name LIKE ? OR unit_quantity LIKE ? ORDER BY product_name");
    $stmt->execute([$search, $search]);
    $search_results = $stmt->fetchAll();
    
    // Add category_id to search results as well
    foreach ($search_results as $key => $product) {
        $name = strtolower($product['product_name']);
        
        if (strpos($name, 'apple') !== false || strpos($name, 'banana') !== false || 
            strpos($name, 'orange') !== false || strpos($name, 'fruit') !== false ||
            strpos($name, 'vegetable') !== false) {
            $search_results[$key]['category_id'] = 1;
        } elseif (strpos($name, 'beef') !== false || strpos($name, 'chicken') !== false || 
                strpos($name, 'pork') !== false || strpos($name, 'fish') !== false) {
            $search_results[$key]['category_id'] = 2;
        } elseif (strpos($name, 'frozen') !== false || strpos($name, 'ice cream') !== false) {
            $search_results[$key]['category_id'] = 3;
        } elseif (strpos($name, 'drink') !== false || strpos($name, 'soda') !== false || 
                strpos($name, 'snack') !== false || strpos($name, 'cookie') !== false) {
            $search_results[$key]['category_id'] = 4;
        } else {
            $search_results[$key]['category_id'] = 5; // Default to household items
        }
    }
    
    $is_search = true;
}

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
    
    // Check stock
    $stmt = $pdo->prepare("SELECT in_stock FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product && $product['in_stock'] >= $quantity) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        // Stay on the current page instead of redirecting to cart
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
}

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
                <img src="img/logo.png" alt="Logo">
                <h1>Fresh Market</h1>
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
                <a href="index.php">Home</a>
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
        <div class="category-list">
            <div class="category-item active">All Products</div>
            <?php foreach ($categories as $category): ?>
            <div class="category-item" data-category="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="subcategories">
            <!-- Subcategories will be loaded dynamically with JavaScript -->
        </div>
    </section>

    <main>
        <?php if ($is_search): ?>
            <h2 class="page-title">Search Results for '<?php echo htmlspecialchars($_GET['search']); ?>'</h2>
            
            <?php if (empty($search_results)): ?>
                <p>No results found. Please try a different search term.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($search_results as $product): ?>
                        <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                            <div class="product-image">
                                <img src="img/products/<?php echo $product['product_id']; ?>.jpg" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='img/no-image.png'">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="price">$<?php echo number_format($product['unit_price'], 2); ?></p>
                                <p class="unit"><?php echo htmlspecialchars($product['unit_quantity']); ?></p>
                                
                                <?php if ($product['in_stock'] > 0): ?>
                                    <p class="stock in-stock">In Stock: <?php echo $product['in_stock']; ?> units</p>
                                    <form method="post" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <div class="quantity-input">
                                            <label for="quantity-<?php echo $product['product_id']; ?>">Quantity:</label>
                                            <input type="number" id="quantity-<?php echo $product['product_id']; ?>" 
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
            <?php endif; ?>
            
        <?php else: ?>
            <h2 class="page-title">Fresh Products for You</h2>
            
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                        <div class="product-image">
                            <img src="img/products/<?php echo $product['product_id']; ?>.jpg" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='img/no-image.png'">
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="price">$<?php echo number_format($product['unit_price'], 2); ?></p>
                            <p class="unit"><?php echo htmlspecialchars($product['unit_quantity']); ?></p>
                            
                            <?php if ($product['in_stock'] > 0): ?>
                                <p class="stock in-stock">In Stock: <?php echo $product['in_stock']; ?> units</p>
                                <form method="post" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <div class="quantity-input">
                                        <label for="quantity-<?php echo $product['product_id']; ?>">Quantity:</label>
                                        <input type="number" id="quantity-<?php echo $product['product_id']; ?>" 
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
        const categoryItems = document.querySelectorAll('.category-item');
        const productCards = document.querySelectorAll('.product-card');
        const subcategoriesContainer = document.querySelector('.subcategories');
        
        categoryItems.forEach(item => {
            item.addEventListener('click', function() {
                // Toggle active class
                categoryItems.forEach(cat => cat.classList.remove('active'));
                this.classList.add('active');
                
                const categoryId = this.dataset.category;
                
                // If "All Products" is selected
                if (!categoryId) {
                    productCards.forEach(card => card.style.display = 'block');
                    subcategoriesContainer.innerHTML = '';
                    return;
                }
                
                // Filter by category
                productCards.forEach(card => {
                    if (card.dataset.category === categoryId) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show subcategories
                loadSubcategories(categoryId);
            });
        });
        
        // Load subcategories function
        function loadSubcategories(categoryId) {
            // In a real application, this data would come from an AJAX request
            const subcategories = {
                '1': [
                    {id: 101, name: 'Vegetables'},
                    {id: 102, name: 'Fruits'},
                    {id: 103, name: 'Salads'}
                ],
                '2': [
                    {id: 201, name: 'Beef'},
                    {id: 202, name: 'Pork'},
                    {id: 203, name: 'Fish'}
                ],
                '3': [
                    {id: 301, name: 'Frozen Meals'},
                    {id: 302, name: 'Ice Cream'}
                ],
                '4': [
                    {id: 401, name: 'Drinks'},
                    {id: 402, name: 'Snacks'},
                    {id: 403, name: 'Bakery'}
                ],
                '5': [
                    {id: 501, name: 'Cleaning Products'},
                    {id: 502, name: 'Paper Products'}
                ]
            };
            
            subcategoriesContainer.innerHTML = '';
            
            if (subcategories[categoryId]) {
                subcategories[categoryId].forEach(subcat => {
                    const subcatElement = document.createElement('div');
                    subcatElement.className = 'subcategory-item';
                    subcatElement.textContent = subcat.name;
                    subcatElement.dataset.subcategory = subcat.id;
                    
                    subcatElement.addEventListener('click', function() {
                        const subcatItems = document.querySelectorAll('.subcategory-item');
                        subcatItems.forEach(item => item.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Additional filtering logic could be added here
                    });
                    
                    subcategoriesContainer.appendChild(subcatElement);
                });
            }
        }
        
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