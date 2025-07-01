<?php
// test_connection.php - Archivo para probar la conexión (CORREGIDO)
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Diagnóstico de Conexión MySQL</h2>";

// 1. Verificar extensiones PHP
echo "<h3>1. Verificando Extensiones PHP:</h3>";
echo "✅ PDO: " . (extension_loaded('pdo') ? 'DISPONIBLE' : '❌ NO DISPONIBLE') . "<br>";
echo "✅ PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'DISPONIBLE' : '❌ NO DISPONIBLE') . "<br>";
echo "✅ MySQLi: " . (extension_loaded('mysqli') ? 'DISPONIBLE' : '❌ NO DISPONIBLE') . "<br>";

// 2. Verificar archivos
echo "<h3>2. Verificando Archivos:</h3>";
$config_path = __DIR__ . '/config/database.php';
$product_path = __DIR__ . '/models/Product.php';

echo "Archivo config: " . ($config_path) . "<br>";
echo "Existe: " . (file_exists($config_path) ? '✅ SÍ' : '❌ NO') . "<br>";
echo "Archivo models: " . ($product_path) . "<br>";
echo "Existe: " . (file_exists($product_path) ? '✅ SÍ' : '❌ NO') . "<br>";

// 3. Probar conexión DIRECTA a MySQL (sin archivos externos)
echo "<h3>3. Probando Conexión Directa a MySQL:</h3>";

// Probar con contraseña vacía (XAMPP por defecto)
echo "<h4>Probando con contraseña VACÍA (XAMPP por defecto):</h4>";
try {
    $pdo_empty = new PDO('mysql:host=localhost', 'root', '');
    echo "✅ CONEXIÓN EXITOSA con contraseña vacía<br>";
    
    // Listar bases de datos
    $stmt = $pdo_empty->query('SHOW DATABASES');
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Bases de datos disponibles: " . implode(', ', $databases) . "<br>";
    
    $db_exists = in_array('cloud_inventario', $databases);
    echo "Base de datos 'cloud_inventario': " . ($db_exists ? '✅ EXISTE' : '❌ NO EXISTE') . "<br>";
    
} catch(PDOException $e) {
    echo "❌ Error con contraseña vacía: " . $e->getMessage() . "<br>";
    
    // Probar con la contraseña configurada
    echo "<h4>Probando con contraseña 'inacap2025':</h4>";
    try {
        $pdo_pass = new PDO('mysql:host=localhost', 'root', 'inacap2025');
        echo "✅ CONEXIÓN EXITOSA con contraseña 'inacap2025'<br>";
    } catch(PDOException $e2) {
        echo "❌ Error con contraseña 'inacap2025': " . $e2->getMessage() . "<br>";
    }
}

// 4. Probar con la clase Database SI los archivos existen
if (file_exists($config_path)) {
    echo "<h3>4. Probando con Clase Database:</h3>";
    require_once $config_path;
    
    try {
        $database = new Database();
        $result = $database->testConnection();
        
        if ($result['success']) {
            echo "✅ CONEXIÓN EXITOSA con clase Database<br>";
            echo "Info del servidor: " . $result['server_info'] . "<br>";
        } else {
            echo "❌ ERROR con clase Database: " . $result['error'] . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ EXCEPCIÓN en clase Database: " . $e->getMessage() . "<br>";
    }
}

// 5. Instrucciones de solución
echo "<h3>5. Instrucciones de Solución:</h3>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; border-left: 4px solid #0066cc;'>";
echo "<strong>Si ves errores arriba, sigue estos pasos:</strong><br><br>";
echo "1. <strong>Contraseña MySQL:</strong><br>";
echo "   - Si funcionó con contraseña vacía: cambia en config/database.php: <code>\$password = '';</code><br>";
echo "   - Si funcionó con 'inacap2025': déjalo como está<br><br>";
echo "2. <strong>Crear base de datos:</strong><br>";
echo "   - Ve a <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a><br>";
echo "   - Crea la base de datos: <code>CREATE DATABASE cloud_inventario;</code><br><br>";
echo "3. <strong>Ejecutar el script SQL:</strong><br>";
echo "   - Usa el script completo que te proporcioné antes<br>";
echo "</div>";

echo "<h3>6. URLs para probar después:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost/Cloud-Inventario/index.html'>Página principal</a></li>";
echo "<li><a href='http://localhost/phpmyadmin'>phpMyAdmin</a></li>";
echo "<li><a href='http://localhost/Cloud-Inventario/api/products.php'>API de productos</a></li>";
echo "</ul>";
?>