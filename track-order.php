<?php
/**
 * BUYOERA - Order Tracking Page
 */

$pageTitle = 'Track Your Order';
require_once __DIR__ . '/includes/header.php';

$orderNumber = trim($_GET['order'] ?? '');
$phoneOrEmail = trim($_GET['phone'] ?? $_GET['email'] ?? '');

$db = Database::getConnection();
$order = null;
$history = [];
$searched = false;
$error = null;

if (!empty($orderNumber) && !empty($phoneOrEmail)) {
    $searched = true;
    $cleanPhone = str_replace([' ', '-', '+91'], '', $phoneOrEmail);

    $stmt = $db->prepare("
        SELECT * FROM orders 
        WHERE order_number = :onum 
          AND (customer_email = :ident OR customer_phone = :ident OR customer_phone = :phone)
        LIMIT 1
    ");
    $stmt->execute([
        'onum' => $orderNumber,
        'ident' => $phoneOrEmail,
        'phone' => $cleanPhone
    ]);
    $order = $stmt->fetch();

    if ($order) {
        $histStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = :oid ORDER BY id ASC");
        $histStmt->execute(['oid' => $order['id']]);
        $history = $histStmt->fetchAll();

        $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :oid");
        $itemStmt->execute(['oid' => $order['id']]);
        $items = $itemStmt->fetchAll();
    } else {
        $error = 'No order found matching the Order Number and Contact info. Please double-check your receipt.';
    }
}

// 6 Pipeline stages
$pipelineSteps = [
    'Order Placed' => ['label' => 'Order Placed', 'icon' => 'bi-clipboard-check'],
    'Order Confirmed' => ['label' => 'Confirmed', 'icon' => 'bi-patch-check'],
    'Processing' => ['label' => 'Processing', 'icon' => 'bi-box-seam'],
    'Shipped' => ['label' => 'Shipped', 'icon' => 'bi-truck'],
    'Out for Delivery' => ['label' => 'Out for Delivery', 'icon' => 'bi-bicycle'],
    'Delivered' => ['label' => 'Delivered', 'icon' => 'bi-house-check-fill']
];

function get_step_class(string $stepKey, string $currentStatus): string {
    $orderSequence = ['Order Placed', 'Order Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
    $currentIndex = array_search($currentStatus, $orderSequence);
    $stepIndex = array_search($stepKey, $orderSequence);

    if ($currentStatus === 'Cancelled') {
        return '';
    }
    if ($currentIndex === false) {
        return '';
    }
    if ($stepIndex < $currentIndex) {
        return 'completed';
    }
    if ($stepIndex === $currentIndex) {
        return 'current';
    }
    return '';
}
?>

<div class="py-5 bg-white border-bottom">
  <div class="container text-center">
    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">LIVE SHIPMENT RADAR</span>
    <h1 class="h2 fw-bold text-navy mb-2">Track Your BUYOERA Order</h1>
    <p class="text-muted mx-auto mb-0" style="max-width: 500px;">
      Enter your Order ID (e.g. BYE-2026-8910) along with your registered mobile number or email to view real-time delivery status.
    </p>
  </div>
</div>

<div class="container py-5">
  
  <!-- Tracking Search Form -->
  <div class="row justify-content-center mb-5">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
        <form action="track-order.php" method="GET">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-navy">Order ID / Number *</label>
              <input type="text" name="order" class="form-control form-control-lg" placeholder="e.g. BYE-2026-8910" value="<?= htmlspecialchars($orderNumber) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-navy">Mobile Number or Email *</label>
              <input type="text" name="phone" class="form-control form-control-lg" placeholder="e.g. 9820112233" value="<?= htmlspecialchars($phoneOrEmail) ?>" required>
            </div>
            <div class="col-12 mt-4 text-center">
              <button type="submit" class="btn btn-buyoera-accent btn-lg rounded-pill px-5">
                <i class="bi bi-search me-1"></i> Track Shipment
              </button>
            </div>
          </div>
        </form>

        <div class="mt-3 text-center small text-muted">
          Need quick assistance? Try our sample demo order: <strong class="text-navy">BYE-2026-8910</strong> / <strong class="text-navy">9820112233</strong>
        </div>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="alert alert-danger shadow-sm rounded-4 text-center p-4">
          <i class="bi bi-exclamation-triangle-fill fs-3 d-block mb-2 text-danger"></i>
          <h5 class="fw-bold"><?= htmlspecialchars($error) ?></h5>
          <p class="small mb-0">Please check the confirmation SMS or email sent to you when placing your order.</p>
        </div>
      </div>
    </div>
  <?php elseif ($order): ?>

    <!-- Tracking Results Card -->
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
          
          <!-- Order Header Bar -->
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom pb-4 mb-4">
            <div>
              <span class="small text-muted d-block">Shipment Tracking For</span>
              <h3 class="fw-bold text-navy mb-0"><?= htmlspecialchars($order['order_number']) ?></h3>
            </div>
            <div class="text-md-end">
              <span class="small text-muted d-block">Current Status</span>
              <?php if ($order['order_status'] === 'Cancelled'): ?>
                <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">Cancelled</span>
              <?php else: ?>
                <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill"><?= htmlspecialchars($order['order_status']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- 6-Stage Visual Stepper Pipeline -->
          <?php if ($order['order_status'] !== 'Cancelled'): ?>
            <div class="tracking-pipeline my-4">
              <?php foreach ($pipelineSteps as $sKey => $sVal): 
                $stepClass = get_step_class($sKey, $order['order_status']);
              ?>
                <div class="tracking-step <?= $stepClass ?>">
                  <div class="tracking-node">
                    <i class="bi <?= $sVal['icon'] ?>"></i>
                  </div>
                  <div class="tracking-label d-none d-md-block"><?= $sVal['label'] ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Courier Details Banner -->
          <?php if (!empty($order['courier_name']) || !empty($order['tracking_number'])): ?>
            <div class="p-4 bg-light rounded-4 border mb-4">
              <div class="row align-items-center gy-3">
                <div class="col-md-6">
                  <span class="small text-muted d-block">Logistics Partner</span>
                  <h6 class="fw-bold text-navy mb-0"><i class="bi bi-truck text-primary me-2"></i> <?= htmlspecialchars($order['courier_name'] ?: 'Express Courier') ?></h6>
                </div>
                <div class="col-md-6 text-md-end">
                  <span class="small text-muted d-block">Tracking / AWB Number</span>
                  <strong class="text-navy"><?= htmlspecialchars($order['tracking_number']) ?></strong>
                  <?php if (!empty($order['tracking_url'])): ?>
                    <a href="<?= htmlspecialchars($order['tracking_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-2 rounded-pill">
                      Partner Portal <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- Tracking Timeline History -->
          <h5 class="fw-bold text-navy mb-3"><i class="bi bi-clock-history me-1"></i> Status Activity Log</h5>
          <div class="border-start border-2 border-primary ps-4 ms-3 mb-5">
            <?php foreach ($history as $h): ?>
              <div class="position-relative mb-4">
                <span class="position-absolute rounded-circle bg-primary" style="left: -31px; top: 4px; width: 12px; height: 12px;"></span>
                <div class="d-flex justify-content-between align-items-baseline mb-1">
                  <h6 class="fw-bold text-navy mb-0"><?= htmlspecialchars($h['status']) ?></h6>
                  <small class="text-muted"><?= date('d M Y, h:i A', strtotime($h['created_at'])) ?></small>
                </div>
                <p class="small text-muted mb-0"><?= htmlspecialchars($h['notes']) ?></p>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Shipping Destination -->
          <div class="p-3 rounded-3 bg-light border">
            <h6 class="fw-bold text-navy mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Delivery Destination:</h6>
            <p class="small text-muted mb-0">
              <strong><?= htmlspecialchars($order['customer_name']) ?></strong> | +91 <?= htmlspecialchars($order['customer_phone']) ?><br>
              <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> – <?= htmlspecialchars($order['shipping_pincode']) ?>
            </p>
          </div>

        </div>
      </div>
    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
