<?php
/**
 * BUYOERA - Privacy Policy
 */

$pageTitle = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-white border-bottom">
  <div class="container text-center">
    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">SECURITY &amp; COMPLIANCE</span>
    <h1 class="h2 fw-bold text-navy mb-2">Privacy Policy</h1>
    <p class="text-muted mx-auto mb-0" style="max-width: 550px;">
      How BUYOERA collects, secures, and handles your personal information in compliance with Indian Information Technology laws.
    </p>
  </div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed">
        
        <p class="small text-muted mb-4">Last Updated: <?= date('F d, Y') ?></p>

        <h4 class="fw-bold text-navy mb-3">1. Information We Collect</h4>
        <p>When you visit or purchase from BUYOERA, we collect information necessary to fulfill your orders:</p>
        <ul>
          <li><strong>Personal Details:</strong> Full name, shipping address, landmark, state, city, PIN code, email address, and 10-digit mobile phone number.</li>
          <li><strong>Transaction Details:</strong> Order items, quantity, payment method selected, and payment transaction IDs. We <em>never</em> store raw credit/debit card numbers or UPI PINs on our servers.</li>
          <li><strong>Technical Data:</strong> Browser type, IP address, and cookie session tokens to keep your shopping cart active.</li>
        </ul>

        <h4 class="fw-bold text-navy mb-3 mt-4">2. Purpose of Data Processing</h4>
        <p>We use your information exclusively to:</p>
        <ul>
          <li>Process, package, and ship your orders through verified Indian courier networks (BlueDart, Delhivery, etc.).</li>
          <li>Send automatic SMS/WhatsApp tracking alerts and invoice receipts.</li>
          <li>Provide customer support, process refunds, and prevent fraudulent transactions.</li>
          <li>Send occasional promotional discounts if you have subscribed to our newsletter (you can opt out at any time).</li>
        </ul>

        <h4 class="fw-bold text-navy mb-3 mt-4">3. Data Sharing &amp; Third-Party Partners</h4>
        <p>
          We do not sell, rent, or trade your personal information to marketing brokers. We share your delivery details solely with:
        </p>
        <ul>
          <li><strong>Courier Partners:</strong> Name, phone number, and delivery address to deliver packages.</li>
          <li><strong>Payment Processors (Razorpay / UPI Banks):</strong> Encrypted tokens required to verify successful payments.</li>
          <li><strong>Fulfillment &amp; Dropship Suppliers:</strong> Product and delivery details needed to dispatch order packages directly.</li>
        </ul>

        <h4 class="fw-bold text-navy mb-3 mt-4">4. Data Security</h4>
        <p>
          Our application uses industry-standard 256-bit SSL encryption, secure password hashing (Bcrypt), prepared database statements (PDO) to protect against SQL injections, and CSRF token defenses against unauthorized form requests.
        </p>

        <h4 class="fw-bold text-navy mb-3 mt-4">5. Grievance Officer Contact</h4>
        <p>
          In accordance with the Information Technology Act 2000 and rules made thereunder, for any privacy inquiries, data deletion requests, or grievances, please reach our designated officer at <strong><?= CONTACT_EMAIL ?></strong>.
        </p>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
