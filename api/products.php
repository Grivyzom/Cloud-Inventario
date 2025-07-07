<?php
// models/Product.php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $sku;
    public $name;
    public $description;
    public $selling_price;
    public $stock_current;
    public $stock_minimum;
    public $category_id;
    public $supplier_id;
    public $brand;
    public $model;
    public $barcode;
    public $weight;
    public $dimensions;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear producto
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  (sku, name, description, selling_price, stock_current, stock_minimum, category_id, supplier_id, brand, model, barcode, weight, dimensions, is_active, date_created, date_updated)
                  VALUES
                  (:sku, :name, :description, :selling_price, :stock_current, :stock_minimum, :category_id, :supplier_id, :brand, :model, :barcode, :weight, :dimensions, :is_active, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);

        // Limpieza de datos
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->selling_price = htmlspecialchars(strip_tags($this->selling_price));
        $this->stock_current = htmlspecialchars(strip_tags($this->stock_current));
        $this->stock_minimum = htmlspecialchars(strip_tags($this->stock_minimum));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->supplier_id = htmlspecialchars(strip_tags($this->supplier_id));
        $this->brand = htmlspecialchars(strip_tags($this->brand));
        $this->model = htmlspecialchars(strip_tags($this->model));
        $this->barcode = htmlspecialchars(strip_tags($this->barcode));
        $this->weight = htmlspecialchars(strip_tags($this->weight));
        $this->dimensions = htmlspecialchars(strip_tags($this->dimensions));
        $this->is_active = htmlspecialchars(strip_tags($this->is_active));

        // Enlazar parámetros
        $stmt->bindParam(':sku', $this->sku);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':selling_price', $this->selling_price);
        $stmt->bindParam(':stock_current', $this->stock_current);
        $stmt->bindParam(':stock_minimum', $this->stock_minimum);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':supplier_id', $this->supplier_id);
        $stmt->bindParam(':brand', $this->brand);
        $stmt->bindParam(':model', $this->model);
        $stmt->bindParam(':barcode', $this->barcode);
        $stmt->bindParam(':weight', $this->weight);
        $stmt->bindParam(':dimensions', $this->dimensions);
        $stmt->bindParam(':is_active', $this->is_active);

        // Ejecutar
        if ($stmt->execute()) {
            return true;
        }

        // Mostrar error en caso de fallo
        echo "Error al insertar: ";
        print_r($stmt->errorInfo());
        return false;
    }
}
?>
