<?php
// api/products.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivos necesarios
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    $product = new Product($db);
    $method = $_SERVER['REQUEST_METHOD'];

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
                    http_response_code(200);
                    echo json_encode($product_arr);
                } else {
                    http_response_code(404);
                    echo json_encode(array("success" => false, "message" => "Producto no encontrado."));
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
                http_response_code(200);
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
                http_response_code(200);
                echo json_encode($products_arr);
            } elseif(isset($_GET['stats'])) {
                // Obtener estadísticas
                $stats = $product->getStats();
                http_response_code(200);
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
                http_response_code(200);
                echo json_encode($products_arr);
            }
            break;

        case 'POST':
            // Crear nuevo producto
            $data = json_decode(file_get_contents("php://input"));
            
            if(!empty($data->name) && isset($data->price) && isset($data->stock) && !empty($data->category)) {
                $product->name = $data->name;
                $product->description = $data->description ?? '';
                $product->price = $data->price;
                $product->stock = $data->stock;
                $product->category = $data->category;
                
                if($product->create()) {
                    http_response_code(201);
                    echo json_encode(array(
                        "success" => true,
                        "message" => "Producto creado exitosamente.",
                        "id" => $product->id
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array(
                        "success" => false,
                        "message" => "No se pudo crear el producto."
                    ));
                }
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Datos incompletos. Se requiere: name, price, stock, category"
                ));
            }
            break;

        case 'PUT':
            // Actualizar producto
            $data = json_decode(file_get_contents("php://input"));
            
            if(!empty($data->id) && !empty($data->name) && isset($data->price) && 
               isset($data->stock) && !empty($data->category)) {
                $product->id = $data->id;
                $product->name = $data->name;
                $product->description = $data->description ?? '';
                $product->price = $data->price;
                $product->stock = $data->stock;
                $product->category = $data->category;
                
                if($product->update()) {
                    http_response_code(200);
                    echo json_encode(array(
                        "success" => true,
                        "message" => "Producto actualizado exitosamente."
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array(
                        "success" => false,
                        "message" => "No se pudo actualizar el producto."
                    ));
                }
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Datos incompletos para actualización."
                ));
            }
            break;

        case 'DELETE':
            // Eliminar producto
            $data = json_decode(file_get_contents("php://input"));
            
            if(!empty($data->id)) {
                $product->id = $data->id;
                
                if($product->delete()) {
                    http_response_code(200);
                    echo json_encode(array(
                        "success" => true,
                        "message" => "Producto eliminado exitosamente."
                    ));
                } else {
                    http_response_code(503);
                    echo json_encode(array(
                        "success" => false,
                        "message" => "No se pudo eliminar el producto."
                    ));
                }
            } else {
                http_response_code(400);
                echo json_encode(array(
                    "success" => false,
                    "message" => "ID del producto requerido."
                ));
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(array(
                "success" => false,
                "message" => "Método no permitido."
            ));
            break;
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Error interno del servidor: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ));
}
?>