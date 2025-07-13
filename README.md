# POS System

A modern Point of Sale (POS) system with an intuitive user interface for managing sales, inventory, and customers.

## Features

- User-friendly dashboard with sales analytics
- Quick and easy point of sale interface
- Product and category management
- Sales history and receipt printing
- User management with role-based access control
- Barcode scanning support
- Responsive design for all devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)
- Modern web browser

## Installation

1. Clone or download this repository to your web server's document root (e.g., `htdocs` folder for XAMPP).

2. Create a MySQL database named `pos_system`.

3. Import the database structure by running the `database.sql` file in phpMyAdmin:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Select the `pos_system` database
   - Click on the "Import" tab
   - Choose the `database.sql` file and click "Go"

4. Configure the database connection:
   - Open `config/db.php`
   - Update the database credentials if needed (default is username: `root`, password: ``)

5. Access the POS system through your web browser:
   - http://localhost/POS%20system/

## Default Login Credentials

- **Username:** admin
- **Password:** admin123

## Usage

### Dashboard

The dashboard provides an overview of your business with key metrics such as:
- Total products
- Today's sales
- Low stock items
- Recent sales history

### Point of Sale

The POS interface allows you to:
- Search products by name or scan barcode
- Filter products by category
- Add items to cart
- Apply discounts
- Process payments
- Print receipts

### Products Management

Manage your inventory:
- Add, edit, and delete products
- Upload product images
- Set prices and stock levels
- Assign products to categories

### Sales History

Track all sales:
- View detailed sales reports
- Filter by date range
- Print or reprint receipts
- View sales by cashier

### User Management

Control access to the system:
- Create and manage user accounts
- Assign roles (admin, cashier)
- Reset passwords

## License

This project is licensed under the MIT License.

## Support

For support, please contact us at support@posystem.com or open an issue on GitHub. 