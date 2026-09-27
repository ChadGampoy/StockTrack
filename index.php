<?php
require_once 'Product.php';
$productObj = new Product();

$editProduct = null;
$error = null;

// Handle Delete Action
if (isset($_GET['delete'])) {
    try {
        $productObj->delete($_GET['delete']);
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Edit Action
if (isset($_GET['edit'])) {
    $editProduct = $productObj->getById($_GET['edit']);
}

// Handle Form Submission (Add or Update)
if (isset($_POST['save_product'])) {
    try {
        if ($_POST['price'] <= 0) {
            throw new Exception("Price must be greater than zero.");
        }

        if (!empty($_POST['product_id'])) {
            $productObj->update(
                $_POST['product_id'],
                $_POST['name'],
                $_POST['category_id'],
                $_POST['supplier_id'],
                $_POST['price'],
                $_POST['stock'],
                $_POST['reorder']
            );
        } else {
            $productObj->create(
                $_POST['name'],
                $_POST['category_id'],
                $_POST['supplier_id'],
                $_POST['price'],
                $_POST['stock'],
                $_POST['reorder']
            );
        }

        header("Location: index.php?success=1");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Search, Filter, and Sort Handling
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['category_id'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'product_id';

$products = $productObj->getAll($search, $cat_filter, $sort_by);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Viewport Meta Tag for Mobile Responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockTrack - Grocery Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-3 p-md-4">
<div class="container-fluid container-md">
    <h2 class="mb-4 text-center text-md-start">StockTrack: Grocery Store Inventory Management</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Operation completed successfully!</div>
    <?php endif; ?>

    <!-- Search, Filter & Sort Bar -->
    <form method="GET" class="row g-2 mb-4">
        <div class="col-12 col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search product..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-12 col-md-3">
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                <option value="1" <?= ($cat_filter == '1') ? 'selected' : '' ?>>Beverages</option>
                <option value="2" <?= ($cat_filter == '2') ? 'selected' : '' ?>>Snacks</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <select name="sort_by" class="form-select">
                <option value="product_id" <?= ($sort_by == 'product_id') ? 'selected' : '' ?>>Sort by ID</option>
                <option value="product_name" <?= ($sort_by == 'product_name') ? 'selected' : '' ?>>Sort by Name</option>
                <option value="unit_price" <?= ($sort_by == 'unit_price') ? 'selected' : '' ?>>Sort by Price</option>
                <option value="stock_quantity" <?= ($sort_by == 'stock_quantity') ? 'selected' : '' ?>>Sort by Stock</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter / Sort</button>
        </div>
    </form>

    <!-- Product Form (Add / Edit) -->
    <div class="card mb-4 p-3 shadow-sm">
        <h5><?= $editProduct ? 'Edit Product ID #' . $editProduct['product_id'] : 'Add New Product' ?></h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="product_id" value="<?= $editProduct['product_id'] ?? '' ?>">
            
            <div class="col-12 col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Product Name" value="<?= htmlspecialchars($editProduct['product_name'] ?? '') ?>" required>
            </div>
            <div class="col-6 col-md-2">
                <select name="category_id" class="form-select" required>
                    <option value="1" <?= (($editProduct['category_id'] ?? '') == '1') ? 'selected' : '' ?>>Beverages</option>
                    <option value="2" <?= (($editProduct['category_id'] ?? '') == '2') ? 'selected' : '' ?>>Snacks</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="supplier_id" class="form-select" required>
                    <option value="1" <?= (($editProduct['supplier_id'] ?? '') == '1') ? 'selected' : '' ?>>Universal Robina</option>
                </select>
            </div>
            <div class="col-4 col-md-2">
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Price" value="<?= $editProduct['unit_price'] ?? '' ?>" required>
            </div>
            <div class="col-4 col-md-1">
                <input type="number" name="stock" class="form-control" placeholder="Stock" value="<?= $editProduct['stock_quantity'] ?? '' ?>" required>
            </div>
            <div class="col-4 col-md-1">
                <input type="number" name="reorder" class="form-control" placeholder="Reorder" value="<?= $editProduct['reorder_level'] ?? '' ?>" required>
            </div>
            <div class="col-12 col-md-1">
                <button type="submit" name="save_product" class="btn <?= $editProduct ? 'btn-warning' : 'btn-success' ?> w-100">
                    <?= $editProduct ? 'Update' : 'Add' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Responsive Table Wrapper -->
    <div class="table-responsive">
        <table class="table table-bordered bg-white shadow-sm align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Product Name</th><th>Category</th><th>Supplier</th><th>Price</th><th>Stock</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $products->fetch_assoc()): ?>
                <tr class="<?= ($row['stock_quantity'] <= $row['reorder_level']) ? 'table-danger' : '' ?>">
                    <td><?= $row['product_id'] ?></td>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                    <td>₱<?= number_format($row['unit_price'], 2) ?></td>
                    <td><?= $row['stock_quantity'] ?></td>
                    <td class="text-nowrap">
                        <a href="index.php?edit=<?= $row['product_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="index.php?delete=<?= $row['product_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>