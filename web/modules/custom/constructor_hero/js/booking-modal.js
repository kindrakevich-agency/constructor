/**
 * @file
 * Booking Modal functionality.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  // Success modal elements (created dynamically).
  var successOverlay = null;
  var successDesktopModal = null;
  var successMobileDrawer = null;
  var successModalCreated = false;

  Drupal.behaviors.bookingModal = {
    attach: function (context) {
      // Move modal elements to body to escape containing blocks
      once('booking-modal-move', '.booking-modal-overlay, .booking-modal-desktop, .booking-drawer', context).forEach(function (el) {
        document.body.appendChild(el);
      });

      // Move header button to the header if it exists
      once('booking-header-button-move', '[data-booking-header-button]', context).forEach(function (wrapper) {
        var button = wrapper.querySelector('.open-booking-modal');
        if (button) {
          // Find the header's right section (before mobile menu button)
          var headerButton = document.querySelector('header .open-booking-modal');
          if (!headerButton) {
            // Clone button to header if not already there
            var headerRightSection = document.querySelector('header .flex.items-center.gap-4');
            if (headerRightSection) {
              var clonedButton = button.cloneNode(true);
              clonedButton.classList.add('hidden', 'lg:inline-flex');
              // Insert before mobile menu button
              var mobileMenuBtn = headerRightSection.querySelector('#open-mobile-menu');
              if (mobileMenuBtn) {
                headerRightSection.insertBefore(clonedButton, mobileMenuBtn);
              } else {
                headerRightSection.appendChild(clonedButton);
              }
              // Re-attach click handler to cloned button
              clonedButton.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openBookingModal();
              });
            }
          }
        }
        // Remove the wrapper
        wrapper.remove();
      });

      // Get modal elements
      var overlay = document.querySelector('.booking-modal-overlay');
      var desktopModal = document.querySelector('.booking-modal-desktop');
      var mobileDrawer = document.querySelector('.booking-drawer');

      if (!overlay || !desktopModal) {
        return;
      }

      // Open modal triggers
      once('booking-modal-open', '.open-booking-modal', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          openBookingModal();
        });
      });

      // Close modal button
      once('booking-modal-close', '.close-booking-modal, [data-booking-close]', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          closeBookingModal();
        });
      });

      // Overlay click to close
      once('booking-overlay-close', '.booking-modal-overlay', context).forEach(function (el) {
        el.addEventListener('click', function () {
          closeBookingModal();
        });
      });

      // Desktop modal click outside
      once('booking-modal-outside', '.booking-modal-desktop', context).forEach(function (el) {
        el.addEventListener('click', function (e) {
          if (e.target === el) {
            closeBookingModal();
          }
        });
      });

      // Mobile drawer drag to close
      once('booking-drawer-drag', '.booking-drawer-handle', context).forEach(function (handle) {
        var startY = 0;
        var currentY = 0;
        var isDragging = false;
        var drawer = handle.closest('.booking-drawer');

        handle.addEventListener('touchstart', function (e) {
          startY = e.touches[0].clientY;
          isDragging = true;
          if (drawer) drawer.classList.add('dragging');
        }, { passive: true });

        document.addEventListener('touchmove', function (e) {
          if (!isDragging) return;

          currentY = e.touches[0].clientY;
          var diff = currentY - startY;

          if (diff > 0 && drawer) {
            var drawerContent = drawer.querySelector('.booking-drawer-content');
            if (drawerContent) drawerContent.style.transform = 'translateY(' + diff + 'px)';
          }
        }, { passive: true });

        document.addEventListener('touchend', function () {
          if (!isDragging) return;

          isDragging = false;
          if (drawer) {
            drawer.classList.remove('dragging');
            var drawerContent = drawer.querySelector('.booking-drawer-content');
            if (drawerContent) drawerContent.style.transform = '';
          }

          var diff = currentY - startY;
          if (diff > 100) {
            closeBookingModal();
          }
        });
      });

      // ESC key to close
      once('booking-esc-close', 'body', context).forEach(function () {
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            closeBookingModal();
            closeSuccessModal();
          }
        });
      });

      // Form submission handler
      once('booking-form-submit', '[data-booking-form], [data-booking-form-mobile]', context).forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          submitBookingForm(form);
        });
      });

      // Helper functions
      function openBookingModal() {
        if (overlay) overlay.classList.add('active');

        // Check viewport width
        if (window.innerWidth >= 1024) {
          if (desktopModal) desktopModal.classList.add('active');
        } else {
          if (mobileDrawer) mobileDrawer.classList.add('active');
        }

        document.body.classList.add('booking-modal-open');

        // Pre-fill field from hero input if available
        var heroInput = document.querySelector('.hero-input');
        if (heroInput && heroInput.value.trim()) {
          var fillType = heroInput.dataset.fillType || 'email';
          var inputValue = heroInput.value.trim();

          // Fill the appropriate field in both desktop and mobile forms
          var forms = document.querySelectorAll('[data-booking-form], [data-booking-form-mobile]');
          forms.forEach(function(form) {
            var targetField = form.querySelector('[name="' + fillType + '"]');
            if (targetField) {
              targetField.value = inputValue;
            }
          });
        }
      }

      function closeBookingModal() {
        if (overlay) overlay.classList.remove('active');
        if (desktopModal) desktopModal.classList.remove('active');
        if (mobileDrawer) mobileDrawer.classList.remove('active');
        document.body.classList.remove('booking-modal-open');
      }

      // Form submission
      function submitBookingForm(form) {
        var submitButton = form.querySelector('button[type="submit"]');
        var originalText = submitButton.innerHTML;

        // Clear previous errors
        form.querySelectorAll('.booking-form-error').forEach(function(el) { el.remove(); });
        form.querySelectorAll('.border-red-500').forEach(function(el) {
          el.classList.remove('border-red-500');
          el.classList.add('border-gray-300');
        });

        // Disable button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<svg class="animate-spin w-5 h-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

        // Collect form data
        var formData = {
          name: form.querySelector('[name="name"]') ? form.querySelector('[name="name"]').value : '',
          email: form.querySelector('[name="email"]') ? form.querySelector('[name="email"]').value : '',
          phone: form.querySelector('[name="phone"]') ? form.querySelector('[name="phone"]').value : '',
          company: form.querySelector('[name="company"]') ? form.querySelector('[name="company"]').value : '',
          message: form.querySelector('[name="message"]') ? form.querySelector('[name="message"]').value : '',
          fields: drupalSettings.bookingModal ? drupalSettings.bookingModal.fields : {}
        };

        // Send via fetch
        fetch('/api/booking/submit', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(formData)
        })
        .then(function(response) {
          return response.json().then(function(data) {
            return { status: response.status, data: data };
          });
        })
        .then(function(result) {
          // Re-enable button
          submitButton.disabled = false;
          submitButton.innerHTML = originalText;

          if (result.data.success) {
            // Reset form
            form.reset();
            // Close booking modal
            closeBookingModal();
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
          submitButton.disabled = false;
          submitButton.innerHTML = originalText;
        });
      }

      // Show form errors
      function showFormErrors(form, errors) {
        Object.keys(errors).forEach(function(field) {
          var input = form.querySelector('[name="' + field + '"]');
          if (input) {
            input.classList.remove('border-gray-300');
            input.classList.add('border-red-500');

            var errorDiv = document.createElement('div');
            errorDiv.className = 'booking-form-error text-red-500 text-sm mt-1';
            errorDiv.textContent = errors[field];
            input.parentNode.appendChild(errorDiv);
          }
        });
      }
    }
  };

  /**
   * Create success modal elements (once).
   */
  function createSuccessModal() {
    if (successModalCreated) return;

    var settings = drupalSettings.bookingModal;
    if (!settings) return;

    var successTitle = settings.successTitle || 'Thank You!';
    var successSubtitle = settings.successSubtitle || 'Your request has been received';
    var successMessage = settings.successMessage || 'We will contact you shortly.';
    var successButtonText = settings.successButtonText || 'Close';

    // Success icon SVG
    var successIconSvg = '<svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

    // Close icon SVG
    var closeIconSvg = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';

    // Create overlay
    successOverlay = document.createElement('div');
    successOverlay.className = 'booking-success-overlay fixed inset-0 bg-black/40 backdrop-blur-sm z-[60]';
    document.body.appendChild(successOverlay);

    // Create desktop modal
    successDesktopModal = document.createElement('div');
    successDesktopModal.className = 'booking-success-desktop fixed inset-0 z-[70] items-center justify-center p-4';
    successDesktopModal.innerHTML = '\
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">\
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-slate-700">\
          <div>\
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">' + successTitle + '</h3>\
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">' + successSubtitle + '</p>\
          </div>\
          <button type="button" class="close-booking-success w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors">\
            ' + closeIconSvg + '\
          </button>\
        </div>\
        <div class="p-6 text-center">\
          <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-500/20 rounded-full flex items-center justify-center">\
            ' + successIconSvg + '\
          </div>\
          <p class="text-gray-600 dark:text-gray-300 mb-6">' + successMessage + '</p>\
          <button type="button" class="close-booking-success w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
            ' + successButtonText + '\
          </button>\
        </div>\
      </div>';
    document.body.appendChild(successDesktopModal);

    // Create mobile drawer
    successMobileDrawer = document.createElement('div');
    successMobileDrawer.className = 'booking-success-drawer fixed inset-x-0 bottom-0 z-[70] lg:hidden';
    successMobileDrawer.setAttribute('role', 'dialog');
    successMobileDrawer.setAttribute('aria-modal', 'true');
    successMobileDrawer.innerHTML = '\
      <div class="booking-success-drawer-content bg-white dark:bg-slate-900 rounded-t-[20px] shadow-2xl max-h-[85vh] flex flex-col">\
        <div class="booking-success-drawer-handle flex justify-center pt-4 pb-2 cursor-grab active:cursor-grabbing">\
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
          <button type="button" class="close-booking-success w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
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
    document.querySelectorAll('.close-booking-success').forEach(function(btn) {
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
    var handle = document.querySelector('.booking-success-drawer-handle');
    if (handle) {
      var startY = 0;
      var currentY = 0;
      var isDragging = false;
      var drawer = handle.closest('.booking-success-drawer');

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
          var drawerContent = drawer.querySelector('.booking-success-drawer-content');
          if (drawerContent) drawerContent.style.transform = 'translateY(' + diff + 'px)';
        }
      }, { passive: true });

      document.addEventListener('touchend', function() {
        if (!isDragging) return;

        isDragging = false;
        if (drawer) {
          drawer.classList.remove('dragging');
          var drawerContent = drawer.querySelector('.booking-success-drawer-content');
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

})(Drupal, drupalSettings, once);
