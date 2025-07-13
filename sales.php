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

// Set default date range (last 7 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

// Check if date range is provided
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// Get sales for the date range
$query = "SELECT s.*, u.name as user_name FROM sales s 
          JOIN users u ON s.user_id = u.id 
          WHERE DATE(s.created_at) BETWEEN ? AND ? 
          ORDER BY s.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$sales = $stmt->get_result();

// Get sales summary
$query = "SELECT 
            COUNT(*) as total_sales,
            SUM(total_amount) as total_amount,
            SUM(discount) as total_discount,
            SUM(tax) as total_tax
          FROM sales 
          WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
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
                    <h1 class="h2">Sales History</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="pos.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-cash-register me-1"></i> New Sale
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-6">
                                <label for="daterange" class="form-label">Date Range</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="text" class="form-control date-range-picker" id="daterange" name="daterange" 
                                           value="<?php echo date('Y-m-d', strtotime($start_date)); ?> - <?php echo date('Y-m-d', strtotime($end_date)); ?>">
                                    <input type="hidden" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                                    <input type="hidden" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase">Total Sales</h6>
                                <h1 class="display-4"><?php echo $summary['total_sales']; ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase">Total Revenue</h6>
                                <h1 class="display-4">$<?php echo number_format($summary['total_amount'], 2); ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase">Total Discount</h6>
                                <h1 class="display-4">$<?php echo number_format($summary['total_discount'], 2); ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase">Total Tax</h6>
                                <h1 class="display-4">$<?php echo number_format($summary['total_tax'], 2); ?></h1>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sales List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Cashier</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($sales->num_rows > 0): ?>
                                        <?php while ($sale = $sales->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $sale['invoice_no']; ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($sale['created_at'])); ?></td>
                                            <td><?php echo !empty($sale['customer_name']) ? $sale['customer_name'] : 'Walk-in Customer'; ?></td>
                                            <td><?php echo $sale['user_name']; ?></td>
                                            <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                            <td><?php echo ucfirst($sale['payment_method']); ?></td>
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
                                            <td>
                                                <a href="view_sale.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="print_receipt.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No sales found for the selected date range.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize date range picker
            $('#daterange').daterangepicker({
                startDate: moment('<?php echo $start_date; ?>'),
                endDate: moment('<?php echo $end_date; ?>'),
                locale: {
                    format: 'YYYY-MM-DD'
                }
            }, function(start, end) {
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
            });
        });
    </script>
</body>
</html> 