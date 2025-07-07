<?php
// setup_rds.php - Script para configurar automáticamente RDS Aurora
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Configuración Automática de RDS Aurora</h1>";
echo "<style>
    body{font-family:Arial,sans-serif;margin:20px;}
    .success{color:#28a745;font-weight:bold;}
    .error{color:#dc3545;font-weight:bold;}
    .warning{color:#ffc107;font-weight:bold;}
    .info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;}
    .step{background:#e3f2fd;padding:10px;margin:10px 0;border-left:4px solid #2196f3;}
    pre{background:#f1f1f1;padding:10px;border-radius:3px;overflow-x:auto;}
    .config-box{background:#fff3cd;border:1px solid #ffeaa7;padding:15px;border-radius:5px;margin:10px 0;}
</style>";

if (isset($_POST['setup'])) {
    echo "<h2>🔧 Ejecutando Configuración...</h2>";
    
    $rds_host = $_POST['rds_host'];
    $rds_username = $_POST['rds_username'];
    $rds_password = $_POST['rds_password'];
    $db_name = $_POST['db_name'];
    
    if (empty($rds_host) || empty($rds_username) || empty($rds_password)) {
        echo "<span class='error'>❌ Todos los campos son requeridos</span>";
    } else {
        // Probar conexión
        try {
            $dsn = "mysql:host={$rds_host};port=3306;charset=utf8mb4";
            $pdo = new PDO($dsn, $rds_username, $rds_password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            echo "<span class='success'>✅ Conexión a RDS exitosa</span><br>";
            
            // Crear base de datos
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<span class='success'>✅ Base de datos '{$db_name}' creada</span><br>";
            
            $pdo->exec("USE `{$db_name}`");
            
            // Crear tablas
            $tables_sql = [
                "categories" => "
                    CREATE TABLE IF NOT EXISTS categories (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        slug VARCHAR(100) UNIQUE NOT NULL,
                        description TEXT,
                        sort_order INT DEFAULT 0,
                        is_active TINYINT(1) DEFAULT 1,
                        date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )",
                "products" => "
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
                    )"
            ];
            
            foreach ($tables_sql as $table => $sql) {
                $pdo->exec($sql);
                echo "<span class='success'>✅ Tabla '{$table}' creada</span><br>";
            }
            
            // Insertar datos de ejemplo
            $pdo->exec("
                INSERT IGNORE INTO categories (name, slug, description, sort_order) VALUES
                ('Electrónica', 'electronica', 'Dispositivos electrónicos y gadgets', 1),
                ('Ropa', 'ropa', 'Ropa y accesorios de vestir', 2),
                ('Hogar', 'hogar', 'Artículos para el hogar y decoración', 3),
                ('Deportes', 'deportes', 'Equipos y accesorios deportivos', 4),
                ('Libros', 'libros', 'Libros y material de lectura', 5),
                ('Otros', 'otros', 'Productos diversos', 6)
            ");
            
            $pdo->exec("
                INSERT IGNORE INTO products (sku, name, description, selling_price, stock_current, category_id, brand) VALUES
                ('PRD-001', 'iPhone 15 Pro', 'Smartphone Apple iPhone 15 Pro 256GB', 1299.99, 15, 1, 'Apple'),
                ('PRD-002', 'Samsung Galaxy S24', 'Smartphone Samsung Galaxy S24 128GB', 899.99, 8, 1, 'Samsung'),
                ('PRD-003', 'Laptop HP Pavilion', 'Laptop HP Pavilion 15.6\" Intel i7 16GB RAM', 849.99, 5, 1, 'HP'),
                ('PRD-004', 'Camiseta Nike', 'Camiseta deportiva Nike Dri-FIT', 29.99, 25, 2, 'Nike'),
                ('PRD-005', 'Jeans Levis 501', 'Jeans clásicos Levis 501 Original', 79.99, 12, 2, 'Levis')
            ");
            
            echo "<span class='success'>✅ Datos de ejemplo insertados</span><br>";
            
            // Crear archivo de configuración
            $config_content = "<?php
// config/database.php - Configuración para AWS RDS Aurora
header(\"Access-Control-Allow-Origin: *\");
header(\"Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS\");
header(\"Access-Control-Allow-Headers: Content-Type, Authorization\");
header(\"Content-Type: application/json; charset=UTF-8\");

if (\$_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class Database {
    private \$host = '{$rds_host}';
    private \$port = 3306;
    private \$db_name = '{$db_name}';
    private \$username = '{$rds_username}';
    private \$password = '{$rds_password}';
    private \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\",
                PDO::ATTR_TIMEOUT => 30
            ];

            \$dsn = \"mysql:host=\" . \$this->host . \";port=\" . \$this->port . \";dbname=\" . \$this->db_name . \";charset=utf8mb4\";
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password, \$options);
            
        } catch (PDOException \$e) {
            error_log(\"RDS Connection error: \" . \$e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión a RDS Aurora.',
                'error' => \$e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return \$this->conn;
    }

    public function testConnection() {
        try {
            \$conn = \$this->getConnection();
            if (\$conn) {
                \$stmt = \$conn->query(\"SELECT VERSION() as version, NOW() as current_time\");
                \$result = \$stmt->fetch();
                
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa a RDS Aurora',
                    'server_info' => \$result['version'],
                    'current_time' => \$result['current_time']
                ];
            }
        } catch (Exception \$e) {
            return [
                'success' => false,
                'error' => \$e->getMessage()
            ];
        }
    }
}
?>";
            
            if (file_put_contents('config/database.php', $config_content)) {
                echo "<span class='success'>✅ Archivo config/database.php actualizado</span><br>";
            } else {
                echo "<span class='error'>❌ Error al escribir config/database.php</span><br>";
            }
            
            echo "<h2>🎉 ¡Configuración Completada!</h2>";
            echo "<div class='info'>";
            echo "<strong>Tu aplicación está configurada con RDS Aurora:</strong><br>";
            echo "• Host: {$rds_host}<br>";
            echo "• Base de datos: {$db_name}<br>";
            echo "• Usuario: {$rds_username}<br>";
            echo "• Tablas creadas: categories, products<br>";
            echo "• Datos de ejemplo: 5 productos insertados<br>";
            echo "</div>";
            
            echo "<h3>🔗 Probar tu aplicación:</h3>";
            $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
            echo "<ul>";
            echo "<li><a href='{$base_url}/api/products.php?test=1' target='_blank'>Test API</a></li>";
            echo "<li><a href='{$base_url}/api/products.php' target='_blank'>Ver productos</a></li>";
            echo "<li><a href='{$base_url}/catalogo.html' target='_blank'>Catálogo</a></li>";
            echo "<li><a href='{$base_url}/index.html' target='_blank'>Página principal</a></li>";
            echo "</ul>";
            
        } catch (PDOException $e) {
            echo "<span class='error'>❌ Error de conexión: " . $e->getMessage() . "</span><br>";
            echo "<div class='info'>";
            echo "<strong>Posibles problemas:</strong><br>";
            echo "• Security Group no permite conexiones desde EC2<br>";
            echo "• Endpoint incorrecto<br>";
            echo "• Credenciales incorrectas<br>";
            echo "• RDS no está disponible públicamente<br>";
            echo "</div>";
        }
    }
} else {
    echo "<h2>📝 Configuración de RDS Aurora</h2>";
    echo "<div class='step'>";
    echo "<strong>Paso 1:</strong> Crea una instancia RDS Aurora MySQL en AWS Console<br>";
    echo "<strong>Paso 2:</strong> Configura el Security Group para permitir conexiones desde tu EC2<br>";
    echo "<strong>Paso 3:</strong> Completa el formulario abajo con los datos de tu RDS<br>";
    echo "</div>";
    
    echo "<form method='post'>";
    echo "<div class='config-box'>";
    echo "<h3>Datos de Conexión RDS</h3>";
    echo "<label>RDS Endpoint (Host):</label><br>";
    echo "<input type='text' name='rds_host' placeholder='your-cluster.cluster-xxxxx.region.rds.amazonaws.com' style='width:100%;padding:8px;margin:5px 0;' required><br>";
    
    echo "<label>Username:</label><br>";
    echo "<input type='text' name='rds_username' value='admin' style='width:100%;padding:8px;margin:5px 0;' required><br>";
    
    echo "<label>Password:</label><br>";
    echo "<input type='password' name='rds_password' placeholder='Tu contraseña de RDS' style='width:100%;padding:8px;margin:5px 0;' required><br>";
    
    echo "<label>Nombre de Base de Datos:</label><br>";
    echo "<input type='text' name='db_name' value='cloud_inventario' style='width:100%;padding:8px;margin:5px 0;' required><br>";
    
    echo "<input type='submit' name='setup' value='🚀 Configurar Automáticamente' style='background:#28a745;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:10px 0;'>";
    echo "</div>";
    echo "</form>";
    
    echo "<h3>💡 Instrucciones para crear RDS Aurora:</h3>";
    echo "<ol>";
    echo "<li>Ve a <strong>AWS RDS Console</strong> → Create database</li>";
    echo "<li>Selecciona <strong>Amazon Aurora</strong> → <strong>Aurora MySQL</strong></li>";
    echo "<li>Template: <strong>Dev/Test</strong></li>";
    echo "<li>DB cluster identifier: <strong>cloud-inventario-cluster</strong></li>";
    echo "<li>Master username: <strong>admin</strong></li>";
    echo "<li>Master password: <strong>(elige una contraseña segura)</strong></li>";
    echo "<li>DB instance class: <strong>db.t3.medium</strong></li>";
    echo "<li>Public access: <strong>Yes</strong></li>";
    echo "<li>VPC security group: <strong>Create new</strong></li>";
    echo "<li>En Security Group, añade regla: <strong>MySQL/Aurora (3306) desde tu IP</strong></li>";
    echo "</ol>";
    
    echo "<div class='info'>";
    echo "<strong>💰 Costos estimados:</strong><br>";
    echo "• db.t3.medium: ~$0.082/hora (~$60/mes)<br>";
    echo "• Aurora Storage: $0.10/GB/mes<br>";
    echo "• Total estimado: ~$65-80/mes para desarrollo<br>";
    echo "</div>";
}
?>