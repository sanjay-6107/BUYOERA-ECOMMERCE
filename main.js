/**
 * BUYOERA - Main JavaScript File
 * Handles Cart AJAX, UI Interactions, Pincode Checker, Image Gallery & Toasts
 */

document.addEventListener('DOMContentLoaded', function () {
  initStickyHeader();
  initQuantitySteppers();
  initGalleryThumbs();
  initAddToCartButtons();
  initPincodeChecker();
  initPaymentSelector();
});

// -------------------------------------------------------------
// 1. Toast Notification System
// -----------------------------------------------------------
function showToast(message, type = 'success') {
  let container = document.querySelector('.toast-container-custom');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container-custom';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast-custom';
  
  let icon = '<i class="bi bi-check-circle-fill text-success"></i>';
  if (type === 'error' || type === 'danger') {
    icon = '<i class="bi bi-exclamation-triangle-fill text-danger"></i>';
    toast.style.borderLeftColor = '#ef4444';
  } else if (type === 'info') {
    icon = '<i class="bi bi-info-circle-fill text-primary"></i>';
    toast.style.borderLeftColor = '#2563eb';
  }

  toast.innerHTML = `${icon} <span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// -------------------------------------------------------------
// 2. Sticky Header Shadow on Scroll
// -----------------------------------------------------------
function initStickyHeader() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });
}

// -------------------------------------------------------------
// 3. Product Gallery Thumbnail Switcher
// -----------------------------------------------------------
function initGalleryThumbs() {
  const thumbs = document.querySelectorAll('.gallery-thumb');
  const mainImg = document.getElementById('mainGalleryImg');

  if (!thumbs.length || !mainImg) return;

  thumbs.forEach(thumb => {
    thumb.addEventListener('click', function () {
      thumbs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      const newSrc = this.getAttribute('data-img');
      if (newSrc) {
        mainImg.style.opacity = '0';
        setTimeout(() => {
          mainImg.src = newSrc;
          mainImg.style.opacity = '1';
        }, 150);
      }
    });
  });
}

// -------------------------------------------------------------
// 4. Quantity Steppers (+ / -)
// -----------------------------------------------------------
function initQuantitySteppers() {
  document.querySelectorAll('.qty-stepper').forEach(stepper => {
    const minusBtn = stepper.querySelector('.btn-qty-minus');
    const plusBtn = stepper.querySelector('.btn-qty-plus');
    const input = stepper.querySelector('.qty-input');

    if (!input) return;

    if (minusBtn) {
      minusBtn.addEventListener('click', () => {
        let val = parseInt(input.value) || 1;
        if (val > 1) {
          input.value = val - 1;
          input.dispatchEvent(new Event('change'));
        }
      });
    }

    if (plusBtn) {
      plusBtn.addEventListener('click', () => {
        let val = parseInt(input.value) || 1;
        let max = parseInt(input.getAttribute('max')) || 999;
        if (val < max) {
          input.value = val + 1;
          input.dispatchEvent(new Event('change'));
        } else {
          showToast(`Maximum available stock is ${max}.`, 'info');
        }
      });
    }
  });
}

// -------------------------------------------------------------
// 5. AJAX Add To Cart
// -----------------------------------------------------------
function initAddToCartButtons() {
  document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const productId = this.getAttribute('data-product-id');
      if (!productId) return;

      // Check if there is an explicit quantity selector on the page
      let quantity = 1;
      const qtyInput = document.getElementById('productQtyInput');
      if (qtyInput) {
        quantity = parseInt(qtyInput.value) || 1;
      }

      const originalHtml = this.innerHTML;
      this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Adding...';
      this.disabled = true;

      fetch('api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', product_id: parseInt(productId), quantity: quantity })
      })
      .then(res => res.json())
      .then(data => {
        this.innerHTML = originalHtml;
        this.disabled = false;

        if (data.success) {
          showToast(data.message || 'Added to cart!', 'success');
          updateCartBadge(data.cart ? data.cart.count : null);
        } else {
          showToast(data.message || 'Could not add to cart.', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        this.innerHTML = originalHtml;
        this.disabled = false;
        showToast('Network error while updating cart.', 'error');
      });
    });
  });
}

function updateCartBadge(count) {
  if (count === null || count === undefined) return;
  document.querySelectorAll('.cart-count-badge').forEach(badge => {
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  });
}

// -------------------------------------------------------------
// 6. Indian Pincode Delivery Estimator
// -----------------------------------------------------------
function initPincodeChecker() {
  const pinInput = document.getElementById('pincodeInput');
  const pinBtn = document.getElementById('checkPincodeBtn');
  const resultDiv = document.getElementById('pincodeResult');

  if (!pinInput || !pinBtn || !resultDiv) return;

  pinBtn.addEventListener('click', () => {
    const pin = pinInput.value.trim();
    if (!/^\d{6}$/.test(pin)) {
      resultDiv.innerHTML = '<span class="text-danger small"><i class="bi bi-x-circle"></i> Please enter a valid 6-digit Indian PIN code.</span>';
      return;
    }

    resultDiv.innerHTML = '<span class="text-muted small"><span class="spinner-border spinner-border-sm"></span> Checking serviceable zones...</span>';

    fetch(`api/check-pincode.php?pincode=${pin}`)
      .then(r => r.json())
      .then(data => {
        if (data.serviceable) {
          resultDiv.innerHTML = `
            <div class="alert alert-success py-2 px-3 mt-2 mb-0 small">
              <i class="bi bi-geo-alt-fill text-success"></i> <strong>Deliverable to ${data.city}, ${data.state}</strong><br>
              <i class="bi bi-truck text-primary"></i> Estimated Delivery: <strong>${data.estimated_days} (${data.estimated_date})</strong><br>
              <i class="bi bi-cash-stack text-success"></i> Cash on Delivery &amp; Express Shipping Available
            </div>
          `;
        } else {
          resultDiv.innerHTML = `<span class="text-danger small"><i class="bi bi-exclamation-circle"></i> ${data.message || 'Delivery not available to this PIN code.'}</span>`;
        }
      })
      .catch(() => {
        resultDiv.innerHTML = '<span class="text-danger small">Error checking PIN code.</span>';
      });
  });
}

// -------------------------------------------------------------
// 7. Payment Method Radio Highlighter
// -----------------------------------------------------------
function initPaymentSelector() {
  const cards = document.querySelectorAll('.payment-method-card');
  cards.forEach(card => {
    card.addEventListener('click', function () {
      const radio = this.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;
        cards.forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
      }
    });
  });
}
