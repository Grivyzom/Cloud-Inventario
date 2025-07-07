<?php
echo "<h1>🔍 Diagnóstico Rápido de Base de Datos</h1>";
echo "<style>body{font-family:Arial;margin:20px;}.success{color:green;}.error{color:red;}</style>";

echo "<h2>Probando conexiones...</h2>";

// Configuraciones a probar
$configs = [
    ['localhost', 'root', ''],
    ['localhost', 'root', 'inacap2025'],
    ['127.0.0.1', 'root', '']
];

$working = false;

foreach ($configs as $i => $config) {
    echo "<h3>Prueba " . ($i + 1) . ": {$config[0]} / {$config[1]} / " . ($config[2] ?: 'sin contraseña') . "</h3>";
    
    try {
        $pdo = new PDO("mysql:host={$config[0]}", $config[1], $config[2]);
        echo "<span class='success'>✅ CONEXIÓN EXITOSA</span><br>";
        
        // Verificar base de datos
        $stmt = $pdo->query("SHOW DATABASES LIKE 'cloud_inventario'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✅ Base de datos 'cloud_inventario' existe</span><br>";
            
            // Conectar a la BD específica
            $pdo->exec("USE cloud_inventario");
            $stmt = $pdo->query("SELECT COUNT(*) FROM products");
            $count = $stmt->fetchColumn();
            echo "<span class='success'>✅ Productos encontrados: $count</span><br>";
            
            $working = true;
            
            echo "<h3>🎯 Usar esta configuración:</h3>";
            echo "Host: {$config[0]}<br>";
            echo "Usuario: {$config[1]}<br>";
            echo "Contraseña: " . ($config[2] ?: '(vacía)') . "<br>";
            break;
        } else {
            echo "<span class='error'>❌ Base de datos 'cloud_inventario' NO existe</span><br>";
        }
        
    } catch (PDOException $e) {
        echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
    }
    echo "<br>";
}

if (!$working) {
    echo "<h2>❌ Ninguna configuración funcionó</h2>";
    echo "<p>Necesitas:</p>";
    echo "<ol>";
    echo "<li>Verificar que MySQL/MariaDB esté ejecutándose</li>";
    echo "<li>Crear la base de datos 'cloud_inventario'</li>";
    echo "<li>Ejecutar el script SQL para crear las tablas</li>";
    echo "</ol>";
}
?>