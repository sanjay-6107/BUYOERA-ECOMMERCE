<?php
/**
 * BUYOERA - Return & Refund Policy
 */

$pageTitle = '7-Day Return & Refund Policy';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-white border-bottom">
  <div class="container text-center">
    <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill mb-2">100% BUYER PROTECTION</span>
    <h1 class="h2 fw-bold text-navy mb-2">7-Day Return &amp; Refund Policy</h1>
    <p class="text-muted mx-auto mb-0" style="max-width: 550px;">
      Hassle-free replacements, simple reverse pickups, and quick refunds directly to your account.
    </p>
  </div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed">
        
        <h4 class="fw-bold text-navy mb-3">1. 7-Day Replacement Guarantee</h4>
        <p>
          At BUYOERA, customer satisfaction is our top priority. We offer a <strong>7-Day Hassle-Free Replacement or Refund Policy</strong> from the date your package is marked as Delivered by our courier partner.
        </p>

        <h4 class="fw-bold text-navy mb-3 mt-4">2. Eligible Reasons for Returns</h4>
        <ul>
          <li><strong>Transit Damage:</strong> Product arrived cracked, leaking, broken, or crushed during courier transit.</li>
          <li><strong>Defective or Non-Functional:</strong> Item is defective upon initial inspection or missing components listed on the product page.</li>
          <li><strong>Incorrect Item:</strong> Received a different model, color, or item from what was ordered.</li>
        </ul>

        <h4 class="fw-bold text-navy mb-3 mt-4">3. Step-by-Step Return Process</h4>
        <ol>
          <li><strong>Notify Us:</strong> Contact our team within 7 days of delivery via WhatsApp at <strong><?= CONTACT_PHONE ?></strong> or email <strong><?= CONTACT_EMAIL ?></strong> with your Order Number and 1-2 clear photos/video of the issue.</li>
          <li><strong>Verification:</strong> Our team reviews your request within 4 business hours and approves reverse pickup.</li>
          <li><strong>Doorstep Reverse Pickup:</strong> Our courier partner visits your address to collect the package. Please ensure the item is kept in its original packaging with all included accessories.</li>
          <li><strong>Resolution:</strong> Upon pickup or transit verification, we immediately dispatch a brand-new replacement unit or process your full refund.</li>
        </ol>

        <h4 class="fw-bold text-navy mb-3 mt-4">4. Refund Processing Timelines</h4>
        <ul>
          <li><strong>Prepaid Orders (UPI / Cards / Net Banking):</strong> Refunds are credited back to the original source payment method within <strong>2 to 4 business days</strong>.</li>
          <li><strong>Cash on Delivery (COD) Orders:</strong> Once approved, we will request your UPI ID (GPay/PhonePe/Paytm) or Bank Account details, and the full refund amount will be credited within <strong>24 to 48 hours</strong> via instant NEFT/IMPS.</li>
        </ul>

        <h4 class="fw-bold text-navy mb-3 mt-4">5. Non-Returnable Scenarios</h4>
        <p>
          Items returned after 7 days, products physically damaged through misuse or unauthorized alteration, or consumable liquid items that have been predominantly consumed are not eligible for returns.
        </p>

        <div class="p-3 bg-light rounded-3 border mt-4">
          <p class="small text-muted mb-0">
            Need urgent help with an exchange? Reach out directly to our dedicated customer support on WhatsApp at <strong><?= CONTACT_PHONE ?></strong>.
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
