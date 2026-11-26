<?php
/**
 * BUYOERA - Cart API Endpoint (JSON)
 */

header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(['success' => true, 'cart' => get_cart_summary()]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? '';
    $productId = isset($input['product_id']) ? (int) $input['product_id'] : 0;
    $quantity = isset($input['quantity']) ? (int) $input['quantity'] : 1;

    switch ($action) {
        case 'add':
            $res = add_to_cart($productId, $quantity);
            echo json_encode($res);
            break;

        case 'update':
            $res = update_cart_quantity($productId, $quantity);
            echo json_encode($res);
            break;

        case 'remove':
            $res = remove_from_cart($productId);
            echo json_encode($res);
            break;

        case 'clear':
            clear_cart();
            echo json_encode(['success' => true, 'message' => 'Cart cleared.', 'cart' => get_cart_summary()]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
