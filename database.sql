-- QR Ordering System Database Schema
-- Database Name: qrordering

CREATE DATABASE IF NOT EXISTS qrordering;
USE qrordering;

-- Shops table
CREATE TABLE shops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_name VARCHAR(255) NOT NULL UNIQUE,
    owner_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu table
CREATE TABLE menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT NOT NULL,
    table_no VARCHAR(50),
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    token VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE
);

-- Shop owners table (optional, for authentication)
CREATE TABLE shop_owners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    shop_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE SET NULL
);

-- Admin users table (optional)
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data
INSERT INTO shops (shop_name) VALUES
('Sample Restaurant'),
('Cafe Corner'),
('Food Plaza');

INSERT INTO menu (shop_id, item_name, price, description) VALUES
(1, 'Chicken Biryani', 250.00, 'Delicious chicken biryani with raita'),
(1, 'Paneer Butter Masala', 180.00, 'Creamy paneer curry with butter'),
(1, 'Masala Dosa', 120.00, 'Crispy dosa with potato filling'),
(2, 'Cappuccino', 150.00, 'Hot cappuccino with chocolate sprinkles'),
(2, 'Sandwich', 100.00, 'Grilled vegetable sandwich'),
(3, 'Pizza Margherita', 300.00, 'Classic pizza with cheese and tomato'),
(3, 'Burger', 200.00, 'Juicy beef burger with fries');
