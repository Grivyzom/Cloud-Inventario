<?php
// database.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

class Database {
    private $host = 'localhost';
    private $db_name = 'cloud_inventario';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
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
}

// Instancia de la clase
$database = new Database();
$conn = $database->getConnection();

// Consulta a la base de datos
try {
    $stmt = $conn->query("SELECT * FROM productos");
    $productos = $stmt->fetchAll();

    echo json_encode($productos);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los productos.',
        'error' => $e->getMessage()
    ]);
}
?>
