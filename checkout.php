<?php
/**
 * BUYOERA - Indian Checkout Page
 */

require_once __DIR__ . '/includes/functions.php';

// Check if Direct "Buy Now" parameter provided
if (isset($_GET['buy_now'])) {
    $buyNowId = (int)$_GET['buy_now'];
    add_to_cart($buyNowId, 1);
}

$summary = get_cart_summary();
if (empty($summary['items'])) {
    header("Location: cart.php");
    exit;
}

$user = current_user();
$db = Database::getConnection();

// Default values prefilled if logged in
$prefillName = $user['name'] ?? '';
$prefillEmail = $user['email'] ?? '';
$prefillPhone = $user['phone'] ?? '';
$prefillAddress = '';
$prefillLandmark = '';
$prefillCity = '';
$prefillState = 'Maharashtra';
$prefillPincode = '';

if ($user) {
    // Check saved default address
    $addrStmt = $db->prepare("SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, id DESC LIMIT 1");
    $addrStmt->execute(['uid' => $user['id']]);
    $savedAddr = $addrStmt->fetch();
    if ($savedAddr) {
        $prefillName = $savedAddr['recipient_name'] ?: $prefillName;
        $prefillPhone = $savedAddr['phone'] ?: $prefillPhone;
        $prefillAddress = $savedAddr['street_address'];
        $prefillLandmark = $savedAddr['landmark'];
        $prefillCity = $savedAddr['city'];
        $prefillState = $savedAddr['state'];
        $prefillPincode = $savedAddr['pincode'];
    }
}

// Handle Order Placement POST
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    } else {
        $custName = sanitize($_POST['customer_name'] ?? '');
        $custEmail = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $custPhone = sanitize($_POST['customer_phone'] ?? '');
        $streetAddr = sanitize($_POST['shipping_address'] ?? '');
        $landmark = sanitize($_POST['landmark'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'cod');
        $saveAddress = isset($_POST['save_address']) && is_logged_in();
        $notes = sanitize($_POST['notes'] ?? '');

        // Validations
        if (empty($custName)) $errors[] = 'Please enter your full name.';
        if (!$custEmail) $errors[] = 'Please enter a valid email address.';
        if (!preg_match('/^[6-9][0-9]{9}$/', str_replace([' ', '-', '+91'], '', $custPhone))) {
            $errors[] = 'Please enter a valid 10-digit Indian mobile number.';
        }
        if (empty($streetAddr) || strlen($streetAddr) < 8) $errors[] = 'Please enter your complete street / house address.';
        if (empty($city)) $errors[] = 'Please enter your city.';
        if (empty($state)) $errors[] = 'Please select your state.';
        if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
            $errors[] = 'Please enter a valid 6-digit Indian PIN code.';
        }

        if (empty($errors)) {
            $orderNumber = 'BYE-' . date('Y') . '-' . rand(1000, 9999);
            $userId = is_logged_in() ? $_SESSION['user_id'] : null;
            $subtotal = $summary['subtotal'];
            $discount = $summary['discount'];
            $shippingCost = $summary['shipping'];
            $totalAmount = $summary['total'];
            $couponCode = $summary['coupon_code'];

            $paymentStatus = in_array($paymentMethod, ['upi', 'card', 'netbanking', 'razorpay']) ? 'paid' : 'pending';
            $orderStatus = 'Order Placed';

            // Insert into orders table
            $insOrder = $db->prepare("
                INSERT INTO orders (
                    order_number, user_id, customer_name, customer_email, customer_phone,
                    shipping_address, shipping_city, shipping_state, shipping_pincode,
                    subtotal, shipping_cost, discount_amount, coupon_code, total_amount,
                    payment_method, payment_status, order_status,
                    supplier_status, notes
                ) VALUES (
                    :onum, :uid, :cname, :cemail, :cphone,
                    :saddr, :scity, :sstate, :spin,
                    :sub, :ship, :disc, :coupon, :total,
                    :pmethod, :pstatus, :ostatus,
                    'Pending Forwarding', :notes
                )
            ");

            $insOrder->execute([
                'onum' => $orderNumber,
                'uid' => $userId,
                'cname' => $custName,
                'cemail' => $custEmail,
                'cphone' => $custPhone,
                'saddr' => $streetAddr . ($landmark ? " (Landmark: $landmark)" : ""),
                'scity' => $city,
                'sstate' => $state,
                'spin' => $pincode,
                'sub' => $subtotal,
                'ship' => $shippingCost,
                'disc' => $discount,
                'coupon' => $couponCode,
                'total' => $totalAmount,
                'pmethod' => $paymentMethod,
                'pstatus' => $paymentStatus,
                'ostatus' => $orderStatus,
                'notes' => $notes
            ]);

            $orderId = $db->lastInsertId();

            // Insert items & decrement stock
            $insItem = $db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total, supplier_cost)
                VALUES (:oid, :pid, :pname, :price, :qty, :total, :scost)
            ");

            $decStock = $db->prepare("UPDATE products SET stock_quantity = MAX(0, stock_quantity - :qty) WHERE id = :pid");

            foreach ($summary['items'] as $it) {
                $itemTotal = $it['price'] * $it['quantity'];
                $insItem->execute([
                    'oid' => $orderId,
                    'pid' => $it['id'],
                    'pname' => $it['name'],
                    'price' => $it['price'],
                    'qty' => $it['quantity'],
                    'total' => $itemTotal,
                    'scost' => $it['supplier_cost'] ?? 0.00
                ]);

                $decStock->execute([
                    'qty' => $it['quantity'],
                    'pid' => $it['id']
                ]);
            }

            // Record status history
            $insHist = $db->prepare("
                INSERT INTO order_status_history (order_id, status, notes, updated_by)
                VALUES (:oid, :st, :nt, 'System')
            ");
            $histNotes = ($paymentMethod === 'cod') 
                ? 'Order placed with Cash on Delivery (COD). Verification pending.'
                : 'Payment received via ' . strtoupper($paymentMethod) . '. Order placed successfully.';
            $insHist->execute([
                'oid' => $orderId,
                'st' => 'Order Placed',
                'nt' => $histNotes
            ]);

            // Save address for logged-in user if requested
            if ($saveAddress && $userId) {
                $insAddr = $db->prepare("
                    INSERT INTO addresses (user_id, recipient_name, phone, street_address, landmark, city, state, pincode, is_default)
                    VALUES (:uid, :rname, :phone, :saddr, :lmark, :city, :state, :pin, 1)
                ");
                $insAddr->execute([
                    'uid' => $userId,
                    'rname' => $custName,
                    'phone' => $custPhone,
                    'saddr' => $streetAddr,
                    'lmark' => $landmark,
                    'city' => $city,
                    'state' => $state,
                    'pin' => $pincode
                ]);
            }

            // Increment coupon usage count if applied
            if (!empty($couponCode)) {
                $updCoupon = $db->prepare("UPDATE coupons SET times_used = times_used + 1 WHERE code = :code");
                $updCoupon->execute(['code' => $couponCode]);
            }

            // Clear session cart
            clear_cart();

            // Redirect to confirmation page
            header("Location: order-confirmation.php?order=" . urlencode($orderNumber));
            exit;
        }
    }
}

$indianStates = get_indian_states();
$pageTitle = 'Secure Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-4 bg-white border-bottom">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1 small">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
        <li class="breadcrumb-item active" aria-current="page">Checkout</li>
      </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between">
      <h1 class="h3 fw-bold text-navy mb-0">Express Checkout</h1>
      <span class="small text-success fw-bold"><i class="bi bi-shield-lock-fill"></i> 256-Bit SSL Encrypted</span>
    </div>
  </div>
</div>

<div class="container py-5">
  
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm rounded-3 mb-4">
      <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form action="checkout.php" method="POST" id="checkoutForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="place_order">

    <div class="row g-4">
      
      <!-- Left Column: Delivery Address & Payment Method -->
      <div class="col-lg-7">
        
        <!-- Step 1: Contact Information -->
        <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
          <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <h5 class="fw-bold text-navy mb-0"><span class="badge bg-primary rounded-circle me-2">1</span> Contact Details</h5>
            <?php if (!is_logged_in()): ?>
              <small class="text-muted">Already have an account? <a href="login.php" class="fw-bold">Log in</a></small>
            <?php endif; ?>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Full Name *</label>
              <input type="text" name="customer_name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= htmlspecialchars($_POST['customer_name'] ?? $prefillName) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">10-Digit Mobile Number *</label>
              <div class="input-group">
                <span class="input-group-text bg-light fw-bold text-muted">+91</span>
                <input type="tel" name="customer_phone" class="form-control" placeholder="9820112233" maxlength="10" value="<?= htmlspecialchars($_POST['customer_phone'] ?? $prefillPhone) ?>" required>
              </div>
              <small class="text-muted">Required for courier delivery updates &amp; OTP</small>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Email Address *</label>
              <input type="email" name="customer_email" class="form-control" placeholder="rahul@example.com" value="<?= htmlspecialchars($_POST['customer_email'] ?? $prefillEmail) ?>" required>
              <small class="text-muted">Order invoice &amp; tracking links will be sent here</small>
            </div>
          </div>
        </div>

        <!-- Step 2: Shipping Address -->
        <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
          <div class="d-flex align-items-center mb-3 border-bottom pb-2">
            <h5 class="fw-bold text-navy mb-0"><span class="badge bg-primary rounded-circle me-2">2</span> Delivery Address (India)</h5>
          </div>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold">Flat, House no., Building, Company, Apartment *</label>
              <input type="text" name="shipping_address" class="form-control" placeholder="e.g. Flat 402, Sunshine Heights, Linking Road" value="<?= htmlspecialchars($_POST['shipping_address'] ?? $prefillAddress) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Landmark (Optional)</label>
              <input type="text" name="landmark" class="form-control" placeholder="e.g. Near National College" value="<?= htmlspecialchars($_POST['landmark'] ?? $prefillLandmark) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">6-Digit PIN Code *</label>
              <input type="text" name="pincode" id="checkoutPincode" class="form-control" placeholder="e.g. 400050" maxlength="6" value="<?= htmlspecialchars($_POST['pincode'] ?? $prefillPincode) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">City / Town / District *</label>
              <input type="text" name="city" id="checkoutCity" class="form-control" placeholder="e.g. Mumbai" value="<?= htmlspecialchars($_POST['city'] ?? $prefillCity) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">State / Union Territory *</label>
              <select name="state" id="checkoutState" class="form-select" required>
                <option value="">Select State</option>
                <?php foreach ($indianStates as $st): ?>
                  <option value="<?= htmlspecialchars($st) ?>" <?= ($prefillState === $st || (isset($_POST['state']) && $_POST['state'] === $st)) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if (is_logged_in()): ?>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="save_address" id="saveAddressCheck" checked>
                  <label class="form-check-label small" for="saveAddressCheck">
                    Save this address to my profile for future orders
                  </label>
                </div>
              </div>
            <?php endif; ?>

            <div class="col-12">
              <label class="form-label small fw-bold">Special Delivery Instructions (Optional)</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Leave with security guard, call before delivery"></textarea>
            </div>
          </div>
        </div>

        <!-- Step 3: Payment Method -->
        <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
          <div class="d-flex align-items-center mb-3 border-bottom pb-2">
            <h5 class="fw-bold text-navy mb-0"><span class="badge bg-primary rounded-circle me-2">3</span> Payment Method</h5>
          </div>

          <!-- Cash on Delivery -->
          <label class="payment-method-card selected">
            <div class="d-flex align-items-center gap-3">
              <input type="radio" name="payment_method" value="cod" checked class="form-check-input mt-0">
              <div>
                <span class="fw-bold text-navy d-block">Cash on Delivery (COD)</span>
                <small class="text-muted">Pay by Cash or UPI QR Code at your doorstep upon delivery.</small>
              </div>
            </div>
            <span class="badge bg-success-subtle text-success fw-bold px-3 py-1">Popular</span>
          </label>

          <!-- Instant UPI -->
          <label class="payment-method-card">
            <div class="d-flex align-items-center gap-3">
              <input type="radio" name="payment_method" value="upi" class="form-check-input mt-0">
              <div>
                <span class="fw-bold text-navy d-block">UPI (Google Pay, PhonePe, Paytm, BHIM)</span>
                <small class="text-muted">Instant payment via any UPI application or UPI ID.</small>
              </div>
            </div>
            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1">Zero Fee</span>
          </label>

          <!-- Debit / Credit Card -->
          <label class="payment-method-card">
            <div class="d-flex align-items-center gap-3">
              <input type="radio" name="payment_method" value="card" class="form-check-input mt-0">
              <div>
                <span class="fw-bold text-navy d-block">Debit &amp; Credit Cards</span>
                <small class="text-muted">RuPay, Visa, MasterCard, Diners, American Express.</small>
              </div>
            </div>
            <span class="badge bg-light text-dark border">All Banks</span>
          </label>

          <!-- Net Banking -->
          <label class="payment-method-card">
            <div class="d-flex align-items-center gap-3">
              <input type="radio" name="payment_method" value="netbanking" class="form-check-input mt-0">
              <div>
                <span class="fw-bold text-navy d-block">Net Banking</span>
                <small class="text-muted">SBI, HDFC, ICICI, Axis, Kotak, and 50+ Indian banks.</small>
              </div>
            </div>
          </label>

          <!-- Razorpay Integration (Test Mode Prepared) -->
          <label class="payment-method-card">
            <div class="d-flex align-items-center gap-3">
              <input type="radio" name="payment_method" value="razorpay" class="form-check-input mt-0">
              <div>
                <span class="fw-bold text-navy d-block">Razorpay Secure Checkout</span>
                <small class="text-muted">Integrated Indian Gateway (Test Mode Prepared).</small>
              </div>
            </div>
            <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">Test Mode</span>
          </label>

        </div>

      </div>

      <!-- Right Column: Order Summary & Place Order -->
      <div class="col-lg-5">
        <div class="cart-summary-card shadow-sm">
          <h5 class="fw-bold text-navy mb-3">Order Details (<?= $summary['count'] ?> Items)</h5>

          <!-- Item list mini -->
          <div class="mb-3 max-h-250 overflow-auto">
            <?php foreach ($summary['items'] as $item): ?>
              <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="44" height="44" class="rounded border bg-light p-1">
                  <div>
                    <h6 class="mb-0 small fw-bold text-navy text-truncate" style="max-width: 170px;"><?= htmlspecialchars($item['name']) ?></h6>
                    <small class="text-muted">Qty: <?= $item['quantity'] ?> × <?= format_price($item['price']) ?></small>
                  </div>
                </div>
                <span class="fw-bold text-navy small"><?= format_price($item['price'] * $item['quantity']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Price Summary -->
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Subtotal</span>
            <span class="fw-bold text-navy"><?= $summary['subtotal_formatted'] ?></span>
          </div>

          <?php if ($summary['discount'] > 0): ?>
            <div class="d-flex justify-content-between mb-2 small text-success">
              <span>Discount Applied (<?= htmlspecialchars($summary['coupon_code']) ?>)</span>
              <span class="fw-bold">- <?= $summary['discount_formatted'] ?></span>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-between mb-3 small">
            <span class="text-muted">Pan-India Express Shipping</span>
            <span class="fw-bold <?= $summary['shipping'] == 0 ? 'text-success' : 'text-navy' ?>">
              <?= $summary['shipping_formatted'] ?>
            </span>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-baseline mb-4">
            <span class="fs-5 fw-bold text-navy">Total Payable</span>
            <span class="display-6 fw-bold text-navy"><?= $summary['total_formatted'] ?></span>
          </div>

          <button type="submit" class="btn btn-buyoera-accent btn-lg w-100 py-3 rounded-pill fw-bold text-uppercase letter-spacing-1">
            <i class="bi bi-shield-check me-1"></i> Place Order (<?= $summary['total_formatted'] ?>)
          </button>

          <div class="mt-3 text-center small text-muted">
            By placing your order, you agree to BUYOERA's <a href="terms.php" class="text-primary">Terms of Service</a> and <a href="privacy-policy.php" class="text-primary">Privacy Policy</a>.
          </div>

          <div class="mt-4 p-3 bg-light rounded-3 border text-center">
            <img src="assets/images/payment-methods.svg" alt="Payment Badges" style="max-width: 250px;" class="img-fluid mb-2">
            <p class="mb-0 text-muted" style="font-size: 0.75rem;">100% Safe &amp; Encrypted Payment Processing</p>
          </div>
        </div>
      </div>

    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
