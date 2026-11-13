<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'Product.php';
$productObj = new Product();

// Fetch categories & suppliers dynamically for dropdowns
require_once 'db.php';
$db = new Database();
$conn = $db->getConnection();
$categoriesList = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetch_all(MYSQLI_ASSOC);
$suppliersList = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name ASC")->fetch_all(MYSQLI_ASSOC);

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
$low_stock_only = isset($_GET['low_stock']) && $_GET['low_stock'] == '1';

// 1. Get global store metrics (always accurate even when filtered)
$all_res = $productObj->getAll();
$total_products = 0;
$low_stock_count = 0;
$total_inventory_value = 0;

while ($r = $all_res->fetch_assoc()) {
    $total_products++;
    if ($r['stock_quantity'] <= $r['reorder_level']) {
        $low_stock_count++;
    }
    $total_inventory_value += ($r['unit_price'] * $r['stock_quantity']);
}

// 2. Get filtered rows for the table display
$result_set = $productObj->getAll($search, $cat_filter, $sort_by, $low_stock_only);
$products = [];
while ($row = $result_set->fetch_assoc()) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockTrack - Grocery Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-box-seam me-2"></i>StockTrack IMS</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 small"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['role'] ?? 'Staff' ?>)</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <!-- Analytics & Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase m-0 small fw-semibold">Total Products</h6>
                        <h3 class="fw-bold m-0 mt-1"><?= $total_products ?></h3>
                    </div>
                    <i class="bi bi-boxes fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="<?= $low_stock_only ? 'index.php' : 'index.php?low_stock=1' ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm bg-danger text-white p-3 <?= $low_stock_only ? 'border border-3 border-dark' : '' ?>" style="cursor: pointer;" title="Click to filter low stock items">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase m-0 small fw-semibold">
                                Low Stock Alerts <?= $low_stock_only ? '<span class="badge bg-dark ms-1">Filtered</span>' : '' ?>
                            </h6>
                            <h3 class="fw-bold m-0 mt-1"><?= $low_stock_count ?></h3>
                        </div>
                        <i class="bi bi-exclamation-triangle-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase m-0 small fw-semibold">Inventory Value</h6>
                        <h3 class="fw-bold m-0 mt-1">₱<?= number_format($total_inventory_value, 2) ?></h3>
                    </div>
                    <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-octagon me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success shadow-sm"><i class="bi bi-check-circle me-2"></i>Operation completed successfully!</div>
    <?php endif; ?>

    <!-- Search, Filter & Sort Bar -->
    <div class="card border-0 shadow-sm mb-4 p-3">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search product..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= ($cat_filter == $cat['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <select name="sort_by" class="form-select">
                    <option value="product_id" <?= ($sort_by == 'product_id') ? 'selected' : '' ?>>Sort by ID</option>
                    <option value="product_name" <?= ($sort_by == 'product_name') ? 'selected' : '' ?>>Sort by Name</option>
                    <option value="unit_price" <?= ($sort_by == 'unit_price') ? 'selected' : '' ?>>Sort by Price</option>
                    <option value="stock_quantity" <?= ($sort_by == 'stock_quantity') ? 'selected' : '' ?>>Sort by Stock</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-filter me-1"></i> Filter</button>
            </div>
            <div class="col-6 col-md-2">
                <a href="index.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i> Clear</a>
            </div>
            <div class="col-12 mt-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="lowStockSwitch" <?= $low_stock_only ? 'checked' : '' ?> onchange="this.form.submit()">
                    <label class="form-check-label small fw-semibold text-danger" for="lowStockSwitch">
                        Show Low-Stock / Reorder Items Only
                    </label>
                </div>
            </div>
        </form>
    </div>

    <!-- Product Form (Add / Edit) -->
    <div class="card border-0 shadow-sm mb-4 p-4">
        <h5 class="fw-bold mb-3 text-secondary">
            <i class="bi <?= $editProduct ? 'bi-pencil-square text-warning' : 'bi-plus-circle text-success' ?> me-2"></i>
            <?= $editProduct ? 'Edit Product ID #' . $editProduct['product_id'] : 'Add New Product Record' ?>
        </h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="product_id" value="<?= $editProduct['product_id'] ?? '' ?>">
            
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Product Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Soda 1.5L" value="<?= htmlspecialchars($editProduct['product_name'] ?? '') ?>" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Category</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= (($editProduct['category_id'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Supplier</label>
                <select name="supplier_id" class="form-select" required>
                    <?php foreach ($suppliersList as $sup): ?>
                        <option value="<?= $sup['supplier_id'] ?>" <?= (($editProduct['supplier_id'] ?? '') == $sup['supplier_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sup['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2">
                <label class="form-label small text-muted">Unit Price (₱)</label>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" value="<?= $editProduct['unit_price'] ?? '' ?>" required>
            </div>
            <div class="col-4 col-md-1">
                <label class="form-label small text-muted">Stock</label>
                <input type="number" name="stock" class="form-control" placeholder="Qty" value="<?= $editProduct['stock_quantity'] ?? '' ?>" required>
            </div>
            <div class="col-4 col-md-1">
                <label class="form-label small text-muted">Reorder</label>
                <input type="number" name="reorder" class="form-control" placeholder="Min" value="<?= $editProduct['reorder_level'] ?? '' ?>" required>
            </div>
            
            <!-- Dynamic Buttons: Add vs. Update + Cancel -->
            <div class="col-12 <?= $editProduct ? 'col-md-2' : 'col-md-1' ?> d-flex align-items-end gap-1">
                <?php if ($editProduct): ?>
                    <button type="submit" name="save_product" class="btn btn-warning text-dark fw-bold w-50" title="Save Changes">Update</button>
                    <a href="index.php" class="btn btn-secondary w-50 text-center text-decoration-none d-flex align-items-center justify-content-center" title="Cancel Edit">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="save_product" class="btn btn-success w-100">Add</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Responsive Inventory Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="m-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Inventory Item List</h5>
        </div>
        <div class="table-responsive m-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No inventory records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $row): ?>
                        <tr class="<?= ($row['stock_quantity'] <= $row['reorder_level']) ? 'table-danger' : '' ?>">
                            <td class="fw-semibold">#<?= $row['product_id'] ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['category_name']) ?></span></td>
                            <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                            <td class="fw-bold text-success">₱<?= number_format($row['unit_price'], 2) ?></td>
                            <td>
                                <?= $row['stock_quantity'] ?>
                                <?php if ($row['stock_quantity'] <= $row['reorder_level']): ?>
                                    <span class="badge bg-danger ms-1">Low Stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="index.php?edit=<?= $row['product_id'] ?>" class="btn btn-warning btn-sm px-2"><i class="bi bi-pencil-square"></i> Edit</a>
                                <a href="index.php?delete=<?= $row['product_id'] ?>" class="btn btn-danger btn-sm px-2" onclick="return confirm('Are you sure you want to delete this record?')"><i class="bi bi-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>