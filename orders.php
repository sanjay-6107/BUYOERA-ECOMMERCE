<?php
/**
 * BUYOERA Mobile API - Orders & Tracking Endpoint
 */

require_once __DIR__ . '/common.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// TRACKING / LISTING (GET)
if ($method === 'GET') {
    $orderNumber = isset($_GET['order']) ? trim($_GET['order']) : null;
    $phoneOrEmail = isset($_GET['phone']) ? trim($_GET['phone']) : (isset($_GET['email']) ? trim($_GET['email']) : null);
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    // Track single order
    if ($orderNumber && $phoneOrEmail) {
        $cleanPhone = str_replace([' ', '-', '+91'], '', $phoneOrEmail);
        $stmt = $db->prepare("
            SELECT * FROM orders 
            WHERE order_number = :onum 
              AND (customer_email = :ident OR customer_phone = :ident OR customer_phone = :phone)
            LIMIT 1
        ");
        $stmt->execute([
            'onum' => $orderNumber,
            'ident' => $phoneOrEmail,
            'phone' => $cleanPhone
        ]);
        $order = $stmt->fetch();

        if (!$order) {
            send_json(['success' => false, 'message' => 'No order found matching the Order ID and contact details.'], 404);
        }

        // Fetch items
        $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :oid");
        $itemStmt->execute(['oid' => $order['id']]);
        $order['items'] = $itemStmt->fetchAll();

        // Fetch history
        $histStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = :oid ORDER BY id ASC");
        $histStmt->execute(['oid' => $order['id']]);
        $order['history'] = $histStmt->fetchAll();

        // 6 pipeline stages
        $pipelineSteps = [
            'Order Placed' => ['label' => 'Order Placed', 'icon' => 'inventory'],
            'Order Confirmed' => ['label' => 'Confirmed', 'icon' => 'verified'],
            'Processing' => ['label' => 'Processing', 'icon' => 'build'],
            'Shipped' => ['label' => 'Shipped', 'icon' => 'local_shipping'],
            'Out for Delivery' => ['label' => 'Out for Delivery', 'icon' => 'delivery_dining'],
            'Delivered' => ['label' => 'Delivered', 'icon' => 'check_circle']
        ];

        $sequence = array_keys($pipelineSteps);
        $currIdx = array_search($order['order_status'], $sequence);
        $stages = [];

        foreach ($pipelineSteps as $key => $info) {
            $stepIdx = array_search($key, $sequence);
            $status = 'upcoming';
            if ($order['order_status'] === 'Cancelled') {
                $status = 'cancelled';
            } elseif ($currIdx !== false) {
                if ($stepIdx < $currIdx) $status = 'completed';
                elseif ($stepIdx === $currIdx) $status = 'current';
            }
            $stages[] = [
                'stage' => $key,
                'label' => $info['label'],
                'icon' => $info['icon'],
                'state' => $status
            ];
        }

        $order['pipeline'] = $stages;

        send_json(['success' => true, 'data' => $order]);
    }

    // List user orders
    if ($userId) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute(['uid' => $userId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$ord) {
            $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :oid");
            $itemStmt->execute(['oid' => $ord['id']]);
            $ord['items'] = $itemStmt->fetchAll();
        }

        send_json(['success' => true, 'data' => $orders]);
    }

    send_json(['success' => false, 'message' => 'Missing order parameters'], 400);
}

// CREATE ORDER (POST)
if ($method === 'POST') {
    $input = get_json_input();

    $name = trim($input['customer_name'] ?? '');
    $email = trim($input['customer_email'] ?? '');
    $phone = trim($input['customer_phone'] ?? '');
    $address = trim($input['shipping_address'] ?? '');
    $city = trim($input['shipping_city'] ?? '');
    $state = trim($input['shipping_state'] ?? '');
    $pincode = trim($input['shipping_pincode'] ?? '');
    $paymentMethod = trim($input['payment_method'] ?? 'cod');
    $items = $input['items'] ?? [];
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : null;
    $notes = trim($input['notes'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        send_json(['success' => false, 'message' => 'Please provide all delivery address and contact fields.'], 400);
    }

    if (empty($items) || !is_array($items)) {
        send_json(['success' => false, 'message' => 'Cart is empty. Please add items before placing order.'], 400);
    }

    // Calculate totals
    $subtotal = 0.0;
    foreach ($items as $it) {
        $qty = max(1, (int)($it['quantity'] ?? 1));
        $price = (float)($it['price'] ?? 0);
        $subtotal += ($qty * $price);
    }

    $discount = (float)($input['discount_amount'] ?? 0);
    $couponCode = trim($input['coupon_code'] ?? '');
    $shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0.0 : FLAT_SHIPPING_FEE;
    $total = max(0, $subtotal - $discount + $shipping);

    $orderNumber = 'BYE-' . date('Y') . '-' . rand(1000, 9999);
    $paymentStatus = in_array($paymentMethod, ['upi', 'card', 'netbanking', 'razorpay']) ? 'paid' : 'pending';
    $orderStatus = 'Order Placed';

    $ins = $db->prepare("
        INSERT INTO orders (
            order_number, user_id, customer_name, customer_email, customer_phone,
            shipping_address, shipping_city, shipping_state, shipping_pincode,
            subtotal, shipping_cost, discount_amount, coupon_code, total_amount,
            payment_method, payment_status, order_status, supplier_status, notes
        ) VALUES (
            :onum, :uid, :cname, :cemail, :cphone,
            :saddr, :scity, :sstate, :spin,
            :sub, :ship, :disc, :coupon, :tot,
            :pmethod, :pstatus, :ostatus, 'Pending Forwarding', :notes
        )
    ");

    $ins->execute([
        'onum' => $orderNumber,
        'uid' => $userId,
        'cname' => $name,
        'cemail' => $email,
        'cphone' => $phone,
        'saddr' => $address,
        'scity' => $city,
        'sstate' => $state,
        'spin' => $pincode,
        'sub' => $subtotal,
        'ship' => $shipping,
        'disc' => $discount,
        'coupon' => $couponCode ?: null,
        'tot' => $total,
        'pmethod' => $paymentMethod,
        'pstatus' => $paymentStatus,
        'ostatus' => $orderStatus,
        'notes' => $notes ?: null
    ]);

    $orderId = (int)$db->lastInsertId();

    // Insert items
    $itemIns = $db->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, sku, price, quantity, total_price)
        VALUES (:oid, :pid, :pname, :sku, :price, :qty, :tot)
    ");

    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? $it['id'] ?? 0);
        $qty = max(1, (int)($it['quantity'] ?? 1));
        $price = (float)($it['price'] ?? 0);
        $pname = $it['name'] ?? 'Product #' . $pid;
        $sku = $it['sku'] ?? 'BYE-' . $pid;

        $itemIns->execute([
            'oid' => $orderId,
            'pid' => $pid,
            'pname' => $pname,
            'sku' => $sku,
            'price' => $price,
            'qty' => $qty,
            'tot' => $price * $qty
        ]);
    }

    // Insert initial status history
    $histIns = $db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (:oid, 'Order Placed', 'Order placed via BUYOERA Mobile App')");
    $histIns->execute(['oid' => $orderId]);

    send_json([
        'success' => true,
        'message' => 'Order created successfully',
        'data' => [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $total,
            'payment_method' => $paymentMethod,
            'customer_name' => $name,
            'phone' => $phone
        ]
    ], 201);
}
