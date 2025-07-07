<?php
// config/database.php - Configuración para EC2
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class Database {
    private $host = 'localhost';
    private $db_name = 'cloud_inventario';
    private $username = 'root';
    private $password = '';  // Ajusta según tu configuración
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_TIMEOUT => 30
            ];

            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión a la base de datos.',
                'error' => $e->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT VERSION() as version, NOW() as current_time");
                $result = $stmt->fetch();
                
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa',
                    'server_info' => $result['version'],
                    'current_time' => $result['current_time']
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>