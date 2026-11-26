<?php
/**
 * BUYOERA Mobile API - Products Endpoint
 */

require_once __DIR__ . '/common.php';

$db = Database::getConnection();

// Detail view by id or slug
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

if ($id || $slug) {
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE " . ($id ? "p.id = :id" : "p.slug = :slug") . "
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($id ? ['id' => $id] : ['slug' => $slug]);
    $product = $stmt->fetch();

    if (!$product) {
        send_json(['success' => false, 'message' => 'Product not found'], 404);
    }

    // Fetch images
    $imgStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = :pid ORDER BY is_primary DESC, sort_order ASC");
    $imgStmt->execute(['pid' => $product['id']]);
    $product['images'] = $imgStmt->fetchAll();

    // Fetch reviews
    $revStmt = $db->prepare("SELECT * FROM reviews WHERE product_id = :pid AND is_approved = 1 ORDER BY id DESC LIMIT 20");
    $revStmt->execute(['pid' => $product['id']]);
    $product['reviews'] = $revStmt->fetchAll();

    // Parse specs JSON
    $product['specs'] = !empty($product['specs_json']) ? json_decode($product['specs_json'], true) : [];

    send_json(['success' => true, 'data' => $product]);
}

// List view
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : null;
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

$where = ["1=1"];
$params = [];

if ($categorySlug) {
    $where[] = "c.slug = :category";
    $params['category'] = $categorySlug;
}

if ($tag === 'trending') {
    $where[] = "p.is_trending = 1";
} elseif ($tag === 'best_seller' || $tag === 'best-seller') {
    $where[] = "p.is_best_seller = 1";
} elseif ($tag === 'new_arrival' || $tag === 'new-arrivals') {
    $where[] = "p.is_new_arrival = 1";
}

if ($search) {
    $where[] = "(p.name LIKE :search OR p.short_description LIKE :search)";
    $params['search'] = "%$search%";
}

$whereClause = implode(" AND ", $where);
$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE $whereClause
    ORDER BY p.id ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Add calculated fields
foreach ($products as &$p) {
    $p['discount_percent'] = calculate_discount_percent((float)$p['regular_price'], (float)$p['sale_price']);
    $p['savings_amount'] = (float)$p['regular_price'] - (float)$p['sale_price'];
}

send_json([
    'success' => true,
    'count' => count($products),
    'data' => $products
]);
