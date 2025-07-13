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

// Process form submissions
$message = '';
$message_type = '';

// Delete product
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    
    // Check if product exists in sales
    $check_query = "SELECT COUNT(*) as count FROM sale_items WHERE product_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        $message = "Cannot delete product. It is associated with sales records.";
        $message_type = "danger";
    } else {
        $delete_query = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $product_id);
        
        if ($delete_stmt->execute()) {
            $message = "Product deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Error deleting product: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Add/Edit product
if (isset($_POST['save_product'])) {
    $product_id = $_POST['product_id'] ?? null;
    $barcode = $_POST['barcode'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $cost = $_POST['cost'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];
    
    // Validate inputs
    if (empty($name) || empty($price) || empty($quantity)) {
        $message = "Please fill all required fields.";
        $message_type = "danger";
    } else {
        // Check if barcode exists (for new products or when changing barcode)
        $barcode_check_query = "SELECT id FROM products WHERE barcode = ? AND id != ?";
        $barcode_check_stmt = $conn->prepare($barcode_check_query);
        $barcode_check_stmt->bind_param("si", $barcode, $product_id);
        $barcode_check_stmt->execute();
        $barcode_check_result = $barcode_check_stmt->get_result();
        
        if (!empty($barcode) && $barcode_check_result->num_rows > 0) {
            $message = "Barcode already exists. Please use a different barcode.";
            $message_type = "danger";
        } else {
            // Handle image upload
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = 'assets/uploads/products/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Check if image file is a actual image
                $check = getimagesize($_FILES['image']['tmp_name']);
                if ($check !== false) {
                    // Check file size (max 5MB)
                    if ($_FILES['image']['size'] > 5000000) {
                        $message = "Image file is too large. Max 5MB allowed.";
                        $message_type = "danger";
                    } else {
                        // Allow certain file formats
                        if ($file_type == "jpg" || $file_type == "png" || $file_type == "jpeg" || $file_type == "gif") {
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                                $image_path = $target_file;
                            } else {
                                $message = "Error uploading image.";
                                $message_type = "danger";
                            }
                        } else {
                            $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
                            $message_type = "danger";
                        }
                    }
                } else {
                    $message = "Uploaded file is not an image.";
                    $message_type = "danger";
                }
            }
            
            // If no errors, proceed with database operation
            if (empty($message)) {
                if ($product_id) {
                    // Update existing product
                    if ($image_path) {
                        $query = "UPDATE products SET barcode = ?, name = ?, description = ?, price = ?, cost = ?, quantity = ?, category_id = ?, image = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssddisis", $barcode, $name, $description, $price, $cost, $quantity, $category_id, $image_path, $product_id);
                    } else {
                        $query = "UPDATE products SET barcode = ?, name = ?, description = ?, price = ?, cost = ?, quantity = ?, category_id = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssddisi", $barcode, $name, $description, $price, $cost, $quantity, $category_id, $product_id);
                    }
                    
                    if ($stmt->execute()) {
                        $message = "Product updated successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Error updating product: " . $conn->error;
                        $message_type = "danger";
                    }
                } else {
                    // Add new product
                    $query = "INSERT INTO products (barcode, name, description, price, cost, quantity, category_id, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssddiss", $barcode, $name, $description, $price, $cost, $quantity, $category_id, $image_path);
                    
                    if ($stmt->execute()) {
                        $message = "Product added successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Error adding product: " . $conn->error;
                        $message_type = "danger";
                    }
                }
            }
        }
    }
}

// Get categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get products with category names
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

if ($filter == 'low_stock') {
    $query .= " AND p.quantity < 10";
}

if (!empty($search)) {
    $search_param = "%$search%";
    $query .= " AND (p.name LIKE ? OR p.barcode LIKE ? OR p.description LIKE ?)";
}

$query .= " ORDER BY p.name";

$stmt = $conn->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - POS System</title>
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
                            <a class="nav-link active" href="products.php">
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
                    <h1 class="h2">Products Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                            <i class="fas fa-plus me-1"></i> Add New Product
                        </button>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                            <?php if (!empty($search) || $filter): ?>
                                <a href="products.php" class="btn btn-outline-secondary ms-2">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="btn-group">
                            <a href="products.php" class="btn btn-outline-secondary <?php echo empty($filter) ? 'active' : ''; ?>">All</a>
                            <a href="products.php?filter=low_stock" class="btn btn-outline-danger <?php echo $filter == 'low_stock' ? 'active' : ''; ?>">Low Stock</a>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Barcode</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Cost</th>
                                <th>Quantity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="50" height="50" class="img-thumbnail">
                                        <?php else: ?>
                                            <img src="assets/img/no-image.png" alt="No Image" width="50" height="50" class="img-thumbnail">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['barcode'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td>$<?php echo number_format($product['cost'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $product['quantity'] < 10 ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo $product['quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-product" 
                                                data-id="<?php echo $product['id']; ?>"
                                                data-barcode="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>"
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($product['description'] ?? ''); ?>"
                                                data-price="<?php echo $product['price']; ?>"
                                                data-cost="<?php echo $product['cost']; ?>"
                                                data-quantity="<?php echo $product['quantity']; ?>"
                                                data-category="<?php echo $product['category_id']; ?>"
                                                data-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                                                data-bs-toggle="modal" data-bs-target="#productModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-product" 
                                                data-id="<?php echo $product['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="product_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="barcode" class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="barcode" name="barcode" placeholder="Enter barcode (optional)">
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name*</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter product description"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="price" class="form-label">Selling Price*</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="cost" class="form-label">Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Quantity*</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="0" placeholder="0" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div id="current_image_container" class="mt-2 d-none">
                                    <small>Current image:</small>
                                    <div id="current_image"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_product" class="btn btn-primary">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the product: <span id="delete_product_name"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST">
                        <input type="hidden" name="product_id" id="delete_product_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit product
        document.querySelectorAll('.edit-product').forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('productModal');
                modal.querySelector('.modal-title').textContent = 'Edit Product';
                
                document.getElementById('product_id').value = this.dataset.id;
                document.getElementById('barcode').value = this.dataset.barcode;
                document.getElementById('name').value = this.dataset.name;
                document.getElementById('description').value = this.dataset.description;
                document.getElementById('price').value = this.dataset.price;
                document.getElementById('cost').value = this.dataset.cost;
                document.getElementById('quantity').value = this.dataset.quantity;
                document.getElementById('category_id').value = this.dataset.category;
                
                // Show current image if exists
                const currentImageContainer = document.getElementById('current_image_container');
                const currentImage = document.getElementById('current_image');
                if (this.dataset.image) {
                    currentImageContainer.classList.remove('d-none');
                    currentImage.innerHTML = `<img src="${this.dataset.image}" alt="Current Image" class="img-thumbnail" style="max-height: 100px">`;
                } else {
                    currentImageContainer.classList.add('d-none');
                    currentImage.innerHTML = '';
                }
            });
        });
        
        // Handle add product
        document.getElementById('productModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('.modal-title').textContent = 'Add New Product';
            this.querySelector('form').reset();
            document.getElementById('product_id').value = '';
            document.getElementById('current_image_container').classList.add('d-none');
        });
        
        // Handle delete product
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_product_id').value = this.dataset.id;
                document.getElementById('delete_product_name').textContent = this.dataset.name;
            });
        });
    </script>
</body>
</html> 