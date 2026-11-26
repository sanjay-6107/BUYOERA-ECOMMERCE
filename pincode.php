<?php
/**
 * BUYOERA Mobile API - Pincode Delivery Estimator Endpoint
 */

require_once __DIR__ . '/common.php';

$pincode = trim($_GET['pincode'] ?? $_POST['pincode'] ?? '');

if (empty($pincode) || !preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
    send_json([
        'success' => false,
        'message' => 'Please enter a valid 6-digit Indian PIN code.'
    ], 400);
}

// Indian postal zones
$zone = (int)substr($pincode, 0, 1);
$days = 3;
$courier = 'Delhivery & BlueDart Express';

switch ($zone) {
    case 1: // Delhi, Haryana, Punjab, HP, J&K
    case 2: // UP, Uttarakhand
        $days = 2;
        $courier = 'BlueDart Air Express (Same/Next Day Dispatch)';
        break;
    case 3: // Rajasthan, Gujarat
    case 4: // Maharashtra, MP, Goa
        $days = 3;
        $courier = 'Delhivery Ground & BlueDart';
        break;
    case 5: // AP, Telangana, Karnataka
    case 6: // Tamil Nadu, Kerala
        $days = 3;
        $courier = 'Delhivery Express & XpressBees';
        break;
    case 7: // WB, Odisha, NE states
    case 8: // Bihar, Jharkhand
        $days = 4;
        $courier = 'DTDC Express & Delhivery';
        break;
    default:
        $days = 4;
}

$estDate = date('D, d M', strtotime("+$days business days"));

send_json([
    'success' => true,
    'data' => [
        'pincode' => $pincode,
        'serviceable' => true,
        'cod_available' => true,
        'estimated_days' => $days,
        'delivery_date' => $estDate,
        'courier_partner' => $courier,
        'shipping_fee' => 0.0 // Free on products > 499
    ]
]);
