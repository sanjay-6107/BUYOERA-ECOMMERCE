<?php
/**
 * BUYOERA - Order Confirmation & Receipt Page
 */

require_once __DIR__ . '/includes/functions.php';

$orderNumber = trim($_GET['order'] ?? '');
if (empty($orderNumber)) {
    header("Location: index.php");
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = :onum LIMIT 1");
$stmt->execute(['onum' => $orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Fetch order items
$itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :oid");
$itemStmt->execute(['oid' => $order['id']]);
$items = $itemStmt->fetchAll();

$pageTitle = 'Order Confirmed - ' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <!-- Success Celebration Card -->
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 text-center bg-white">
        <div class="mb-3">
          <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="bi bi-check2-circle display-4"></i>
          </div>
        </div>
        <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 rounded-pill mb-2">ORDER PLACED SUCCESSFULLY</span>
        <h1 class="h2 fw-bold text-navy mb-2">Thank you, <?= htmlspecialchars(explode(' ', $order['customer_name'])[0]) ?>!</h1>
        <p class="text-muted mb-4 mx-auto" style="max-width: 500px;">
          Your order has been received and confirmed. We have sent an email confirmation and invoice receipt to <strong><?= htmlspecialchars($order['customer_email']) ?></strong>.
        </p>

        <div class="p-3 bg-light rounded-3 d-inline-flex flex-wrap gap-4 text-start justify-content-center mb-4 border">
          <div>
            <span class="small text-muted d-block">Order Number</span>
            <strong class="text-navy"><?= htmlspecialchars($order['order_number']) ?></strong>
          </div>
          <div>
            <span class="small text-muted d-block">Order Date</span>
            <strong class="text-navy"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></strong>
          </div>
          <div>
            <span class="small text-muted d-block">Payment Method</span>
            <strong class="text-navy text-uppercase"><?= htmlspecialchars($order['payment_method']) ?> (<?= htmlspecialchars($order['payment_status']) ?>)</strong>
          </div>
          <div>
            <span class="small text-muted d-block">Total Amount</span>
            <strong class="text-navy"><?= format_price($order['total_amount']) ?></strong>
          </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-3">
          <a href="track-order.php?order=<?= urlencode($order['order_number']) ?>&phone=<?= urlencode($order['customer_phone']) ?>" class="btn btn-buyoera-accent rounded-pill px-4">
            <i class="bi bi-truck me-1"></i> Track Your Shipment
          </a>
          <button type="button" onclick="window.print()" class="btn btn-buyoera-outline rounded-pill px-4">
            <i class="bi bi-printer me-1"></i> Print Invoice
          </button>
          <a href="shop.php" class="btn btn-light border rounded-pill px-4">
            Continue Shopping
          </a>
        </div>
      </div>

      <!-- Order Details Breakdown Card -->
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <h4 class="fw-bold text-navy mb-4 border-bottom pb-2">Order Summary</h4>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th>Product Description</th>
                <th>Price</th>
                <th class="text-center">Quantity</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td>
                    <strong class="text-navy"><?= htmlspecialchars($it['product_name']) ?></strong>
                  </td>
                  <td><?= format_price($it['price']) ?></td>
                  <td class="text-center"><?= (int)$it['quantity'] ?></td>
                  <td class="text-end fw-bold"><?= format_price($it['total']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Totals Row -->
        <div class="row justify-content-end mb-4">
          <div class="col-md-6">
            <div class="d-flex justify-content-between mb-2 small">
              <span class="text-muted">Subtotal:</span>
              <span class="fw-bold text-navy"><?= format_price($order['subtotal']) ?></span>
            </div>
            <?php if ($order['discount_amount'] > 0): ?>
              <div class="d-flex justify-content-between mb-2 small text-success">
                <span>Discount (<?= htmlspecialchars($order['coupon_code']) ?>):</span>
                <span class="fw-bold">- <?= format_price($order['discount_amount']) ?></span>
              </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-2 small">
              <span class="text-muted">Express Shipping:</span>
              <span class="fw-bold <?= $order['shipping_cost'] == 0 ? 'text-success' : 'text-navy' ?>">
                <?= $order['shipping_cost'] == 0 ? 'FREE' : format_price($order['shipping_cost']) ?>
              </span>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
              <span class="h6 fw-bold text-navy mb-0">Total Amount:</span>
              <span class="h5 fw-bold text-navy mb-0"><?= format_price($order['total_amount']) ?></span>
            </div>
          </div>
        </div>

        <!-- Shipping Address Details -->
        <div class="p-3 bg-light rounded-3 border">
          <h6 class="fw-bold text-navy mb-2"><i class="bi bi-geo-alt text-primary me-1"></i> Delivery Address:</h6>
          <p class="mb-0 text-secondary small">
            <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
            <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
            <?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> – <?= htmlspecialchars($order['shipping_pincode']) ?><br>
            Phone: +91 <?= htmlspecialchars($order['customer_phone']) ?> | Email: <?= htmlspecialchars($order['customer_email']) ?>
          </p>
        </div>

      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
