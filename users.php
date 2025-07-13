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

// Delete user
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $delete_user_id = $_POST['user_id'];
    
    // Prevent deleting yourself
    if ($delete_user_id == $user_id) {
        $message = "You cannot delete your own account.";
        $message_type = "danger";
    } else {
        // Check if user has associated sales
        $check_query = "SELECT COUNT(*) as count FROM sales WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $delete_user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['count'] > 0) {
            $message = "Cannot delete user. They have associated sales records.";
            $message_type = "danger";
        } else {
            $delete_query = "DELETE FROM users WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $delete_user_id);
            
            if ($delete_stmt->execute()) {
                $message = "User deleted successfully.";
                $message_type = "success";
            } else {
                $message = "Error deleting user: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Add/Edit user
if (isset($_POST['save_user'])) {
    $edit_user_id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($name) || empty($role)) {
        $message = "Please fill all required fields.";
        $message_type = "danger";
    } else {
        // Check if username exists (for new users or when changing username)
        $username_check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
        $username_check_stmt = $conn->prepare($username_check_query);
        $username_check_stmt->bind_param("si", $username, $edit_user_id);
        $username_check_stmt->execute();
        $username_check_result = $username_check_stmt->get_result();
        
        if ($username_check_result->num_rows > 0) {
            $message = "Username already exists. Please choose a different username.";
            $message_type = "danger";
        } else {
            // For new user or password change, validate password
            $password_required = empty($edit_user_id) || !empty($password);
            
            if ($password_required && (empty($password) || strlen($password) < 6)) {
                $message = "Password must be at least 6 characters long.";
                $message_type = "danger";
            } elseif ($password_required && $password !== $confirm_password) {
                $message = "Passwords do not match.";
                $message_type = "danger";
            } else {
                if ($edit_user_id) {
                    // Update existing user
                    if (!empty($password)) {
                        // Update with new password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE users SET username = ?, password = ?, name = ?, role = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssssi", $username, $hashed_password, $name, $role, $edit_user_id);
                    } else {
                        // Update without changing password
                        $query = "UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssi", $username, $name, $role, $edit_user_id);
                    }
                    
                    if ($stmt->execute()) {
                        $message = "User updated successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Error updating user: " . $conn->error;
                        $message_type = "danger";
                    }
                } else {
                    // Add new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssss", $username, $hashed_password, $name, $role);
                    
                    if ($stmt->execute()) {
                        $message = "User added successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Error adding user: " . $conn->error;
                        $message_type = "danger";
                    }
                }
            }
        }
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY username";
$result = $conn->query($query);
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - POS System</title>
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
                            <a class="nav-link active" href="users.php">
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
                    <h1 class="h2">Users Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus me-1"></i> Add New User
                        </button>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $u['role'] == 'admin' ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-user" 
                                                data-id="<?php echo $u['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                                data-name="<?php echo htmlspecialchars($u['name']); ?>"
                                                data-role="<?php echo $u['role']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#userModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($u['id'] != $user_id): ?>
                                        <button type="button" class="btn btn-sm btn-danger delete-user" 
                                                data-id="<?php echo $u['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($u['name']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="user_id">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username*</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name*</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter full name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role*</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="cashier">Cashier</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label"><span id="password_label">Password*</span></label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                            <div id="passwordHelp" class="form-text">Leave blank to keep current password (when editing).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><span id="confirm_password_label">Confirm Password*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_user" class="btn btn-primary">Save User</button>
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
                    <p>Are you sure you want to delete the user: <span id="delete_user_name"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit user
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('userModal');
                modal.querySelector('.modal-title').textContent = 'Edit User';
                
                document.getElementById('user_id').value = this.dataset.id;
                document.getElementById('username').value = this.dataset.username;
                document.getElementById('name').value = this.dataset.name;
                document.getElementById('role').value = this.dataset.role;
                
                // Password is optional when editing
                document.getElementById('password_label').textContent = 'Password (optional)';
                document.getElementById('confirm_password_label').textContent = 'Confirm Password (optional)';
                document.getElementById('password').required = false;
                document.getElementById('confirm_password').required = false;
                document.getElementById('passwordHelp').style.display = 'block';
            });
        });
        
        // Handle add user
        document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('.modal-title').textContent = 'Add New User';
            this.querySelector('form').reset();
            document.getElementById('user_id').value = '';
            
            // Password is required for new users
            document.getElementById('password_label').textContent = 'Password*';
            document.getElementById('confirm_password_label').textContent = 'Confirm Password*';
            document.getElementById('password').required = true;
            document.getElementById('confirm_password').required = true;
            document.getElementById('passwordHelp').style.display = 'none';
        });
        
        // Handle delete user
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_user_id').value = this.dataset.id;
                document.getElementById('delete_user_name').textContent = this.dataset.name;
            });
        });
    </script>
</body>
</html> 