-- Database for PriceSmart

CREATE DATABASE IF NOT EXISTS pricesmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pricesmart;

-- Companies / Settings
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18),
    tax_regime ENUM('Mei', 'Simples Nacional', 'Lucro Presumido', 'Lucro Real') DEFAULT 'Simples Nacional',
    base_tax_rate DECIMAL(5,2) DEFAULT 6.00,
    target_margin DECIMAL(5,2) DEFAULT 30.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Manager', 'Viewer') DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Products / SKUs
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    sku VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Costs
CREATE TABLE IF NOT EXISTS costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NULL, -- NULL if it's a global/fixed cost
    company_id INT,
    name VARCHAR(255) NOT NULL,
    type ENUM('Fixed', 'Variable', 'Tax') DEFAULT 'Variable',
    value DECIMAL(10,2) NOT NULL,
    is_percentage BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- AI Suggestions (Mock)
CREATE TABLE IF NOT EXISTS ai_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    suggested_price DECIMAL(10,2),
    reason TEXT,
    status ENUM('Pending', 'Applied', 'Ignored') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Seed Initial Data
INSERT INTO companies (name, cnpj, tax_regime, base_tax_rate, target_margin) 
VALUES ('Minha Loja Exemplo', '12.345.678/0001-90', 'Simples Nacional', 6.00, 35.00);

SET @company_id = LAST_INSERT_ID();

INSERT INTO users (company_id, name, email, password) 
VALUES (@company_id, 'Marcos Fernandes', 'admin@pricesmart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password is 'password'

INSERT INTO products (company_id, sku, name, current_price, stock_quantity, category) VALUES
(@company_id, 'SKU-Gamer-001', 'Cadeira Gamer Omega', 1299.00, 15, 'Móveis'),
(@company_id, 'SKU-Desk-002', 'Mesa de Escritório Pro', 850.00, 2, 'Móveis'),
(@company_id, 'SKU-Acc-003', 'Mouse Pad XL RGB', 120.00, 85, 'Acessórios'),
(@company_id, 'SKU-Tech-004', 'Fone Bluetooth X1', 350.00, 10, 'Eletrônicos');

-- Costs
INSERT INTO costs (company_id, product_id, name, type, value, is_percentage) VALUES
(@company_id, 1, 'Custo de Aquisição', 'Variable', 600.00, FALSE),
(@company_id, 1, 'Frete Produção', 'Variable', 50.00, FALSE),
(@company_id, 2, 'Custo de Aquisição', 'Variable', 420.00, FALSE),
(@company_id, 3, 'Custo de Aquisição', 'Variable', 25.00, FALSE);

-- Global Costs
INSERT INTO costs (company_id, name, type, value, is_percentage) VALUES
(@company_id, 'Aluguel do Galpão', 'Fixed', 2500.00, FALSE),
(@company_id, 'Marketing Digital', 'Variable', 5.00, TRUE);
