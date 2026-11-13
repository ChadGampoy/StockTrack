<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'Product.php';

$productObj = new Product();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $item = $productObj->getById($_GET['id']);
            if ($item) {
                http_response_code(200);
                echo json_encode(["status" => "success", "data" => $item]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Product not found"]);
            }
        } else {
            $res = $productObj->getAll($_GET['search'] ?? '', $_GET['category_id'] ?? '', $_GET['sort_by'] ?? 'product_id');
            $items = [];
            while ($row = $res->fetch_assoc()) {
                $items[] = $row;
            }
            http_response_code(200);
            echo json_encode(["status" => "success", "count" => count($items), "data" => $items]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['product_name']) && isset($data['unit_price'])) {
            $ok = $productObj->create(
                $data['product_name'],
                $data['category_id'] ?? 1,
                $data['supplier_id'] ?? 1,
                $data['unit_price'],
                $data['stock_quantity'] ?? 0,
                $data['reorder_level'] ?? 5
            );
            http_response_code($ok ? 201 : 400);
            echo json_encode(["status" => $ok ? "created" : "error"]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['product_id'])) {
            $ok = $productObj->update(
                $data['product_id'],
                $data['product_name'],
                $data['category_id'],
                $data['supplier_id'],
                $data['unit_price'],
                $data['stock_quantity'],
                $data['reorder_level']
            );
            http_response_code($ok ? 200 : 400);
            echo json_encode(["status" => $ok ? "updated" : "error"]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Product ID required"]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if ($id) {
            $ok = $productObj->delete($id);
            http_response_code($ok ? 200 : 400);
            echo json_encode(["status" => $ok ? "deleted" : "error"]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Product ID required"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
        break;
}
?>