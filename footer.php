<?php
/**
 * BUYOERA - Global Footer Template
 */
?>
  <!-- Newsletter Section -->
  <section class="py-5 bg-white border-top">
    <div class="container">
      <div class="row align-items-center justify-content-between gy-4">
        <div class="col-lg-6">
          <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">JOIN THE SMART SHOPPERS CLUB</span>
          <h3 class="fw-bold text-navy mb-2">Get ₹100 Off On Your First Order</h3>
          <p class="text-muted mb-0">Subscribe to our newsletter for exclusive drops, secret deals, and lifestyle tips. No spam, ever.</p>
        </div>
        <div class="col-lg-5">
          <form class="d-flex gap-2" onsubmit="event.preventDefault(); showToast('Thank you for subscribing! Your discount code is WELCOME10', 'success'); this.reset();">
            <input type="email" class="form-control form-control-lg rounded-pill px-4 border-2" placeholder="Enter your email address" required>
            <button type="submit" class="btn btn-buyoera-accent rounded-pill px-4 text-nowrap">Subscribe</button>
          </form>
          <small class="text-muted d-block mt-2 ps-3"><i class="bi bi-shield-check text-success"></i> 100% Privacy guaranteed. Unsubscribe anytime.</small>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Dark Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="row gy-5">
        
        <!-- Brand & Contact -->
        <div class="col-lg-4 col-md-6">
          <div class="footer-logo">
            <a href="index.php">
              <img src="assets/images/logo-white.svg" alt="<?= SITE_NAME ?> - Shop Smart. Live Better.">
            </a>
          </div>
          <p class="footer-text mb-4 pe-lg-4">
            BUYOERA is India's premium curated multi-category e-commerce brand. We source and deliver highly practical, problem-solving lifestyle innovations engineered for quality and longevity.
          </p>
          <div class="footer-contact-item">
            <i class="bi bi-geo-alt-fill"></i>
            <span><?= CONTACT_ADDRESS ?></span>
          </div>
          <div class="footer-contact-item">
            <i class="bi bi-telephone-fill"></i>
            <span><?= CONTACT_PHONE ?> (Mon-Sat, 9am - 7pm IST)</span>
          </div>
          <div class="footer-contact-item">
            <i class="bi bi-envelope-fill"></i>
            <span><?= CONTACT_EMAIL ?></span>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="col-lg-2 col-md-3 col-6">
          <h5 class="footer-heading">Quick Links</h5>
          <ul class="footer-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="shop.php">Shop All Products</a></li>
            <li><a href="shop.php?tag=trending">Trending Items</a></li>
            <li><a href="track-order.php">Track Order</a></li>
            <li><a href="account.php">My Account</a></li>
            <li><a href="cart.php">Shopping Cart</a></li>
            <li><a href="about.php">About BUYOERA</a></li>
            <li><a href="contact.php">Contact Us</a></li>
          </ul>
        </div>

        <!-- Categories -->
        <div class="col-lg-3 col-md-3 col-6">
          <h5 class="footer-heading">Categories</h5>
          <ul class="footer-links">
            <li><a href="category.php?slug=shoe-care">Sneaker &amp; Shoe Care</a></li>
            <li><a href="category.php?slug=electronics-accessories">Electronics Accessories</a></li>
            <li><a href="category.php?slug=home-kitchen">Home &amp; Kitchen Organizers</a></li>
            <li><a href="shop.php?tag=best-seller">Best Sellers</a></li>
            <li><a href="shop.php?tag=new-arrivals">New Arrivals</a></li>
            <li><a href="shop.php?tag=special-offers">Special Offers</a></li>
          </ul>
        </div>

        <!-- Customer Policies & Trust -->
        <div class="col-lg-3 col-md-12">
          <h5 class="footer-heading">Customer Care &amp; Trust</h5>
          <ul class="footer-links mb-4">
            <li><a href="shipping-policy.php"><i class="bi bi-truck me-1"></i> Shipping Policy</a></li>
            <li><a href="return-policy.php"><i class="bi bi-arrow-repeat me-1"></i> 7-Day Return &amp; Refund Policy</a></li>
            <li><a href="privacy-policy.php"><i class="bi bi-shield-lock me-1"></i> Privacy Policy</a></li>
            <li><a href="terms.php"><i class="bi bi-file-earmark-text me-1"></i> Terms &amp; Conditions</a></li>
          </ul>
          
          <h6 class="text-white small fw-bold mb-2">100% SECURE INDIAN PAYMENTS</h6>
          <img src="assets/images/payment-methods.svg" alt="UPI, RuPay, Visa, Mastercard, NetBanking, COD" style="max-width: 280px;" class="img-fluid bg-white p-1 rounded-2">
        </div>

      </div>

      <!-- Footer Bottom Bar -->
      <div class="footer-bottom text-center text-md-start">
        <div class="row align-items-center">
          <div class="col-md-6 mb-3 mb-md-0">
            <p class="mb-0 small text-muted">
              &copy; <?= date('Y') ?> <strong><?= SITE_NAME ?></strong> (BUYOERA E-Commerce Pvt Ltd). All Rights Reserved. Crafted for Indian Online Shoppers.
            </p>
          </div>
          <div class="col-md-6 text-md-end">
            <span class="small text-muted me-3"><i class="bi bi-shield-fill-check text-success"></i> SSL 256-Bit Encrypted</span>
            <span class="small text-muted"><i class="bi bi-truck text-primary"></i> Pan-India Delivery</span>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <!-- Mobile Bottom App Navigation Bar -->
  <div class="mobile-bottom-nav">
    <div class="container d-flex justify-content-around">
      <a href="index.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-house-door"></i>
        <span>Home</span>
      </a>
      <a href="shop.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : '' ?>">
        <i class="bi bi-grid"></i>
        <span>Shop</span>
      </a>
      <a href="track-order.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'track-order.php' ? 'active' : '' ?>">
        <i class="bi bi-truck"></i>
        <span>Track</span>
      </a>
      <a href="cart.php" class="mobile-nav-item <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>">
        <i class="bi bi-bag"></i>
        <span class="badge-counter cart-count-badge" style="<?= $cartCount > 0 ? 'display:flex;' : 'display:none;' ?>"><?= $cartCount ?></span>
        <span>Cart</span>
      </a>
      <a href="<?= is_logged_in() ? 'account.php' : 'login.php' ?>" class="mobile-nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['account.php', 'login.php', 'register.php']) ? 'active' : '' ?>">
        <i class="bi bi-person"></i>
        <span>Account</span>
      </a>
    </div>
  </div>

  <!-- Bootstrap 5.3 Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- BUYOERA Core Main Script -->
  <script src="assets/js/main.js"></script>
</body>
</html>
