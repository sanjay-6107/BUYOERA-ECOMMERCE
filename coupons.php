<?php
/**
 * BUYOERA Mobile API - Coupon Validator
 */

require_once __DIR__ . '/common.php';

$db = Database::getConnection();
$input = get_json_input();
$code = strtoupper(trim($_GET['code'] ?? $input['code'] ?? ''));
$subtotal = (float)($_GET['subtotal'] ?? $input['subtotal'] ?? 0);

if (empty($code)) {
    send_json(['success' => false, 'message' => 'Please enter coupon code.'], 400);
}

$stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code AND is_active = 1 LIMIT 1");
$stmt->execute(['code' => $code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    send_json(['success' => false, 'message' => 'Invalid or expired coupon code.'], 404);
}

if ($subtotal < (float)$coupon['min_order_value']) {
    send_json([
        'success' => false,
        'message' => 'Minimum order value of ₹' . number_format($coupon['min_order_value']) . ' required for this coupon.'
    ], 400);
}

$discount = 0.0;
if ($coupon['discount_type'] === 'percent') {
    $discount = ($subtotal * (float)$coupon['discount_value']) / 100.0;
    if (!empty($coupon['max_discount']) && $discount > (float)$coupon['max_discount']) {
        $discount = (float)$coupon['max_discount'];
    }
} else {
    $discount = (float)$coupon['discount_value'];
}

$discount = min($subtotal, $discount);

send_json([
    'success' => true,
    'message' => 'Coupon ' . $coupon['code'] . ' applied successfully!',
    'data' => [
        'code' => $coupon['code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => (float)$coupon['discount_value'],
        'discount_amount' => round($discount, 2),
        'new_total' => max(0, $subtotal - $discount)
    ]
]);
