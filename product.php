<?php
/**
 * BUYOERA - Product Details Page
 */

require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$db = Database::getConnection();

if (!empty($slug)) {
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.slug = :slug LIMIT 1
    ");
    $stmt->execute(['slug' => $slug]);
} else {
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
}

$product = $stmt->fetch();
if (!$product) {
    header("Location: shop.php");
    exit;
}

// Fetch all images
$imgStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = :pid ORDER BY is_primary DESC, sort_order ASC");
$imgStmt->execute(['pid' => $product['id']]);
$images = $imgStmt->fetchAll();
if (empty($images)) {
    $images = [['image_url' => 'assets/images/placeholder.svg', 'is_primary' => 1]];
}

// Handle New Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $reviewerName = sanitize($_POST['reviewer_name'] ?? '');
        $reviewerLoc = sanitize($_POST['reviewer_location'] ?? 'India');
        $reviewRating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $reviewTitle = sanitize($_POST['title'] ?? '');
        $reviewComment = sanitize($_POST['comment'] ?? '');
        $userId = is_logged_in() ? $_SESSION['user_id'] : null;

        if (!empty($reviewerName) && !empty($reviewTitle) && !empty($reviewComment)) {
            $insReview = $db->prepare("
                INSERT INTO reviews (product_id, user_id, reviewer_name, reviewer_location, rating, title, comment, is_verified, is_approved)
                VALUES (:pid, :uid, :rname, :rloc, :rrating, :rtitle, :rcomment, 1, 1)
            ");
            $insReview->execute([
                'pid' => $product['id'],
                'uid' => $userId,
                'rname' => $reviewerName,
                'rloc' => $reviewerLoc,
                'rrating' => $reviewRating,
                'rtitle' => $reviewTitle,
                'rcomment' => $reviewComment
            ]);

            // Recalculate product review count and rating
            $statStmt = $db->prepare("SELECT COUNT(*) as cnt, AVG(rating) as avg_r FROM reviews WHERE product_id = :pid AND is_approved = 1");
            $statStmt->execute(['pid' => $product['id']]);
            $stats = $statStmt->fetch();

            $updProd = $db->prepare("UPDATE products SET total_reviews = :cnt, rating = :r WHERE id = :pid");
            $updProd->execute([
                'cnt' => (int)$stats['cnt'],
                'r' => round((float)$stats['avg_r'], 1),
                'pid' => $product['id']
            ]);

            set_flash('success', 'Thank you! Your verified review has been submitted.');
            header("Location: product.php?slug=" . urlencode($product['slug']) . "#reviews");
            exit;
        } else {
            set_flash('danger', 'Please fill in all review fields.');
        }
    }
}

// Fetch Reviews
$revStmt = $db->prepare("SELECT * FROM reviews WHERE product_id = :pid AND is_approved = 1 ORDER BY id DESC");
$revStmt->execute(['pid' => $product['id']]);
$reviews = $revStmt->fetchAll();

// Fetch Related Products
$relStmt = $db->prepare("
    SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id != :pid
    ORDER BY p.is_featured DESC LIMIT 3
");
$relStmt->execute(['pid' => $product['id']]);
$relatedProducts = $relStmt->fetchAll();

// Decode specifications JSON
$specs = !empty($product['specs_json']) ? json_decode($product['specs_json'], true) : [];

$discountPct = calculate_discount_percent((float)$product['regular_price'], (float)$product['sale_price']);
$savingsAmount = (float)$product['regular_price'] - (float)$product['sale_price'];

$pageTitle = $product['name'];
$pageDescription = $product['short_description'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="py-3 bg-white border-bottom">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
        <li class="breadcrumb-item"><a href="category.php?slug=<?= htmlspecialchars($product['category_slug']) ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
        <li class="breadcrumb-item active text-truncate" style="max-width: 250px;"><?= htmlspecialchars($product['name']) ?></li>
      </ol>
    </nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    
    <!-- Left Column: Image Gallery -->
    <div class="col-lg-6">
      <div class="sticky-top" style="top: 90px;">
        <div class="product-gallery-main position-relative">
          <?php if ($discountPct > 0): ?>
            <span class="product-card-badge sale"><?= $discountPct ?>% OFF</span>
          <?php endif; ?>
          <img id="mainGalleryImg" src="<?= htmlspecialchars($images[0]['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid">
        </div>

        <!-- Thumbnails -->
        <div class="product-gallery-thumbs justify-content-center">
          <?php foreach ($images as $idx => $img): ?>
            <div class="gallery-thumb <?= $idx === 0 ? 'active' : '' ?>" data-img="<?= htmlspecialchars($img['image_url']) ?>">
              <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Thumbnail <?= $idx + 1 ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Right Column: Product Info & Purchase Form -->
    <div class="col-lg-6">
      <div class="product-info-wrap">
        <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase px-3 py-1 rounded-pill mb-2">
          <?= htmlspecialchars($product['category_name']) ?>
        </span>
        
        <h1 class="product-detail-title"><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Rating & SKU -->
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
          <div class="d-flex align-items-center gap-2">
            <span class="stars fs-6">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </span>
            <span class="fw-bold text-dark"><?= number_format($product['rating'], 1) ?></span>
            <a href="#reviews" class="text-muted small">(<?= (int)$product['total_reviews'] ?> Verified Reviews)</a>
          </div>
          <span class="text-muted small">|</span>
          <span class="text-muted small">SKU: <strong class="text-dark"><?= htmlspecialchars($product['sku']) ?></strong></span>
        </div>

        <!-- Pricing Card Box -->
        <div class="p-3 bg-white rounded-3 border mb-4 shadow-sm">
          <div class="d-flex align-items-baseline gap-3 mb-1">
            <span class="display-6 fw-bold text-navy"><?= format_price($product['sale_price']) ?></span>
            <span class="fs-5 text-muted text-decoration-line-through"><?= format_price($product['regular_price']) ?></span>
            <span class="discount-pill fs-6">Save <?= format_price($savingsAmount) ?> (<?= $discountPct ?>% OFF)</span>
          </div>
          <p class="small text-muted mb-0">Inclusive of all taxes. Free express shipping on this order.</p>
        </div>

        <!-- Short Description -->
        <p class="text-secondary mb-4 leading-relaxed">
          <?= htmlspecialchars($product['short_description']) ?>
        </p>

        <!-- Stock Status & Quantity Stepper -->
        <div class="d-flex align-items-center gap-3 mb-4">
          <div>
            <label class="small fw-bold text-muted d-block mb-1">Quantity</label>
            <div class="qty-stepper">
              <button type="button" class="btn-qty-minus"><i class="bi bi-dash"></i></button>
              <input type="text" id="productQtyInput" class="qty-input" value="1" max="<?= (int)$product['stock_quantity'] ?>" readonly>
              <button type="button" class="btn-qty-plus"><i class="bi bi-plus"></i></button>
            </div>
          </div>
          <div class="ms-3">
            <label class="small fw-bold text-muted d-block mb-1">Availability</label>
            <?php if ($product['stock_quantity'] > 0): ?>
              <span class="stock-badge">
                <span class="stock-dot"></span> In Stock (<?= (int)$product['stock_quantity'] ?> Ready to Ship)
              </span>
            <?php else: ?>
              <span class="badge bg-danger">Out of Stock</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Purchase Buttons -->
        <div class="row g-3 mb-4">
          <div class="col-sm-6">
            <button type="button" class="btn btn-buyoera-outline btn-lg w-100 py-3 btn-add-to-cart" data-product-id="<?= $product['id'] ?>" <?= $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>
              <i class="bi bi-bag-plus"></i> Add to Cart
            </button>
          </div>
          <div class="col-sm-6">
            <a href="checkout.php?buy_now=<?= $product['id'] ?>" class="btn btn-buyoera-accent btn-lg w-100 py-3 <?= $product['stock_quantity'] < 1 ? 'disabled' : '' ?>">
              <i class="bi bi-lightning-charge-fill"></i> Buy Now
            </a>
          </div>
        </div>

        <!-- Indian Pincode Delivery Estimator Box -->
        <div class="pincode-checker-box border">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-geo-alt-fill text-primary"></i>
            <span class="fw-bold small text-navy">Check Delivery Date &amp; COD Availability:</span>
          </div>
          <div class="pincode-input-group">
            <input type="text" id="pincodeInput" placeholder="Enter 6-digit PIN code" maxlength="6" class="form-control form-control-sm">
            <button type="button" id="checkPincodeBtn" class="btn btn-sm btn-buyoera-primary">Check PIN</button>
          </div>
          <div id="pincodeResult" class="mt-1"></div>
        </div>

        <!-- Key Trust Badges -->
        <div class="row g-2 text-muted small py-3 border-top border-bottom mb-4">
          <div class="col-6"><i class="bi bi-shield-check text-success me-1"></i> 100% Genuine Quality Verified</div>
          <div class="col-6"><i class="bi bi-truck text-primary me-1"></i> Dispatched in 24 Hours</div>
          <div class="col-6"><i class="bi bi-arrow-repeat text-info me-1"></i> 7-Day Easy Replacement</div>
          <div class="col-6"><i class="bi bi-cash-stack text-warning me-1"></i> Cash on Delivery Available</div>
        </div>

      </div>
    </div>
  </div>

  <!-- Product Details Tabs (Description, Specs, Shipping, Returns) -->
  <div class="mt-5 pt-4">
    <ul class="nav nav-pills nav-justified mb-4 bg-white p-2 rounded-4 shadow-sm border" id="productTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold py-3" id="desc-tab" data-bs-toggle="pill" data-bs-target="#desc-pane" type="button" role="tab">Detailed Description</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="specs-tab" data-bs-toggle="pill" data-bs-target="#specs-pane" type="button" role="tab">Specifications</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="shipping-tab" data-bs-toggle="pill" data-bs-target="#shipping-pane" type="button" role="tab">Shipping &amp; Delivery</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold py-3" id="returns-tab" data-bs-toggle="pill" data-bs-target="#returns-pane" type="button" role="tab">Return &amp; Refund Policy</button>
      </li>
    </ul>

    <div class="tab-content bg-white p-4 p-md-5 rounded-4 shadow-sm border" id="productTabContent">
      
      <!-- Description Tab -->
      <div class="tab-pane fade show active" id="desc-pane" role="tabpanel">
        <h4 class="fw-bold text-navy mb-4">Product Overview</h4>
        <div class="product-description-content text-secondary leading-relaxed">
          <?= $product['description'] ?>
        </div>
      </div>

      <!-- Specifications Tab -->
      <div class="tab-pane fade" id="specs-pane" role="tabpanel">
        <h4 class="fw-bold text-navy mb-4">Technical Specifications</h4>
        <?php if (!empty($specs)): ?>
          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <tbody>
                <?php foreach ($specs as $key => $val): ?>
                  <tr>
                    <th class="w-25 text-navy bg-light"><?= htmlspecialchars($key) ?></th>
                    <td><?= htmlspecialchars($val) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted">Standard specifications apply.</p>
        <?php endif; ?>
      </div>

      <!-- Shipping Tab -->
      <div class="tab-pane fade" id="shipping-pane" role="tabpanel">
        <h4 class="fw-bold text-navy mb-3"><i class="bi bi-truck text-primary me-2"></i> Pan-India Shipping Information</h4>
        <p class="text-secondary"><?= htmlspecialchars($product['shipping_info']) ?></p>
        <ul class="text-secondary small">
          <li>Orders placed before 2:00 PM IST are processed and dispatched the same business day.</li>
          <li>Courier partners: BlueDart, Delhivery, DTDC, XpressBees.</li>
          <li>Tracking updates sent via WhatsApp and SMS immediately upon pickup.</li>
        </ul>
      </div>

      <!-- Returns Tab -->
      <div class="tab-pane fade" id="returns-pane" role="tabpanel">
        <h4 class="fw-bold text-navy mb-3"><i class="bi bi-arrow-repeat text-success me-2"></i> 7-Day Hassle-Free Returns</h4>
        <p class="text-secondary"><?= htmlspecialchars($product['return_info']) ?></p>
        <p class="text-secondary small">
          If your item arrives damaged, defective, or different from the description, simply notify our WhatsApp support at <?= CONTACT_PHONE ?> within 7 days of delivery. We will arrange a reverse pickup and issue an immediate replacement or full refund to your original payment method.
        </p>
      </div>

    </div>
  </div>

  <!-- Customer Reviews Section -->
  <section class="mt-5 pt-4" id="reviews">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
      <div>
        <h3 class="fw-bold text-navy mb-1">Customer Reviews &amp; Ratings</h3>
        <p class="text-muted small mb-0">Verified reviews from authentic Indian buyers.</p>
      </div>
      <button class="btn btn-buyoera-primary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#writeReviewCollapse">
        <i class="bi bi-pencil-square me-1"></i> Write a Review
      </button>
    </div>

    <!-- Review Submission Collapse Form -->
    <div class="collapse mb-4" id="writeReviewCollapse">
      <div class="card card-body border-0 shadow-sm rounded-4 p-4 bg-light border">
        <h5 class="fw-bold text-navy mb-3">Share Your Experience with this Product</h5>
        <form action="product.php?slug=<?= urlencode($product['slug']) ?>" method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_review">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Your Name *</label>
              <input type="text" name="reviewer_name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Your City / State (e.g. Pune, MH) *</label>
              <input type="text" name="reviewer_location" class="form-control" placeholder="e.g. Bengaluru, Karnataka" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Rating *</label>
              <select name="rating" class="form-select" required>
                <option value="5" selected>⭐⭐⭐⭐⭐ (5 - Outstanding)</option>
                <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                <option value="3">⭐⭐⭐ (3 - Average)</option>
                <option value="2">⭐⭐ (2 - Below Expectations)</option>
                <option value="1">⭐ (1 - Poor)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Review Title *</label>
              <input type="text" name="title" class="form-control" placeholder="Summarize your review" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Your Detailed Review *</label>
              <textarea name="comment" rows="3" class="form-control" placeholder="What did you like or dislike? How did this product work for you?" required></textarea>
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-buyoera-primary px-4 rounded-pill">Submit Review</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Reviews Listing -->
    <div class="row g-4">
      <?php if (empty($reviews)): ?>
        <div class="col-12">
          <div class="p-4 bg-white rounded-4 border text-center text-muted">
            Be the first to review this product!
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
          <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star-fill <?= $i <= $rev['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                  <?php endfor; ?>
                </div>
                <span class="badge bg-success-subtle text-success small"><i class="bi bi-patch-check-fill"></i> Verified Purchase</span>
              </div>
              <h6 class="fw-bold text-navy mb-2"><?= htmlspecialchars($rev['title']) ?></h6>
              <p class="text-secondary small mb-3"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
              <div class="mt-auto d-flex align-items-center justify-content-between pt-2 border-top">
                <span class="small fw-bold text-navy"><?= htmlspecialchars($rev['reviewer_name']) ?> <small class="text-muted fw-normal">(<?= htmlspecialchars($rev['reviewer_location']) ?>)</small></span>
                <span class="small text-muted"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Related Products Section -->
  <section class="mt-5 pt-4">
    <div class="section-title-wrap mb-4">
      <span class="section-tag">SIMILAR FINDS</span>
      <h3 class="fw-bold text-navy">You May Also Like</h3>
    </div>

    <div class="row g-4">
      <?php foreach ($relatedProducts as $rel): 
        $relDiscount = calculate_discount_percent((float)$rel['regular_price'], (float)$rel['sale_price']);
      ?>
        <div class="col-md-4">
          <div class="product-card">
            <?php if ($relDiscount > 0): ?>
              <span class="product-card-badge sale"><?= $relDiscount ?>% OFF</span>
            <?php endif; ?>
            <a href="product.php?slug=<?= htmlspecialchars($rel['slug']) ?>" class="product-card-img-wrap">
              <img src="<?= htmlspecialchars($rel['primary_image']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" class="product-card-img">
            </a>
            <div class="product-card-body">
              <span class="product-card-category"><?= htmlspecialchars($rel['category_name']) ?></span>
              <h4 class="product-card-title">
                <a href="product.php?slug=<?= htmlspecialchars($rel['slug']) ?>"><?= htmlspecialchars($rel['name']) ?></a>
              </h4>
              <div class="product-card-price-wrap">
                <span class="sale-price"><?= format_price($rel['sale_price']) ?></span>
                <span class="regular-price"><?= format_price($rel['regular_price']) ?></span>
              </div>
              <div class="product-card-actions">
                <button type="button" class="btn btn-buyoera-outline btn-add-to-cart w-100" data-product-id="<?= $rel['id'] ?>">
                  <i class="bi bi-bag-plus"></i> Add
                </button>
                <a href="checkout.php?buy_now=<?= $rel['id'] ?>" class="btn btn-buyoera-accent w-100">
                  Buy Now
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
