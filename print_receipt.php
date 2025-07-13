<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/db.php';

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
$query = "SELECT si.*, p.name as product_name FROM sale_items si 
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
    <title>Receipt #<?php echo $sale['invoice_no']; ?> - POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Courier New', Courier, monospace;
        }
        .receipt {
            max-width: 400px;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-header h2 {
            margin-bottom: 5px;
        }
        .receipt-body {
            margin-bottom: 20px;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .receipt-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .receipt-table th, .receipt-table td {
            padding: 5px;
        }
        .receipt-table th {
            text-align: left;
        }
        .receipt-table td.amount {
            text-align: right;
        }
        .receipt-total {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 10px;
        }
        .buttons {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            .buttons, .no-print {
                display: none;
            }
            body {
                background-color: white;
            }
            .receipt {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt">
            <div class="receipt-header">
                <h2>POS System</h2>
                <p>123 Main Street, City, Country</p>
                <p>Phone: (123) 456-7890</p>
                <p>Email: info@posystem.com</p>
                <hr>
                <h4>RECEIPT</h4>
                <p>Invoice #: <?php echo $sale['invoice_no']; ?></p>
                <p>Date: <?php echo date('d M Y H:i', strtotime($sale['created_at'])); ?></p>
                <p>Cashier: <?php echo $sale['user_name']; ?></p>
                <?php if (!empty($sale['customer_name'])): ?>
                <p>Customer: <?php echo $sale['customer_name']; ?></p>
                <?php if (!empty($sale['customer_phone'])): ?>
                <p>Phone: <?php echo $sale['customer_phone']; ?></p>
                <?php endif; ?>
                <?php else: ?>
                <p>Customer: Walk-in Customer</p>
                <?php endif; ?>
            </div>
            
            <div class="receipt-body">
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th class="amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        while ($item = $sale_items->fetch_assoc()): 
                            $subtotal += $item['subtotal'];
                        ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td class="amount">$<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="receipt-total">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($sale['discount'] > 0): ?>
                    <div class="d-flex justify-content-between">
                        <span>Discount:</span>
                        <span>$<?php echo number_format($sale['discount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($sale['tax'] > 0): ?>
                    <div class="d-flex justify-content-between">
                        <span>Tax:</span>
                        <span>$<?php echo number_format($sale['tax'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span>$<?php echo number_format($sale['total_amount'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Paid (<?php echo ucfirst($sale['payment_method']); ?>):</span>
                        <span>$<?php echo number_format($sale['paid_amount'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Change:</span>
                        <span>$<?php echo number_format($sale['change_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="receipt-footer">
                <p>Payment Status: <?php echo ucfirst($sale['payment_status']); ?></p>
                <p>Thank you for your purchase!</p>
                <p>Please come again</p>
            </div>
        </div>
        
        <div class="buttons">
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print me-2"></i> Print Receipt
            </button>
            <a href="pos.php" class="btn btn-secondary">
                <i class="fas fa-cash-register me-2"></i> Back to POS
            </a>
            <a href="sales.php" class="btn btn-info">
                <i class="fas fa-list me-2"></i> View All Sales
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html> 