<?php  
// Debug login script  
error_reporting(E_ALL);  
ini_set('display_errors', 1);  
// Include database connection  
require_once 'config/db.php';  
echo "<h1>POS System Login Debug</h1>";  
// Check database connection  
echo "<h2>Database Connection</h2>";  
if ($conn->ping()) {  
echo "<p style='color:green'>Database connection is working!</p>";  
} else {  
echo "<p style='color:red'>Database connection failed: " . $conn->error . "</p>";  
exit;  
} 
// Check if users table exists and has data  
echo "<h2>Users Table Check</h2>";  
$result = $conn->query("SHOW TABLES LIKE 'users'");  
if ($result->num_rows > 0) {  
echo "<p style='color:green'>Users table exists.</p>";  
// Check for admin user  
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");  
if ($result->num_rows > 0) {  
$user = $result->fetch_assoc();  
echo "<p style='color:green'>Admin user found!</p>";  
echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";  
echo "<p>Role: " . htmlspecialchars($user['role']) . "</p>";  
echo "<p>Password hash: " . htmlspecialchars($user['password']) . "</p>"; 
// Test password verification  
$test_password = "admin123";  
if (password_verify($test_password, $user['password'])) {  
echo "<p style='color:green'>Password 'admin123' verified successfully!</p>";  
} else {  
echo "<p style='color:red'>Password 'admin123' verification failed!</p>";  
// Create a new hash for comparison  
$new_hash = password_hash($test_password, PASSWORD_DEFAULT);  
echo "<p>New hash for 'admin123': " . $new_hash . "</p>";  
}  
} else {  
echo "<p style='color:red'>Admin user not found!</p>";  
}  
} else {  
echo "<p style='color:red'>Users table does not exist!</p>";  
} 
// Create a test admin user if needed  
echo "<h2>Fix Options</h2>";  
echo "<form method='post'>";  
echo "<button type='submit' name='fix' value='recreate'>Recreate Admin User</button>";  
echo "</form>";  
if (isset($_POST['fix']) && $_POST['fix'] == 'recreate') {  
// Create a new password hash  
$username = 'admin';  
$password = 'admin123';  
$name = 'Administrator';  
$role = 'admin';  
$hashed_password = password_hash($password, PASSWORD_DEFAULT); 
// Delete existing admin user if exists  
$conn->query("DELETE FROM users WHERE username = 'admin'");  
// Create new admin user  
$query = "INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)";  
$stmt = $conn->prepare($query);  
$stmt->bind_param("ssss", $username, $hashed_password, $name, $role);  
if ($stmt->execute()) {  
echo "<p style='color:green'>Admin user recreated successfully!</p>";  
echo "<p>New password hash: " . $hashed_password . "</p>";  
} else {  
echo "<p style='color:red'>Failed to recreate admin user: " . $stmt->error . "</p>";  
}  
}  
echo "<p><a href='login.php'>Return to Login Page</a></p>";  
?> 
