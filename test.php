<?php
echo "PHP is working!";
echo "<br>Current time: " . date('Y-m-d H:i:s');
echo "<br>Server info: " . $_SERVER['SERVER_SOFTWARE'];

// Test database connection
require_once 'config/db.php';
echo "<br><br>Database connection: ";
if ($conn->ping()) {
    echo "SUCCESS";
    
    // Check if tables exist
    $result = $conn->query("SHOW TABLES");
    echo "<br>Tables in database:<br>";
    while ($row = $result->fetch_row()) {
        echo "- " . $row[0] . "<br>";
    }
    
    // Check if users table has data
    $result = $conn->query("SELECT * FROM users");
    echo "<br>Number of users: " . $result->num_rows;
} else {
    echo "FAILED - " . $conn->error;
}
?> 