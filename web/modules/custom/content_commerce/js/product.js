/**
 * @file
 * JavaScript for product interactions.
 */

(function () {
  'use strict';

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', function () {
    initProductGallery();
    initColorSwatches();
    initQuantityControls();
    initShippingAccordion();
    initProductFilters();
    initAddToCart();
    initWishlist();
    initProductCarousel();
  });

  /**
   * Initialize product gallery thumbnail switching.
   */
  function initProductGallery() {
    const thumbnails = document.querySelectorAll('.product-thumbnail');

    thumbnails.forEach(function (thumbnail) {
      thumbnail.addEventListener('click', function () {
        const imageUrl = this.getAttribute('data-image');
        const productId = this.getAttribute('data-product-id');

        // Find the main image
        let mainImage;
        if (productId) {
          mainImage = document.getElementById('product-main-image-' + productId);
        } else {
          mainImage = document.getElementById('product-main-image');
        }

        if (mainImage && imageUrl) {
          mainImage.src = imageUrl;

          // Update thumbnail states
          const container = this.closest('.flex');
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
    const swatches = document.querySelectorAll('.product-color-swatch');

    swatches.forEach(function (swatch) {
      swatch.addEventListener('click', function () {
        const color = this.getAttribute('data-color');

        // Update swatch states
        const container = this.closest('.flex');
        if (container) {
          container.querySelectorAll('.product-color-swatch').forEach(function (s) {
            s.classList.remove('ring-blue-500');
            s.classList.add('ring-transparent');
          });
        }

        this.classList.remove('ring-transparent');
        this.classList.add('ring-blue-500');

        // Update color dropdown if exists
        const colorSelect = document.querySelector('.product-color-select');
        if (colorSelect && color) {
          colorSelect.value = color.toLowerCase();
        }
      });
    });

    // Sync color dropdown with swatches
    const colorSelect = document.querySelector('.product-color-select');
    if (colorSelect) {
      colorSelect.addEventListener('change', function () {
        const selectedColor = this.value;
        const swatches = document.querySelectorAll('.product-color-swatch');

        swatches.forEach(function (swatch) {
          const swatchColor = swatch.getAttribute('data-color').toLowerCase();
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
        const productId = this.getAttribute('data-product-id');
        let input;
        if (productId) {
          input = document.querySelector('.qty-input[data-product-id="' + productId + '"]');
        } else {
          input = this.closest('.flex').querySelector('.qty-input');
        }

        if (input) {
          const currentVal = parseInt(input.value) || 1;
          if (currentVal > 1) {
            input.value = currentVal - 1;
          }
        }
      });
    });

    // Plus buttons
    document.querySelectorAll('.qty-plus').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const productId = this.getAttribute('data-product-id');
        let input;
        if (productId) {
          input = document.querySelector('.qty-input[data-product-id="' + productId + '"]');
        } else {
          input = this.closest('.flex').querySelector('.qty-input');
        }

        if (input) {
          const currentVal = parseInt(input.value) || 1;
          input.value = currentVal + 1;
        }
      });
    });

    // Validate input
    document.querySelectorAll('.qty-input').forEach(function (input) {
      input.addEventListener('change', function () {
        const val = parseInt(this.value) || 1;
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
        const container = this.closest('div');
        const content = container.querySelector('.shipping-content');
        const icon = this.querySelector('.shipping-icon');

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
    const filterButtons = document.querySelectorAll('.product-filter-btn');
    const productsGrid = document.getElementById('products-grid');

    if (!filterButtons.length || !productsGrid) return;

    filterButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const filter = this.getAttribute('data-filter');

        // Update button states
        filterButtons.forEach(function (b) {
          b.classList.remove('bg-blue-500', 'text-white');
          b.classList.add('bg-gray-100', 'dark:bg-slate-800', 'text-gray-600', 'dark:text-gray-300');
        });

        this.classList.remove('bg-gray-100', 'dark:bg-slate-800', 'text-gray-600', 'dark:text-gray-300');
        this.classList.add('bg-blue-500', 'text-white');

        // Filter products
        const products = productsGrid.querySelectorAll('.product-card');
        products.forEach(function (product) {
          const category = product.getAttribute('data-category');

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
   * Initialize add to cart buttons.
   */
  function initAddToCart() {
    document.querySelectorAll('.add-to-cart-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();

        const productId = this.getAttribute('data-product-id');
        const originalText = this.innerHTML;

        // Show loading state
        this.innerHTML = '<svg class="animate-spin w-5 h-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        this.disabled = true;

        // Simulate adding to cart (no actual cart functionality)
        setTimeout(function () {
          btn.innerHTML = '<svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Added!';
          btn.classList.remove('bg-gray-900', 'dark:bg-white', 'bg-blue-500');
          btn.classList.add('bg-green-500', 'text-white');

          setTimeout(function () {
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('bg-green-500');
            btn.classList.add('bg-gray-900', 'dark:bg-white', 'bg-blue-500');
          }, 2000);
        }, 800);
      });
    });
  }

  /**
   * Initialize wishlist buttons.
   */
  function initWishlist() {
    document.querySelectorAll('.product-wishlist, .product-wishlist-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const svg = this.querySelector('svg');
        if (svg) {
          const isActive = svg.getAttribute('fill') === 'currentColor';

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
    const productSwiper = document.querySelector('.product-swiper');

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
