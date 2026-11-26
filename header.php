<?php
/**
 * BUYOERA - Global Header Template
 */

require_once __DIR__ . '/functions.php';
init_cart();
$cartCount = get_cart_count();
$user = current_user();
$flash = get_flash();

$pageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' – ' . SITE_TAGLINE;
$pageDescription = isset($pageDescription) ? htmlspecialchars($pageDescription) : SITE_DESCRIPTION;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <meta name="description" content="<?= $pageDescription ?>">
  <meta name="keywords" content="buyoera, online shopping india, sneaker cleaning kit, cable organizer clips, kitchen turntable organizer, smart living india">
  
  <!-- Open Graph / Social Sharing -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= $pageTitle ?>">
  <meta property="og:description" content="<?= $pageDescription ?>">
  <meta property="og:site_name" content="<?= SITE_NAME ?>">
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">

  <!-- Google Fonts: Plus Jakarta Sans & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 & Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- BUYOERA Custom Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Top Announcement Bar -->
  <div class="announcement-bar">
    <div class="container d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center text-truncate">
        <span class="badge-deal">FESTIVAL SPECIAL</span>
        <span class="text-truncate">Free Express Delivery across India on orders above ₹499 | Cash on Delivery (COD) Available!</span>
      </div>
      <div class="d-none d-md-flex align-items-center">
        <a href="track-order.php" class="announcement-link"><i class="bi bi-geo-alt"></i> Track Order</a>
        <a href="contact.php" class="announcement-link"><i class="bi bi-telephone"></i> 24/7 Support: <?= CONTACT_PHONE ?></a>
      </div>
    </div>
  </div>

  <!-- Sticky Main Header -->
  <header class="site-header">
    <div class="container py-2">
      <div class="row align-items-center">
        
        <!-- Mobile Hamburger & Logo -->
        <div class="col-auto d-flex align-items-center gap-2">
          <button class="btn btn-link text-dark d-lg-none p-0 me-2 fs-4" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuDrawer" aria-label="Toggle Navigation">
            <i class="bi bi-list"></i>
          </button>
          <a href="index.php" class="brand-logo" title="<?= SITE_NAME ?> - Home">
            <img src="assets/images/logo.svg" alt="<?= SITE_NAME ?> Logo">
          </a>
        </div>

        <!-- Search Bar -->
        <div class="col d-none d-md-flex justify-content-center px-4">
          <form action="shop.php" method="GET" class="header-search-form">
            <input type="text" name="q" placeholder="Search smart essentials, categories, products..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off" required>
            <button type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
          </form>
        </div>

        <!-- Right Header Actions (Track, Account, Cart) -->
        <div class="col-auto ms-auto">
          <div class="header-actions">
            
            <a href="track-order.php" class="header-action-btn d-none d-lg-flex" title="Track Your Order">
              <span class="icon-wrap"><i class="bi bi-truck"></i></span>
              <span class="d-none d-xl-inline">Track Order</span>
            </a>

            <!-- Customer Account Dropdown -->
            <?php if ($user): ?>
              <div class="dropdown">
                <a href="#" class="header-action-btn dropdown-toggle" id="accountMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="icon-wrap"><i class="bi bi-person-check-fill text-primary"></i></span>
                  <span class="d-none d-md-inline text-truncate" style="max-width: 110px;">Hi, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" aria-labelledby="accountMenuBtn">
                  <li class="px-3 py-1 text-muted small">Logged in as<br><strong><?= htmlspecialchars($user['email']) ?></strong></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item py-2" href="account.php"><i class="bi bi-person me-2"></i> My Profile &amp; Orders</a></li>
                  <li><a class="dropdown-item py-2" href="track-order.php"><i class="bi bi-geo-alt me-2"></i> Track Shipments</a></li>
                  <?php if ($user['role'] === 'admin'): ?>
                    <li><a class="dropdown-item py-2 text-danger fw-bold" href="admin/index.php"><i class="bi bi-speedometer2 me-2"></i> Admin Panel</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                </ul>
              </div>
            <?php else: ?>
              <a href="login.php" class="header-action-btn" title="Sign In or Register">
                <span class="icon-wrap"><i class="bi bi-person"></i></span>
                <span class="d-none d-md-inline">Account</span>
              </a>
            <?php endif; ?>

            <!-- Cart Button with Dynamic Badge -->
            <a href="cart.php" class="header-action-btn" title="View Shopping Cart">
              <span class="icon-wrap">
                <i class="bi bi-bag"></i>
                <span class="badge-counter cart-count-badge" style="<?= $cartCount > 0 ? 'display:flex;' : 'display:none;' ?>"><?= $cartCount ?></span>
              </span>
              <span class="d-none d-md-inline">Cart</span>
            </a>

          </div>
        </div>

      </div>

      <!-- Mobile Search Form Row -->
      <div class="d-md-none mt-2">
        <form action="shop.php" method="GET" class="header-search-form">
          <input type="text" name="q" placeholder="Search products..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" required>
          <button type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
        </form>
      </div>

    </div>

    <!-- Desktop Sub-Navigation Menu -->
    <nav class="sub-navbar d-none d-lg-block">
      <div class="container d-flex align-items-center justify-content-between">
        <ul class="sub-nav-links">
          
          <!-- Category Dropdown -->
          <li class="dropdown">
            <a href="#" class="category-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-grid-fill me-1"></i> All Categories
            </a>
            <ul class="dropdown-menu shadow-sm border-0 py-2 mt-1">
              <li><a class="dropdown-item py-2" href="category.php?slug=shoe-care"><i class="bi bi-arrow-right-short text-primary"></i> Shoe Care</a></li>
              <li><a class="dropdown-item py-2" href="category.php?slug=electronics-accessories"><i class="bi bi-arrow-right-short text-primary"></i> Electronics Accessories</a></li>
              <li><a class="dropdown-item py-2" href="category.php?slug=home-kitchen"><i class="bi bi-arrow-right-short text-primary"></i> Home &amp; Kitchen</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2 fw-bold text-primary" href="shop.php">Browse All Products →</a></li>
            </ul>
          </li>

          <li><a href="shop.php"><i class="bi bi-collection"></i> Shop All</a></li>
          <li><a href="shop.php?tag=trending"><i class="bi bi-fire text-warning"></i> Trending</a></li>
          <li><a href="shop.php?tag=best-seller"><i class="bi bi-award text-primary"></i> Best Sellers</a></li>
          <li><a href="shop.php?tag=new-arrivals"><i class="bi bi-stars text-success"></i> New Arrivals</a></li>
          <li><a href="shop.php?tag=special-offers"><i class="bi bi-percent text-danger"></i> Deals &amp; Offers</a></li>
        </ul>

        <div class="d-flex align-items-center gap-3">
          <a href="about.php" class="text-secondary small fw-semibold">About Us</a>
          <a href="contact.php" class="text-secondary small fw-semibold">Contact</a>
        </div>
      </div>
    </nav>
  </header>

  <!-- Mobile Offcanvas Drawer -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenuDrawer" aria-labelledby="mobileMenuDrawerLabel">
    <div class="offcanvas-header border-bottom">
      <img src="assets/images/logo.svg" alt="BUYOERA" height="34">
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
      
      <div class="p-3 bg-light border-bottom">
        <?php if ($user): ?>
          <p class="mb-1 small text-muted">Welcome back,</p>
          <h6 class="mb-2 fw-bold text-primary"><?= htmlspecialchars($user['name']) ?></h6>
          <a href="account.php" class="btn btn-sm btn-outline-primary me-2"><i class="bi bi-person"></i> My Account</a>
          <a href="logout.php" class="btn btn-sm btn-outline-danger">Sign Out</a>
        <?php else: ?>
          <h6 class="mb-2 fw-bold">Sign in for the best experience</h6>
          <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-sm btn-buyoera-primary flex-fill">Log In</a>
            <a href="register.php" class="btn btn-sm btn-buyoera-outline flex-fill">Register</a>
          </div>
        <?php endif; ?>
      </div>

      <div class="p-3">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Shop Categories</h6>
        <ul class="list-unstyled mb-4">
          <li class="mb-2"><a href="category.php?slug=shoe-care" class="d-flex align-items-center py-2 text-dark text-decoration-none fw-semibold"><i class="bi bi-chevron-right text-primary me-2"></i> Shoe Care</a></li>
          <li class="mb-2"><a href="category.php?slug=electronics-accessories" class="d-flex align-items-center py-2 text-dark text-decoration-none fw-semibold"><i class="bi bi-chevron-right text-primary me-2"></i> Electronics Accessories</a></li>
          <li class="mb-2"><a href="category.php?slug=home-kitchen" class="d-flex align-items-center py-2 text-dark text-decoration-none fw-semibold"><i class="bi bi-chevron-right text-primary me-2"></i> Home &amp; Kitchen</a></li>
        </ul>

        <h6 class="text-uppercase small fw-bold text-muted mb-3">Quick Navigation</h6>
        <ul class="list-unstyled mb-4">
          <li class="mb-2"><a href="shop.php" class="d-flex align-items-center py-1 text-secondary text-decoration-none"><i class="bi bi-collection me-2"></i> Shop All Products</a></li>
          <li class="mb-2"><a href="track-order.php" class="d-flex align-items-center py-1 text-secondary text-decoration-none"><i class="bi bi-truck me-2"></i> Track Your Order</a></li>
          <li class="mb-2"><a href="about.php" class="d-flex align-items-center py-1 text-secondary text-decoration-none"><i class="bi bi-info-circle me-2"></i> About BUYOERA</a></li>
          <li class="mb-2"><a href="contact.php" class="d-flex align-items-center py-1 text-secondary text-decoration-none"><i class="bi bi-envelope me-2"></i> Customer Support</a></li>
        </ul>

        <div class="pt-3 border-top text-muted small">
          <p class="mb-1"><i class="bi bi-telephone text-primary me-2"></i> <?= CONTACT_PHONE ?></p>
          <p class="mb-0"><i class="bi bi-envelope text-primary me-2"></i> <?= CONTACT_EMAIL ?></p>
        </div>
      </div>

    </div>
  </div>

  <!-- Flash Notification Banner -->
  <?php if ($flash): ?>
    <div class="container mt-3">
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
