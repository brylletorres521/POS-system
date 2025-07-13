<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/db.php';

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$categories = $conn->query($query);

// Get products
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.name";
$products = $conn->query($query);

// Process sale
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $customer_name = $_POST['customer_name'] ?? null;
    $customer_phone = $_POST['customer_phone'] ?? null;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $payment_status = $_POST['payment_status'] ?? 'paid';
    $subtotal = $_POST['subtotal'];
    $discount = $_POST['discount'] ?? 0;
    $tax = $_POST['tax'] ?? 0;
    $total_amount = $_POST['total'];
    $paid_amount = $_POST['paid_amount'];
    $change_amount = $_POST['change_amount'];
    $cart_items = json_decode($_POST['cart_items'], true);
    
    if (empty($cart_items)) {
        $error = "Cart is empty. Cannot process sale.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Generate invoice number
            $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Insert sale
            $query = "INSERT INTO sales (invoice_no, customer_name, customer_phone, total_amount, discount, tax, 
                      paid_amount, change_amount, payment_method, payment_status, user_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssddddsssi", $invoice_no, $customer_name, $customer_phone, $total_amount, 
                             $discount, $tax, $paid_amount, $change_amount, $payment_method, $payment_status, $user_id);
            $stmt->execute();
            
            // Get sale ID
            $sale_id = $conn->insert_id;
            
            // Insert sale items and update inventory
            foreach ($cart_items as $item) {
                $product_id = $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $subtotal = $price * $quantity;
                
                // Insert sale item
                $query = "INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iidd", $sale_id, $product_id, $quantity, $price, $subtotal);
                $stmt->execute();
                
                // Update product inventory
                $query = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to receipt page
            header("Location: print_receipt.php?id=" . $sale_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error processing sale: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h3 class="text-light">POS System</h3>
                        <p class="text-light"><?php echo $user['name']; ?> (<?php echo ucfirst($user['role']); ?>)</p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="pos.php">
                                <i class="fas fa-cash-register me-2"></i> Point of Sale
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box me-2"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fas fa-tags me-2"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sales.php">
                                <i class="fas fa-chart-line me-2"></i> Sales
                            </a>
                        </li>
                        <?php if ($user['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mt-5">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Point of Sale</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearCart">
                                <i class="fas fa-trash me-1"></i> Clear Cart
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Products Section -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Products</h5>
                                <div class="input-group" style="max-width: 300px;">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Barcode Scanner -->
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                        <input type="text" class="form-control" id="barcodeInput" placeholder="Scan barcode or enter product code...">
                                    </div>
                                </div>
                                
                                <!-- Categories Filter -->
                                <div class="mb-3 d-flex flex-wrap">
                                    <button class="btn btn-outline-primary me-2 mb-2 category-filter active" data-category="all">
                                        All Categories
                                    </button>
                                    <?php while ($category = $categories->fetch_assoc()): ?>
                                    <button class="btn btn-outline-primary me-2 mb-2 category-filter" data-category="<?php echo $category['id']; ?>">
                                        <?php echo $category['name']; ?>
                                    </button>
                                    <?php endwhile; ?>
                                </div>
                                
                                <!-- Products Grid -->
                                <div class="row">
                                    <?php while ($product = $products->fetch_assoc()): ?>
                                    <div class="col-md-3 col-sm-6 product-item" data-category="<?php echo $product['category_id']; ?>">
                                        <div class="card h-100">
                                            <div class="product-image">
                                                <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                                <?php else: ?>
                                                <i class="fas fa-box fa-3x text-secondary"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-info">
                                                <h6 class="product-name"><?php echo $product['name']; ?></h6>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                                    <button class="btn btn-sm btn-primary add-to-cart" 
                                                            data-id="<?php echo $product['id']; ?>" 
                                                            data-name="<?php echo $product['name']; ?>" 
                                                            data-price="<?php echo $product['price']; ?>">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted"><?php echo $product['quantity']; ?> in stock</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Section -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Shopping Cart <span class="badge bg-primary" id="cartItemCount">0</span></h5>
                            </div>
                            <div class="card-body p-0">
                                <!-- Cart Items -->
                                <div id="cartItems" style="max-height: 400px; overflow-y: auto;">
                                    <div class="text-center py-4">Cart is empty</div>
                                </div>
                                
                                <!-- Cart Summary -->
                                <div class="cart-summary p-3 border-top">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span>$<span id="cartSubtotal">0.00</span></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <span>Discount:</span>
                                            <div class="input-group input-group-sm ms-2" style="width: 100px;">
                                                <input type="number" class="form-control" id="discountAmount" min="0" step="0.01" placeholder="0.00">
                                                <button class="btn btn-outline-secondary" type="button" id="applyDiscount">Apply</button>
                                            </div>
                                        </div>
                                        <span>$<span id="cartDiscount">0.00</span></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tax:</span>
                                        <span>$<span id="cartTax">0.00</span></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="fw-bold">Total:</span>
                                        <span class="cart-total">$<span id="cartTotal">0.00</span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <form id="paymentForm" method="POST" action="">
                                    <!-- Hidden fields for cart data -->
                                    <input type="hidden" name="subtotal" id="inputSubtotal" value="0.00">
                                    <input type="hidden" name="discount" id="inputDiscount" value="0.00">
                                    <input type="hidden" name="tax" id="inputTax" value="0.00">
                                    <input type="hidden" name="total" id="inputTotal" value="0.00">
                                    <input type="hidden" name="change_amount" id="inputChange" value="0.00">
                                    <input type="hidden" name="cart_items" id="inputCartItems" value="[]">
                                    
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Walk-in Customer">
                                    </div>
                                    <div class="mb-3">
                                        <label for="customer_phone" class="form-label">Customer Phone</label>
                                        <input type="text" class="form-control" id="customer_phone" name="customer_phone">
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method</label>
                                        <select class="form-select" id="payment_method" name="payment_method">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_status" class="form-label">Payment Status</label>
                                        <select class="form-select" id="payment_status" name="payment_status">
                                            <option value="paid">Paid</option>
                                            <option value="pending">Pending</option>
                                            <option value="partial">Partial</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="paidAmount" class="form-label">Amount Paid</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="paidAmount" name="paid_amount" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Change</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control" id="changeDisplay" value="0.00" readonly>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="button" id="processPayment" class="btn btn-success btn-lg">
                                            <i class="fas fa-money-bill-wave me-2"></i> Process Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize cart
            renderCart();
            updateCartSummary();
        });
    </script>
</body>
</html> 