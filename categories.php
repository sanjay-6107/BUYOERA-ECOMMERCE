<?php
/**
 * BUYOERA Mobile API - Categories Endpoint
 */

require_once __DIR__ . '/common.php';

$db = Database::getConnection();
$stmt = $db->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$categories = $stmt->fetchAll();

send_json([
    'success' => true,
    'count' => count($categories),
    'data' => $categories
]);
