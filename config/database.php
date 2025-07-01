<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'cloud_inventario';
    private $username = 'root';
    private $password = 'inacap2025';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>

<?php
// models/Product.php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $category;
    public $date_added;
    public $date_updated;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear producto
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, price, stock, category, date_added) 
                  VALUES (:name, :description, :price, :stock, :category, NOW())";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->category = htmlspecialchars(strip_tags($this->category));

        // Bind valores
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":category", $this->category);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Leer todos los productos
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY date_added DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un producto por ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->stock = $row['stock'];
            $this->category = $row['category'];
            $this->date_added = $row['date_added'];
            $this->date_updated = $row['date_updated'];
            return true;
        }
        return false;
    }

    // Actualizar producto
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, description = :description, price = :price, 
                      stock = :stock, category = :category, date_updated = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind valores
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Eliminar producto
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Buscar productos
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE name LIKE :keywords OR description LIKE :keywords 
                  ORDER BY date_added DESC";
        
        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(":keywords", $keywords);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener productos por categoría
    public function getByCategory($category) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE category = :category 
                  ORDER BY date_added DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":category", $category);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener estadísticas
    public function getStats() {
        $stats = array();
        
        // Total de productos
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total'] = $row['total'];
        
        // Productos disponibles (stock > 0)
        $query = "SELECT COUNT(*) as available FROM " . $this->table_name . " WHERE stock > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['available'] = $row['available'];
        
        // Productos con stock bajo (stock <= 5)
        $query = "SELECT COUNT(*) as low_stock FROM " . $this->table_name . " WHERE stock <= 5 AND stock > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['low_stock'] = $row['low_stock'];
        
        // Productos agotados
        $query = "SELECT COUNT(*) as out_of_stock FROM " . $this->table_name . " WHERE stock = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['out_of_stock'] = $row['out_of_stock'];
        
        return $stats;
    }
}
?>

<?php
// api/products.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            if(isset($_GET['id'])) {
                // Obtener un producto específico
                $product->id = $_GET['id'];
                if($product->readOne()) {
                    $product_arr = array(
                        "id" => $product->id,
                        "name" => $product->name,
                        "description" => $product->description,
                        "price" => floatval($product->price),
                        "stock" => intval($product->stock),
                        "category" => $product->category,
                        "date_added" => $product->date_added,
                        "date_updated" => $product->date_updated
                    );
                    echo json_encode($product_arr);
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Producto no encontrado."));
                }
            } elseif(isset($_GET['search'])) {
                // Buscar productos
                $stmt = $product->search($_GET['search']);
                $products_arr = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $product_item = array(
                        "id" => $row['id'],
                        "name" => $row['name'],
                        "description" => $row['description'],
                        "price" => floatval($row['price']),
                        "stock" => intval($row['stock']),
                        "category" => $row['category'],
                        "date_added" => $row['date_added'],
                        "date_updated" => $row['date_updated']
                    );
                    array_push($products_arr, $product_item);
                }
                echo json_encode($products_arr);
            } elseif(isset($_GET['category'])) {
                // Obtener productos por categoría
                $stmt = $product->getByCategory($_GET['category']);
                $products_arr = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $product_item = array(
                        "id" => $row['id'],
                        "name" => $row['name'],
                        "description" => $row['description'],
                        "price" => floatval($row['price']),
                        "stock" => intval($row['stock']),
                        "category" => $row['category'],
                        "date_added" => $row['date_added'],
                        "date_updated" => $row['date_updated']
                    );
                    array_push($products_arr, $product_item);
                }
                echo json_encode($products_arr);
            } elseif(isset($_GET['stats'])) {
                // Obtener estadísticas
                $stats = $product->getStats();
                echo json_encode($stats);
            } else {
                // Obtener todos los productos
                $stmt = $product->read();
                $products_arr = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $product_item = array(
                        "id" => $row['id'],
                        "name" => $row['name'],
                        "description" => $row['description'],
                        "price" => floatval($row['price']),
                        "stock" => intval($row['stock']),
                        "category" => $row['category'],
                        "date_added" => $row['date_added'],
                        "date_updated" => $row['date_updated']
                    );
                    array_push($products_arr, $product_item);
                }
                echo json_encode($products_arr);
            }
            break;

        case 'POST':
            // Crear nuevo producto
            $data = json_decode(file_get_contents("php://input"));
            
            if(!empty($data->name) && !empty($data->price) && !empty($data->stock) && !empty($data->category)) {
                $product->name = $data->name;
                $product->description = $data->description ?? '';
                $product->price = $data->price;
                $product->stock = $data->stock;
                $product->category = $data->category;
                
                if($product->create()) {
                    http_response_code(201);
                    echo json_encode(array(
                        "message" => "Producto creado exitosamente.",
                        "id" => $product->id
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "No se pudo crear el producto."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Datos incompletos."));
            }
            break;

        case 'PUT':
            // Actualizar producto
            $data = json_decode(file_get_contents("php://input"));
            
            if(!empty($data->id) && !empty($data->name) && !empty($data->price) && 
               !empty($data->stock) && !empty($data->category)) {
                $product->id = $data->id;
                $product->name = $data->name;
                $product->description = $data->description ?? '';
                $product->price = $data->price;
                $product->stock = $data->stock;
                $product->category = $data->category;
                
                if($product->update()) {
                    echo json_encode(array("message" => "Producto actualizado exitosamente."));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "No se pudo actualizar el producto."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "Datos incompletos."));
            }
            break;

        case 'DELETE':
            // Eliminar producto
            $data = json_decode(file_get_contents("php://input"));
            
            if(!empty($data->id)) {
                $product->id = $data->id;
                
                if($product->delete()) {
                    echo json_encode(array("message" => "Producto eliminado exitosamente."));
                } else {
                    http_response_code(503);
                    echo json_encode(array("message" => "No se pudo eliminar el producto."));
                }
            } else {
                http_response_code(400);
                echo json_encode(array("message" => "ID del producto requerido."));
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(array("message" => "Método no permitido."));
            break;
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error interno del servidor: " . $e->getMessage()));
}
?>

<?php
// models/User.php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $date_created;
    public $date_updated;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear usuario
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password, role, date_created) 
                  VALUES (:name, :email, :password, :role, NOW())";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Hash de la contraseña
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind valores
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":role", $this->role);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Verificar si el email ya existe
    public function emailExists() {
        $query = "SELECT id, name, email, password, role FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }

    // Autenticar usuario
    public function authenticate($password) {
        return password_verify($password, $this->password);
    }
}
?>

<?php
// api/auth.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(isset($data->action)) {
        switch($data->action) {
            case 'login':
                if(!empty($data->email) && !empty($data->password)) {
                    $user->email = $data->email;
                    
                    if($user->emailExists()) {
                        if($user->authenticate($data->password)) {
                            $user_data = array(
                                "id" => $user->id,
                                "name" => $user->name,
                                "email" => $user->email,
                                "role" => $user->role
                            );
                            
                            echo json_encode(array(
                                "success" => true,
                                "message" => "Login exitoso",
                                "user" => $user_data
                            ));
                        } else {
                            http_response_code(401);
                            echo json_encode(array(
                                "success" => false,
                                "message" => "Contraseña incorrecta"
                            ));
                        }
                    } else {
                        http_response_code(404);
                        echo json_encode(array(
                            "success" => false,
                            "message" => "Usuario no encontrado"
                        ));
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(array(
                        "success" => false,
                        "message" => "Email y contraseña requeridos"
                    ));
                }
                break;

            case 'register':
                if(!empty($data->name) && !empty($data->email) && !empty($data->password)) {
                    $user->email = $data->email;
                    
                    if(!$user->emailExists()) {
                        $user->name = $data->name;
                        $user->password = $data->password;
                        $user->role = 'user';
                        
                        if($user->create()) {
                            http_response_code(201);
                            echo json_encode(array(
                                "success" => true,
                                "message" => "Usuario creado exitosamente",
                                "user_id" => $user->id
                            ));
                        } else {
                            http_response_code(503);
                            echo json_encode(array(
                                "success" => false,
                                "message" => "No se pudo crear el usuario"
                            ));
                        }
                    } else {
                        http_response_code(409);
                        echo json_encode(array(
                            "success" => false,
                            "message" => "El email ya está registrado"
                        ));
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(array(
                        "success" => false,
                        "message" => "Nombre, email y contraseña requeridos"
                    ));
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Acción no válida"
                ));
        }
    } else {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Acción requerida"
        ));
    }
} else {
    http_response_code(405);
    echo json_encode(array(
        "success" => false,
        "message" => "Método no permitido"
    ));
}
?>