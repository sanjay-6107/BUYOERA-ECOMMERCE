<?php
/**
 * BUYOERA - Admin Add Product
 */

$adminPageTitle = 'Add New Product';
require_once __DIR__ . '/includes/admin-header.php';

$db = Database::getConnection();

// Fetch Categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll();

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
        $supplierName = sanitize($_POST['supplier_name'] ?? 'Direct Supplier India');
        $supplierSku = sanitize($_POST['supplier_sku'] ?? '');
        $supplierUrl = sanitize($_POST['supplier_url'] ?? '');
        $stock = (int)($_POST['stock_quantity'] ?? 50);
        $shortDesc = sanitize($_POST['short_description'] ?? '');
        $desc = $_POST['description'] ?? ''; // Allow formatting HTML
        $imageUrl = sanitize($_POST['image_url'] ?? 'assets/images/placeholder.svg');
        $specsJson = trim($_POST['specs_json'] ?? '');
        $shippingInfo = sanitize($_POST['shipping_info'] ?? 'Dispatched within 24 hours. Express delivery across India in 3-5 days.');
        $returnInfo = sanitize($_POST['return_info'] ?? '7-Day replacement guarantee against defect or transit damage.');

        // Auto slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        if (empty($name)) $errors[] = 'Product name is required.';
        if ($categoryId <= 0) $errors[] = 'Please select a valid category.';
        if ($regularPrice <= 0 || $salePrice <= 0) $errors[] = 'Please enter valid regular and sale prices.';
        if (empty($sku)) $sku = 'BYE-' . strtoupper(substr(md5(uniqid()), 0, 8));

        if (empty($errors)) {
            $ins = $db->prepare("
                INSERT INTO products (
                    category_id, name, slug, short_description, description,
                    regular_price, sale_price, supplier_cost, supplier_name, supplier_sku, supplier_url,
                    sku, stock_quantity, specs_json, shipping_info, return_info
                ) VALUES (
                    :cid, :name, :slug, :sdesc, :desc,
                    :regp, :salep, :scost, :sname, :ssku, :surl,
                    :sku, :stock, :specs, :ship, :ret
                )
            ");
            $ins->execute([
                'cid' => $categoryId,
                'name' => $name,
                'slug' => $slug,
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
                'ret' => $returnInfo
            ]);

            $newPid = $db->lastInsertId();

            // Insert Primary Image
            $insImg = $db->prepare("INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES (:pid, :img, 1, 1)");
            $insImg->execute(['pid' => $newPid, 'img' => $imageUrl]);

            set_flash('success', 'Product "' . htmlspecialchars($name) . '" has been added to catalog!');
            header("Location: products.php");
            exit;
        }
    }
}
?>

<div class="mb-4">
  <a href="products.php" class="btn btn-sm btn-outline-secondary rounded-pill mb-2">← Back to Products</a>
  <h4 class="fw-bold text-navy mb-0">Add New Product</h4>
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

<form action="product-add.php" method="POST">
  <?= csrf_field() ?>

  <div class="row g-4">
    
    <!-- Left Column: Core Product Info -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-navy mb-3">General Information</h5>

        <div class="mb-3">
          <label class="form-label small fw-bold">Product Title *</label>
          <input type="text" name="name" class="form-control form-control-lg" placeholder="e.g. BUYOERA Ergonomic Silicone Mouse Wrist Rest" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Category *</label>
          <select name="category_id" class="form-select" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Short Summary / Tagline *</label>
          <textarea name="short_description" class="form-control" rows="2" placeholder="Brief 1-2 sentence overview displayed in cards"><?= htmlspecialchars($_POST['short_description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Full Product Description (HTML supported)</label>
          <textarea name="description" class="form-control" rows="6" placeholder="<p>Full description, bullet points, benefits, material details...</p>"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Primary Image Path</label>
          <input type="text" name="image_url" class="form-control" placeholder="assets/images/products/... or URL" value="<?= htmlspecialchars($_POST['image_url'] ?? 'assets/images/products/sneaker-kit-1.svg') ?>">
        </div>

        <div class="mb-0">
          <label class="form-label small fw-bold">Specifications (JSON format)</label>
          <textarea name="specs_json" class="form-control" rows="3" placeholder='{"Material":"Silicone","Dimensions":"12x8 cm","Origin":"India"}'><?= htmlspecialchars($_POST['specs_json'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Right Column: Pricing & Dropshipping Info -->
    <div class="col-lg-4">
      
      <!-- Pricing Card -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-navy mb-3">Pricing &amp; Inventory</h5>

        <div class="mb-3">
          <label class="form-label small fw-bold">Regular Price / MRP (₹) *</label>
          <input type="number" step="0.01" name="regular_price" class="form-control" placeholder="1499" value="<?= htmlspecialchars($_POST['regular_price'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Customer Selling Price (₹) *</label>
          <input type="number" step="0.01" name="sale_price" class="form-control" placeholder="699" value="<?= htmlspecialchars($_POST['sale_price'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Initial Stock Quantity</label>
          <input type="number" name="stock_quantity" class="form-control" value="50" required>
        </div>

        <div class="mb-0">
          <label class="form-label small fw-bold">SKU Code</label>
          <input type="text" name="sku" class="form-control" placeholder="e.g. BYE-CARE-004">
        </div>
      </div>

      <!-- Dropshipping Supplier Card -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white border-primary">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-truck-flatbed text-primary fs-5"></i>
          <h5 class="fw-bold text-navy mb-0">Dropshipping Supplier</h5>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier Unit Cost (₹) *</label>
          <input type="number" step="0.01" name="supplier_cost" class="form-control" placeholder="280" value="<?= htmlspecialchars($_POST['supplier_cost'] ?? '280.00') ?>" required>
          <small class="text-muted">Used for automatic net profit margin reporting.</small>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier Company Name</label>
          <input type="text" name="supplier_name" class="form-control" placeholder="e.g. QuickSource India Hub" value="<?= htmlspecialchars($_POST['supplier_name'] ?? 'Direct Supplier India') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier SKU / Item Ref</label>
          <input type="text" name="supplier_sku" class="form-control" placeholder="e.g. SUP-ITEM-881">
        </div>

        <div class="mb-0">
          <label class="form-label small fw-bold">Supplier Portal / Product Link</label>
          <input type="url" name="supplier_url" class="form-control" placeholder="https://supplier.example.com/item">
        </div>
      </div>

      <button type="submit" class="btn btn-buyoera-accent btn-lg w-100 rounded-pill py-3 fw-bold">
        <i class="bi bi-check2-circle me-1"></i> Publish Product
      </button>

    </div>

  </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
