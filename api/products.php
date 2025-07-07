<?php
// api/products.php - API completa para productos
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS y de respuesta
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir archivos necesarios
require_once '../config/database.php';
require_once '../models/Product.php';

// Crear conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo conectar a la base de datos'
    ]);
    exit;
}

// Instanciar el modelo Product
$product = new Product($db);

// Obtener el método de la request
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Función para enviar respuesta JSON
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para obtener datos JSON del body
function getJsonInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true);
}

try {
    switch ($method) {
        case 'GET':
            // Verificar parámetros de la URL
            if (isset($_GET['test'])) {
                // Test de conexión
                $test_result = $database->testConnection();
                sendResponse($test_result);
                
            } elseif (isset($_GET['stats'])) {
                // Obtener estadísticas
                $stats = $product->getStats();
                sendResponse([
                    'success' => true,
                    'data' => $stats
                ]);
                
            } elseif (isset($_GET['id'])) {
                // Obtener un producto específico
                $product->id = intval($_GET['id']);
                if ($product->readOne()) {
                    sendResponse([
                        'success' => true,
                        'data' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => floatval($product->price),
                            'stock' => intval($product->stock),
                            'category' => $product->category,
                            'date_added' => $product->date_added,
                            'date_updated' => $product->date_updated
                        ]
                    ]);
                } else {
                    sendResponse([
                        'success' => false,
                        'message' => 'Producto no encontrado'
                    ], 404);
                }
                
            } elseif (isset($_GET['search'])) {
                // Buscar productos
                $keywords = $_GET['search'];
                $stmt = $product->search($keywords);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse([
                    'success' => true,
                    'data' => $products,
                    'count' => count($products)
                ]);
                
            } elseif (isset($_GET['category'])) {
                // Obtener productos por categoría
                $category = $_GET['category'];
                $stmt = $product->getByCategory($category);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse([
                    'success' => true,
                    'data' => $products,
                    'count' => count($products)
                ]);
                
            } else {
                // Obtener todos los productos
                $stmt = $product->read();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse([
                    'success' => true,
                    'data' => $products,
                    'count' => count($products)
                ]);
            }
            break;

        case 'POST':
            // Crear nuevo producto
            $data = getJsonInput();
            
            if (!$data) {
                sendResponse([
                    'success' => false,
                    'message' => 'Datos JSON inválidos'
                ], 400);
            }

            // Validar campos requeridos
            $required_fields = ['name', 'price', 'stock'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    sendResponse([
                        'success' => false,
                        'message' => "El campo '{$field}' es requerido"
                    ], 400);
                }
            }

            // Asignar valores al producto
            $product->name = $data['name'];
            $product->description = $data['description'] ?? '';
            $product->price = floatval($data['price']);
            $product->stock = intval($data['stock']);
            $product->category = $data['category'] ?? 'Otros';

            // Crear el producto
            if ($product->create()) {
                sendResponse([
                    'success' => true,
                    'message' => 'Producto creado exitosamente',
                    'data' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'stock' => $product->stock,
                        'category' => $product->category
                    ]
                ], 201);
            } else {
                sendResponse([
                    'success' => false,
                    'message' => 'Error al crear el producto'
                ], 500);
            }
            break;

        case 'PUT':
            // Actualizar producto
            $data = getJsonInput();
            
            if (!$data || !isset($data['id'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'ID del producto es requerido'
                ], 400);
            }

            // Asignar valores al producto
            $product->id = intval($data['id']);
            $product->name = $data['name'] ?? '';
            $product->description = $data['description'] ?? '';
            $product->price = isset($data['price']) ? floatval($data['price']) : 0;
            $product->stock = isset($data['stock']) ? intval($data['stock']) : 0;
            $product->category = $data['category'] ?? '';

            // Actualizar el producto
            if ($product->update()) {
                sendResponse([
                    'success' => true,
                    'message' => 'Producto actualizado exitosamente'
                ]);
            } else {
                sendResponse([
                    'success' => false,
                    'message' => 'Error al actualizar el producto'
                ], 500);
            }
            break;

        case 'DELETE':
            // Eliminar producto
            $data = getJsonInput();
            
            if (!$data || !isset($data['id'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'ID del producto es requerido'
                ], 400);
            }

            $product->id = intval($data['id']);

            // Eliminar el producto
            if ($product->delete()) {
                sendResponse([
                    'success' => true,
                    'message' => 'Producto eliminado exitosamente'
                ]);
            } else {
                sendResponse([
                    'success' => false,
                    'message' => 'Error al eliminar el producto'
                ], 500);
            }
            break;

        default:
            sendResponse([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            break;
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ], 500);
}
?>