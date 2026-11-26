<?php
/**
 * BUYOERA - Admin Store Configuration
 */

$adminPageTitle = 'Store Settings';
require_once __DIR__ . '/includes/admin-header.php';

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $fields = [
            'site_name', 'site_tagline', 'currency_symbol',
            'free_shipping_threshold', 'shipping_fee',
            'contact_email', 'contact_phone', 'announcement_text',
            'razorpay_enabled', 'razorpay_key_id'
        ];

        $updStmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2");
        if (Database::getDriver() === 'sqlite') {
            $updStmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (:k, :v)");
        }

        foreach ($fields as $f) {
            $val = trim($_POST[$f] ?? '');
            if (Database::getDriver() === 'sqlite') {
                $updStmt->execute(['k' => $f, 'v' => $val]);
            } else {
                $updStmt->execute(['k' => $f, 'v' => $val, 'v2' => $val]);
            }
        }

        set_flash('success', 'Store settings updated successfully.');
        header("Location: settings.php");
        exit;
    }
}

// Fetch current settings
$settings = [];
$sStmt = $db->query("SELECT setting_key, setting_value FROM settings");
while ($r = $sStmt->fetch()) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
?>

<div class="mb-4">
  <h4 class="fw-bold text-navy mb-1">Store Configuration &amp; Payment Gateway</h4>
  <p class="text-muted small mb-0">Configure Indian market parameters, shipping rules, and Razorpay test credentials.</p>
</div>

<form action="settings.php" method="POST">
  <?= csrf_field() ?>

  <div class="row g-4">
    
    <!-- General Store Settings -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-shop me-1 text-primary"></i> Brand Identity</h5>
        
        <div class="mb-3">
          <label class="form-label small fw-bold">Brand Name</label>
          <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? 'BUYOERA') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Brand Tagline</label>
          <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($settings['site_tagline'] ?? 'Shop Smart. Live Better.') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Top Announcement Ticker Bar Text</label>
          <textarea name="announcement_text" class="form-control" rows="2"><?= htmlspecialchars($settings['announcement_text'] ?? '⚡ Mega Indian Festival Sale: Free Express Shipping over ₹499 | Cash on Delivery (COD) Available! Use code WELCOME10 for 10% OFF') ?></textarea>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Support Email</label>
            <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email'] ?? 'support@buyoera.com') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Support Helpline (India)</label>
            <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone'] ?? '+91 98765 43210') ?>">
          </div>
        </div>
      </div>

      <!-- Shipping Rules -->
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-truck me-1 text-primary"></i> Shipping Charges &amp; Free Delivery</h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Free Shipping Minimum Cart Value (₹)</label>
            <input type="number" name="free_shipping_threshold" class="form-control" value="<?= htmlspecialchars($settings['free_shipping_threshold'] ?? '499') ?>" required>
            <small class="text-muted">Carts above this amount receive automatic free shipping.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Flat Shipping Fee Below Threshold (₹)</label>
            <input type="number" name="shipping_fee" class="form-control" value="<?= htmlspecialchars($settings['shipping_fee'] ?? '49') ?>" required>
            <small class="text-muted">Standard courier flat rate.</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Gateway & Currency -->
    <div class="col-lg-5">
      
      <!-- Currency -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-currency-rupee text-primary me-1"></i> Currency Format</h5>
        <div class="mb-3">
          <label class="form-label small fw-bold">Currency Symbol</label>
          <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '₹') ?>" required>
        </div>
      </div>

      <!-- Razorpay Test Mode Configuration -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white border-warning">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="fw-bold text-navy mb-0"><i class="bi bi-credit-card text-warning me-1"></i> Razorpay Integration</h5>
          <span class="badge bg-warning text-dark">Test Mode</span>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Enable Razorpay Gateway</label>
          <select name="razorpay_enabled" class="form-select">
            <option value="1" <?= ($settings['razorpay_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Enabled (Active)</option>
            <option value="0" <?= ($settings['razorpay_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Disabled</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Razorpay Test Key ID</label>
          <input type="text" name="razorpay_key_id" class="form-control" value="<?= htmlspecialchars($settings['razorpay_key_id'] ?? 'rzp_test_buyoeraSampleKey123') ?>">
          <small class="text-muted">Public client key starting with <code>rzp_test_</code></small>
        </div>

        <div class="p-3 bg-light rounded-3 small border">
          <i class="bi bi-shield-check text-success me-1"></i> <strong>Security Guard:</strong> Razorpay Secret Key is securely managed in <code>config/config.php</code> and never exposed to the browser or frontend scripts.
        </div>
      </div>

      <button type="submit" class="btn btn-buyoera-accent btn-lg w-100 rounded-pill py-3 fw-bold">
        <i class="bi bi-check2-circle me-1"></i> Save Store Settings
      </button>

    </div>

  </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
