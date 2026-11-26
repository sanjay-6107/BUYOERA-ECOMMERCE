<?php
/**
 * BUYOERA - Dedicated Dropshipping & Profit Operations Command Center
 */

$adminPageTitle = 'Dropshipping & Margins';
require_once __DIR__ . '/includes/admin-header.php';

$db = Database::getConnection();

// Handle Quick Supplier Forward Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_forward') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $oid = (int)($_POST['order_id'] ?? 0);
        $soid = sanitize($_POST['supplier_order_id'] ?? '');
        $trackNum = sanitize($_POST['supplier_tracking'] ?? '');
        $courier = sanitize($_POST['courier_name'] ?? 'BlueDart Express');

        $upd = $db->prepare("
            UPDATE orders SET
                supplier_status = 'Forwarded to Supplier',
                supplier_order_id = :soid,
                tracking_number = CASE WHEN :tnum != '' THEN :tnum ELSE tracking_number END,
                courier_name = CASE WHEN :cname != '' THEN :cname ELSE courier_name END,
                order_status = 'Processing'
            WHERE id = :id
        ");
        $upd->execute(['soid' => $soid, 'tnum' => $trackNum, 'cname' => $courier, 'id' => $oid]);

        // Add history
        $insH = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (:oid, 'Processing', :nt, 'Admin')");
        $insH->execute([
            'oid' => $oid,
            'nt' => "Forwarded to dropship supplier with Ref: $soid. Courier: $courier"
        ]);

        set_flash('success', "Order #$oid marked as Forwarded to Supplier.");
        header("Location: dropshipping.php");
        exit;
    }
}

// 1. Financial Overview
$statsStmt = $db->query("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_rev,
        COALESCE(SUM(shipping_cost), 0) as total_shipping
    FROM orders WHERE order_status != 'Cancelled'
");
$stats = $statsStmt->fetch();

$itemStatsStmt = $db->query("
    SELECT 
        COALESCE(SUM(oi.supplier_cost * oi.quantity), 0) as total_supplier_cost,
        COALESCE(SUM(oi.total), 0) as total_items_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status != 'Cancelled'
");
$itemStats = $itemStatsStmt->fetch();

$totalRevenue = (float)$stats['total_rev'];
$totalSupplierCost = (float)$itemStats['total_supplier_cost'];
$totalGrossProfit = $totalRevenue - $totalSupplierCost;
$overallMarginPct = ($totalRevenue > 0) ? round(($totalGrossProfit / $totalRevenue) * 100) : 0;

// 2. Products Supplier & Margin Analysis
$prodStmt = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id ASC
");
$products = $prodStmt->fetchAll();

// 3. Orders Requiring Supplier Action
$pendingOrdersStmt = $db->query("
    SELECT * FROM orders 
    WHERE supplier_status = 'Pending Forwarding' AND order_status != 'Cancelled'
    ORDER BY id ASC
");
$pendingOrders = $pendingOrdersStmt->fetchAll();

// 4. Recently Forwarded Orders
$forwardedOrdersStmt = $db->query("
    SELECT * FROM orders 
    WHERE supplier_status != 'Pending Forwarding'
    ORDER BY id DESC LIMIT 5
");
$forwardedOrders = $forwardedOrdersStmt->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h4 class="fw-bold text-navy mb-1">Dropshipping Operations &amp; Profit Center</h4>
    <p class="text-muted small mb-0">India-focused dropshipping fulfillment workflow, supplier margins, and tracking synchronization.</p>
  </div>
</div>

<!-- Dropshipping Profitability Overview -->
<div class="row g-4 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon blue"><i class="bi bi-wallet2"></i></div>
      <h3 class="metric-value"><?= format_price($totalRevenue) ?></h3>
      <p class="metric-label">Total Store Revenue</p>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon amber"><i class="bi bi-cart-x"></i></div>
      <h3 class="metric-value text-danger"><?= format_price($totalSupplierCost) ?></h3>
      <p class="metric-label">Supplier Product Costs</p>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon green"><i class="bi bi-piggy-bank"></i></div>
      <h3 class="metric-value text-success"><?= format_price($totalGrossProfit) ?></h3>
      <p class="metric-label">Net Dropshipping Margin</p>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="metric-card">
      <div class="metric-icon purple"><i class="bi bi-pie-chart"></i></div>
      <h3 class="metric-value"><?= $overallMarginPct ?>%</h3>
      <p class="metric-label">Average Profit Margin</p>
    </div>
  </div>
</div>

<!-- Pending Supplier Forwarding Queue -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white border-top border-4 border-warning">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h5 class="fw-bold text-navy mb-0"><i class="bi bi-hourglass-split text-warning me-1"></i> Supplier Forwarding Queue (<?= count($pendingOrders) ?> Orders Pending)</h5>
      <small class="text-muted">Place these customer orders with your suppliers and input the supplier tracking code below.</small>
    </div>
  </div>

  <?php if (empty($pendingOrders)): ?>
    <div class="p-4 text-center text-muted small bg-light rounded-3">
      <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
      All current customer orders have been forwarded to suppliers!
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle admin-table mb-0">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer &amp; City</th>
            <th>Items</th>
            <th>Customer Paid</th>
            <th>Forward to Supplier</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingOrders as $po): ?>
            <tr>
              <td>
                <strong class="text-navy"><?= htmlspecialchars($po['order_number']) ?></strong>
                <small class="d-block text-muted"><?= date('d M, h:i A', strtotime($po['created_at'])) ?></small>
              </td>
              <td>
                <div class="fw-bold text-dark"><?= htmlspecialchars($po['customer_name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($po['shipping_city']) ?>, <?= htmlspecialchars($po['shipping_state']) ?></small>
              </td>
              <td>
                <a href="order-detail.php?id=<?= $po['id'] ?>" class="btn btn-sm btn-link p-0 text-primary fw-bold">View Items &amp; Address →</a>
              </td>
              <td class="fw-bold text-navy"><?= format_price($po['total_amount']) ?></td>
              <td>
                <form action="dropshipping.php" method="POST" class="d-flex flex-wrap gap-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="quick_forward">
                  <input type="hidden" name="order_id" value="<?= $po['id'] ?>">

                  <input type="text" name="supplier_order_id" class="form-control form-control-sm" placeholder="Supplier Ref #" style="max-width: 140px;" required>
                  <input type="text" name="supplier_tracking" class="form-control form-control-sm" placeholder="Tracking / AWB" style="max-width: 150px;">
                  <button type="submit" class="btn btn-sm btn-warning fw-bold text-nowrap rounded-pill">
                    <i class="bi bi-arrow-right-circle"></i> Mark Forwarded
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Product Margin Matrix Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
  <div class="p-4 border-bottom">
    <h5 class="fw-bold text-navy mb-0"><i class="bi bi-calculator me-1"></i> Product Margin &amp; Supplier Matrix</h5>
    <small class="text-muted">Breakdown of supplier acquisition costs versus customer selling prices</small>
  </div>

  <div class="table-responsive">
    <table class="table align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Product</th>
          <th>Supplier Company</th>
          <th>Supplier Cost (₹)</th>
          <th>Selling Price (₹)</th>
          <th>Gross Profit per Unit</th>
          <th>Margin (%)</th>
          <th>Supplier Link</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): 
          $profitPerUnit = $p['sale_price'] - $p['supplier_cost'];
          $mPct = ($p['sale_price'] > 0) ? round(($profitPerUnit / $p['sale_price']) * 100) : 0;
        ?>
          <tr>
            <td>
              <strong class="text-navy"><?= htmlspecialchars($p['name']) ?></strong>
              <small class="d-block text-muted">SKU: <?= htmlspecialchars($p['sku']) ?></small>
            </td>
            <td>
              <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['supplier_name']) ?></span>
              <small class="d-block text-muted">Ref: <?= htmlspecialchars($p['supplier_sku'] ?: 'N/A') ?></small>
            </td>
            <td class="text-danger fw-bold">
              <?= format_price($p['supplier_cost']) ?>
            </td>
            <td class="text-navy fw-bold">
              <?= format_price($p['sale_price']) ?>
            </td>
            <td class="text-success fw-bold">
              + <?= format_price($profitPerUnit) ?>
            </td>
            <td>
              <span class="badge <?= $mPct >= 50 ? 'bg-success' : 'bg-primary' ?> px-3 py-1 rounded-pill">
                <?= $mPct ?>% Margin
              </span>
            </td>
            <td>
              <?php if (!empty($p['supplier_url'])): ?>
                <a href="<?= htmlspecialchars($p['supplier_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary rounded-pill">
                  Supplier Page <i class="bi bi-box-arrow-up-right"></i>
                </a>
              <?php else: ?>
                <span class="text-muted small">Offline Direct</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
