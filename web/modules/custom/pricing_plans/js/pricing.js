/**
 * @file
 * Pricing Plans functionality.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  // Modal elements (created dynamically).
  var formOverlay = null;
  var formDesktopModal = null;
  var formMobileDrawer = null;
  var successOverlay = null;
  var successDesktopModal = null;
  var successMobileDrawer = null;
  var modalCreated = false;
  var successModalCreated = false;
  var currentBillingType = 'annual';
  var selectedPlan = null;

  Drupal.behaviors.pricingPlans = {
    attach: function (context) {
      // Billing toggle.
      once('pricing-billing-toggle', '.billing-toggle', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var isAnnual = btn.id === 'billing-annual';
          currentBillingType = isAnnual ? 'annual' : 'monthly';

          // Update toggle buttons.
          document.querySelectorAll('.billing-toggle').forEach(function (toggleBtn) {
            if (toggleBtn.id === 'billing-annual') {
              if (isAnnual) {
                toggleBtn.classList.add('bg-blue-500', 'text-white');
                toggleBtn.classList.remove('text-gray-600', 'dark:text-gray-400');
              } else {
                toggleBtn.classList.remove('bg-blue-500', 'text-white');
                toggleBtn.classList.add('text-gray-600', 'dark:text-gray-400');
              }
            } else {
              if (!isAnnual) {
                toggleBtn.classList.add('bg-blue-500', 'text-white');
                toggleBtn.classList.remove('text-gray-600', 'dark:text-gray-400');
              } else {
                toggleBtn.classList.remove('bg-blue-500', 'text-white');
                toggleBtn.classList.add('text-gray-600', 'dark:text-gray-400');
              }
            }
          });

          // Update prices.
          document.querySelectorAll('.price-amount').forEach(function (priceEl) {
            var monthly = priceEl.getAttribute('data-monthly');
            var annual = priceEl.getAttribute('data-annual');
            var symbol = priceEl.getAttribute('data-symbol') || '$';
            priceEl.textContent = symbol + (isAnnual ? annual : monthly);
          });
        });
      });

      // Plan CTA buttons.
      once('pricing-cta', '.pricing-cta-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          selectedPlan = {
            id: btn.getAttribute('data-plan-id'),
            title: btn.getAttribute('data-plan-title'),
            price: btn.getAttribute('data-plan-price'),
            billingType: currentBillingType
          };
          openFormModal();
        });
      });

      // ESC key to close.
      once('pricing-esc-close', 'body', context).forEach(function () {
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            closeFormModal();
            closeSuccessModal();
          }
        });
      });
    }
  };

  /**
   * Create form modal elements (once).
   */
  function createFormModal() {
    if (modalCreated) return;

    var settings = drupalSettings.pricingPlans || {};
    var labels = settings.formLabels || {};

    var closeIconSvg = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';

    var formHtml = '\
      <form data-pricing-form class="space-y-4">\
        <input type="hidden" name="planId" value="">\
        <input type="hidden" name="planTitle" value="">\
        <input type="hidden" name="planPrice" value="">\
        <input type="hidden" name="billingType" value="">\
        <div>\
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (labels.name || 'Name') + ' *</label>\
          <input type="text" name="name" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" placeholder="' + (labels.name || 'Name') + '">\
        </div>\
        <div>\
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (labels.email || 'Email') + ' *</label>\
          <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" placeholder="' + (labels.email || 'Email') + '">\
        </div>\
        <div>\
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (labels.phone || 'Phone') + '</label>\
          <input type="tel" name="phone" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" placeholder="' + (labels.phone || 'Phone') + '">\
        </div>\
        <div>\
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (labels.company || 'Company') + '</label>\
          <input type="text" name="company" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" placeholder="' + (labels.company || 'Company') + '">\
        </div>\
        <div>\
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + (labels.message || 'Message') + '</label>\
          <textarea name="message" rows="3" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-none" placeholder="' + (labels.message || 'Message') + '"></textarea>\
        </div>\
        <button type="submit" class="w-full py-3 px-6 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
          ' + (labels.submit || 'Submit') + '\
        </button>\
      </form>';

    // Create overlay.
    formOverlay = document.createElement('div');
    formOverlay.className = 'pricing-form-overlay fixed inset-0 bg-black/40 backdrop-blur-sm z-[60]';
    document.body.appendChild(formOverlay);

    // Create desktop modal.
    formDesktopModal = document.createElement('div');
    formDesktopModal.className = 'pricing-form-desktop fixed inset-0 z-[70] items-center justify-center p-4';
    formDesktopModal.innerHTML = '\
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">\
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-slate-700">\
          <div>\
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">' + (labels.modalTitle || 'Get Started') + '</h3>\
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 pricing-plan-info"></p>\
          </div>\
          <button type="button" class="close-pricing-form w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors">\
            ' + closeIconSvg + '\
          </button>\
        </div>\
        <div class="p-6 max-h-[70vh] overflow-y-auto">\
          ' + formHtml + '\
        </div>\
      </div>';
    document.body.appendChild(formDesktopModal);

    // Create mobile drawer.
    formMobileDrawer = document.createElement('div');
    formMobileDrawer.className = 'pricing-form-drawer fixed inset-x-0 bottom-0 z-[70] lg:hidden';
    formMobileDrawer.setAttribute('role', 'dialog');
    formMobileDrawer.setAttribute('aria-modal', 'true');
    formMobileDrawer.innerHTML = '\
      <div class="pricing-form-drawer-content bg-white dark:bg-slate-900 rounded-t-[20px] shadow-2xl max-h-[90vh] flex flex-col">\
        <div class="pricing-form-drawer-handle flex justify-center pt-4 pb-2 cursor-grab active:cursor-grabbing">\
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-600 rounded-full"></div>\
        </div>\
        <div class="px-6 pb-4 border-b border-gray-100 dark:border-slate-700">\
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">' + (labels.modalTitle || 'Get Started') + '</h3>\
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 pricing-plan-info"></p>\
        </div>\
        <div class="p-6 flex-1 overflow-y-auto">\
          ' + formHtml + '\
        </div>\
        <div class="h-6 bg-white dark:bg-slate-900"></div>\
      </div>';
    document.body.appendChild(formMobileDrawer);

    modalCreated = true;
    initFormModalEventListeners();
  }

  /**
   * Initialize form modal event listeners.
   */
  function initFormModalEventListeners() {
    // Close button clicks.
    document.querySelectorAll('.close-pricing-form').forEach(function (btn) {
      btn.addEventListener('click', closeFormModal);
    });

    // Overlay click to close.
    if (formOverlay) {
      formOverlay.addEventListener('click', closeFormModal);
    }

    // Desktop modal click outside content.
    if (formDesktopModal) {
      formDesktopModal.addEventListener('click', function (e) {
        if (e.target === formDesktopModal) {
          closeFormModal();
        }
      });
    }

    // Mobile drawer drag to close.
    var handle = document.querySelector('.pricing-form-drawer-handle');
    if (handle) {
      var startY = 0;
      var currentY = 0;
      var isDragging = false;
      var drawer = handle.closest('.pricing-form-drawer');

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
          var drawerContent = drawer.querySelector('.pricing-form-drawer-content');
          if (drawerContent) drawerContent.style.transform = 'translateY(' + diff + 'px)';
        }
      }, { passive: true });

      document.addEventListener('touchend', function () {
        if (!isDragging) return;

        isDragging = false;
        if (drawer) {
          drawer.classList.remove('dragging');
          var drawerContent = drawer.querySelector('.pricing-form-drawer-content');
          if (drawerContent) drawerContent.style.transform = '';
        }

        var diff = currentY - startY;
        if (diff > 100) {
          closeFormModal();
        }
      });
    }

    // Form submission.
    document.querySelectorAll('[data-pricing-form]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitPricingForm(form);
      });
    });
  }

  /**
   * Open form modal.
   */
  function openFormModal() {
    createFormModal();

    // Update plan info in modal.
    if (selectedPlan) {
      document.querySelectorAll('.pricing-plan-info').forEach(function (el) {
        el.textContent = selectedPlan.title + ' - ' + selectedPlan.price;
      });

      // Update hidden fields.
      document.querySelectorAll('[data-pricing-form]').forEach(function (form) {
        var planIdField = form.querySelector('[name="planId"]');
        var planTitleField = form.querySelector('[name="planTitle"]');
        var planPriceField = form.querySelector('[name="planPrice"]');
        var billingTypeField = form.querySelector('[name="billingType"]');

        if (planIdField) planIdField.value = selectedPlan.id;
        if (planTitleField) planTitleField.value = selectedPlan.title;
        if (planPriceField) planPriceField.value = selectedPlan.price;
        if (billingTypeField) billingTypeField.value = selectedPlan.billingType;
      });
    }

    if (formOverlay) formOverlay.classList.add('active');

    if (window.innerWidth >= 1024) {
      if (formDesktopModal) formDesktopModal.classList.add('active');
    } else {
      if (formMobileDrawer) formMobileDrawer.classList.add('active');
      document.body.classList.add('drawer-scaled');
    }

    document.body.classList.add('drawer-open');
  }

  /**
   * Close form modal.
   */
  function closeFormModal() {
    if (formOverlay) formOverlay.classList.remove('active');
    if (formDesktopModal) formDesktopModal.classList.remove('active');
    if (formMobileDrawer) formMobileDrawer.classList.remove('active');
    document.body.classList.remove('drawer-open', 'drawer-scaled');
  }

  /**
   * Submit pricing form.
   */
  function submitPricingForm(form) {
    var submitButton = form.querySelector('button[type="submit"]');
    var originalText = submitButton.innerHTML;

    // Clear previous errors.
    form.querySelectorAll('.pricing-form-error').forEach(function (el) { el.remove(); });
    form.querySelectorAll('.border-red-500').forEach(function (el) {
      el.classList.remove('border-red-500');
      el.classList.add('border-gray-300');
    });

    // Disable button and show loading state.
    submitButton.disabled = true;
    submitButton.innerHTML = '<svg class="animate-spin w-5 h-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    // Collect form data.
    var formData = {
      name: form.querySelector('[name="name"]') ? form.querySelector('[name="name"]').value : '',
      email: form.querySelector('[name="email"]') ? form.querySelector('[name="email"]').value : '',
      phone: form.querySelector('[name="phone"]') ? form.querySelector('[name="phone"]').value : '',
      company: form.querySelector('[name="company"]') ? form.querySelector('[name="company"]').value : '',
      message: form.querySelector('[name="message"]') ? form.querySelector('[name="message"]').value : '',
      planId: form.querySelector('[name="planId"]') ? form.querySelector('[name="planId"]').value : '',
      planTitle: form.querySelector('[name="planTitle"]') ? form.querySelector('[name="planTitle"]').value : '',
      planPrice: form.querySelector('[name="planPrice"]') ? form.querySelector('[name="planPrice"]').value : '',
      billingType: form.querySelector('[name="billingType"]') ? form.querySelector('[name="billingType"]').value : 'annual'
    };

    // Send via fetch.
    fetch('/api/pricing/submit', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    })
    .then(function (response) {
      return response.json().then(function (data) {
        return { status: response.status, data: data };
      });
    })
    .then(function (result) {
      // Re-enable button.
      submitButton.disabled = false;
      submitButton.innerHTML = originalText;

      if (result.data.success) {
        // Reset form.
        form.reset();
        // Close form modal.
        closeFormModal();
        // Show success modal.
        showSuccessModal();
      } else if (result.data.errors) {
        // Show errors.
        showFormErrors(form, result.data.errors);
      }
    })
    .catch(function (error) {
      console.error('Error:', error);
      // Re-enable button.
      submitButton.disabled = false;
      submitButton.innerHTML = originalText;
    });
  }

  /**
   * Show form errors.
   */
  function showFormErrors(form, errors) {
    Object.keys(errors).forEach(function (field) {
      var input = form.querySelector('[name="' + field + '"]');
      if (input) {
        input.classList.remove('border-gray-300');
        input.classList.add('border-red-500');

        var errorDiv = document.createElement('div');
        errorDiv.className = 'pricing-form-error text-red-500 text-sm mt-1';
        errorDiv.textContent = errors[field];
        input.parentNode.appendChild(errorDiv);
      }
    });
  }

  /**
   * Create success modal elements (once).
   */
  function createSuccessModal() {
    if (successModalCreated) return;

    var settings = drupalSettings.pricingPlans || {};
    var successTitle = settings.successTitle || 'Thank You!';
    var successSubtitle = settings.successSubtitle || 'Your request has been received';
    var successMessage = settings.successMessage || 'We will contact you shortly.';
    var successButtonText = settings.successButtonText || 'Close';

    var successIconSvg = '<svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    var closeIconSvg = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';

    // Create overlay.
    successOverlay = document.createElement('div');
    successOverlay.className = 'pricing-success-overlay fixed inset-0 bg-black/40 backdrop-blur-sm z-[80]';
    document.body.appendChild(successOverlay);

    // Create desktop modal.
    successDesktopModal = document.createElement('div');
    successDesktopModal.className = 'pricing-success-desktop fixed inset-0 z-[90] items-center justify-center p-4';
    successDesktopModal.innerHTML = '\
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">\
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-slate-700">\
          <div>\
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">' + successTitle + '</h3>\
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">' + successSubtitle + '</p>\
          </div>\
          <button type="button" class="close-pricing-success w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors">\
            ' + closeIconSvg + '\
          </button>\
        </div>\
        <div class="p-6 text-center">\
          <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-500/20 rounded-full flex items-center justify-center">\
            ' + successIconSvg + '\
          </div>\
          <p class="text-gray-600 dark:text-gray-300 mb-6">' + successMessage + '</p>\
          <button type="button" class="close-pricing-success w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
            ' + successButtonText + '\
          </button>\
        </div>\
      </div>';
    document.body.appendChild(successDesktopModal);

    // Create mobile drawer.
    successMobileDrawer = document.createElement('div');
    successMobileDrawer.className = 'pricing-success-drawer fixed inset-x-0 bottom-0 z-[90] lg:hidden';
    successMobileDrawer.setAttribute('role', 'dialog');
    successMobileDrawer.setAttribute('aria-modal', 'true');
    successMobileDrawer.innerHTML = '\
      <div class="pricing-success-drawer-content bg-white dark:bg-slate-900 rounded-t-[20px] shadow-2xl max-h-[85vh] flex flex-col">\
        <div class="pricing-success-drawer-handle flex justify-center pt-4 pb-2 cursor-grab active:cursor-grabbing">\
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
          <button type="button" class="close-pricing-success w-full py-3 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition-colors">\
            ' + successButtonText + '\
          </button>\
        </div>\
        <div class="h-6 bg-white dark:bg-slate-900"></div>\
      </div>';
    document.body.appendChild(successMobileDrawer);

    successModalCreated = true;
    initSuccessModalEventListeners();
  }

  /**
   * Initialize success modal event listeners.
   */
  function initSuccessModalEventListeners() {
    // Close button clicks.
    document.querySelectorAll('.close-pricing-success').forEach(function (btn) {
      btn.addEventListener('click', closeSuccessModal);
    });

    // Overlay click to close.
    if (successOverlay) {
      successOverlay.addEventListener('click', closeSuccessModal);
    }

    // Desktop modal click outside content.
    if (successDesktopModal) {
      successDesktopModal.addEventListener('click', function (e) {
        if (e.target === successDesktopModal) {
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
