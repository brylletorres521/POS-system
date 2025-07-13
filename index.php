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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
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
                            <a class="nav-link active" href="index.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-calendar me-1"></i> Today: <?php echo date('d M Y'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Content -->
                <div class="row">
                    <?php
                    // Get total products
                    $query = "SELECT COUNT(*) as total FROM products";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    $total_products = $row['total'];

                    // Get total sales today
                    $today = date('Y-m-d');
                    $query = "SELECT COUNT(*) as total, SUM(total_amount) as amount FROM sales WHERE DATE(created_at) = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $today);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $total_sales_today = $row['total'] ?? 0;
                    $total_amount_today = $row['amount'] ?? 0;

                    // Get low stock products
                    $query = "SELECT COUNT(*) as total FROM products WHERE quantity < 10";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    $low_stock = $row['total'];
                    ?>

                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Total Products</h6>
                                        <h1 class="display-4"><?php echo $total_products; ?></h1>
                                    </div>
                                    <i class="fas fa-box fa-3x"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="products.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-angle-right text-white"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Today's Sales</h6>
                                        <h1 class="display-4"><?php echo $total_sales_today; ?></h1>
                                        <p>$<?php echo number_format($total_amount_today, 2); ?></p>
                                    </div>
                                    <i class="fas fa-cash-register fa-3x"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="sales.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-angle-right text-white"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase">Low Stock Items</h6>
                                        <h1 class="display-4"><?php echo $low_stock; ?></h1>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="products.php?filter=low_stock" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-angle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales Table -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Sales</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Invoice #</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Payment</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Get recent sales
                                            $query = "SELECT s.*, u.name as user_name FROM sales s 
                                                      JOIN users u ON s.user_id = u.id 
                                                      ORDER BY s.created_at DESC LIMIT 10";
                                            $result = $conn->query($query);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo '<tr>';
                                                    echo '<td>' . $row['invoice_no'] . '</td>';
                                                    echo '<td>' . ($row['customer_name'] ? $row['customer_name'] : 'Walk-in Customer') . '</td>';
                                                    echo '<td>$' . number_format($row['total_amount'], 2) . '</td>';
                                                    echo '<td>' . ucfirst($row['payment_method']) . '</td>';
                                                    echo '<td>' . date('d M Y H:i', strtotime($row['created_at'])) . '</td>';
                                                    echo '<td>
                                                            <a href="view_sale.php?id=' . $row['id'] . '" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="print_receipt.php?id=' . $row['id'] . '" class="btn btn-sm btn-secondary">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                          </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">No sales found</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
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
</body>
</html> 