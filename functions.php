<?php
/**
 * BUYOERA - Core Utility & Business Logic Helper Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// ==========================================
// Formatting & Currency Helpers
// ==========================================

/**
 * Format Indian Rupee Currency with commas (e.g. ₹1,499.00 or ₹699)
 */
function format_price(float $amount, bool $showDecimals = false): string {
    if ($showDecimals) {
        return CURRENCY_SYMBOL . number_format($amount, 2);
    }
    return CURRENCY_SYMBOL . number_format($amount, 0);
}

/**
 * Calculate discount percentage
 */
function calculate_discount_percent(float $regularPrice, float $salePrice): int {
    if ($regularPrice <= 0 || $salePrice >= $regularPrice) {
        return 0;
    }
    return (int) round((($regularPrice - $salePrice) / $regularPrice) * 100);
}

// ==========================================
// Security & Sanitization Helpers
// ==========================================

/**
 * Sanitize user input string
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Render CSRF Hidden Input
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

// ==========================================
// Flash Messaging
// ==========================================

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ==========================================
// Authentication Helpers
// ==========================================

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_login(string $redirect = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        header("Location: " . $redirect);
        exit;
    }
}

function require_admin(string $redirect = '../admin/login.php'): void {
    if (!is_admin()) {
        set_flash('danger', 'Unauthorized access. Administrator privileges required.');
        header("Location: " . $redirect);
        exit;
    }
}

// ==========================================
// Cart Management Helpers
// ==========================================

function init_cart(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!isset($_SESSION['coupon'])) {
        $_SESSION['coupon'] = null;
    }
}

function add_to_cart(int $productId, int $quantity = 1): array {
    init_cart();
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT p.id, p.name, p.slug, p.regular_price, p.sale_price, p.stock_quantity, p.supplier_cost,
               (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as image_url
        FROM products p WHERE p.id = :id LIMIT 1
    ");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        return ['success' => false, 'message' => 'Product not found.'];
    }

    if ($product['stock_quantity'] < 1) {
        return ['success' => false, 'message' => 'Sorry, this product is currently out of stock.'];
    }

    $currentQty = isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId]['quantity'] : 0;
    $newQty = $currentQty + $quantity;

    if ($newQty > $product['stock_quantity']) {
        $newQty = $product['stock_quantity'];
    }

    $_SESSION['cart'][$productId] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'slug' => $product['slug'],
        'price' => (float) $product['sale_price'],
        'regular_price' => (float) $product['regular_price'],
        'image' => $product['image_url'] ?: 'assets/images/placeholder.svg',
        'quantity' => $newQty,
        'stock' => (int) $product['stock_quantity'],
        'supplier_cost' => (float) $product['supplier_cost']
    ];

    return [
        'success' => true,
        'message' => 'Added to cart successfully!',
        'cart' => get_cart_summary()
    ];
}

function update_cart_quantity(int $productId, int $quantity): array {
    init_cart();
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
        return ['success' => true, 'message' => 'Item removed from cart.', 'cart' => get_cart_summary()];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $stock = $_SESSION['cart'][$productId]['stock'];
        if ($quantity > $stock) {
            $quantity = $stock;
        }
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
    }

    return ['success' => true, 'message' => 'Cart updated.', 'cart' => get_cart_summary()];
}

function remove_from_cart(int $productId): array {
    init_cart();
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
    return ['success' => true, 'message' => 'Item removed.', 'cart' => get_cart_summary()];
}

function clear_cart(): void {
    $_SESSION['cart'] = [];
    $_SESSION['coupon'] = null;
}

function get_cart_items(): array {
    init_cart();
    return $_SESSION['cart'];
}

function get_cart_count(): int {
    init_cart();
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int) $item['quantity'];
    }
    return $count;
}

function get_cart_subtotal(): float {
    init_cart();
    $subtotal = 0.0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
    }
    return (float) $subtotal;
}

function get_shipping_cost(float $subtotal): float {
    if ($subtotal <= 0) {
        return 0.0;
    }
    return ($subtotal >= FREE_SHIPPING_THRESHOLD) ? 0.0 : FLAT_SHIPPING_FEE;
}

function get_cart_discount(float $subtotal): float {
    init_cart();
    if (empty($_SESSION['coupon'])) {
        return 0.0;
    }

    $coupon = $_SESSION['coupon'];
    if ($subtotal < (float)$coupon['min_order_value']) {
        return 0.0;
    }

    if ($coupon['discount_type'] === 'percent') {
        $discount = ($subtotal * (float)$coupon['discount_value']) / 100;
        if (!empty($coupon['max_discount']) && $discount > (float)$coupon['max_discount']) {
            $discount = (float)$coupon['max_discount'];
        }
        return (float)$discount;
    } else {
        return min((float)$coupon['discount_value'], $subtotal);
    }
}

function get_cart_summary(): array {
    $items = get_cart_items();
    $count = get_cart_count();
    $subtotal = get_cart_subtotal();
    $discount = get_cart_discount($subtotal);
    $shipping = get_shipping_cost($subtotal - $discount);
    $total = max(0.0, ($subtotal - $discount + $shipping));
    $freeShippingRemaining = max(0.0, FREE_SHIPPING_THRESHOLD - $subtotal);

    return [
        'items' => array_values($items),
        'count' => $count,
        'subtotal' => $subtotal,
        'subtotal_formatted' => format_price($subtotal),
        'discount' => $discount,
        'discount_formatted' => format_price($discount),
        'coupon_code' => $_SESSION['coupon']['code'] ?? null,
        'shipping' => $shipping,
        'shipping_formatted' => ($shipping == 0.0 && $subtotal > 0) ? 'FREE' : format_price($shipping),
        'total' => $total,
        'total_formatted' => format_price($total),
        'free_shipping_threshold' => FREE_SHIPPING_THRESHOLD,
        'free_shipping_remaining' => $freeShippingRemaining,
        'free_shipping_percent' => min(100, round(($subtotal / FREE_SHIPPING_THRESHOLD) * 100))
    ];
}

// ==========================================
// Settings & Store Helpers
// ==========================================

function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // fallback
        }
    }
    return $settingsCache[$key] ?? $default;
}

// List of all Indian States and Union Territories for Checkout Dropdown
function get_indian_states(): array {
    return [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
        'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
        'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
        'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
        'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi (NCT)', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
    ];
}
