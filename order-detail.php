<?php
/**
 * BUYOERA - Admin Order Detail & Fulfillment Page
 */

$adminPageTitle = 'Order Fulfillment Details';
require_once __DIR__ . '/includes/admin-header.php';

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}

$db = Database::getConnection();

// Handle Order Status or Logistics Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_status') {
            $newStatus = sanitize($_POST['order_status'] ?? '');
            $notes = sanitize($_POST['status_notes'] ?? '');
            $courierName = sanitize($_POST['courier_name'] ?? '');
            $trackingNumber = sanitize($_POST['tracking_number'] ?? '');
            $trackingUrl = sanitize($_POST['tracking_url'] ?? '');

            $upd = $db->prepare("
                UPDATE orders SET
                    order_status = :ostatus,
                    courier_name = :cname,
                    tracking_number = :tnum,
                    tracking_url = :turl
                WHERE id = :id
            ");
            $upd->execute([
                'ostatus' => $newStatus,
                'cname' => $courierName,
                'tnum' => $trackingNumber,
                'turl' => $trackingUrl,
                'id' => $orderId
            ]);

            // Add history entry
            $insH = $db->prepare("
                INSERT INTO order_status_history (order_id, status, notes, updated_by)
                VALUES (:oid, :st, :nt, :by)
            ");
            $insH->execute([
                'oid' => $orderId,
                'st' => $newStatus,
                'nt' => $notes ?: "Order status updated to $newStatus by Admin.",
                'by' => 'Administrator'
            ]);

            set_flash('success', 'Order status updated successfully.');
        } elseif ($action === 'update_dropship') {
            $suppStatus = sanitize($_POST['supplier_status'] ?? '');
            $suppOrderId = sanitize($_POST['supplier_order_id'] ?? '');
            $dropNotes = sanitize($_POST['dropship_notes'] ?? '');

            $updD = $db->prepare("
                UPDATE orders SET
                    supplier_status = :sstatus,
                    supplier_order_id = :soid,
                    dropship_notes = :dnotes
                WHERE id = :id
            ");
            $updD->execute([
                'sstatus' => $suppStatus,
                'soid' => $suppOrderId,
                'dnotes' => $dropNotes,
                'id' => $orderId
            ]);

            set_flash('success', 'Dropshipping forwarding details updated.');
        }

        header("Location: order-detail.php?id=" . $orderId);
        exit;
    }
}

// Fetch Order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit;
}

// Fetch Order Items
$itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :oid");
$itemStmt->execute(['oid' => $order['id']]);
$items = $itemStmt->fetchAll();

// Fetch History
$histStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = :oid ORDER BY id ASC");
$histStmt->execute(['oid' => $order['id']]);
$history = $histStmt->fetchAll();

// Calculate total supplier cost
$totalSupplierCost = 0.0;
foreach ($items as $it) {
    $totalSupplierCost += ($it['supplier_cost'] * $it['quantity']);
}
$orderGrossProfit = $order['total_amount'] - $totalSupplierCost;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <a href="orders.php" class="btn btn-sm btn-outline-secondary rounded-pill mb-2">← Back to Orders</a>
    <h4 class="fw-bold text-navy mb-0">Order: <?= htmlspecialchars($order['order_number']) ?></h4>
    <small class="text-muted">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></small>
  </div>
  <div class="d-flex gap-2">
    <button type="button" onclick="window.print()" class="btn btn-outline-dark rounded-pill">
      <i class="bi bi-printer me-1"></i> Print Packing Slip
    </button>
  </div>
</div>

<div class="row g-4">
  
  <!-- Left Column: Items, Customer, Address -->
  <div class="col-lg-8">
    
    <!-- Ordered Items Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
      <h5 class="fw-bold text-navy mb-3">Ordered Items</h5>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>Item</th>
              <th>Customer Price</th>
              <th>Qty</th>
              <th>Total</th>
              <th>Supplier Unit Cost</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td>
                  <strong class="text-navy"><?= htmlspecialchars($it['product_name']) ?></strong>
                </td>
                <td><?= format_price($it['price']) ?></td>
                <td><?= (int)$it['quantity'] ?></td>
                <td class="fw-bold"><?= format_price($it['total']) ?></td>
                <td class="small text-muted"><?= format_price($it['supplier_cost']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Financial Totals -->
      <div class="row justify-content-end mt-3">
        <div class="col-md-6">
          <div class="d-flex justify-content-between mb-1 small">
            <span class="text-muted">Subtotal:</span>
            <span><?= format_price($order['subtotal']) ?></span>
          </div>
          <?php if ($order['discount_amount'] > 0): ?>
            <div class="d-flex justify-content-between mb-1 small text-success">
              <span>Discount (<?= htmlspecialchars($order['coupon_code']) ?>):</span>
              <span>- <?= format_price($order['discount_amount']) ?></span>
            </div>
          <?php endif; ?>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Shipping:</span>
            <span><?= $order['shipping_cost'] == 0 ? 'FREE' : format_price($order['shipping_cost']) ?></span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between mb-2">
            <strong class="text-navy">Total Customer Paid:</strong>
            <strong class="text-navy"><?= format_price($order['total_amount']) ?></strong>
          </div>
          <div class="d-flex justify-content-between text-success small pt-1 border-top">
            <strong>Estimated Gross Profit:</strong>
            <strong><?= format_price($orderGrossProfit) ?></strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer & Delivery Address Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
      <h5 class="fw-bold text-navy mb-3">Customer &amp; Shipping Details</h5>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="small text-muted fw-bold d-block">Customer Name</label>
          <div class="fw-bold text-dark"><?= htmlspecialchars($order['customer_name']) ?></div>
        </div>
        <div class="col-md-6">
          <label class="small text-muted fw-bold d-block">Mobile Contact</label>
          <div>+91 <?= htmlspecialchars($order['customer_phone']) ?></div>
        </div>
        <div class="col-md-6">
          <label class="small text-muted fw-bold d-block">Email Address</label>
          <div><?= htmlspecialchars($order['customer_email']) ?></div>
        </div>
        <div class="col-md-6">
          <label class="small text-muted fw-bold d-block">Payment Method</label>
          <span class="badge bg-light text-dark border text-uppercase">
            <?= htmlspecialchars($order['payment_method']) ?> (<?= htmlspecialchars($order['payment_status']) ?>)
          </span>
        </div>
        <div class="col-12">
          <label class="small text-muted fw-bold d-block">Delivery Address</label>
          <div class="p-3 bg-light rounded-3 border">
            <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
            <?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> – <?= htmlspecialchars($order['shipping_pincode']) ?>
          </div>
        </div>
        <?php if (!empty($order['notes'])): ?>
          <div class="col-12">
            <label class="small text-muted fw-bold d-block">Customer Notes</label>
            <p class="small text-secondary mb-0 bg-warning-subtle p-2 rounded"><?= htmlspecialchars($order['notes']) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status History -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <h5 class="fw-bold text-navy mb-3">Order Activity Timeline</h5>
      <div class="border-start border-2 border-primary ps-4 ms-3">
        <?php foreach ($history as $h): ?>
          <div class="position-relative mb-3">
            <span class="position-absolute rounded-circle bg-primary" style="left: -31px; top: 4px; width: 12px; height: 12px;"></span>
            <div class="d-flex justify-content-between align-items-baseline">
              <strong class="text-navy"><?= htmlspecialchars($h['status']) ?></strong>
              <small class="text-muted"><?= date('d M Y, h:i A', strtotime($h['created_at'])) ?> (by <?= htmlspecialchars($h['updated_by']) ?>)</small>
            </div>
            <p class="small text-muted mb-0"><?= htmlspecialchars($h['notes']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- Right Column: Status Update & Dropshipping Controls -->
  <div class="col-lg-4">
    
    <!-- Order Status & Courier Assignment Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
      <h5 class="fw-bold text-navy mb-3"><i class="bi bi-truck text-primary me-1"></i> Update Status &amp; Tracking</h5>
      
      <form action="order-detail.php?id=<?= $order['id'] ?>" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_status">

        <div class="mb-3">
          <label class="form-label small fw-bold">Order Status *</label>
          <select name="order_status" class="form-select" required>
            <?php foreach (['Order Placed', 'Order Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'] as $st): ?>
              <option value="<?= $st ?>" <?= $order['order_status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Courier Partner</label>
          <input type="text" name="courier_name" class="form-control" placeholder="e.g. BlueDart Express, Delhivery" value="<?= htmlspecialchars($order['courier_name'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Tracking / AWB Number</label>
          <input type="text" name="tracking_number" class="form-control" placeholder="e.g. BD89201928IN" value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Public Tracking Portal URL</label>
          <input type="url" name="tracking_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($order['tracking_url'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Status Update Note</label>
          <textarea name="status_notes" class="form-control" rows="2" placeholder="e.g. Package dispatched via air express"></textarea>
        </div>

        <button type="submit" class="btn btn-buyoera-primary w-100 rounded-pill">
          Update Order Status
        </button>
      </form>
    </div>

    <!-- Dropshipping Supplier Forwarding Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white border-warning">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-box-arrow-right text-warning fs-5"></i>
        <h5 class="fw-bold text-navy mb-0">Dropship Forwarding</h5>
      </div>

      <form action="order-detail.php?id=<?= $order['id'] ?>" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_dropship">

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier Order Status *</label>
          <select name="supplier_status" class="form-select" required>
            <?php foreach (['Pending Forwarding', 'Forwarded to Supplier', 'Shipped by Supplier', 'Fulfilled'] as $ss): ?>
              <option value="<?= $ss ?>" <?= $order['supplier_status'] === $ss ? 'selected' : '' ?>><?= $ss ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Supplier Order / Invoice ID</label>
          <input type="text" name="supplier_order_id" class="form-control" placeholder="e.g. SUP-ORD-9912" value="<?= htmlspecialchars($order['supplier_order_id'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Dropshipping Internal Notes</label>
          <textarea name="dropship_notes" class="form-control" rows="3" placeholder="Order forwarded to supplier on..."><?= htmlspecialchars($order['dropship_notes'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill">
          Save Dropship Info
        </button>
      </form>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
