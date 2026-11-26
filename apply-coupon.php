<?php
/**
 * BUYOERA - Apply / Remove Coupon API Endpoint
 */

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? 'apply';
$code = strtoupper(trim($input['code'] ?? ''));

if ($action === 'remove') {
    $_SESSION['coupon'] = null;
    echo json_encode([
        'success' => true,
        'message' => 'Coupon removed.',
        'cart' => get_cart_summary()
    ]);
    exit;
}

if (empty($code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a coupon code.'
    ]);
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code AND is_active = 1 LIMIT 1");
$stmt->execute(['code' => $code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or expired coupon code.'
    ]);
    exit;
}

// Check expiry if set
if (!empty($coupon['expiry_date']) && strtotime($coupon['expiry_date']) < strtotime(date('Y-m-d'))) {
    echo json_encode([
        'success' => false,
        'message' => 'This coupon has expired.'
    ]);
    exit;
}

// Check usage limit
if (!empty($coupon['usage_limit']) && (int)$coupon['times_used'] >= (int)$coupon['usage_limit']) {
    echo json_encode([
        'success' => false,
        'message' => 'This coupon usage limit has been reached.'
    ]);
    exit;
}

$subtotal = get_cart_subtotal();
$minOrder = (float) $coupon['min_order_value'];

if ($subtotal < $minOrder) {
    echo json_encode([
        'success' => false,
        'message' => 'Minimum order amount for this coupon is ' . format_price($minOrder) . '.'
    ]);
    exit;
}

$_SESSION['coupon'] = $coupon;

echo json_encode([
    'success' => true,
    'message' => 'Coupon ' . htmlspecialchars($coupon['code']) . ' applied successfully!',
    'cart' => get_cart_summary()
]);
