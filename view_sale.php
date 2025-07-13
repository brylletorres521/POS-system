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

// Check if sale ID is provided
if (!isset($_GET['id'])) {
    header("Location: sales.php");
    exit();
}

$sale_id = $_GET['id'];

// Get sale information
$query = "SELECT s.*, u.name as user_name FROM sales s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: sales.php");
    exit();
}

$sale = $result->fetch_assoc();

// Get sale items
$query = "SELECT si.*, p.name as product_name, p.barcode FROM sale_items si 
          JOIN products p ON si.product_id = p.id 
          WHERE si.sale_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale_items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sale #<?php echo $sale['invoice_no']; ?> - POS System</title>
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
                            <a class="nav-link" href="pos.php">
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
                            <a class="nav-link active" href="sales.php">
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
                    <h1 class="h2">Sale Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="print_receipt.php?id=<?php echo $sale_id; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-print me-1"></i> Print Receipt
                            </a>
                            <a href="sales.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> All Sales
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Sale Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Invoice Number:</th>
                                        <td><?php echo $sale['invoice_no']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date:</th>
                                        <td><?php echo date('d M Y H:i', strtotime($sale['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Cashier:</th>
                                        <td><?php echo $sale['user_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Customer:</th>
                                        <td><?php echo !empty($sale['customer_name']) ? $sale['customer_name'] : 'Walk-in Customer'; ?></td>
                                    </tr>
                                    <?php if (!empty($sale['customer_phone'])): ?>
                                    <tr>
                                        <th>Phone:</th>
                                        <td><?php echo $sale['customer_phone']; ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Payment Method:</th>
                                        <td><?php echo ucfirst($sale['payment_method']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Status:</th>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            switch ($sale['payment_status']) {
                                                case 'paid':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'partial':
                                                    $status_class = 'bg-info';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($sale['payment_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Payment Summary</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="35%">Subtotal:</th>
                                        <td>$<?php echo number_format($sale['total_amount'] + $sale['discount'] - $sale['tax'], 2); ?></td>
                                    </tr>
                                    <?php if ($sale['discount'] > 0): ?>
                                    <tr>
                                        <th>Discount:</th>
                                        <td>$<?php echo number_format($sale['discount'], 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($sale['tax'] > 0): ?>
                                    <tr>
                                        <th>Tax:</th>
                                        <td>$<?php echo number_format($sale['tax'], 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th>Total:</th>
                                        <td><strong>$<?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Paid Amount:</th>
                                        <td>$<?php echo number_format($sale['paid_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Change:</th>
                                        <td>$<?php echo number_format($sale['change_amount'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Sale Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Barcode</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $i = 1;
                                    $total = 0;
                                    while ($item = $sale_items->fetch_assoc()): 
                                        $total += $item['subtotal'];
                                    ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo $item['product_name']; ?></td>
                                        <td><?php echo $item['barcode']; ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-end">Total:</th>
                                        <th class="text-end">$<?php echo number_format($total, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html> 