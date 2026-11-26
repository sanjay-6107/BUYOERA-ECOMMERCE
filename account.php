<?php
/**
 * BUYOERA - Customer Account Dashboard
 */

require_once __DIR__ . '/includes/functions.php';
require_login('login.php?redirect=account.php');

$user = current_user();
$db = Database::getConnection();

// Handle Profile Update POST
$profileMsg = null;
$profileErr = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $profileErr = 'Invalid security token.';
    } elseif ($_POST['action'] === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $cleanPhone = str_replace([' ', '-', '+91'], '', $phone);

        if (empty($name)) {
            $profileErr = 'Name cannot be empty.';
        } elseif (!preg_match('/^[6-9][0-9]{9}$/', $cleanPhone)) {
            $profileErr = 'Please enter a valid 10-digit Indian mobile number.';
        } else {
            $upd = $db->prepare("UPDATE users SET name = :name, phone = :phone WHERE id = :id");
            $upd->execute(['name' => $name, 'phone' => $cleanPhone, 'id' => $user['id']]);
            $_SESSION['user_name'] = $name;
            $profileMsg = 'Profile updated successfully!';
            $user = current_user();
        }
    } elseif ($_POST['action'] === 'change_password') {
        $currentPass = trim($_POST['current_password'] ?? '');
        $newPass = trim($_POST['new_password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');

        // Fetch current password hash
        $pStmt = $db->prepare("SELECT password FROM users WHERE id = :id");
        $pStmt->execute(['id' => $user['id']]);
        $row = $pStmt->fetch();

        if (!password_verify($currentPass, $row['password'])) {
            $profileErr = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $profileErr = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $profileErr = 'New passwords do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $updPass = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
            $updPass->execute(['p' => $newHash, 'id' => $user['id']]);
            $profileMsg = 'Password changed successfully!';
        }
    } elseif ($_POST['action'] === 'add_address') {
        $rname = sanitize($_POST['recipient_name'] ?? '');
        $rphone = sanitize($_POST['phone'] ?? '');
        $saddr = sanitize($_POST['street_address'] ?? '');
        $landmark = sanitize($_POST['landmark'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');

        if (!empty($rname) && !empty($saddr) && !empty($city) && !empty($pincode)) {
            $insA = $db->prepare("
                INSERT INTO addresses (user_id, recipient_name, phone, street_address, landmark, city, state, pincode, is_default)
                VALUES (:uid, :rn, :rp, :sa, :lm, :ci, :st, :pin, 0)
            ");
            $insA->execute([
                'uid' => $user['id'], 'rn' => $rname, 'rp' => $rphone,
                'sa' => $saddr, 'lm' => $landmark, 'ci' => $city, 'st' => $state, 'pin' => $pincode
            ]);
            $profileMsg = 'New delivery address saved!';
        } else {
            $profileErr = 'Please fill all required address fields.';
        }
    } elseif ($_POST['action'] === 'delete_address') {
        $aid = (int)($_POST['address_id'] ?? 0);
        $del = $db->prepare("DELETE FROM addresses WHERE id = :aid AND user_id = :uid");
        $del->execute(['aid' => $aid, 'uid' => $user['id']]);
        $profileMsg = 'Address removed.';
    }
}

// Fetch Customer Orders
$ordStmt = $db->prepare("SELECT * FROM orders WHERE user_id = :uid OR customer_email = :email ORDER BY id DESC");
$ordStmt->execute(['uid' => $user['id'], 'email' => $user['email']]);
$orders = $ordStmt->fetchAll();

// Fetch Saved Addresses
$addrStmt = $db->prepare("SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, id DESC");
$addrStmt->execute(['uid' => $user['id']]);
$addresses = $addrStmt->fetchAll();

$indianStates = get_indian_states();
$pageTitle = 'My Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-4 bg-white border-bottom">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-0">My Account Dashboard</h1>
        <p class="text-muted small mb-0">Welcome back, <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</p>
      </div>
      <div>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="admin/index.php" class="btn btn-outline-danger me-2"><i class="bi bi-speedometer2"></i> Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
  </div>
</div>

<div class="container py-5">
  
  <?php if ($profileMsg): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4">
      <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($profileMsg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($profileErr): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4">
      <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($profileErr) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    
    <!-- Left Navigation Tabs -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm rounded-4 p-3 bg-white sticky-top" style="top: 90px;">
        <div class="d-flex align-items-center gap-3 p-2 mb-3 border-bottom pb-3">
          <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center fs-5" style="width: 50px; height: 50px;">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <div class="text-truncate">
            <h6 class="fw-bold text-navy mb-0 text-truncate"><?= htmlspecialchars($user['name']) ?></h6>
            <span class="badge bg-light text-muted border text-capitalize"><?= htmlspecialchars($user['role']) ?></span>
          </div>
        </div>

        <div class="nav flex-column nav-pills gap-1" id="accountTabs" role="tablist">
          <button class="nav-link active text-start py-2 px-3 fw-bold" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders-pane" type="button" role="tab">
            <i class="bi bi-bag-check me-2"></i> Order History (<?= count($orders) ?>)
          </button>
          <button class="nav-link text-start py-2 px-3 fw-bold" id="addresses-tab" data-bs-toggle="pill" data-bs-target="#addresses-pane" type="button" role="tab">
            <i class="bi bi-geo-alt me-2"></i> Saved Addresses (<?= count($addresses) ?>)
          </button>
          <button class="nav-link text-start py-2 px-3 fw-bold" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile-pane" type="button" role="tab">
            <i class="bi bi-person me-2"></i> Profile Settings
          </button>
          <button class="nav-link text-start py-2 px-3 fw-bold" id="security-tab" data-bs-toggle="pill" data-bs-target="#security-pane" type="button" role="tab">
            <i class="bi bi-shield-lock me-2"></i> Change Password
          </button>
        </div>
      </div>
    </div>

    <!-- Right Pane Content -->
    <div class="col-lg-9">
      <div class="tab-content" id="accountTabsContent">
        
        <!-- 1. Orders Tab -->
        <div class="tab-pane fade show active" id="orders-pane" role="tabpanel">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h4 class="fw-bold text-navy mb-4">Your Past Orders</h4>

            <?php if (empty($orders)): ?>
              <div class="text-center py-5">
                <i class="bi bi-bag-x display-4 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold text-navy">No Orders Placed Yet</h5>
                <p class="text-muted small mb-4">When you place an order, you can track shipments and view invoices here.</p>
                <a href="shop.php" class="btn btn-buyoera-primary rounded-pill px-4">Start Shopping</a>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Order ID</th>
                      <th>Date</th>
                      <th>Total</th>
                      <th>Payment</th>
                      <th>Status</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($orders as $o): ?>
                      <tr>
                        <td>
                          <strong class="text-navy"><?= htmlspecialchars($o['order_number']) ?></strong>
                        </td>
                        <td class="small text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        <td class="fw-bold text-navy"><?= format_price($o['total_amount']) ?></td>
                        <td class="small text-uppercase">
                          <span class="badge bg-light text-dark border"><?= htmlspecialchars($o['payment_method']) ?></span>
                        </td>
                        <td>
                          <?php
                          $badgeClass = 'bg-primary-subtle text-primary';
                          if ($o['order_status'] === 'Delivered') $badgeClass = 'bg-success-subtle text-success';
                          elseif ($o['order_status'] === 'Shipped') $badgeClass = 'bg-info-subtle text-info';
                          elseif ($o['order_status'] === 'Cancelled') $badgeClass = 'bg-danger-subtle text-danger';
                          ?>
                          <span class="badge <?= $badgeClass ?> px-3 py-1 rounded-pill"><?= htmlspecialchars($o['order_status']) ?></span>
                        </td>
                        <td class="text-end">
                          <a href="track-order.php?order=<?= urlencode($o['order_number']) ?>&phone=<?= urlencode($o['customer_phone']) ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                            Track <i class="bi bi-truck"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- 2. Saved Addresses Tab -->
        <div class="tab-pane fade" id="addresses-pane" role="tabpanel">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <h4 class="fw-bold text-navy mb-0">Delivery Addresses</h4>
              <button class="btn btn-sm btn-buyoera-primary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#newAddrForm">
                <i class="bi bi-plus-circle me-1"></i> Add New Address
              </button>
            </div>

            <!-- Add Address Collapse -->
            <div class="collapse mb-4" id="newAddrForm">
              <div class="p-4 rounded-4 bg-light border">
                <h6 class="fw-bold text-navy mb-3">Add a New Indian Delivery Address</h6>
                <form action="account.php" method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="add_address">
                  
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label small fw-bold">Recipient Full Name *</label>
                      <input type="text" name="recipient_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-bold">Mobile Number *</label>
                      <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile" required>
                    </div>
                    <div class="col-12">
                      <label class="form-label small fw-bold">Street Address / Flat No *</label>
                      <input type="text" name="street_address" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-bold">Landmark</label>
                      <input type="text" name="landmark" class="form-control">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-bold">City *</label>
                      <input type="text" name="city" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label small fw-bold">PIN Code *</label>
                      <input type="text" name="pincode" maxlength="6" class="form-control" required>
                    </div>
                    <div class="col-12">
                      <label class="form-label small fw-bold">State *</label>
                      <select name="state" class="form-select" required>
                        <?php foreach ($indianStates as $st): ?>
                          <option value="<?= htmlspecialchars($st) ?>"><?= htmlspecialchars($st) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 text-end">
                      <button type="submit" class="btn btn-buyoera-accent rounded-pill px-4">Save Address</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <!-- List Addresses -->
            <div class="row g-3">
              <?php if (empty($addresses)): ?>
                <div class="col-12 text-center py-4 text-muted small">
                  No saved addresses yet. You can add one above or during checkout.
                </div>
              <?php else: ?>
                <?php foreach ($addresses as $ad): ?>
                  <div class="col-md-6">
                    <div class="p-3 rounded-4 border h-100 bg-light position-relative">
                      <?php if ($ad['is_default']): ?>
                        <span class="badge bg-success mb-2">Default Address</span>
                      <?php endif; ?>
                      <h6 class="fw-bold text-navy mb-1"><?= htmlspecialchars($ad['recipient_name']) ?></h6>
                      <p class="small text-muted mb-2">
                        <?= htmlspecialchars($ad['street_address']) ?><?= $ad['landmark'] ? ', ' . htmlspecialchars($ad['landmark']) : '' ?><br>
                        <?= htmlspecialchars($ad['city']) ?>, <?= htmlspecialchars($ad['state']) ?> – <?= htmlspecialchars($ad['pincode']) ?><br>
                        Phone: +91 <?= htmlspecialchars($ad['phone']) ?>
                      </p>
                      <form action="account.php" method="POST" onsubmit="return confirm('Delete this address?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_address">
                        <input type="hidden" name="address_id" value="<?= $ad['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-3 rounded-pill">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

          </div>
        </div>

        <!-- 3. Profile Settings Tab -->
        <div class="tab-pane fade" id="profile-pane" role="tabpanel">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h4 class="fw-bold text-navy mb-4">Edit Profile</h4>
            <form action="account.php" method="POST" style="max-width: 500px;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update_profile">

              <div class="mb-3">
                <label class="form-label small fw-bold text-navy">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-navy">Email Address</label>
                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                <small class="text-muted">Email address cannot be changed directly for account security.</small>
              </div>

              <div class="mb-4">
                <label class="form-label small fw-bold text-navy">Mobile Number</label>
                <div class="input-group">
                  <span class="input-group-text bg-light text-muted fw-bold">+91</span>
                  <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" maxlength="10" required>
                </div>
              </div>

              <button type="submit" class="btn btn-buyoera-primary rounded-pill px-4">
                Save Changes
              </button>
            </form>
          </div>
        </div>

        <!-- 4. Security Tab -->
        <div class="tab-pane fade" id="security-pane" role="tabpanel">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h4 class="fw-bold text-navy mb-4">Change Password</h4>
            <form action="account.php" method="POST" style="max-width: 500px;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="change_password">

              <div class="mb-3">
                <label class="form-label small fw-bold text-navy">Current Password *</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-navy">New Password *</label>
                <input type="password" name="new_password" class="form-control" placeholder="At least 6 characters" required>
              </div>

              <div class="mb-4">
                <label class="form-label small fw-bold text-navy">Confirm New Password *</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>

              <button type="submit" class="btn btn-buyoera-primary rounded-pill px-4">
                Update Password
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
