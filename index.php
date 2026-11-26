<?php
/**
 * BUYOERA - Admin Dashboard Overview
 */

$adminPageTitle = 'Operations Dashboard';
require_once __DIR__ . '/includes/admin-header.php';

$db = Database::getConnection();

// 1. Total Revenue & Total Orders
$revStmt = $db->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_revenue FROM orders");
$revData = $revStmt->fetch();

// 2. Total Gross Profit Calculation (Revenue - Supplier Costs)
$profitStmt = $db->query("
    SELECT COALESCE(SUM((oi.price - oi.supplier_cost) * oi.quantity), 0) as total_profit
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status != 'Cancelled'
");
$profitData = $profitStmt->fetch();

// 3. Customer Count
$custStmt = $db->query("SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'");
$totalCustomers = $custStmt->fetch()['total_customers'];

// 4. Products Count & Low Stock Count
$prodStmt = $db->query("SELECT COUNT(*) as total_prods, SUM(CASE WHEN stock_quantity < 20 THEN 1 ELSE 0 END) as low_stock FROM products");
$prodData = $prodStmt->fetch();

// 5. Pending Dropshipping Orders to Forward
$dropStmt = $db->query("SELECT COUNT(*) as pending_dropship FROM orders WHERE supplier_status = 'Pending Forwarding' AND order_status != 'Cancelled'");
$pendingDropship = $dropStmt->fetch()['pending_dropship'];

// 6. Recent Orders
$recentStmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 6");
$recentOrders = $recentStmt->fetchAll();

// 7. Products with Low Stock
$lowStockStmt = $db->query("SELECT * FROM products WHERE stock_quantity < 50 ORDER BY stock_quantity ASC LIMIT 4");
$lowStockProducts = $lowStockStmt->fetchAll();
?>

<!-- 4 Key Metric Cards Row -->
<div class="row g-4 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon blue"><i class="bi bi-currency-rupee"></i></div>
      <h3 class="metric-value"><?= format_price($revData['total_revenue']) ?></h3>
      <p class="metric-label">Total Store Revenue</p>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon green"><i class="bi bi-graph-up-arrow"></i></div>
      <h3 class="metric-value text-success"><?= format_price($profitData['total_profit']) ?></h3>
      <p class="metric-label">Gross Dropshipping Profit</p>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon amber"><i class="bi bi-bag-check"></i></div>
      <h3 class="metric-value"><?= (int)$revData['total_orders'] ?></h3>
      <p class="metric-label">Total Customer Orders</p>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon purple"><i class="bi bi-people"></i></div>
      <h3 class="metric-value"><?= (int)$totalCustomers ?></h3>
      <p class="metric-label">Registered Customers</p>
    </div>
  </div>
</div>

<!-- Dropshipping Action Alert Banner -->
<?php if ($pendingDropship > 0): ?>
  <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="fs-2 text-warning"><i class="bi bi-exclamation-octagon-fill"></i></div>
      <div>
        <h5 class="fw-bold mb-1 text-dark">Action Required: <?= $pendingDropship ?> Orders Pending Supplier Forwarding</h5>
        <p class="mb-0 text-muted small">You have customer orders ready to be forwarded to fulfillment suppliers.</p>
      </div>
    </div>
    <a href="dropshipping.php" class="btn btn-warning fw-bold rounded-pill px-4">
      Open Dropshipping Queue <i class="bi bi-arrow-right"></i>
    </a>
  </div>
<?php endif; ?>

<!-- Main Dashboard Grid -->
<div class="row g-4">
  
  <!-- Left Column: Recent Orders -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 class="fw-bold text-navy mb-0">Recent Customer Orders</h5>
          <small class="text-muted">Live incoming customer orders across India</small>
        </div>
        <a href="orders.php" class="btn btn-sm btn-outline-primary rounded-pill">View All Orders →</a>
      </div>

      <div class="table-responsive">
        <table class="table align-middle admin-table mb-0">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>City / State</th>
              <th>Amount</th>
              <th>Status</th>
              <th class="text-end">Manage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $ro): ?>
              <tr>
                <td>
                  <strong class="text-navy"><?= htmlspecialchars($ro['order_number']) ?></strong>
                  <small class="d-block text-muted"><?= date('d M, h:i A', strtotime($ro['created_at'])) ?></small>
                </td>
                <td>
                  <div class="fw-semibold text-dark"><?= htmlspecialchars($ro['customer_name']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($ro['customer_phone']) ?></small>
                </td>
                <td class="small">
                  <?= htmlspecialchars($ro['shipping_city']) ?>, <?= htmlspecialchars($ro['shipping_state']) ?>
                </td>
                <td>
                  <strong class="text-navy"><?= format_price($ro['total_amount']) ?></strong>
                  <span class="d-block small text-muted text-uppercase"><?= htmlspecialchars($ro['payment_method']) ?></span>
                </td>
                <td>
                  <?php
                  $badgeClass = 'badge-order-placed';
                  if ($ro['order_status'] === 'Delivered') $badgeClass = 'badge-delivered';
                  elseif ($ro['order_status'] === 'Shipped') $badgeClass = 'badge-shipped';
                  elseif ($ro['order_status'] === 'Processing') $badgeClass = 'badge-processing';
                  elseif ($ro['order_status'] === 'Cancelled') $badgeClass = 'badge-cancelled';
                  ?>
                  <span class="badge <?= $badgeClass ?> px-3 py-1 rounded-pill"><?= htmlspecialchars($ro['order_status']) ?></span>
                </td>
                <td class="text-end">
                  <a href="order-detail.php?id=<?= $ro['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                    Details
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Right Column: Quick Shortcuts & Low Stock -->
  <div class="col-lg-4">
    
    <!-- Quick Actions Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
      <h5 class="fw-bold text-navy mb-3">Quick Operations</h5>
      <div class="d-grid gap-2">
        <a href="product-add.php" class="btn btn-buyoera-primary rounded-pill text-start py-2 px-3">
          <i class="bi bi-plus-circle me-2"></i> Add New Product
        </a>
        <a href="dropshipping.php" class="btn btn-outline-dark rounded-pill text-start py-2 px-3">
          <i class="bi bi-truck-flatbed me-2"></i> Supplier Forwarding Queue
        </a>
        <a href="coupons.php" class="btn btn-outline-dark rounded-pill text-start py-2 px-3">
          <i class="bi bi-tag me-2"></i> Create Promo Coupon
        </a>
        <a href="settings.php" class="btn btn-outline-dark rounded-pill text-start py-2 px-3">
          <i class="bi bi-sliders me-2"></i> Store Configuration
        </a>
      </div>
    </div>

    <!-- Inventory / Stock Overview Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold text-navy mb-0">Inventory Levels</h5>
        <a href="products.php" class="small text-primary">All Products</a>
      </div>

      <div class="list-group list-group-flush">
        <?php foreach ($lowStockProducts as $lp): ?>
          <div class="list-group-item px-0 py-2 border-0 d-flex align-items-center justify-content-between">
            <div class="text-truncate me-2">
              <h6 class="mb-0 text-navy small fw-bold text-truncate" style="max-width: 180px;"><?= htmlspecialchars($lp['name']) ?></h6>
              <small class="text-muted">SKU: <?= htmlspecialchars($lp['sku']) ?></small>
            </div>
            <span class="badge <?= $lp['stock_quantity'] < 20 ? 'bg-danger' : 'bg-success' ?> rounded-pill">
              <?= $lp['stock_quantity'] ?> left
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
