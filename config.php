<?php
/**
 * BUYOERA - Configuration File
 * Brand: BUYOERA
 * Tagline: Shop Smart. Live Better.
 * Target Market: India
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        $sessPath = session_save_path();
        if (empty($sessPath) || !is_writable($sessPath)) {
            session_save_path(sys_get_temp_dir());
        }
    }
    @session_start();
}

// Set Indian Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

// Environment & Debugging (Set to false in production)
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Brand Information
define('SITE_NAME', 'BUYOERA');
define('SITE_TAGLINE', 'Shop Smart. Live Better.');
define('SITE_DESCRIPTION', 'Premium multi-category online store in India. Curated essentials for your daily lifestyle, tech setups, and home living.');

// Dynamic Site URL detection (Works seamlessly on WAMP localhost/buyoera, Virtual Host, or php -S)
if (!defined('SITE_URL')) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
    $basePath = preg_replace('#/(admin|api/v1|includes|assets).*$#', '', $scriptDir);
    define('SITE_URL', rtrim($protocol . $host . $basePath, '/'));
}

// Business & Contact Details
define('CONTACT_EMAIL', 'support@buyoera.com');
define('CONTACT_PHONE', '+91 98765 43210');
define('CONTACT_WHATSAPP', '+91 98765 43210');
define('CONTACT_ADDRESS', 'BUYOERA E-Commerce Pvt Ltd, Level 4, Tech Park, Andheri East, Mumbai, Maharashtra 400069, India');
define('WORKING_HOURS', 'Mon - Sat: 9:00 AM - 7:00 PM IST');

// Currency & Pricing (Indian Rupee)
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');
define('FREE_SHIPPING_THRESHOLD', 499); // Free shipping on orders >= ₹499
define('FLAT_SHIPPING_FEE', 49);        // Standard shipping fee below threshold

// Database Configuration (MySQL / MariaDB for XAMPP / Production)
define('DB_DRIVER', 'mysql'); // 'mysql' or 'sqlite'
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'buyoera_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Razorpay Test Configuration (Keep keys in test mode until live credentials)
define('RAZORPAY_ENABLED', true);
define('RAZORPAY_KEY_ID', 'rzp_test_buyoeraSampleKey123'); // Replace with live Key ID
define('RAZORPAY_KEY_SECRET', 'sample_secret_key_placeholder'); // Server-side only! Never expose in frontend
define('RAZORPAY_TEST_MODE', true);

// Security & Salt
define('APP_SECRET', 'buyoera_secure_salt_key_2026_x87b9');
