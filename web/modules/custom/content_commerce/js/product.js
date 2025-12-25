/**
 * @file
 * JavaScript for product interactions.
 */

(function () {
  'use strict';

  // Success modal elements (created dynamically).
  var successOverlay = null;
  var successDesktopModal = null;
  var successMobileDrawer = null;
  var successModalCreated = false;

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', function () {
    initProductGallery();
    initColorSwatches();
    initQuantityControls();
    initShippingAccordion();
    initProductFilters();
    initBuyDrawer();
    initWishlist();
    initProductCarousel();
  });

  /**
   * Initialize product gallery thumbnail switching.
   */
  function initProductGallery() {
    var thumbnails = document.querySelectorAll('.product-thumbnail');

    thumbnails.forEach(function (thumbnail) {
      thumbnail.addEventListener('click', function () {
        var imageUrl = this.getAttribute('data-image');
        var productId = this.getAttribute('data-product-id');

        // Find the main image
        var mainImage;
        if (productId) {
          mainImage = document.getElementById('product-main-image-' + productId);
        } else {
          mainImage = document.getElementById('product-main-image');
        }

        if (mainImage && imageUrl) {
          mainImage.src = imageUrl;

          // Update thumbnail states
          var container = this.closest('.flex');
          if (container) {
            container.querySelectorAll('.product-thumbnail').forEach(function (thumb) {
              thumb.classList.remove('opacity-100', 'ring-2', 'ring-blue-500');
              thumb.classList.add('opacity-60');
            });
          }

          this.classList.remove('opacity-60');
          this.classList.add('opacity-100', 'ring-2', 'ring-blue-500');
        }
      });
    });
  }

  /**
   * Initialize color swatch selection.
   */
  function initColorSwatches() {
    var swatches = document.querySelectorAll('.product-color-swatch');

    swatches.forEach(function (swatch) {
      swatch.addEventListener('click', function () {
        var color = this.getAttribute('data-color');

        // Update swatch states
        var container = this.closest('.flex');
        if (container) {
          container.querySelectorAll('.product-color-swatch').forEach(function (s) {
            s.classList.remove('ring-blue-500');
            s.classList.add('ring-transparent');
          });
        }

        this.classList.remove('ring-transparent');
        this.classList.add('ring-blue-500');

        // Update color dropdown if exists
        var colorSelect = document.querySelector('.product-color-select');
        if (colorSelect && color) {
          colorSelect.value = color.toLowerCase();
        }
      });
    });

    // Sync color dropdown with swatches
    var colorSelect = document.querySelector('.product-color-select');
    if (colorSelect) {
      colorSelect.addEventListener('change', function () {
        var selectedColor = this.value;
        var swatches = document.querySelectorAll('.product-color-swatch');

        swatches.forEach(function (swatch) {
          var swatchColor = swatch.getAttribute('data-color').toLowerCase();
          swatch.classList.remove('ring-blue-500');
          swatch.classList.add('ring-transparent');

          if (swatchColor === selectedColor) {
            swatch.classList.remove('ring-transparent');
            swatch.classList.add('ring-blue-500');
          }
        });
      });
    }
  }

  /**
   * Initialize quantity controls.
   */
  function initQuantityControls() {
    // Minus buttons
    document.querySelectorAll('.qty-minus').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var productId = this.getAttribute('data-product-id');
        var input;
        if (productId) {
          input = document.querySelector('.qty-input[data-product-id="' + productId + '"]');
        } else {
          input = this.closest('.flex').querySelector('.qty-input');
        }

        if (input) {
          var currentVal = parseInt(input.value) || 1;
          if (currentVal > 1) {
            input.value = currentVal - 1;
          }
        }
      });
    });

    // Plus buttons
    document.querySelectorAll('.qty-plus').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var productId = this.getAttribute('data-product-id');
        var input;
        if (productId) {
          input = document.querySelector('.qty-input[data-product-id="' + productId + '"]');
        } else {
          input = this.closest('.flex').querySelector('.qty-input');
        }

        if (input) {
          var currentVal = parseInt(input.value) || 1;
          input.value = currentVal + 1;
        }
      });
    });

    // Validate input
    document.querySelectorAll('.qty-input').forEach(function (input) {
      input.addEventListener('change', function () {
        var val = parseInt(this.value) || 1;
        if (val < 1) {
          this.value = 1;
        }
      });
    });
  }

  /**
   * Initialize shipping accordion.
   */
  function initShippingAccordion() {
    document.querySelectorAll('.shipping-toggle').forEach(function (toggle) {
      toggle.addEventListener('click', function () {
        var container = this.closest('div');
        var content = container.querySelector('.shipping-content');
        var icon = this.querySelector('.shipping-icon');

        if (content) {
          content.classList.toggle('hidden');
        }

        if (icon) {
          icon.classList.toggle('rotate-180');
        }
      });
    });
  }

  /**
   * Initialize product category filters.
   */
  function initProductFilters() {
    var filterButtons = document.querySelectorAll('.product-filter-btn');
    var productsGrid = document.getElementById('products-grid');

    if (!filterButtons.length || !productsGrid) return;

    filterButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var filter = this.getAttribute('data-filter');

        // Update button states
        filterButtons.forEach(function (b) {
          b.classList.remove('bg-blue-500', 'text-white');
          b.classList.add('bg-gray-100', 'dark:bg-slate-800', 'text-gray-600', 'dark:text-gray-300');
        });

        this.classList.remove('bg-gray-100', 'dark:bg-slate-800', 'text-gray-600', 'dark:text-gray-300');
        this.classList.add('bg-blue-500', 'text-white');

        // Filter products
        var products = productsGrid.querySelectorAll('.product-card');
        products.forEach(function (product) {
          var category = product.getAttribute('data-category');

          if (filter === 'all' || category === filter) {
            product.style.display = '';
            product.classList.add('animate-fadeIn');
          } else {
            product.style.display = 'none';
            product.classList.remove('animate-fadeIn');
          }
        });
      });
    });
  }

  /**
   * Check if the device is mobile.
   */
  function isMobile() {
    return window.innerWidth < 1024;
  }

  /**
   * Initialize buy drawer/popup functionality.
   * Shows popup on desktop, drawer on mobile (like language switcher).
   */
  function initBuyDrawer() {
    // Open drawer/popup on Buy button click
    document.querySelectorAll('.buy-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();

        // Don't open if button is disabled
        if (this.disabled) return;

        var productId = this.getAttribute('data-product-id');
        var drawer = document.getElementById('buy-drawer-' + productId);
        var overlay = document.getElementById('buy-drawer-overlay-' + productId);

        if (drawer && overlay) {
          if (isMobile()) {
            openBuyDrawer(drawer, overlay);
          } else {
            openBuyPopup(drawer, overlay);
          }
        }
      });
    });

    // Close drawer/popup on overlay click
    document.querySelectorAll('.buy-drawer-overlay').forEach(function (overlay) {
      overlay.addEventListener('click', function () {
        var drawerId = this.id.replace('buy-drawer-overlay-', 'buy-drawer-');
        var drawer = document.getElementById(drawerId);
        if (drawer) {
          closeBuyModal(drawer, this);
        }
      });
    });

    // Close drawer/popup on close button click
    document.querySelectorAll('.buy-drawer-close').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var productId = this.getAttribute('data-product-id');
        var drawer = document.getElementById('buy-drawer-' + productId);
        var overlay = document.getElementById('buy-drawer-overlay-' + productId);

        if (drawer && overlay) {
          closeBuyModal(drawer, overlay);
        }
      });
    });

    // Close drawer/popup on Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        var openDrawer = document.querySelector('.buy-drawer:not(.hidden)');
        if (openDrawer) {
          var productId = openDrawer.getAttribute('data-product-id');
          var overlay = document.getElementById('buy-drawer-overlay-' + productId);
          closeBuyModal(openDrawer, overlay);
        }
        closeSuccessModal();
      }
    });

    // Handle drag to close
    initDrawerDrag();

    // Handle form submission
    document.querySelectorAll('.buy-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitBuyForm(form);
      });
    });
  }

  /**
   * Submit buy form via API.
   */
  function submitBuyForm(form) {
    var productId = form.getAttribute('data-product-id');
    var submitBtn = form.querySelector('button[type="submit"]');
    var originalText = submitBtn.innerHTML;

    // Clear previous errors
    form.querySelectorAll('.buy-form-error').forEach(function(el) { el.remove(); });
    form.querySelectorAll('.border-red-500').forEach(function(el) {
      el.classList.remove('border-red-500');
      el.classList.add('border-gray-300');
    });

    // Show loading state
    submitBtn.innerHTML = '<svg class="animate-spin w-5 h-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    submitBtn.disabled = true;

    // Get form data
    var formData = new FormData(form);

    // Get product options
    var qtyInput = document.querySelector('.qty-input[data-product-id="' + productId + '"]');
    var colorSelect = document.querySelector('.product-color-select');
    var sizeSelect = document.querySelector('[name="size"]');

    var data = {
      name: formData.get('name'),
      phone: formData.get('phone'),
      address: formData.get('address'),
      productId: productId,
      quantity: qtyInput ? qtyInput.value : '1',
      color: colorSelect ? colorSelect.value : '',
      size: sizeSelect ? sizeSelect.value : ''
    };

    // Send via fetch
    fetch('/api/order/submit', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data)
    })
    .then(function(response) {
      return response.json().then(function(responseData) {
        return { status: response.status, data: responseData };
      });
    })
    .then(function(result) {
      // Re-enable button
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;

      if (result.data.success) {
        // Close buy modal
        var drawer = document.getElementById('buy-drawer-' + productId);
        var overlay = document.getElementById('buy-drawer-overlay-' + productId);
        if (drawer && overlay) {
          closeBuyModal(drawer, overlay);
        }

        // Reset form
        form.reset();

        // Show success modal
        showSuccessModal();
      } else if (result.data.errors) {
        // Show errors
        showFormErrors(form, result.data.errors);
      }
    })
    .catch(function(error) {
      console.error('Error:', error);
      // Re-enable button
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    });
  }

  /**
   * Show form errors.
   */
  function showFormErrors(form, errors) {
    Object.keys(errors).forEach(function(field) {
      var input = form.querySelector('[name="' + field + '"]');
      if (input) {
        input.classList.remove('border-gray-300');
        input.classList.add('border-red-500');

        var errorDiv = document.createElement('div');
        errorDiv.className = 'buy-form-error text-red-500 text-sm mt-1';
        errorDiv.textContent = errors[field];
        input.parentNode.appendChild(errorDiv);
      }
    });
  }

  /**
   * Initialize drawer drag to close functionality.
   */
  function initDrawerDrag() {
    document.querySelectorAll('.buy-drawer-handle').forEach(function (handle) {
      var startY = 0;
      var currentY = 0;
      var isDragging = false;
      var drawer = handle.closest('.buy-drawer');
      var content = drawer ? drawer.querySelector('.buy-drawer-content') : null;

      if (!drawer || !content) return;

      handle.addEventListener('touchstart', function (e) {
        isDragging = true;
        startY = e.touches[0].clientY;
        content.style.transition = 'none';
      });

      handle.addEventListener('touchmove', function (e) {
        if (!isDragging) return;
        currentY = e.touches[0].clientY;
        var diff = currentY - startY;

        if (diff > 0) {
          content.style.transform = 'translateY(' + diff + 'px)';
        }
      });

      handle.addEventListener('touchend', function () {
        if (!isDragging) return;
        isDragging = false;

        var diff = currentY - startY;
        content.style.transition = 'transform 0.3s ease';

        if (diff > 100) {
          // Close drawer
          var productId = drawer.getAttribute('data-product-id');
          var overlay = document.getElementById('buy-drawer-overlay-' + productId);
          closeBuyModal(drawer, overlay);
        } else {
          // Reset position
          content.style.transform = 'translateY(0)';
        }
      });
    });
  }

  /**
   * Open buy drawer with animation (mobile - bottom sheet).
   */
  function openBuyDrawer(drawer, overlay) {
    // Set drawer mode for styling
    drawer.setAttribute('data-mode', 'drawer');

    // Show elements
    overlay.classList.remove('hidden');
    drawer.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Animate in
    requestAnimationFrame(function () {
      overlay.style.opacity = '0';
      overlay.style.transition = 'opacity 0.3s ease';

      var content = drawer.querySelector('.buy-drawer-content');
      if (content) {
        // Reset popup styles if any
        content.style.position = '';
        content.style.top = '';
        content.style.left = '';
        content.style.maxWidth = '';
        content.style.borderRadius = '';

        // Apply drawer styles
        content.style.transform = 'translateY(100%)';
        content.style.transition = 'transform 0.3s cubic-bezier(0.32, 0.72, 0, 1)';
      }

      requestAnimationFrame(function () {
        overlay.style.opacity = '1';
        if (content) {
          content.style.transform = 'translateY(0)';
        }
      });
    });
  }

  /**
   * Open buy popup with animation (desktop - centered modal).
   */
  function openBuyPopup(drawer, overlay) {
    // Set popup mode for styling
    drawer.setAttribute('data-mode', 'popup');

    // Show elements
    overlay.classList.remove('hidden');
    drawer.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    var content = drawer.querySelector('.buy-drawer-content');

    // Apply popup styles
    if (content) {
      content.style.position = 'fixed';
      content.style.top = '50%';
      content.style.left = '50%';
      content.style.transform = 'translate(-50%, -50%) scale(0.95)';
      content.style.maxWidth = '480px';
      content.style.width = '100%';
      content.style.maxHeight = '90vh';
      content.style.borderRadius = '20px';
      content.style.opacity = '0';
      content.style.transition = 'transform 0.3s cubic-bezier(0.32, 0.72, 0, 1), opacity 0.3s ease';
    }

    // Animate in
    requestAnimationFrame(function () {
      overlay.style.opacity = '0';
      overlay.style.transition = 'opacity 0.3s ease';

      requestAnimationFrame(function () {
        overlay.style.opacity = '1';
        if (content) {
          content.style.transform = 'translate(-50%, -50%) scale(1)';
          content.style.opacity = '1';
        }
      });
    });
  }

  /**
   * Close buy modal (drawer or popup) with animation.
   */
  function closeBuyModal(drawer, overlay) {
    var content = drawer.querySelector('.buy-drawer-content');
    var mode = drawer.getAttribute('data-mode') || 'drawer';

    if (overlay) {
      overlay.style.opacity = '0';
    }

    if (content) {
      if (mode === 'popup') {
        content.style.transform = 'translate(-50%, -50%) scale(0.95)';
        content.style.opacity = '0';
      } else {
        content.style.transform = 'translateY(100%)';
      }
    }

    setTimeout(function () {
      drawer.classList.add('hidden');
      drawer.removeAttribute('data-mode');

      if (overlay) {
        overlay.classList.add('hidden');
        overlay.style.opacity = '';
        overlay.style.transition = '';
      }
      document.body.style.overflow = '';

      // Reset content styles
      if (content) {
        content.style.transform = '';
        content.style.transition = '';
        content.style.opacity = '';
        content.style.position = '';
        content.style.top = '';
        content.style.left = '';
        content.style.maxWidth = '';
        content.style.width = '';
        content.style.maxHeight = '';
        content.style.borderRadius = '';
      }
    }, 300);
  }

  /**
   * Create success modal elements (once).
   */
  function createSuccessModal() {
    if (successModalCreated) return;

    var successTitle = 'Order Placed!';
    var successSubtitle = 'Your order has been received';
    var successMessage = 'We will contact you shortly to confirm your order details.';
    var successButtonText = 'Close';

    // Check if drupalSettings has custom messages
    if (typeof drupalSettings !== 'undefined' && drupalSettings.buyForm) {
      successTitle = drupalSettings.buyForm.successTitle || successTitle;
      successSubtitle = drupalSettings.buyForm.successSubtitle || successSubtitle;
      successMessage = drupalSettings.buyForm.successMessage || successMessage;
      successButtonText = drupalSettings.buyForm.successButtonText || successButtonText;
    }

    // Success icon SVG
    var successIconSvg = '<svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

    // Close icon SVG
    var closeIconSvg = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';

    // Create overlay
    successOverlay = document.createElement('div');
    successOverlay.className = 'buy-success-overlay fixed inset-0 bg-black/40 backdrop-blur-sm z-[60]';
    document.body.appendChild(successOverlay);

    // Create desktop modal
    successDesktopModal = document.createElement('div');
    successDesktopModal.className = 'buy-success-desktop fixed inset-0 z-[70] items-center justify-center p-4';
    successDesktopModal.innerHTML = '\
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">\
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-slate-700">\
          <div>\
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">' + successTitle + '</h3>\
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">' + successSubtitle + '</p>\
          </div>\
          <button type="button" class="close-buy-success w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors">\
            ' + closeIconSvg + '\
          </button>\
        </div>\
        <div class="p-6 text-center">\
          <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-500/20 rounded-full flex items-center justify-center">\
            ' + successIconSvg + '\
          </div>\
          <p class="text-gray-600 dark:text-gray-300 mb-6">' + successMessage + '</p>\
          <button type="button" class="close-buy-success w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
            ' + successButtonText + '\
          </button>\
        </div>\
      </div>';
    document.body.appendChild(successDesktopModal);

    // Create mobile drawer
    successMobileDrawer = document.createElement('div');
    successMobileDrawer.className = 'buy-success-drawer fixed inset-x-0 bottom-0 z-[70] lg:hidden';
    successMobileDrawer.setAttribute('role', 'dialog');
    successMobileDrawer.setAttribute('aria-modal', 'true');
    successMobileDrawer.innerHTML = '\
      <div class="buy-success-drawer-content bg-white dark:bg-slate-900 rounded-t-[20px] shadow-2xl max-h-[85vh] flex flex-col">\
        <div class="buy-success-drawer-handle flex justify-center pt-4 pb-2 cursor-grab active:cursor-grabbing">\
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-600 rounded-full"></div>\
        </div>\
        <div class="px-6 pb-4 border-b border-gray-100 dark:border-slate-700">\
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">' + successTitle + '</h3>\
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">' + successSubtitle + '</p>\
        </div>\
        <div class="p-6 text-center">\
          <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-500/20 rounded-full flex items-center justify-center">\
            ' + successIconSvg + '\
          </div>\
          <p class="text-gray-600 dark:text-gray-300 mb-6">' + successMessage + '</p>\
          <button type="button" class="close-buy-success w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
            ' + successButtonText + '\
          </button>\
        </div>\
        <div class="h-6 bg-white dark:bg-slate-900"></div>\
      </div>';
    document.body.appendChild(successMobileDrawer);

    successModalCreated = true;

    // Initialize event listeners for success modal
    initSuccessModalEventListeners();
  }

  /**
   * Initialize success modal event listeners.
   */
  function initSuccessModalEventListeners() {
    // Close button clicks
    document.querySelectorAll('.close-buy-success').forEach(function(btn) {
      btn.addEventListener('click', closeSuccessModal);
    });

    // Overlay click to close
    if (successOverlay) {
      successOverlay.addEventListener('click', closeSuccessModal);
    }

    // Desktop modal click outside content
    if (successDesktopModal) {
      successDesktopModal.addEventListener('click', function(e) {
        if (e.target === successDesktopModal) {
          closeSuccessModal();
        }
      });
    }

    // Mobile drawer drag to close
    var handle = document.querySelector('.buy-success-drawer-handle');
    if (handle) {
      var startY = 0;
      var currentY = 0;
      var isDragging = false;
      var drawer = handle.closest('.buy-success-drawer');

      handle.addEventListener('touchstart', function(e) {
        startY = e.touches[0].clientY;
        isDragging = true;
        if (drawer) drawer.classList.add('dragging');
      }, { passive: true });

      document.addEventListener('touchmove', function(e) {
        if (!isDragging) return;

        currentY = e.touches[0].clientY;
        var diff = currentY - startY;

        if (diff > 0 && drawer) {
          var drawerContent = drawer.querySelector('.buy-success-drawer-content');
          if (drawerContent) drawerContent.style.transform = 'translateY(' + diff + 'px)';
        }
      }, { passive: true });

      document.addEventListener('touchend', function() {
        if (!isDragging) return;

        isDragging = false;
        if (drawer) {
          drawer.classList.remove('dragging');
          var drawerContent = drawer.querySelector('.buy-success-drawer-content');
          if (drawerContent) drawerContent.style.transform = '';
        }

        var diff = currentY - startY;
        if (diff > 100) {
          closeSuccessModal();
        }
      });
    }
  }

  /**
   * Show success modal.
   */
  function showSuccessModal() {
    createSuccessModal();

    if (successOverlay) successOverlay.classList.add('active');

    if (window.innerWidth >= 1024) {
      if (successDesktopModal) successDesktopModal.classList.add('active');
    } else {
      if (successMobileDrawer) successMobileDrawer.classList.add('active');
      document.body.classList.add('drawer-scaled');
    }

    document.body.classList.add('drawer-open');
  }

  /**
   * Close success modal.
   */
  function closeSuccessModal() {
    if (successOverlay) successOverlay.classList.remove('active');
    if (successDesktopModal) successDesktopModal.classList.remove('active');
    if (successMobileDrawer) successMobileDrawer.classList.remove('active');
    document.body.classList.remove('drawer-open', 'drawer-scaled');
  }

  /**
   * Initialize wishlist buttons.
   */
  function initWishlist() {
    document.querySelectorAll('.product-wishlist, .product-wishlist-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var svg = this.querySelector('svg');
        if (svg) {
          var isActive = svg.getAttribute('fill') === 'currentColor';

          if (isActive) {
            svg.setAttribute('fill', 'none');
            svg.classList.remove('text-red-500');
            svg.classList.add('text-gray-600', 'dark:text-gray-300');
          } else {
            svg.setAttribute('fill', 'currentColor');
            svg.classList.remove('text-gray-600', 'dark:text-gray-300');
            svg.classList.add('text-red-500');
          }
        }
      });
    });
  }

  /**
   * Initialize product carousel (Swiper integration).
   */
  function initProductCarousel() {
    var productSwiper = document.querySelector('.product-swiper');

    if (!productSwiper || typeof Swiper === 'undefined') return;

    new Swiper('.product-swiper', {
      slidesPerView: 2,
      spaceBetween: 16,
      navigation: {
        nextEl: '.swiper-button-next-custom',
        prevEl: '.swiper-button-prev-custom',
      },
      pagination: {
        el: '.swiper-pagination-custom',
        clickable: true,
        bulletClass: 'w-2 h-2 rounded-full bg-gray-300 dark:bg-slate-600 cursor-pointer transition-all',
        bulletActiveClass: '!bg-blue-500 !w-6',
      },
      breakpoints: {
        640: {
          slidesPerView: 3,
          spaceBetween: 20,
        },
        1024: {
          slidesPerView: 4,
          spaceBetween: 24,
        },
        1280: {
          slidesPerView: 5,
          spaceBetween: 24,
        },
      },
    });
  }

})();
