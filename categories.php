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

// Delete category
if (isset($_POST['delete_category']) && isset($_POST['category_id'])) {
    $category_id = $_POST['category_id'];
    
    // Check if category has products
    $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        $message = "Cannot delete category. It has associated products.";
        $message_type = "danger";
    } else {
        $delete_query = "DELETE FROM categories WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $category_id);
        
        if ($delete_stmt->execute()) {
            $message = "Category deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Error deleting category: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Add/Edit category
if (isset($_POST['save_category'])) {
    $category_id = $_POST['category_id'] ?? null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    // Validate inputs
    if (empty($name)) {
        $message = "Category name is required.";
        $message_type = "danger";
    } else {
        // Check if category name exists (for new categories or when changing name)
        $name_check_query = "SELECT id FROM categories WHERE name = ? AND id != ?";
        $name_check_stmt = $conn->prepare($name_check_query);
        $name_check_stmt->bind_param("si", $name, $category_id);
        $name_check_stmt->execute();
        $name_check_result = $name_check_stmt->get_result();
        
        if ($name_check_result->num_rows > 0) {
            $message = "Category name already exists. Please use a different name.";
            $message_type = "danger";
        } else {
            if ($category_id) {
                // Update existing category
                $query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssi", $name, $description, $category_id);
                
                if ($stmt->execute()) {
                    $message = "Category updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error updating category: " . $conn->error;
                    $message_type = "danger";
                }
            } else {
                // Add new category
                $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $name, $description);
                
                if ($stmt->execute()) {
                    $message = "Category added successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error adding category: " . $conn->error;
                    $message_type = "danger";
                }
            }
        }
    }
}

// Get categories with product counts
$query = "SELECT c.*, COUNT(p.id) as product_count FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$result = $conn->query($query);
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - POS System</title>
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
                            <a class="nav-link active" href="categories.php">
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
                    <h1 class="h2">Categories Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus me-1"></i> Add New Category
                        </button>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $category['product_count']; ?></span>
                                        <?php if ($category['product_count'] > 0): ?>
                                            <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary ms-1">View</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-category" 
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>"
                                                data-bs-toggle="modal" data-bs-target="#categoryModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-category" 
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-count="<?php echo $category['product_count']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No categories found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="category_id">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name*</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter category name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter category description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_category" class="btn btn-primary">Save Category</button>
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
                    <p>Are you sure you want to delete the category: <span id="delete_category_name"></span>?</p>
                    <div id="category_has_products" class="alert alert-warning d-none">
                        This category has <span id="product_count"></span> products associated with it. You cannot delete it until you reassign or delete these products.
                    </div>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST">
                        <input type="hidden" name="category_id" id="delete_category_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_category" class="btn btn-danger" id="confirm_delete">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit category
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('categoryModal');
                modal.querySelector('.modal-title').textContent = 'Edit Category';
                
                document.getElementById('category_id').value = this.dataset.id;
                document.getElementById('name').value = this.dataset.name;
                document.getElementById('description').value = this.dataset.description;
            });
        });
        
        // Handle add category
        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('.modal-title').textContent = 'Add New Category';
            this.querySelector('form').reset();
            document.getElementById('category_id').value = '';
        });
        
        // Handle delete category
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_category_id').value = this.dataset.id;
                document.getElementById('delete_category_name').textContent = this.dataset.name;
                
                const productCount = parseInt(this.dataset.count);
                if (productCount > 0) {
                    document.getElementById('category_has_products').classList.remove('d-none');
                    document.getElementById('product_count').textContent = productCount;
                    document.getElementById('confirm_delete').disabled = true;
                } else {
                    document.getElementById('category_has_products').classList.add('d-none');
                    document.getElementById('confirm_delete').disabled = false;
                }
            });
        });
    </script>
</body>
</html> 