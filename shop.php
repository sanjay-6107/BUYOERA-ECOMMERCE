<?php
/**
 * BUYOERA - Shop All / Product Catalog
 */

$pageTitle = 'Shop All Products';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();

// Categories for filter
$catStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$allCategories = $catStmt->fetchAll();

// Query filtering
$query = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$sort = trim($_GET['sort'] ?? 'featured');

$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($query)) {
    $sql .= " AND (p.name LIKE :q OR p.short_description LIKE :q OR p.description LIKE :q)";
    $params['q'] = '%' . $query . '%';
}

if (!empty($categorySlug)) {
    $sql .= " AND c.slug = :cslug";
    $params['cslug'] = $categorySlug;
}

if ($tag === 'trending') {
    $sql .= " AND p.is_trending = 1";
} elseif ($tag === 'best-seller') {
    $sql .= " AND p.is_best_seller = 1";
} elseif ($tag === 'new-arrivals') {
    $sql .= " AND p.is_new_arrival = 1";
} elseif ($tag === 'special-offers') {
    $sql .= " AND p.regular_price > p.sale_price";
}

switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.sale_price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.sale_price DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY p.rating DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.id DESC";
        break;
    default:
        $sql .= " ORDER BY p.is_featured DESC, p.id ASC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="py-4 bg-white border-bottom">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2 small">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Shop All</li>
        <?php if (!empty($categorySlug)): ?>
          <li class="breadcrumb-item active"><?= htmlspecialchars(ucwords(str_replace('-', ' ', $categorySlug))) ?></li>
        <?php endif; ?>
      </ol>
    </nav>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <h1 class="h2 fw-bold text-navy mb-1">
          <?php 
          if (!empty($query)) echo 'Search: "' . htmlspecialchars($query) . '"';
          elseif (!empty($categorySlug)) echo htmlspecialchars(ucwords(str_replace('-', ' ', $categorySlug)));
          elseif (!empty($tag)) echo htmlspecialchars(ucwords(str_replace('-', ' ', $tag)));
          else echo 'All Curated Essentials';
          ?>
        </h1>
        <p class="text-muted small mb-0">Showing <?= count($products) ?> premium products with free express shipping on orders over ₹499.</p>
      </div>

      <!-- Sort Controls -->
      <form method="GET" class="d-flex align-items-center gap-2">
        <?php if ($query): ?><input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>"><?php endif; ?>
        <?php if ($categorySlug): ?><input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug) ?>"><?php endif; ?>
        <?php if ($tag): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($tag) ?>"><?php endif; ?>
        
        <label for="sortSelect" class="small fw-bold text-muted text-nowrap">Sort By:</label>
        <select name="sort" id="sortSelect" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured</option>
          <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
          <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Customer Rating</option>
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
        </select>
      </form>
    </div>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    
    <!-- Sidebar Filters -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm p-4 rounded-4 sticky-top" style="top: 90px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="fw-bold mb-0 text-navy"><i class="bi bi-funnel me-1"></i> Filter By</h5>
          <a href="shop.php" class="small text-danger text-decoration-none">Reset All</a>
        </div>

        <h6 class="small text-uppercase fw-bold text-muted mt-3 mb-2">Category</h6>
        <div class="list-group list-group-flush mb-4">
          <a href="shop.php" class="list-group-item list-group-item-action px-0 border-0 small <?= empty($categorySlug) ? 'fw-bold text-primary' : 'text-secondary' ?>">
            All Categories
          </a>
          <?php foreach ($allCategories as $c): ?>
            <a href="shop.php?category=<?= urlencode($c['slug']) ?>" class="list-group-item list-group-item-action px-0 border-0 small <?= $categorySlug === $c['slug'] ? 'fw-bold text-primary' : 'text-secondary' ?>">
              <?= htmlspecialchars($c['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <h6 class="small text-uppercase fw-bold text-muted mb-2">Curated Tags</h6>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <a href="shop.php?tag=trending" class="badge rounded-pill <?= $tag === 'trending' ? 'bg-primary text-white' : 'bg-light text-dark border' ?> text-decoration-none p-2">Trending</a>
          <a href="shop.php?tag=best-seller" class="badge rounded-pill <?= $tag === 'best-seller' ? 'bg-primary text-white' : 'bg-light text-dark border' ?> text-decoration-none p-2">Best Sellers</a>
          <a href="shop.php?tag=new-arrivals" class="badge rounded-pill <?= $tag === 'new-arrivals' ? 'bg-primary text-white' : 'bg-light text-dark border' ?> text-decoration-none p-2">New Arrivals</a>
          <a href="shop.php?tag=special-offers" class="badge rounded-pill <?= $tag === 'special-offers' ? 'bg-primary text-white' : 'bg-light text-dark border' ?> text-decoration-none p-2">Deals &amp; Offers</a>
        </div>

        <div class="p-3 bg-light rounded-3 mt-2 border">
          <h6 class="small fw-bold mb-1 text-navy"><i class="bi bi-truck text-primary"></i> Free Express Shipping</h6>
          <p class="small text-muted mb-0">Automatic free delivery on orders above ₹499 across all Indian states.</p>
        </div>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="col-lg-9">
      <?php if (empty($products)): ?>
        <div class="card border-0 shadow-sm p-5 text-center rounded-4">
          <div class="fs-1 text-muted mb-3"><i class="bi bi-search"></i></div>
          <h4 class="fw-bold text-navy">No products match your selection</h4>
          <p class="text-muted mb-4">Try checking your spelling or clearing filters to see our full curated catalog.</p>
          <div>
            <a href="shop.php" class="btn btn-buyoera-primary rounded-pill px-4">View All Products</a>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($products as $prod): 
            $discountPct = calculate_discount_percent((float)$prod['regular_price'], (float)$prod['sale_price']);
          ?>
            <div class="col-md-6 col-xl-4">
              <div class="product-card">
                <?php if ($discountPct > 0): ?>
                  <span class="product-card-badge sale"><?= $discountPct ?>% OFF</span>
                <?php endif; ?>
                
                <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>" class="product-card-img-wrap">
                  <img src="<?= htmlspecialchars($prod['primary_image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-card-img">
                </a>

                <div class="product-card-body">
                  <span class="product-card-category"><?= htmlspecialchars($prod['category_name']) ?></span>
                  <h3 class="product-card-title">
                    <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>"><?= htmlspecialchars($prod['name']) ?></a>
                  </h3>

                  <div class="product-card-rating">
                    <span class="stars">
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
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

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
