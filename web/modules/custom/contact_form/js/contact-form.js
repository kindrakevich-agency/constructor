/**
 * @file
 * Contact form functionality.
 */

(function() {
  'use strict';

  // Modal elements (will be created dynamically)
  var overlay = null;
  var desktopModal = null;
  var mobileDrawer = null;
  var modalCreated = false;

  // Mobile detection
  function isMobile() {
    return window.innerWidth < 1024;
  }

  /**
   * Create the contact success modal and drawer elements (once).
   */
  function createContactModal() {
    if (modalCreated) return;

    var settings = window.drupalSettings && window.drupalSettings.contactForm;
    if (!settings) return;

    var successTitle = settings.successTitle || 'Thank You!';
    var successSubtitle = settings.successSubtitle || 'Your message has been received';
    var successMessage = settings.successMessage || 'Your message has been sent successfully.';
    var successButtonText = settings.successButtonText || 'Close';

    // Success icon SVG
    var successIconSvg = '<svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

    // Close icon SVG
    var closeIconSvg = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';

    // Create overlay
    overlay = document.createElement('div');
    overlay.className = 'contact-modal-overlay fixed inset-0 bg-black/40 backdrop-blur-sm z-[60]';
    document.body.appendChild(overlay);

    // Create desktop modal
    desktopModal = document.createElement('div');
    desktopModal.className = 'contact-modal-desktop fixed inset-0 z-[70] items-center justify-center p-4';
    desktopModal.innerHTML = '\
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">\
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-slate-700">\
          <div>\
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">' + successTitle + '</h3>\
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">' + successSubtitle + '</p>\
          </div>\
          <button type="button" class="close-contact-modal w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors">\
            ' + closeIconSvg + '\
          </button>\
        </div>\
        <div class="p-6 text-center">\
          <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-500/20 rounded-full flex items-center justify-center">\
            ' + successIconSvg + '\
          </div>\
          <p class="text-gray-600 dark:text-gray-300 mb-6">' + successMessage + '</p>\
          <button type="button" class="close-contact-modal w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
            ' + successButtonText + '\
          </button>\
        </div>\
      </div>';
    document.body.appendChild(desktopModal);

    // Create mobile drawer
    mobileDrawer = document.createElement('div');
    mobileDrawer.className = 'contact-drawer fixed inset-x-0 bottom-0 z-[70] lg:hidden';
    mobileDrawer.setAttribute('role', 'dialog');
    mobileDrawer.setAttribute('aria-modal', 'true');
    mobileDrawer.innerHTML = '\
      <div class="contact-drawer-content bg-white dark:bg-slate-900 rounded-t-[20px] shadow-2xl max-h-[85vh] flex flex-col">\
        <div class="contact-drawer-handle flex justify-center pt-4 pb-2 cursor-grab active:cursor-grabbing">\
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
          <button type="button" class="close-contact-modal w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
            ' + successButtonText + '\
          </button>\
        </div>\
        <div class="h-6 bg-white dark:bg-slate-900"></div>\
      </div>';
    document.body.appendChild(mobileDrawer);

    modalCreated = true;

    // Initialize event listeners for newly created elements
    initModalEventListeners();
  }

  // Open desktop modal
  function openDesktopModal() {
    if (!overlay || !desktopModal) return;
    overlay.classList.add('active');
    desktopModal.classList.add('active');
    document.body.classList.add('drawer-open');
  }

  // Open mobile drawer
  function openMobileDrawer() {
    if (!overlay || !mobileDrawer) return;
    overlay.classList.add('active');
    mobileDrawer.classList.add('active');
    document.body.classList.add('drawer-open', 'drawer-scaled');
  }

  // Close all modals
  function closeAll() {
    if (overlay) overlay.classList.remove('active');
    if (desktopModal) desktopModal.classList.remove('active');
    if (mobileDrawer) mobileDrawer.classList.remove('active');
    document.body.classList.remove('drawer-open', 'drawer-scaled');
  }

  // Show success modal (called after form submission)
  window.openContactSuccessModal = function() {
    createContactModal();
    if (isMobile()) {
      openMobileDrawer();
    } else {
      openDesktopModal();
    }
  };

  // Close success modal
  window.closeContactSuccessModal = closeAll;

  // Clear form errors
  function clearFormErrors(form) {
    var errorElements = form.querySelectorAll('.contact-form-error');
    errorElements.forEach(function(el) {
      el.remove();
    });
    var errorInputs = form.querySelectorAll('.border-red-500');
    errorInputs.forEach(function(el) {
      el.classList.remove('border-red-500');
      el.classList.add('border-gray-300');
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
        errorDiv.className = 'contact-form-error text-red-500 text-sm mt-1';
        errorDiv.textContent = errors[field];
        input.parentNode.appendChild(errorDiv);
      }
    });
  }

  // Initialize contact form
  function initContactForm() {
    var form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var submitButton = form.querySelector('.contact-form-submit');
      var originalText = submitButton.value;

      // Clear previous errors
      clearFormErrors(form);

      // Disable button and show loading state
      submitButton.disabled = true;
      submitButton.value = 'Sending...';
      submitButton.classList.add('opacity-75', 'cursor-not-allowed');

      // Collect form data
      var formData = {
        name: form.querySelector('[name="name"]').value,
        email: form.querySelector('[name="email"]').value,
        company: form.querySelector('[name="company"]').value,
        subject: form.querySelector('[name="subject"]').value,
        message: form.querySelector('[name="message"]').value,
        website_url: form.querySelector('[name="website_url"]').value
      };

      // Send via fetch
      fetch('/api/contact-form/submit', {
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
        submitButton.value = originalText;
        submitButton.classList.remove('opacity-75', 'cursor-not-allowed');

        if (result.data.success) {
          // Reset form
          form.reset();
          // Show success modal
          openContactSuccessModal();
        } else if (result.data.errors) {
          // Show errors
          showFormErrors(form, result.data.errors);
        }
      })
      .catch(function(error) {
        console.error('Error:', error);
        // Re-enable button
        submitButton.disabled = false;
        submitButton.value = originalText;
        submitButton.classList.remove('opacity-75', 'cursor-not-allowed');
      });
    });
  }

  // Initialize modal event listeners
  function initModalEventListeners() {
    // Close button clicks
    document.querySelectorAll('.close-contact-modal').forEach(function(btn) {
      btn.addEventListener('click', closeAll);
    });

    // Overlay click to close
    if (overlay) {
      overlay.addEventListener('click', closeAll);
    }

    // Desktop modal click outside content
    if (desktopModal) {
      desktopModal.addEventListener('click', function(e) {
        if (e.target === desktopModal) {
          closeAll();
        }
      });
    }

    // Mobile drawer drag to close
    var handle = document.querySelector('.contact-drawer-handle');
    if (handle) {
      var startY = 0;
      var currentY = 0;
      var isDragging = false;
      var drawer = handle.closest('.contact-drawer');

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
          var drawerContent = drawer.querySelector('.contact-drawer-content');
          if (drawerContent) drawerContent.style.transform = 'translateY(' + diff + 'px)';
        }
      }, { passive: true });

      document.addEventListener('touchend', function() {
        if (!isDragging) return;

        isDragging = false;
        if (drawer) {
          drawer.classList.remove('dragging');
          var drawerContent = drawer.querySelector('.contact-drawer-content');
          if (drawerContent) drawerContent.style.transform = '';
        }

        var diff = currentY - startY;
        if (diff > 100) {
          closeAll();
        }
      });
    }

    // ESC key to close
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeAll();
      }
    });
  }

  // Initialize when DOM is ready
  function init() {
    initContactForm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
