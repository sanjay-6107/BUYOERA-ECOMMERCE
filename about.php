<?php
/**
 * BUYOERA - About Us Page
 */

$pageTitle = 'About Us – Shop Smart. Live Better.';
$pageDescription = 'Learn about BUYOERA: India\'s curated multi-category e-commerce brand delivering quality lifestyle innovations.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Header -->
<div class="py-5 text-center text-white" style="background: linear-gradient(135deg, #0a192f 0%, #1e3a8a 100%);">
  <div class="container py-4">
    <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3">OUR STORY &amp; PHILOSOPHY</span>
    <h1 class="display-4 fw-bold mb-3">Shop Smart. Live Better.</h1>
    <p class="lead text-light mx-auto mb-0" style="max-width: 650px;">
      BUYOERA was born out of a simple observation: Indian consumers deserve well-engineered, practical lifestyle products without paying inflated luxury markups.
    </p>
  </div>
</div>

<div class="container py-5">
  
  <!-- Brand Mission Row -->
  <div class="row align-items-center gy-5 mb-5">
    <div class="col-lg-6">
      <span class="section-tag">WHO WE ARE</span>
      <h2 class="section-title mb-4">Curated Utility for Modern Indian Living</h2>
      <p class="text-secondary leading-relaxed mb-3">
        In an internet saturated with thousands of cheap, disposable gadgets and unreliable marketplaces, finding genuinely useful products is exhausting. At <strong>BUYOERA</strong>, we do things differently.
      </p>
      <p class="text-secondary leading-relaxed mb-4">
        We specialize in multi-category problem solvers: from keeping your prized sneakers pristine with eco-friendly formulas, to creating clutter-free workspaces with authentic 3M cable clips, and transforming chaotic kitchens with 360-degree rotating organizers. Every item in our catalog is tested, vetted, and backed by our quality promise.
      </p>
      <div class="row g-3">
        <div class="col-sm-6">
          <div class="p-3 bg-white rounded-3 border shadow-sm">
            <h4 class="fw-bold text-navy mb-1">100%</h4>
            <p class="small text-muted mb-0">Quality verified before dispatch</p>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="p-3 bg-white rounded-3 border shadow-sm">
            <h4 class="fw-bold text-navy mb-1">19,000+</h4>
            <p class="small text-muted mb-0">PIN codes delivered across India</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6 text-center">
      <img src="assets/images/hero-banner.svg" alt="BUYOERA Philosophy" class="img-fluid rounded-4 shadow-lg">
    </div>
  </div>

  <!-- Core Values Grid -->
  <div class="py-5 border-top">
    <div class="text-center mb-5">
      <span class="section-tag">OUR CORE PRINCIPLES</span>
      <h2 class="section-title">The Three Pillars of BUYOERA</h2>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
          <div class="fs-1 text-primary mb-3"><i class="bi bi-bullseye"></i></div>
          <h4 class="fw-bold text-navy mb-2">Intentional Curation</h4>
          <p class="text-muted small mb-0">We don't stock endless clutter. Each category features only the best-performing, highest-rated solution so you never have to guess.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
          <div class="fs-1 text-primary mb-3"><i class="bi bi-tag-fill"></i></div>
          <h4 class="fw-bold text-navy mb-2">Fair Indian Pricing</h4>
          <p class="text-muted small mb-0">By streamlining our dropshipping and supplier fulfillment channels, we pass the savings directly to you, making premium living affordable.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="p-4 bg-white rounded-4 border shadow-sm h-100">
          <div class="fs-1 text-primary mb-3"><i class="bi bi-shield-heart-fill"></i></div>
          <h4 class="fw-bold text-navy mb-2">Customer-First Service</h4>
          <p class="text-muted small mb-0">7-Day easy returns, Cash on Delivery everywhere, and real customer support via WhatsApp and phone. We treat every customer like family.</p>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
