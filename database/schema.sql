-- Crear base de datos
CREATE DATABASE IF NOT EXISTS cloud_inventario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cloud_inventario;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_current INT NOT NULL DEFAULT 0,
    stock_minimum INT DEFAULT 5,
    category_id INT,
    supplier_id INT DEFAULT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    barcode VARCHAR(50),
    weight DECIMAL(8,3),
    dimensions VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_name (name),
    INDEX idx_category (category_id),
    INDEX idx_active (is_active)
);

-- Insertar categorías por defecto
INSERT INTO categories (name, slug, description, sort_order) VALUES
('Electrónica', 'electronica', 'Dispositivos electrónicos y gadgets', 1),
('Ropa', 'ropa', 'Ropa y accesorios de vestir', 2),
('Hogar', 'hogar', 'Artículos para el hogar y decoración', 3),
('Deportes', 'deportes', 'Equipos y accesorios deportivos', 4),
('Libros', 'libros', 'Libros y material de lectura', 5),
('Otros', 'otros', 'Productos diversos', 6);

-- Insertar productos de ejemplo
INSERT INTO products (sku, name, description, selling_price, stock_current, category_id, brand) VALUES
('PRD-001', 'iPhone 15 Pro', 'Smartphone Apple iPhone 15 Pro 256GB', 1299.99, 15, 1, 'Apple'),
('PRD-002', 'Samsung Galaxy S24', 'Smartphone Samsung Galaxy S24 128GB', 899.99, 8, 1, 'Samsung'),
('PRD-003', 'Laptop HP Pavilion', 'Laptop HP Pavilion 15.6" Intel i7 16GB RAM', 849.99, 5, 1, 'HP'),
('PRD-004', 'Camiseta Nike', 'Camiseta deportiva Nike Dri-FIT', 29.99, 25, 2, 'Nike'),
('PRD-005', 'Jeans Levis 501', 'Jeans clásicos Levis 501 Original', 79.99, 12, 2, 'Levis'),
('PRD-006', 'Sofá Esquinero', 'Sofá esquinero 3 plazas color gris', 599.99, 3, 3, 'IKEA'),
('PRD-007', 'Mesa de Centro', 'Mesa de centro moderna de madera', 199.99, 7, 3, 'IKEA'),
('PRD-008', 'Balón de Fútbol', 'Balón oficial FIFA talla 5', 34.99, 20, 4, 'Adidas'),
('PRD-009', 'Raqueta de Tenis', 'Raqueta profesional de tenis', 149.99, 6, 4, 'Wilson'),
('PRD-010', 'El Principito', 'Libro clásico "El Principito"', 15.99, 30, 5, 'Editorial Planeta');

-- Verificar datos insertados
SELECT 'CATEGORÍAS:' as tabla;
SELECT * FROM categories;

SELECT 'PRODUCTOS:' as tabla;
SELECT p.*, c.name as category_name FROM products p 
LEFT JOIN categories c ON p.category_id = c.id;