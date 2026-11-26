-- =======================================================
-- BUYOERA - Premium Multi-Category E-Commerce Database SQL
-- Target Market: India | Currency: INR (₹)
-- Brand: BUYOERA (Shop Smart. Live Better.)
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+
-- =======================================================

CREATE DATABASE IF NOT EXISTS `buyoera_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `buyoera_db`;

-- -------------------------------------------------------
-- 1. Table structure for table `users`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `reset_token` VARCHAR(100) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Table structure for table `addresses`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `recipient_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `street_address` TEXT NOT NULL,
  `landmark` VARCHAR(150) DEFAULT NULL,
  `city` VARCHAR(80) NOT NULL,
  `state` VARCHAR(80) NOT NULL,
  `pincode` VARCHAR(10) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_addresses_user` (`user_id`),
  CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Table structure for table `categories`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. Table structure for table `products`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `short_description` TEXT DEFAULT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `regular_price` DECIMAL(10,2) NOT NULL,
  `sale_price` DECIMAL(10,2) NOT NULL,
  `supplier_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `supplier_name` VARCHAR(150) DEFAULT 'Direct Source India',
  `supplier_sku` VARCHAR(100) DEFAULT NULL,
  `supplier_url` VARCHAR(255) DEFAULT NULL,
  `sku` VARCHAR(60) NOT NULL UNIQUE,
  `stock_quantity` INT NOT NULL DEFAULT 50,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 1,
  `is_trending` TINYINT(1) NOT NULL DEFAULT 1,
  `is_best_seller` TINYINT(1) NOT NULL DEFAULT 1,
  `is_new_arrival` TINYINT(1) NOT NULL DEFAULT 1,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 4.8,
  `total_reviews` INT NOT NULL DEFAULT 0,
  `specs_json` TEXT DEFAULT NULL,
  `shipping_info` TEXT DEFAULT NULL,
  `return_info` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_prod_slug` (`slug`),
  INDEX `idx_prod_cat` (`category_id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. Table structure for table `product_images`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_img_prod` (`product_id`),
  CONSTRAINT `fk_img_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 6. Table structure for table `coupons`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_order_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(10,2) DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `usage_limit` INT DEFAULT NULL,
  `times_used` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_coupons_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 7. Table structure for table `orders`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `shipping_city` VARCHAR(80) NOT NULL,
  `shipping_state` VARCHAR(80) NOT NULL,
  `shipping_pincode` VARCHAR(10) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` VARCHAR(50) DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cod', 'upi', 'card', 'netbanking', 'razorpay') NOT NULL DEFAULT 'cod',
  `payment_status` ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
  `order_status` ENUM('Order Placed', 'Order Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Order Placed',
  `courier_name` VARCHAR(80) DEFAULT NULL,
  `tracking_number` VARCHAR(100) DEFAULT NULL,
  `tracking_url` VARCHAR(255) DEFAULT NULL,
  `supplier_status` ENUM('Pending Forwarding', 'Forwarded to Supplier', 'Shipped by Supplier', 'Fulfilled') NOT NULL DEFAULT 'Pending Forwarding',
  `supplier_order_id` VARCHAR(100) DEFAULT NULL,
  `dropship_notes` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_orders_number` (`order_number`),
  INDEX `idx_orders_user` (`user_id`),
  INDEX `idx_orders_status` (`order_status`),
  INDEX `idx_orders_phone` (`customer_phone`),
  INDEX `idx_orders_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 8. Table structure for table `order_items`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `total` DECIMAL(10,2) NOT NULL,
  `supplier_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  INDEX `idx_items_order` (`order_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 9. Table structure for table `order_status_history`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `updated_by` VARCHAR(80) DEFAULT 'System',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_history_order` (`order_id`),
  CONSTRAINT `fk_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 10. Table structure for table `reviews`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `reviewer_name` VARCHAR(100) NOT NULL,
  `reviewer_location` VARCHAR(100) DEFAULT 'India',
  `rating` INT NOT NULL DEFAULT 5,
  `title` VARCHAR(200) NOT NULL,
  `comment` TEXT NOT NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 1,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_reviews_prod` (`product_id`),
  CONSTRAINT `fk_reviews_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 11. Table structure for table `settings`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(80) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 12. Table structure for table `contact_messages`
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied') NOT NULL DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- INITIAL SEED DATA
-- Default Admin: admin@buyoera.com / Admin@1234
-- Default Demo Customer: rahul.sharma@example.com / Customer@1234
-- =======================================================

-- 1. Users
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`) VALUES
(1, 'BUYOERA Administrator', 'admin@buyoera.com', '+919876543210', '$2y$10$wE96X04QY5B4M05G4Yh5CeN8g/XyTf1.gM6kHh9yN32R.O2xS3g5m', 'admin'),
(2, 'Rahul Sharma', 'rahul.sharma@example.com', '9820112233', '$2y$10$wE96X04QY5B4M05G4Yh5CeN8g/XyTf1.gM6kHh9yN32R.O2xS3g5m', 'customer')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 2. Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image_url`, `is_featured`) VALUES
(1, 'Shoe Care', 'shoe-care', 'Premium cleaning solutions, hydrophobic protectors, and professional brushes to keep your sneakers box-fresh.', 'assets/images/categories/shoe-care.svg', 1),
(2, 'Electronics Accessories', 'electronics-accessories', 'Clean cable management, ergonomic clips, and modern workspace essentials for minimal clutter.', 'assets/images/categories/electronics-accessories.svg', 1),
(3, 'Home & Kitchen', 'home-kitchen', 'Smart rotating organizers, airtight pantry essentials, and space-saving kitchen storage solutions.', 'assets/images/categories/home-kitchen.svg', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 3. Products
INSERT INTO `products` (
  `id`, `category_id`, `name`, `slug`, `short_description`, `description`,
  `regular_price`, `sale_price`, `supplier_cost`, `supplier_name`, `supplier_sku`, `supplier_url`,
  `sku`, `stock_quantity`, `is_featured`, `is_trending`, `is_best_seller`, `is_new_arrival`,
  `rating`, `total_reviews`, `specs_json`, `shipping_info`, `return_info`
) VALUES
(
  1,
  1,
  'BUYOERA Pro Sneaker Cleaning & Care Kit (All-in-One Solution)',
  'buyoera-pro-sneaker-cleaning-care-kit',
  'Complete professional shoe cleaning kit with deep foaming cleanser, natural hog-hair brush, microfiber cloth, and deodorizer spray. Safe on leather, knit, mesh, suede, and canvas.',
  '<p>Elevate your footwear care with the <strong>BUYOERA Pro Sneaker Cleaning & Care Kit</strong>. Engineered specifically for sneaker enthusiasts who demand pristine footwear without damage. Our pH-balanced foaming formula penetrates stubborn grime, mud, oil stains, and salt marks while safeguarding sensitive materials including suede, nubuck, Flyknit, and premium leather.</p><h4>What is Inside the Kit:</h4><ul><li><strong>150ml Deep Clean Foam Solution:</strong> 100% biodegradable and non-toxic formula that cleans up to 50 pairs of shoes.</li><li><strong>Premium Hardwood Hog Bristle Brush:</strong> Soft enough for delicate mesh yet dense enough to scrub midsoles.</li><li><strong>Ultra-Absorbent Microfiber Towel:</strong> Scratch-free drying that locks moisture and lifted dirt.</li><li><strong>Long-Lasting Fresh Sneaker Deodorizer Spray (50ml):</strong> Neutralizes odor-causing bacteria with a crisp fresh scent.</li></ul>',
  1499.00,
  699.00,
  280.00,
  'KicksCare Direct India',
  'SUP-SCK-01',
  'https://supplier.example.com/item/shoe-kit-01',
  'BYE-SHOE-001',
  85,
  1, 1, 1, 1,
  4.9,
  48,
  '{"Capacity":"150ml Foam + 50ml Spray","Brush Material":"Natural Boar Bristle + Solid Beechwood","Suitability":"Canvas, Leather, Suede, Nubuck, Mesh, Rubber","Origin":"Proudly Formulated in India","Package Dimensions":"22 x 12 x 6 cm","Weight":"420g"}',
  'Dispatched within 24 hours. Free express shipping across India on orders above ₹499. Delivered in 3-5 business days with live SMS/WhatsApp tracking.',
  '7-Day Hassle-Free Replacement / Refund guarantee if received damaged or defective. Instant pickup arranged.'
),
(
  2,
  2,
  'BUYOERA Self-Adhesive Multi-Channel Wire & Cable Organizer Clips (Pack of 20)',
  'buyoera-self-adhesive-wire-cable-clips-pack-20',
  'Heavy-duty silicone cable clips with authentic 3M high-bond adhesive backing. Tames phone chargers, USB-C cords, HDMI, mouse, and power cables without surface damage.',
  '<p>Say goodbye to tangled wires, tripping hazards, and messy desks. The <strong>BUYOERA Self-Adhesive Multi-Channel Wire & Cable Organizer Clips</strong> transform cluttered workstations, bedside tables, entertainment consoles, and car dashboards into sleek, organized spaces.</p><h4>Key Features:</h4><ul><li><strong>Ultra-Strong 3M VHB Adhesive:</strong> Adheres firmly to wood, glass, metal, tile, and marble without peeling or leaving sticky residue upon removal.</li><li><strong>Eco-Friendly Flexible Silicone:</strong> Soft rubber slots securely grip cables from 2.5mm up to 7mm diameter without pinching or crimping sensitive copper wires.</li><li><strong>Multi-Channel Configurations:</strong> Pack of 20 includes single-slot, double-slot, 3-slot, and 5-slot organizers to handle every cable configuration.</li><li><strong>Universal Application:</strong> Perfect for USB cables, iPhone Lightning, Type-C cables, mouse cords, headphone wires, and audio jacks.</li></ul>',
  799.00,
  349.00,
  110.00,
  'TechGrip Logistics Delhi',
  'SUP-WCL-20',
  'https://supplier.example.com/item/wire-clips-20',
  'BYE-ELEC-002',
  140,
  1, 1, 1, 1,
  4.8,
  62,
  '{"Quantity":"Pack of 20 Clips (Assorted 1/2/3/5 slots)","Material":"High-Grade Flexible Silicone","Adhesive Type":"Authentic 3M High-Bonding Acrylic Tape","Color":"Matte Midnight Black & Arctic White","Compatibility":"Cables up to 7mm diameter","Origin":"Manufactured for BUYOERA"}',
  'Dispatched within 24 hours. Pan-India delivery via Bluedart and Delhivery. Typical transit time 2 to 4 days.',
  '7-Day return policy. Complete refund or replacement if item does not meet expectations.'
),
(
  3,
  3,
  'BUYOERA 360° Rotating Multi-Tier Kitchen Storage Organizer & Spice Turntable',
  'buyoera-360-rotating-kitchen-organizer-turntable',
  'Effortless 360-degree rotating Lazy Susan organizer with non-skid TPR base and raised safety rim. Perfect for spices, condiment bottles, pantry snacks, and cosmetics.',
  '<p>Maximize every square inch of your kitchen cabinets, refrigerator, pantry, or bathroom vanity. The <strong>BUYOERA 360° Rotating Multi-Tier Kitchen Storage Organizer</strong> ensures everything is accessible with a gentle spin—no more knocking over bottles or searching in deep dark shelves.</p><h4>Why You Will Love It:</h4><ul><li><strong>Smooth Stainless Steel Ball Bearings:</strong> Glides effortlessly 360 degrees even when fully loaded with heavy glass spice jars and oil cruets.</li><li><strong>Anti-Slip TPR Rubber Base:</strong> Textured silicone lining ensures jars, bottles, and cans remain upright without slipping during rotation.</li><li><strong>Raised Perimeter Wall:</strong> 4.5cm elevated protective rim stops items from tipping over the edge while turning.</li><li><strong>BPA-Free Shatterproof Material:</strong> Crafted from food-safe, durable ABS polymer that washes easily with mild soapy water.</li></ul>',
  1999.00,
  899.00,
  360.00,
  'KitchenCraft India Hub',
  'SUP-KSO-01',
  'https://supplier.example.com/item/kitchen-turntable-01',
  'BYE-HOME-003',
  60,
  1, 1, 1, 1,
  4.9,
  54,
  '{"Diameter":"25 cm (10 inches)","Height":"5 cm (2 inches)","Rotation":"360° Dual Stainless Steel Bearings","Material":"BPA-Free Food-Safe ABS + Non-Slip TPR Base","Load Capacity":"Up to 8 kg","Cleaning":"Hand wash with mild soap"}',
  'Free Express Shipping on this item! Packed in reinforced shockproof double-corrugated box. Delivery in 3-5 days across India.',
  '7-Day replacement guarantee against shipping transit damages or defects.'
)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 4. Product Images
INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `is_primary`, `sort_order`) VALUES
(1, 1, 'assets/images/products/sneaker-kit-1.svg', 1, 1),
(2, 1, 'assets/images/products/sneaker-kit-2.svg', 0, 2),
(3, 1, 'assets/images/products/sneaker-kit-3.svg', 0, 3),
(4, 2, 'assets/images/products/wire-clips-1.svg', 1, 1),
(5, 2, 'assets/images/products/wire-clips-2.svg', 0, 2),
(6, 2, 'assets/images/products/wire-clips-3.svg', 0, 3),
(7, 3, 'assets/images/products/kitchen-organizer-1.svg', 1, 1),
(8, 3, 'assets/images/products/kitchen-organizer-2.svg', 0, 2),
(9, 3, 'assets/images/products/kitchen-organizer-3.svg', 0, 3)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 5. Coupons
INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `expiry_date`, `usage_limit`, `times_used`, `is_active`) VALUES
(1, 'WELCOME10', 'percent', 10.00, 499.00, 200.00, '2027-12-31', 1000, 14, 1),
(2, 'BUYOERA50', 'fixed', 50.00, 399.00, NULL, '2027-12-31', 500, 28, 1),
(3, 'PREMIUM20', 'percent', 20.00, 999.00, 500.00, '2027-12-31', 250, 9, 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 6. Sample Customer Reviews
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `reviewer_name`, `reviewer_location`, `rating`, `title`, `comment`, `is_verified`, `is_approved`) VALUES
(1, 1, 2, 'Rahul Sharma', 'Mumbai, Maharashtra', 5, 'Cleaned my white Air Jordans like brand new!', 'I was hesitant at first because suede is tricky, but this foam cleaner worked like magic without leaving water stains. The brush bristles are premium quality and do not shed. Fast delivery in Mumbai within 2 days!', 1, 1),
(2, 1, NULL, 'Pooja Venkatesh', 'Bengaluru, Karnataka', 5, 'Must have for sneaker lovers', 'The deodorizer spray smells fresh and clean, not chemically. The microfiber cloth absorbed all the dirt easily. BUYOERA packing was very premium.', 1, 1),
(3, 2, NULL, 'Aditya Mehta', 'Pune, Maharashtra', 5, 'Superb 3M adhesive strength', 'Stuck them under my work-from-home desk 3 weeks ago. None of them have fallen off, even with heavy laptop power cords. Best desk upgrade for ₹349.', 1, 1),
(4, 2, NULL, 'Sneha Kapoor', 'Gurugram, Haryana', 4, 'Very handy and neat clips', 'My bedside table was cluttered with phone and watch chargers. Now everything stays in place. Will buy another pack for the living room TV wires.', 1, 1),
(5, 3, NULL, 'Meenakshi Iyer', 'Chennai, Tamil Nadu', 5, 'Rotates effortlessly even when full', 'Loaded it with heavy glass pickle jars and masala containers. The ball bearings are so smooth and the rubber grip keeps jars from sliding. Excellent product!', 1, 1),
(6, 3, NULL, 'Vikram Rajput', 'Jaipur, Rajasthan', 5, 'High quality kitchen organizer', 'Really impressed with the packaging and build quality. Sturdy ABS plastic and sleek navy blue border accents. My wife loved it.', 1, 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 7. Sample Orders for Demonstration
INSERT INTO `orders` (
  `id`, `order_number`, `user_id`, `customer_name`, `customer_email`, `customer_phone`,
  `shipping_address`, `shipping_city`, `shipping_state`, `shipping_pincode`,
  `subtotal`, `shipping_cost`, `discount_amount`, `coupon_code`, `total_amount`,
  `payment_method`, `payment_status`, `order_status`,
  `courier_name`, `tracking_number`, `tracking_url`,
  `supplier_status`, `supplier_order_id`, `dropship_notes`, `notes`
) VALUES
(
  1,
  'BYE-2026-8910',
  2,
  'Rahul Sharma',
  'rahul.sharma@example.com',
  '9820112233',
  'Flat 402, Sunshine Heights, Linking Road, Bandra West',
  'Mumbai',
  'Maharashtra',
  '400050',
  699.00,
  0.00,
  50.00,
  'BUYOERA50',
  649.00,
  'upi',
  'paid',
  'Shipped',
  'BlueDart Express',
  'BD89201928IN',
  'https://www.bluedart.com/tracking?track=BD89201928IN',
  'Shipped by Supplier',
  'SUP-ORD-7721',
  'Forwarded to KicksCare Direct India on 2026-11-04. Dispatched via BlueDart air express.',
  'Customer requested contactless delivery at security gate.'
),
(
  2,
  'BYE-2026-8911',
  NULL,
  'Ananya Deshmukh',
  'ananya.d@example.com',
  '9871122334',
  'House 12B, Green Glen Layout, Bellandur',
  'Bengaluru',
  'Karnataka',
  '560103',
  1248.00,
  0.00,
  124.80,
  'WELCOME10',
  1123.20,
  'cod',
  'pending',
  'Processing',
  'Delhivery Logistics',
  'DEL991028372',
  'https://www.delhivery.com/track/package/DEL991028372',
  'Forwarded to Supplier',
  'SUP-ORD-7729',
  'Forwarded to KitchenCraft Hub India. Awaiting packaging dispatch confirmation.',
  'Cash on Delivery verified by customer SMS confirmation.'
)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 8. Order Items for Sample Orders
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total`, `supplier_cost`) VALUES
(1, 1, 1, 'BUYOERA Pro Sneaker Cleaning & Care Kit (All-in-One Solution)', 699.00, 1, 699.00, 280.00),
(2, 2, 2, 'BUYOERA Self-Adhesive Multi-Channel Wire & Cable Organizer Clips (Pack of 20)', 349.00, 1, 349.00, 110.00),
(3, 2, 3, 'BUYOERA 360° Rotating Multi-Tier Kitchen Storage Organizer & Spice Turntable', 899.00, 1, 899.00, 360.00)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 9. Order Status History
INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `updated_by`) VALUES
(1, 1, 'Order Placed', 'Order placed successfully by customer via UPI payment.', 'Customer'),
(2, 1, 'Order Confirmed', 'Payment verified and stock reserved.', 'System'),
(3, 1, 'Processing', 'Order forwarded to supplier for fulfillment.', 'Admin'),
(4, 1, 'Shipped', 'Handed over to BlueDart Express with tracking BD89201928IN.', 'Admin'),
(5, 2, 'Order Placed', 'Order placed with Cash on Delivery (COD).', 'Customer'),
(6, 2, 'Order Confirmed', 'Order confirmed by automated customer verification.', 'System'),
(7, 2, 'Processing', 'Order forwarded to supplier for packing.', 'Admin')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 10. Store Settings
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'site_name', 'BUYOERA'),
(2, 'site_tagline', 'Shop Smart. Live Better.'),
(3, 'currency_symbol', '₹'),
(4, 'free_shipping_threshold', '499'),
(5, 'shipping_fee', '49'),
(6, 'contact_email', 'support@buyoera.com'),
(7, 'contact_phone', '+91 98765 43210'),
(8, 'announcement_text', '⚡ Mega Indian Festival Sale: Free Express Shipping over ₹499 | Cash on Delivery (COD) Available! Use code WELCOME10 for 10% OFF'),
(9, 'razorpay_enabled', '1'),
(10, 'razorpay_key_id', 'rzp_test_buyoeraSampleKey123'),
(11, 'supplier_auto_forward', '0')
ON DUPLICATE KEY UPDATE `id`=`id`;
