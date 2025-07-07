<?php
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico de Conexión a Base de Datos</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:#28a745;font-weight:bold;}.error{color:#dc3545;font-weight:bold;}.info{background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;}</style>";

echo "<h2>🗄️ Probando Conexiones a MySQL/MariaDB</h2>";

$configs = [
    ['localhost', 'root', ''],
    ['localhost', 'root', 'inacap2025'],
    ['127.0.0.1', 'root', '']
];

$working_config = null;

foreach ($configs as $i => $config) {
    echo "<h3>Prueba " . ($i + 1) . ": {$config[0]} / {$config[1]} / " . ($config[2] ?: 'sin contraseña') . "</h3>";
    
    try {
        $pdo = new PDO("mysql:host={$config[0]}", $config[1], $config[2], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "<span class='success'>✅ CONEXIÓN EXITOSA</span><br>";
        
        // Verificar base de datos
        $stmt = $pdo->query("SHOW DATABASES LIKE 'cloud_inventario'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Base de datos 'cloud_inventario' EXISTE<br>";
            
            // Conectar a la base de datos específica
            $pdo->exec("USE cloud_inventario");
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "📋 Tablas: " . implode(', ', $tables) . "<br>";
            
            // Verificar productos
            if (in_array('products', $tables)) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM products");
                $count = $stmt->fetchColumn();
                echo "📦 Productos en BD: <strong>$count</strong><br>";
                
                if ($count > 0) {
                    $stmt = $pdo->query("SELECT name, selling_price, stock_current FROM products LIMIT 3");
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo "📋 Ejemplos de productos:<br>";
                    foreach ($products as $prod) {
                        echo "&nbsp;&nbsp;• {$prod['name']} - $" . number_format($prod['selling_price'], 2) . " (Stock: {$prod['stock_current']})<br>";
                    }
                }
            }
            
            $working_config = $config;
            break;
        } else {
            echo "❌ Base de datos 'cloud_inventario' NO EXISTE<br>";
        }
        
    } catch (PDOException $e) {
        echo "<span class='error'>❌ ERROR: " . $e->getMessage() . "</span><br>";
    }
    echo "<br>";
}

if ($working_config) {
    echo "<h2>⚙️ Configuración Correcta Encontrada</h2>";
    echo "<div class='info'>";
    echo "<strong>Configuración que funciona:</strong><br>";
    echo "Host: {$working_config[0]}<br>";
    echo "Usuario: {$working_config[1]}<br>";
    echo "Contraseña: " . ($working_config[2] ?: '(vacía)') . "<br>";
    echo "</div>";
    
    // Probar la clase Database
    echo "<h2>🔗 Probando Clase Database</h2>";
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        try {
            $database = new Database();
            $result = $database->testConnection();
            
            if ($result['success']) {
                echo "<span class='success'>✅ Clase Database funciona correctamente</span><br>";
                echo "Servidor: {$result['server_info']}<br>";
            } else {
                echo "<span class='error'>❌ Error en clase Database: {$result['error']}</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Excepción: " . $e->getMessage() . "</span><br>";
        }
    }
    
} else {
    echo "<h2>❌ No se encontró configuración funcional</h2>";
    echo "<div class='info'>";
    echo "<strong>Posibles soluciones:</strong><br>";
    echo "1. Verificar que MySQL/MariaDB esté ejecutándose<br>";
    echo "2. Crear la base de datos 'cloud_inventario'<br>";
    echo "3. Ejecutar el script SQL para crear las tablas<br>";
    echo "</div>";
}

echo "<h2>🌐 URLs de Prueba</h2>";
$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
echo "<a href='{$base_url}/api/products.php?test=1' target='_blank'>Test API</a><br>";
echo "<a href='{$base_url}/catalogo.html' target='_blank'>Catálogo</a><br>";
?>