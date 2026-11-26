<?php
/**
 * BUYOERA - Admin Edit Product
 */

$adminPageTitle = 'Edit Product';
require_once __DIR__ . '/includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: products.php");
    exit;
}

$db = Database::getConnection();

// Fetch Categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// Fetch Product
$stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $sku = sanitize($_POST['sku'] ?? '');
        $regularPrice = (float)($_POST['regular_price'] ?? 0);
        $salePrice = (float)($_POST['sale_price'] ?? 0);
        $supplierCost = (float)($_POST['supplier_cost'] ?? 0);
        $supplierName = sanitize($_POST['supplier_name'] ?? '');
        $supplierSku = sanitize($_POST['supplier_sku'] ?? '');
        $supplierUrl = sanitize($_POST['supplier_url'] ?? '');
        $stock = (int)($_POST['stock_quantity'] ?? 0);
        $shortDesc = sanitize($_POST['short_description'] ?? '');
        $desc = $_POST['description'] ?? '';
        $specsJson = trim($_POST['specs_json'] ?? '');
        $shippingInfo = sanitize($_POST['shipping_info'] ?? '');
        $returnInfo = sanitize($_POST['return_info'] ?? '');

        if (empty($name)) $errors[] = 'Product name is required.';
        if ($categoryId <= 0) $errors[] = 'Please select a valid category.';
        if ($regularPrice <= 0 || $salePrice <= 0) $errors[] = 'Please enter valid regular and sale prices.';

        if (empty($errors)) {
            $upd = $db->prepare("
                UPDATE products SET
                    category_id = :cid, name = :name, short_description = :sdesc, description = :desc,
                    regular_price = :regp, sale_price = :salep, supplier_cost = :scost,
                    supplier_name = :sname, supplier_sku = :ssku, supplier_url = :surl,
                    sku = :sku, stock_quantity = :stock, specs_json = :specs,
                    shipping_info = :ship, return_info = :ret
                WHERE id = :id
            ");
            $upd->execute([
                'cid' => $categoryId,
                'name' => $name,
                'sdesc' => $shortDesc,
                'desc' => $desc,
                'regp' => $regularPrice,
                'salep' => $salePrice,
                'scost' => $supplierCost,
                'sname' => $supplierName,
                'ssku' => $supplierSku,
                'surl' => $supplierUrl,
                'sku' => $sku,
                'stock' => $stock,
                'specs' => $specsJson,
                'ship' => $shippingInfo,
                'ret' => $returnInfo,
                'id' => $id
            ]);

            set_flash('success', 'Product "' . htmlspecialchars($name) . '" updated successfully!');
            header("Location: products.php");
            exit;
        }
    }
}
?>

<div class="mb-4">
  <a href="products.php" class="btn btn-sm btn-outline-secondary rounded-pill mb-2">← Back to Products</a>
  <h4 class="fw-bold text-navy mb-0">Edit Product: <?= htmlspecialchars($product['name']) ?></h4>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger rounded-3 shadow-sm mb-4">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form action="product-edit.php?id=<?= $product['id'] ?>" method="POST">
  <?= csrf_field() ?>

  <div class="row g-4">
    
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-navy mb-3">General Information</h5>

        <div class="mb-3">
          <label class="form-label small fw-bold">Product Title *</label>
          <input type="text" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Category *</label>
          <select name="category_id" class="form-select" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Short Summary *</label>
          <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($product['short_description']) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Detailed Description (HTML)</label>
          <textarea name="description" class="form-control" rows="8"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Specifications (JSON format)</label>
          <textarea name="specs_json" class="form-control" rows="4"><?= htmlspecialchars($product['specs_json']) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Shipping Info</label>
          <textarea name="shipping_info" class="form-control" rows="2"><?= htmlspecialchars($product['shipping_info']) ?></textarea>
        </div>

        <div class="mb-0">
          <label class="form-label small fw-bold">Return Info</label>
          <textarea name="return_info" class="form-control" rows="2"><?= htmlspecialchars($product['return_info']) ?></textarea>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      
      <!-- Pricing & Stock -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-navy mb-3">Pricing &amp; Inventory</h5>

        <div class="mb-3">
          <label class="form-label small fw-bold">Regular Price / MRP (₹) *</label>
          <input type="number" step="0.01" name="regular_price" class="form-control" value="<?= htmlspecialchars($product['regular_price']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Customer Selling Price (₹) *</label>
          <input type="number" step="0.01" name="sale_price" class="form-control" value="<?= htmlspecialchars($product['sale_price']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Available Stock</label>
          <input type="number" name="stock_quantity" class="form-control" value="<?= (int)$product['stock_quantity'] ?>" required>
        </div>

        <div class="mb-0">
          <label class="form-label small fw-bold">SKU Code</label>
          <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($product['sku']) ?>" required>
        </div>
      </div>

      <!-- Dropshipping Supplier -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white border-primary">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-truck-flatbed text-primary me-1"></i> Dropship Supplier</h5>

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier Unit Cost (₹) *</label>
          <input type="number" step="0.01" name="supplier_cost" class="form-control" value="<?= htmlspecialchars($product['supplier_cost']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier Name</label>
          <input type="text" name="supplier_name" class="form-control" value="<?= htmlspecialchars($product['supplier_name']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier SKU</label>
          <input type="text" name="supplier_sku" class="form-control" value="<?= htmlspecialchars($product['supplier_sku']) ?>">
        </div>

        <div class="mb-0">
          <label class="form-label small fw-bold">Supplier URL</label>
          <input type="url" name="supplier_url" class="form-control" value="<?= htmlspecialchars($product['supplier_url']) ?>">
        </div>
      </div>

      <button type="submit" class="btn btn-buyoera-accent btn-lg w-100 rounded-pill py-3 fw-bold">
        <i class="bi bi-check2-circle me-1"></i> Save Changes
      </button>

    </div>

  </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
