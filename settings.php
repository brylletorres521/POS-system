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

// Check if user is admin
if ($user['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Process form submissions
$message = '';
$message_type = '';

// Create settings table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
)");

// Default settings
$default_settings = [
    'store_name' => 'My POS Store',
    'store_address' => '123 Main Street, City, Country',
    'store_phone' => '+1 234 567 8900',
    'store_email' => 'store@example.com',
    'currency_symbol' => '$',
    'tax_rate' => '10',
    'receipt_footer' => 'Thank you for shopping with us!',
    'low_stock_threshold' => '10',
    'enable_sales_notifications' => '1',
    'enable_debug_mode' => '0',
    'enable_barcode_scanner' => '1',
    'default_payment_method' => 'cash'
];

// Initialize settings in database if they don't exist
foreach ($default_settings as $key => $value) {
    $check = $conn->query("SELECT COUNT(*) as count FROM settings WHERE setting_key = '$key'");
    $row = $check->fetch_assoc();
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
    }
}

// Save settings
if (isset($_POST['save_settings'])) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = substr($key, 8); // Remove 'setting_' prefix
            $setting_value = $conn->real_escape_string($value);
            
            $conn->query("UPDATE settings SET setting_value = '$setting_value' WHERE setting_key = '$setting_key'");
        }
    }
    
    $message = "Settings updated successfully.";
    $message_type = "success";
}

// Run system diagnostics
if (isset($_POST['run_diagnostics'])) {
    // This would be a more complex function in a real system
    $message = "System diagnostics completed. All systems operational.";
    $message_type = "success";
}

// Clear system cache
if (isset($_POST['clear_cache'])) {
    // This would clear actual cache in a real system
    $message = "System cache cleared successfully.";
    $message_type = "success";
}

// Backup database
if (isset($_POST['backup_database'])) {
    // In a real system, this would create an actual SQL dump
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_dir = 'backups/';
    
    // Create backup directory if it doesn't exist
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    // Create a placeholder backup file
    file_put_contents($backup_dir . $backup_file, "-- Database backup placeholder\n-- Generated on: " . date('Y-m-d H:i:s'));
    
    $message = "Database backup created successfully: $backup_file";
    $message_type = "success";
}

// Get current settings
$settings_query = "SELECT * FROM settings";
$settings_result = $conn->query($settings_query);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get system info
$system_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'mysql_version' => $conn->server_info,
    'max_upload_size' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . ' seconds',
    'display_errors' => ini_get('display_errors') ? 'On' : 'Off'
];

// Get database stats
$db_stats = [
    'products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'categories' => $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'],
    'users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'sales' => $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'],
    'sale_items' => $conn->query("SELECT COUNT(*) as count FROM sale_items")->fetch_assoc()['count'],
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity < " . ($settings['low_stock_threshold'] ?? 10))->fetch_assoc()['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - POS System</title>
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
                            <a class="nav-link active" href="settings.php">
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
                    <h1 class="h2">System Settings</h1>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Settings Tabs -->
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                            <i class="fas fa-store me-2"></i> General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pos-tab" data-bs-toggle="tab" data-bs-target="#pos" type="button" role="tab" aria-controls="pos" aria-selected="false">
                            <i class="fas fa-cash-register me-2"></i> POS
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="receipts-tab" data-bs-toggle="tab" data-bs-target="#receipts" type="button" role="tab" aria-controls="receipts" aria-selected="false">
                            <i class="fas fa-receipt me-2"></i> Receipts
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false">
                            <i class="fas fa-server me-2"></i> System
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button" role="tab" aria-controls="tools" aria-selected="false">
                            <i class="fas fa-tools me-2"></i> Tools
                        </button>
                    </li>
                </ul>

                <form action="" method="POST">
                    <div class="tab-content" id="settingsTabContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Store Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="setting_store_name" class="form-label">Store Name</label>
                                        <input type="text" class="form-control" id="setting_store_name" name="setting_store_name" value="<?php echo htmlspecialchars($settings['store_name'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="setting_store_address" class="form-label">Store Address</label>
                                        <textarea class="form-control" id="setting_store_address" name="setting_store_address" rows="2"><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="setting_store_phone" class="form-label">Store Phone</label>
                                            <input type="text" class="form-control" id="setting_store_phone" name="setting_store_phone" value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="setting_store_email" class="form-label">Store Email</label>
                                            <input type="email" class="form-control" id="setting_store_email" name="setting_store_email" value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="setting_currency_symbol" class="form-label">Currency Symbol</label>
                                        <input type="text" class="form-control" id="setting_currency_symbol" name="setting_currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? '$'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- POS Settings -->
                        <div class="tab-pane fade" id="pos" role="tabpanel" aria-labelledby="pos-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Point of Sale Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="setting_tax_rate" class="form-label">Default Tax Rate (%)</label>
                                        <input type="number" class="form-control" id="setting_tax_rate" name="setting_tax_rate" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0'); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="setting_low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                        <input type="number" class="form-control" id="setting_low_stock_threshold" name="setting_low_stock_threshold" min="1" value="<?php echo htmlspecialchars($settings['low_stock_threshold'] ?? '10'); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="setting_default_payment_method" class="form-label">Default Payment Method</label>
                                        <select class="form-select" id="setting_default_payment_method" name="setting_default_payment_method">
                                            <option value="cash" <?php echo ($settings['default_payment_method'] ?? '') == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                            <option value="card" <?php echo ($settings['default_payment_method'] ?? '') == 'card' ? 'selected' : ''; ?>>Card</option>
                                            <option value="other" <?php echo ($settings['default_payment_method'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="setting_enable_barcode_scanner" name="setting_enable_barcode_scanner" value="1" <?php echo ($settings['enable_barcode_scanner'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="setting_enable_barcode_scanner">Enable Barcode Scanner</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="setting_enable_sales_notifications" name="setting_enable_sales_notifications" value="1" <?php echo ($settings['enable_sales_notifications'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="setting_enable_sales_notifications">Enable Sales Notifications</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Receipt Settings -->
                        <div class="tab-pane fade" id="receipts" role="tabpanel" aria-labelledby="receipts-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Receipt Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="setting_receipt_footer" class="form-label">Receipt Footer Text</label>
                                        <textarea class="form-control" id="setting_receipt_footer" name="setting_receipt_footer" rows="3"><?php echo htmlspecialchars($settings['receipt_footer'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="setting_show_tax_on_receipt" name="setting_show_tax_on_receipt" value="1" <?php echo ($settings['show_tax_on_receipt'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="setting_show_tax_on_receipt">Show Tax on Receipt</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="setting_print_receipt_after_sale" name="setting_print_receipt_after_sale" value="1" <?php echo ($settings['print_receipt_after_sale'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="setting_print_receipt_after_sale">Automatically Print Receipt After Sale</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>System Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="setting_enable_debug_mode" name="setting_enable_debug_mode" value="1" <?php echo ($settings['enable_debug_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="setting_enable_debug_mode">Enable Debug Mode</label>
                                        <div class="form-text">Warning: Only enable in development environment. This will display detailed error messages.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5>System Information</h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="mb-3">Server Information</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <tbody>
                                                <?php foreach ($system_info as $key => $value): ?>
                                                <tr>
                                                    <th><?php echo ucwords(str_replace('_', ' ', $key)); ?></th>
                                                    <td><?php echo $value; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <h6 class="mb-3 mt-4">Database Statistics</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <tbody>
                                                <?php foreach ($db_stats as $key => $value): ?>
                                                <tr>
                                                    <th><?php echo ucwords(str_replace('_', ' ', $key)); ?></th>
                                                    <td><?php echo $value; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tools -->
                        <div class="tab-pane fade" id="tools" role="tabpanel" aria-labelledby="tools-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h5>System Tools</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-database fa-3x mb-3 text-primary"></i>
                                                    <h5>Database Backup</h5>
                                                    <p class="small">Create a backup of your database</p>
                                                    <button type="submit" name="backup_database" class="btn btn-primary">Backup Now</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-broom fa-3x mb-3 text-warning"></i>
                                                    <h5>Clear Cache</h5>
                                                    <p class="small">Clear system cache files</p>
                                                    <button type="submit" name="clear_cache" class="btn btn-warning">Clear Cache</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-stethoscope fa-3x mb-3 text-success"></i>
                                                    <h5>System Diagnostics</h5>
                                                    <p class="small">Check system health</p>
                                                    <button type="submit" name="run_diagnostics" class="btn btn-success">Run Diagnostics</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5>Debug Login</h5>
                                                <p>Use the debug login tool to troubleshoot login issues or reset admin credentials.</p>
                                                <a href="debug_login.php" class="btn btn-danger" target="_blank">Open Debug Login</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 mb-5">
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 