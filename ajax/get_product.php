<?php
// Include database connection
require_once '../config/db.php';

// Check if barcode is provided
if (isset($_GET['barcode'])) {
    $barcode = $_GET['barcode'];
    
    // Prepare query
    $query = "SELECT * FROM products WHERE barcode = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Product found
        $product = $result->fetch_assoc();
        
        // Return product data as JSON
        echo json_encode([
            'success' => true,
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $product['quantity']
            ]
        ]);
    } else {
        // Product not found
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }
} else {
    // Barcode not provided
    echo json_encode([
        'success' => false,
        'message' => 'Barcode not provided'
    ]);
}
?> 