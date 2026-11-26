<?php
/**
 * BUYOERA - Shipping & Delivery Policy
 */

$pageTitle = 'Shipping & Delivery Policy';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-white border-bottom">
  <div class="container text-center">
    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">PAN-INDIA LOGISTICS</span>
    <h1 class="h2 fw-bold text-navy mb-2">Shipping &amp; Delivery Policy</h1>
    <p class="text-muted mx-auto mb-0" style="max-width: 550px;">
      Transparent timelines, trusted courier partners, and live shipment tracking across India.
    </p>
  </div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed">
        
        <h4 class="fw-bold text-navy mb-3">1. Shipping Coverage &amp; Serviceable Zones</h4>
        <p>
          BUYOERA delivers to over 19,000+ PIN codes across all 28 states and 8 Union Territories in India. We partner exclusively with premium national logistics couriers including <strong>BlueDart Express, Delhivery, DTDC, and XpressBees</strong> to guarantee safe and expedited transit.
        </p>

        <h4 class="fw-bold text-navy mb-3 mt-4">2. Processing &amp; Dispatch SLA</h4>
        <ul>
          <li><strong>Same-Day Dispatch:</strong> Orders placed before 2:00 PM IST (Monday to Saturday) are packaged and handed over to the courier partner the same business day.</li>
          <li><strong>Next-Day Dispatch:</strong> Orders placed after 2:00 PM IST or on Sundays/National Holidays are dispatched on the next business day.</li>
          <li>Each parcel is packed in multi-layered tamper-evident and shock-resistant bubble wrapping to avoid transit damage.</li>
        </ul>

        <h4 class="fw-bold text-navy mb-3 mt-4">3. Delivery Timelines</h4>
        <div class="table-responsive mb-3">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Region</th>
                <th>Estimated Transit Time</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Tier 1 Metro Cities (Mumbai, Delhi-NCR, Bengaluru, Pune, Hyderabad, Chennai, Kolkata)</td>
                <td><strong>1 to 3 Business Days</strong></td>
              </tr>
              <tr>
                <td>Tier 2 &amp; Tier 3 Cities (State Capitals &amp; District Centers)</td>
                <td><strong>2 to 4 Business Days</strong></td>
              </tr>
              <tr>
                <td>Remote Areas &amp; North East India / J&amp;K</td>
                <td><strong>4 to 6 Business Days</strong></td>
              </tr>
            </tbody>
          </table>
        </div>

        <h4 class="fw-bold text-navy mb-3 mt-4">4. Shipping Charges &amp; Free Delivery Threshold</h4>
        <ul>
          <li><strong>Free Express Delivery:</strong> Automatically applied to all orders with a cart value of <strong><?= format_price(FREE_SHIPPING_THRESHOLD) ?> or higher</strong>.</li>
          <li><strong>Standard Flat Fee:</strong> Orders below <?= format_price(FREE_SHIPPING_THRESHOLD) ?> incur a nominal flat shipping fee of <strong><?= format_price(FLAT_SHIPPING_FEE) ?></strong> anywhere in India.</li>
        </ul>

        <h4 class="fw-bold text-navy mb-3 mt-4">5. Live Shipment Tracking</h4>
        <p>
          Once your parcel is scanned into the courier facility, you will receive an instant notification with your AWB tracking number via WhatsApp and Email. You can also monitor real-time tracking at any moment by visiting our dedicated <a href="track-order.php" class="text-primary fw-bold">Track Order Page</a>.
        </p>

        <h4 class="fw-bold text-navy mb-3 mt-4">6. Cash on Delivery (COD) Guidelines</h4>
        <p>
          Cash on Delivery is available across most serviceable PIN codes. To ensure genuine deliveries, our automated system may send a 1-click confirmation prompt via SMS or WhatsApp before dispatch. Customers are requested to have the exact cash amount or UPI QR code ready at the time of delivery.
        </p>

        <div class="p-3 bg-light rounded-3 border mt-4">
          <p class="small text-muted mb-0">
            For any shipping inquiries or delivery rescheduling, please contact our logistics desk at <strong><?= CONTACT_EMAIL ?></strong> or WhatsApp <strong><?= CONTACT_PHONE ?></strong>.
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
