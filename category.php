<?php
/**
 * BUYOERA - Category Page
 */

require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: shop.php");
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM categories WHERE slug = :slug LIMIT 1");
$stmt->execute(['slug' => $slug]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: shop.php");
    exit;
}

$pageTitle = $category['name'] . ' – Curated Essentials';
$pageDescription = $category['description'];
require_once __DIR__ . '/includes/header.php';

// Fetch products in category
$pStmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE c.id = :cid
    ORDER BY p.is_featured DESC, p.id ASC
");
$pStmt->execute(['cid' => $category['id']]);
$products = $pStmt->fetchAll();
?>

<!-- Category Banner Header -->
<div class="py-5 bg-white border-bottom">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-3 small">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="shop.php">Categories</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($category['name']) ?></li>
      </ol>
    </nav>
    <div class="row align-items-center gy-4">
      <div class="col-lg-8">
        <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">CATEGORY COLLECTION</span>
        <h1 class="display-5 fw-bold text-navy mb-3"><?= htmlspecialchars($category['name']) ?></h1>
        <p class="lead text-muted mb-0" style="max-width: 650px;">
          <?= htmlspecialchars($category['description']) ?>
        </p>
      </div>
      <div class="col-lg-4 text-center">
        <img src="<?= htmlspecialchars($category['image_url']) ?>" alt="<?= htmlspecialchars($category['name']) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height: 180px;">
      </div>
    </div>
  </div>
</div>

<!-- Products in Category -->
<div class="container py-5">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h3 class="fw-bold text-navy mb-0">Products (<?= count($products) ?>)</h3>
    <a href="shop.php" class="btn btn-sm btn-buyoera-outline">View All Categories →</a>
  </div>

  <?php if (empty($products)): ?>
    <div class="p-5 text-center bg-white rounded-4 shadow-sm">
      <p class="text-muted mb-3">No products found in this category currently.</p>
      <a href="shop.php" class="btn btn-buyoera-primary">Browse All Products</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($products as $prod): 
        $discountPct = calculate_discount_percent((float)$prod['regular_price'], (float)$prod['sale_price']);
      ?>
        <div class="col-lg-4 col-md-6">
          <div class="product-card">
            <?php if ($discountPct > 0): ?>
              <span class="product-card-badge sale"><?= $discountPct ?>% OFF</span>
            <?php endif; ?>

            <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>" class="product-card-img-wrap">
              <img src="<?= htmlspecialchars($prod['primary_image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-card-img">
            </a>

            <div class="product-card-body">
              <span class="product-card-category"><?= htmlspecialchars($category['name']) ?></span>
              <h3 class="product-card-title">
                <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>"><?= htmlspecialchars($prod['name']) ?></a>
              </h3>

              <div class="product-card-rating">
                <span class="stars">
                  <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </span>
                <span class="fw-bold text-dark small"><?= number_format($prod['rating'], 1) ?></span>
                <span class="rating-count">(<?= (int)$prod['total_reviews'] ?>)</span>
              </div>

              <div class="product-card-price-wrap">
                <span class="sale-price"><?= format_price($prod['sale_price']) ?></span>
                <span class="regular-price"><?= format_price($prod['regular_price']) ?></span>
                <span class="discount-pill">Save <?= format_price($prod['regular_price'] - $prod['sale_price']) ?></span>
              </div>

              <div class="product-card-actions">
                <button type="button" class="btn btn-buyoera-outline btn-add-to-cart w-100" data-product-id="<?= $prod['id'] ?>">
                  <i class="bi bi-bag-plus"></i> Add to Cart
                </button>
                <a href="checkout.php?buy_now=<?= $prod['id'] ?>" class="btn btn-buyoera-accent w-100">
                  <i class="bi bi-lightning-fill"></i> Buy Now
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
