<?php
/**
 * BUYOERA - Admin Customers Management
 */

$adminPageTitle = 'Customer Base';
require_once __DIR__ . '/includes/admin-header.php';

$db = Database::getConnection();

$search = trim($_GET['s'] ?? '');
$sql = "
    SELECT u.id, u.name, u.email, u.phone, u.created_at,
           COUNT(o.id) as order_count,
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON (u.id = o.user_id OR u.email = o.customer_email)
    WHERE u.role = 'customer'
";
$params = [];
if (!empty($search)) {
    $sql .= " AND (u.name LIKE :s OR u.email LIKE :s OR u.phone LIKE :s)";
    $params['s'] = '%' . $search . '%';
}
$sql .= " GROUP BY u.id ORDER BY u.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h4 class="fw-bold text-navy mb-1">Customer Accounts</h4>
    <p class="text-muted small mb-0">View registered customers, purchase history, and lifetime value.</p>
  </div>
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
  <form action="customers.php" method="GET" class="row g-2">
    <div class="col-md-5">
      <input type="text" name="s" class="form-control" placeholder="Search by customer name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-auto">
      <button type="submit" class="btn btn-buyoera-primary">Search</button>
      <?php if (!empty($search)): ?>
        <a href="customers.php" class="btn btn-outline-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Customers Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
  <div class="table-responsive">
    <table class="table align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Contact</th>
          <th>Joined On</th>
          <th>Orders Placed</th>
          <th>Lifetime Spent</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">No customer accounts found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <?= strtoupper(substr($c['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold text-navy"><?= htmlspecialchars($c['name']) ?></h6>
                    <small class="text-muted">User ID: #<?= $c['id'] ?></small>
                  </div>
                </div>
              </td>
              <td class="small">
                <div><i class="bi bi-envelope text-muted me-1"></i> <?= htmlspecialchars($c['email']) ?></div>
                <div><i class="bi bi-telephone text-muted me-1"></i> +91 <?= htmlspecialchars($c['phone'] ?: 'N/A') ?></div>
              </td>
              <td class="small text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
              <td>
                <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">
                  <?= (int)$c['order_count'] ?> orders
                </span>
              </td>
              <td>
                <strong class="text-navy"><?= format_price($c['total_spent']) ?></strong>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
