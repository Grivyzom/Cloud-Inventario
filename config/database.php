<?php
// config/database.php - Configuración para AWS RDS Aurora
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class Database {
    // Configuración para AWS RDS Aurora
    private $host = 'cloud-inventario-cluster.cluster-xxxxx.us-east-1.rds.amazonaws.com'; // Reemplaza con tu endpoint
    private $port = 3306;
    private $db_name = 'cloud_inventario';
    private $username = 'admin';
    private $password = 'CloudInventario2025!'; // Usa la contraseña que configuraste
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Opciones optimizadas para RDS
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => false, // No usar conexiones persistentes con RDS
                PDO::MYSQL_ATTR_SSL_CA => false, // SSL opcional para desarrollo
            ];

            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log("RDS Connection error: " . $e->getMessage());
            
            // Información detallada para debug
            $debug_info = [
                'host' => $this->host,
                'port' => $this->port,
                'database' => $this->db_name,
                'username' => $this->username,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ];
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión a RDS Aurora.',
                'error' => $e->getMessage(),
                'debug' => $debug_info
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return $this->conn;
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT VERSION() as version, NOW() as current_time, @@hostname as hostname");
                $result = $stmt->fetch();
                
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa a RDS Aurora',
                    'server_info' => $result['version'],
                    'current_time' => $result['current_time'],
                    'hostname' => $result['hostname'],
                    'connection_info' => [
                        'host' => $this->host,
                        'port' => $this->port,
                        'database' => $this->db_name,
                        'username' => $this->username
                    ]
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'connection_attempted' => [
                    'host' => $this->host,
                    'port' => $this->port,
                    'database' => $this->db_name,
                    'username' => $this->username
                ]
            ];
        }
    }

    // Método para crear la base de datos y tablas iniciales
    public function setupDatabase() {
        try {
            // Conectar sin especificar base de datos
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $this->username, $this->password);
            
            // Crear base de datos si no existe
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$this->db_name}`");
            
            // Crear tabla categories
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) UNIQUE NOT NULL,
                    description TEXT,
                    sort_order INT DEFAULT 0,
                    is_active TINYINT(1) DEFAULT 1,
                    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Crear tabla products
            $pdo->exec("
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
                )
            ");
            
            // Insertar categorías por defecto
            $pdo->exec("
                INSERT IGNORE INTO categories (name, slug, description, sort_order) VALUES
                ('Electrónica', 'electronica', 'Dispositivos electrónicos y gadgets', 1),
                ('Ropa', 'ropa', 'Ropa y accesorios de vestir', 2),
                ('Hogar', 'hogar', 'Artículos para el hogar y decoración', 3),
                ('Deportes', 'deportes', 'Equipos y accesorios deportivos', 4),
                ('Libros', 'libros', 'Libros y material de lectura', 5),
                ('Otros', 'otros', 'Productos diversos', 6)
            ");
            
            // Insertar productos de ejemplo
            $pdo->exec("
                INSERT IGNORE INTO products (sku, name, description, selling_price, stock_current, category_id, brand) VALUES
                ('PRD-001', 'iPhone 15 Pro', 'Smartphone Apple iPhone 15 Pro 256GB', 1299.99, 15, 1, 'Apple'),
                ('PRD-002', 'Samsung Galaxy S24', 'Smartphone Samsung Galaxy S24 128GB', 899.99, 8, 1, 'Samsung'),
                ('PRD-003', 'Laptop HP Pavilion', 'Laptop HP Pavilion 15.6\" Intel i7 16GB RAM', 849.99, 5, 1, 'HP'),
                ('PRD-004', 'Camiseta Nike', 'Camiseta deportiva Nike Dri-FIT', 29.99, 25, 2, 'Nike'),
                ('PRD-005', 'Jeans Levis 501', 'Jeans clásicos Levis 501 Original', 79.99, 12, 2, 'Levis'),
                ('PRD-006', 'Sofá Esquinero', 'Sofá esquinero 3 plazas color gris', 599.99, 3, 3, 'IKEA'),
                ('PRD-007', 'Mesa de Centro', 'Mesa de centro moderna de madera', 199.99, 7, 3, 'IKEA'),
                ('PRD-008', 'Balón de Fútbol', 'Balón oficial FIFA talla 5', 34.99, 20, 4, 'Adidas'),
                ('PRD-009', 'Raqueta de Tenis', 'Raqueta profesional de tenis', 149.99, 6, 4, 'Wilson'),
                ('PRD-010', 'El Principito', 'Libro clásico \"El Principito\"', 15.99, 30, 5, 'Editorial Planeta')
            ");
            
            return [
                'success' => true,
                'message' => 'Base de datos y tablas creadas exitosamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>