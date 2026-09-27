<?php
require_once 'db.php';

abstract class BaseModel {
    protected $db;
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
}

class Product extends BaseModel {
    public function getAll($search = '', $category_id = '', $sort_by = 'product_name', $order = 'ASC') {
        $sql = "SELECT p.*, c.category_name, s.supplier_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
                WHERE 1=1";
        
        if (!empty($search)) {
            $sql .= " AND p.product_name LIKE '%" . $this->db->real_escape_string($search) . "%'";
        }

        if (!empty($category_id)) {
            $sql .= " AND p.category_id = " . intval($category_id);
        }

        $allowed_sort = ['product_name', 'unit_price', 'stock_quantity', 'product_id'];
        if (!in_array($sort_by, $allowed_sort)) {
            $sort_by = 'product_id';
        }

        $order = ($order === 'DESC') ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$sort_by} {$order}";

        return $this->db->query($sql);
    }

    public function create($product_name, $category_id, $supplier_id, $price, $stock_quantity, $reorder_level) {
        if ($price < 0) {
            throw new Exception("Price cannot be negative.");
        }

        // Changed $this->conn to $this->db here:
        $query = "INSERT INTO products (product_name, category_id, supplier_id, unit_price, stock_quantity, reorder_level) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("siidii", $product_name, $category_id, $supplier_id, $price, $stock_quantity, $reorder_level);
        return $stmt->execute();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $name, $cat_id, $sup_id, $price, $stock, $reorder) {
        $stmt = $this->db->prepare("UPDATE products SET product_name = ?, category_id = ?, supplier_id = ?, unit_price = ?, stock_quantity = ?, reorder_level = ? WHERE product_id = ?");
        $stmt->bind_param("siidiii", $name, $cat_id, $sup_id, $price, $stock, $reorder, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>