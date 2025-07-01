<?php
// models/Product.php - Actualizado para base de datos completa
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $sku;
    public $name;
    public $description;
    public $selling_price;
    public $stock_current;
    public $category_id;
    public $supplier_id;
    public $brand;
    public $model;
    public $is_active;
    public $date_created;
    public $date_updated;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear producto
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (sku, name, description, selling_price, stock_current, category_id, is_active, date_created) 
                  VALUES (:sku, :name, :description, :selling_price, :stock_current, :category_id, :is_active, NOW())";

        $stmt = $this->conn->prepare($query);

        // Generar SKU automático si no se proporciona
        if (empty($this->sku)) {
            $this->sku = 'PRD-' . time() . '-' . rand(100, 999);
        }

        // Limpiar datos
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
        $this->selling_price = floatval($this->selling_price);
        $this->stock_current = intval($this->stock_current);
        $this->category_id = !empty($this->category_id) ? intval($this->category_id) : null;
        $this->is_active = 1;

        // Bind valores
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":selling_price", $this->selling_price);
        $stmt->bindParam(":stock_current", $this->stock_current);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":is_active", $this->is_active);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Leer todos los productos con información de categorías
    public function read() {
        $query = "SELECT 
                    p.id,
                    p.sku,
                    p.name,
                    p.description,
                    p.selling_price as price,
                    p.stock_current as stock,
                    p.brand,
                    p.model,
                    p.is_active,
                    p.date_created,
                    p.date_updated,
                    c.name as category_name,
                    COALESCE(c.name, 'Sin categoría') as category
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_active = 1
                  ORDER BY p.date_created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un producto por ID
    public function readOne() {
        $query = "SELECT 
                    p.*,
                    c.name as category_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = :id AND p.is_active = 1 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->sku = $row['sku'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->selling_price = $row['selling_price'];
            $this->stock_current = $row['stock_current'];
            $this->category_id = $row['category_id'];
            $this->brand = $row['brand'];
            $this->model = $row['model'];
            $this->is_active = $row['is_active'];
            $this->date_created = $row['date_created'];
            $this->date_updated = $row['date_updated'];
            return true;
        }
        return false;
    }

    // Actualizar producto
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, 
                      description = :description, 
                      selling_price = :selling_price, 
                      stock_current = :stock_current, 
                      category_id = :category_id,
                      date_updated = NOW()
                  WHERE id = :id AND is_active = 1";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description ?? ''));
        $this->selling_price = floatval($this->selling_price);
        $this->stock_current = intval($this->stock_current);
        $this->category_id = !empty($this->category_id) ? intval($this->category_id) : null;
        $this->id = intval($this->id);

        // Bind valores
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":selling_price", $this->selling_price);
        $stmt->bindParam(":stock_current", $this->stock_current);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Eliminar producto (soft delete)
    public function delete() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 0, date_updated = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $this->id = intval($this->id);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Buscar productos
    public function search($keywords) {
        $query = "SELECT 
                    p.id,
                    p.sku,
                    p.name,
                    p.description,
                    p.selling_price as price,
                    p.stock_current as stock,
                    p.brand,
                    p.model,
                    p.date_created,
                    p.date_updated,
                    COALESCE(c.name, 'Sin categoría') as category
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_active = 1 
                  AND (p.name LIKE :keywords OR p.description LIKE :keywords OR p.sku LIKE :keywords)
                  ORDER BY p.date_created DESC";
        
        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(":keywords", $keywords);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener productos por categoría
    public function getByCategory($category_name) {
        $query = "SELECT 
                    p.id,
                    p.sku,
                    p.name,
                    p.description,
                    p.selling_price as price,
                    p.stock_current as stock,
                    p.brand,
                    p.model,
                    p.date_created,
                    p.date_updated,
                    c.name as category
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_active = 1 
                  AND c.name = :category
                  ORDER BY p.date_created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":category", $category_name);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener estadísticas
    public function getStats() {
        $stats = array();
        
        // Total de productos activos
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total'] = intval($row['total']);
        
        // Productos disponibles (stock > 0)
        $query = "SELECT COUNT(*) as available FROM " . $this->table_name . " WHERE is_active = 1 AND stock_current > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['available'] = intval($row['available']);
        
        // Productos con stock bajo (stock <= 5)
        $query = "SELECT COUNT(*) as low_stock FROM " . $this->table_name . " WHERE is_active = 1 AND stock_current <= 5 AND stock_current > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['low_stock'] = intval($row['low_stock']);
        
        // Productos agotados
        $query = "SELECT COUNT(*) as out_of_stock FROM " . $this->table_name . " WHERE is_active = 1 AND stock_current = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['out_of_stock'] = intval($row['out_of_stock']);
        
        return $stats;
    }

    // Obtener categorías disponibles
    public function getCategories() {
        $query = "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>