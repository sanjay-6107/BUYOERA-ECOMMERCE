<?php
/**
 * BUYOERA - Contact Us Page
 */

require_once __DIR__ . '/includes/functions.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = sanitize($_POST['phone'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (empty($name) || !$email || empty($subject) || empty($message)) {
            $error = 'Please fill in all required fields.';
        } else {
            $db = Database::getConnection();
            $ins = $db->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, status)
                VALUES (:name, :email, :phone, :subj, :msg, 'unread')
            ");
            $ins->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subj' => $subject,
                'msg' => $message
            ]);
            $success = 'Thank you! Your message has been received. Our team will contact you within 24 business hours.';
        }
    }
}

$pageTitle = 'Contact Us – 24/7 Support';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-white border-bottom">
  <div class="container text-center">
    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">WE ARE HERE TO HELP</span>
    <h1 class="h2 fw-bold text-navy mb-2">Get in Touch with BUYOERA</h1>
    <p class="text-muted mx-auto mb-0" style="max-width: 500px;">
      Have a question about an order, tracking, or a product? Send us a message or chat with our team directly.
    </p>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    
    <!-- Contact Info Cards -->
    <div class="col-lg-5">
      <div class="p-4 bg-white rounded-4 border shadow-sm mb-4">
        <h4 class="fw-bold text-navy mb-4">Direct Contact Information</h4>
        
        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-5">
            <i class="bi bi-telephone-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold text-navy mb-1">Phone / WhatsApp Support</h6>
            <p class="text-muted small mb-1"><?= CONTACT_PHONE ?></p>
            <span class="badge bg-success-subtle text-success small">Mon - Sat: 9:00 AM - 7:00 PM IST</span>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-5">
            <i class="bi bi-envelope-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold text-navy mb-1">Email Customer Care</h6>
            <p class="text-muted small mb-1"><?= CONTACT_EMAIL ?></p>
            <small class="text-muted">Typical response within 2-4 business hours</small>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-5">
            <i class="bi bi-geo-alt-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold text-navy mb-1">Registered Corporate Office</h6>
            <p class="text-muted small mb-0"><?= CONTACT_ADDRESS ?></p>
          </div>
        </div>

        <div class="pt-3 border-top">
          <a href="https://wa.me/919876543210" target="_blank" rel="noopener" class="btn btn-success w-100 rounded-pill py-2 fw-bold">
            <i class="bi bi-whatsapp me-1"></i> Chat with Us on WhatsApp
          </a>
        </div>
      </div>

      <!-- Quick FAQs -->
      <div class="p-4 bg-light rounded-4 border">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-question-circle me-1"></i> Frequently Asked Questions</h5>
        <div class="accordion accordion-flush" id="faqAccordion">
          <div class="accordion-item bg-transparent">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent py-2 px-0 fw-bold small text-navy" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                How long does shipping take?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body px-0 text-muted small">
                Orders are dispatched within 24 hours. Transit time is typically 2 to 4 business days depending on your PIN code.
              </div>
            </div>
          </div>

          <div class="accordion-item bg-transparent">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent py-2 px-0 fw-bold small text-navy" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Is Cash on Delivery (COD) available?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body px-0 text-muted small">
                Yes! We offer Cash on Delivery across 19,000+ Indian PIN codes. You can also pay via UPI upon delivery.
              </div>
            </div>
          </div>

          <div class="accordion-item bg-transparent">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent py-2 px-0 fw-bold small text-navy" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                How do I request a return or replacement?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body px-0 text-muted small">
                We have a 7-day hassle-free policy. Just reach out on WhatsApp or email with your Order ID and photo, and we will arrange a reverse pickup.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <h4 class="fw-bold text-navy mb-3">Send Us a Message</h4>
        <p class="text-muted small mb-4">Fill out the form below and our team will get back to you shortly.</p>

        <?php if ($success): ?>
          <div class="alert alert-success rounded-3 p-3 mb-4">
            <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger rounded-3 p-3 mb-4">
            <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form action="contact.php" method="POST">
          <?= csrf_field() ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-navy">Your Name *</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-navy">Email Address *</label>
              <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-navy">Phone Number (Optional)</label>
              <input type="tel" name="phone" class="form-control" placeholder="9820112233">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-navy">Subject *</label>
              <input type="text" name="subject" class="form-control" placeholder="e.g. Question about Sneaker Kit" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold text-navy">Message *</label>
              <textarea name="message" class="form-control" rows="5" placeholder="How can we assist you today?" required></textarea>
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-buyoera-primary btn-lg rounded-pill px-5">
                Send Message
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
