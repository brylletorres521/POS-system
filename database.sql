-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `pos_system`;
USE `pos_system`;

-- Create Products table
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`)
);

-- Create Categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Create Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('admin','cashier') NOT NULL DEFAULT 'cashier',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
);

-- Create Sales table
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `tax` decimal(10,2) DEFAULT '0.00',
  `paid_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','other') NOT NULL DEFAULT 'cash',
  `payment_status` enum('paid','pending','partial') NOT NULL DEFAULT 'paid',
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
);

-- Create Sale Items table
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO `users` (`username`, `password`, `name`, `role`) VALUES
('admin', '$2y$10$xLRzRCVSBzZ5.C0Y3cC6UOoYkuJ8O.wY1TC2VY7dYMXTJIqvVXGHe', 'Administrator', 'admin');

-- Insert some sample categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Beverages', 'Drinks, juices, and other beverages'),
('Snacks', 'Chips, cookies, and other snack items'),
('Groceries', 'Basic grocery items'),
('Electronics', 'Electronic devices and accessories');

-- Insert some sample products
INSERT INTO `products` (`barcode`, `name`, `description`, `price`, `cost`, `quantity`, `category_id`) VALUES
('8901234567890', 'Coca Cola 500ml', 'Refreshing soft drink', 2.50, 1.80, 100, 1),
('8902345678901', 'Lays Classic 100g', 'Potato chips', 1.99, 1.20, 50, 2),
('8903456789012', 'Milk 1L', 'Fresh whole milk', 3.49, 2.75, 30, 3),
('8904567890123', 'USB Cable', 'Type-C charging cable', 9.99, 5.50, 20, 4),
('8905678901234', 'Bread', 'White sandwich bread', 2.99, 1.85, 25, 3),
('8906789012345', 'Pepsi 500ml', 'Soft drink', 2.50, 1.80, 100, 1); 