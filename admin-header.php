<?php
/**
 * BUYOERA - Admin Dashboard Header
 */

require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_admin('../admin/login.php');

$adminUser = current_user();
$currentFile = basename($_SERVER['PHP_SELF']);
$adminPageTitle = $adminPageTitle ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($adminPageTitle) ?> | BUYOERA Admin</title>
  
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">

  <!-- Google Fonts: Plus Jakarta Sans & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800;900&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Admin Custom Stylesheet -->
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

  <!-- Fixed Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-sidebar-brand d-flex align-items-center justify-content-between">
      <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
        <img src="../assets/images/logo-white.svg" alt="BUYOERA" height="34">
      </a>
      <span class="badge bg-warning text-dark small fw-bold">ADMIN</span>
    </div>

    <ul class="admin-nav">
      <li class="admin-nav-item">
        <a href="index.php" class="admin-nav-link <?= $currentFile === 'index.php' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="orders.php" class="admin-nav-link <?= in_array($currentFile, ['orders.php', 'order-detail.php']) ? 'active' : '' ?>">
          <i class="bi bi-bag-check"></i> Orders
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="dropshipping.php" class="admin-nav-link <?= $currentFile === 'dropshipping.php' ? 'active' : '' ?>">
          <i class="bi bi-truck-flatbed"></i> Dropshipping &amp; Profit
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="products.php" class="admin-nav-link <?= in_array($currentFile, ['products.php', 'product-add.php', 'product-edit.php']) ? 'active' : '' ?>">
          <i class="bi bi-box-seam"></i> Products
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="categories.php" class="admin-nav-link <?= $currentFile === 'categories.php' ? 'active' : '' ?>">
          <i class="bi bi-grid"></i> Categories
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="customers.php" class="admin-nav-link <?= $currentFile === 'customers.php' ? 'active' : '' ?>">
          <i class="bi bi-people"></i> Customers
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="coupons.php" class="admin-nav-link <?= $currentFile === 'coupons.php' ? 'active' : '' ?>">
          <i class="bi bi-percent"></i> Discounts &amp; Coupons
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="settings.php" class="admin-nav-link <?= $currentFile === 'settings.php' ? 'active' : '' ?>">
          <i class="bi bi-gear"></i> Store Settings
        </a>
      </li>
    </ul>

    <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
      <a href="../index.php" target="_blank" class="admin-nav-link text-white small mb-1">
        <i class="bi bi-box-arrow-up-right"></i> View Live Store
      </a>
      <a href="logout.php" class="admin-nav-link text-danger small">
        <i class="bi bi-box-arrow-right"></i> Sign Out
      </a>
    </div>
  </aside>

  <!-- Admin Main Wrapper -->
  <div class="admin-wrapper">
    
    <!-- Topbar -->
    <header class="admin-topbar">
      <div>
        <h5 class="fw-bold mb-0 text-navy"><?= htmlspecialchars($adminPageTitle) ?></h5>
        <small class="text-muted">BUYOERA E-Commerce Operations Control Center</small>
      </div>

      <div class="d-flex align-items-center gap-3">
        <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
          <i class="bi bi-shop me-1"></i> Open Storefront
        </a>

        <div class="dropdown">
          <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-navy dropdown-toggle" data-bs-toggle="dropdown">
            <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <?= strtoupper(substr($adminUser['name'], 0, 1)) ?>
            </div>
            <div class="d-none d-md-block text-start">
              <span class="small fw-bold d-block text-navy"><?= htmlspecialchars($adminUser['name']) ?></span>
              <small class="text-muted" style="font-size: 0.72rem;">Administrator</small>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2">
            <li><a class="dropdown-item py-2" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
          </ul>
        </div>
      </div>
    </header>

    <!-- Page Content Container -->
    <main class="admin-page-content">
      <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm rounded-3 mb-4">
          <?= htmlspecialchars($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
