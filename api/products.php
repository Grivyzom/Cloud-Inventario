<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../models/Product.php';

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

$product = new Product($db);
$method = $_SERVER['REQUEST_METHOD'];

function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true);
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['test'])) {
                $test_result = $database->testConnection();
                sendResponse($test_result);
                
            } elseif (isset($_GET['stats'])) {
                $stats = $product->getStats();
                sendResponse([
                    'success' => true,
                    'data' => $stats
                ]);
                
            } elseif (isset($_GET['id'])) {
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
                
            } else {
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
            $data = getJsonInput();
            
            if (!$data) {
                sendResponse([
                    'success' => false,
                    'message' => 'Datos JSON inválidos'
                ], 400);
            }

            $required_fields = ['name', 'price', 'stock'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    sendResponse([
                        'success' => false,
                        'message' => "El campo '{$field}' es requerido"
                    ], 400);
                }
            }

            $product->name = $data['name'];
            $product->description = $data['description'] ?? '';
            $product->price = floatval($data['price']);
            $product->stock = intval($data['stock']);
            $product->category = $data['category'] ?? 'Otros';

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