<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'cloud_inventario';
    private $username = 'root';
    private $password = ''; // VACÍO para XAMPP por defecto
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Crear conexión PDO
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
            
            error_log("✅ Conexión a base de datos exitosa");
            
        } catch(PDOException $exception) {
            error_log("❌ Error de conexión PDO: " . $exception->getMessage());
            
            // Mostrar error más claro
            throw new Exception("Error de conexión: " . $exception->getMessage());
        }
        return $this->conn;
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa',
                    'server_info' => $conn->getAttribute(PDO::ATTR_SERVER_INFO)
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión',
                'error' => $e->getMessage()
            ];
        }
    }
}
?>