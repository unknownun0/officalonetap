-- Database schema for the NFC Product/App application
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS nfc_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nfc_app;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    price DECIMAL(10, 2) DEFAULT 0.00,
    specs TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) NOT NULL UNIQUE,
    activation_code VARCHAR(64) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 0,
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer details table
CREATE TABLE IF NOT EXISTS customer_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    title VARCHAR(255),
    company VARCHAR(255),
    bio TEXT,
    phone VARCHAR(50),
    socials JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table (for contact form)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample admin (password: admin123)
INSERT INTO admins (email, password_hash) VALUES 
('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample products
INSERT INTO products (name, description, image_url, price, specs) VALUES
('NFC Business Card', 'Premium metal business card with embedded NFC chip', 'https://via.placeholder.com/400x300/2563eb/ffffff?text=NFC+Card', 29.99, 'Material: Stainless Steel\nChip: NTAG216\nMemory: 888 bytes\nWaterproof: Yes\nDimensions: 85x54mm'),
('Smart Sticker', 'Programmable NFC sticker for automation', 'https://via.placeholder.com/400x300/16a34a/ffffff?text=Smart+Sticker', 12.99, 'Chip: NTAG213\nMemory: 144 bytes\nAdhesive: 3M\nSize: 25mm diameter\nPack: 10 pieces'),
('NFC Key Fob', 'Durable key fob for access control', 'https://via.placeholder.com/400x300/dc2626/ffffff?text=Key+Fob', 19.99, 'Chip: NTAG215\nMemory: 504 bytes\nMaterial: ABS Plastic\nWaterproof: IP67\nColors: Black, Blue, Red'),
('Wireless Charger NFC', 'Qi wireless charger with NFC trigger', 'https://via.placeholder.com/400x300/7c3aed/ffffff?text=Charger+NFC', 49.99, 'Output: 15W Max\nInput: USB-C\nNFC: Auto-launch app\nLED Indicator\nDimensions: 100x100mm');