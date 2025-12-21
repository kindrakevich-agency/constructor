/**
 * @file
 * Constructor Install Theme JavaScript.
 */

(function () {
  'use strict';

  /**
   * Initialize installer functionality when DOM is ready.
   */
  document.addEventListener('DOMContentLoaded', function () {
    initDatabaseDriverSwitcher();
    initMultilingualToggle();
    initCardSelection();
    initScrollToErrors();
    initInputFocus();
    initProgressAnimation();
  });

  /**
   * Handle database driver switching on install form.
   * This replaces Drupal's states system which doesn't work during installation.
   */
  function initDatabaseDriverSwitcher() {
    var driverRadios = document.querySelectorAll('input[name="driver"]');
    if (!driverRadios.length) return;

    // Find driver containers by analyzing the form structure
    // Each driver has a container with data-drupal-selector like "edit-drupalmysqldriverdatabasemysql"
    function findDriverContainers() {
      var containers = {};

      driverRadios.forEach(function(radio) {
        var driverValue = radio.value;
        // Convert driver namespace to selector pattern
        // "Drupal\mysql\Driver\Database\mysql" -> "drupalmysqldriverdatabasemysql"
        var selectorPattern = driverValue.toLowerCase().replace(/\\/g, '');

        // Find container by data-drupal-selector attribute
        var container = document.querySelector('[data-drupal-selector="edit-' + selectorPattern + '"]');

        if (container) {
          containers[driverValue] = container;
        }
      });

      return containers;
    }

    var driverContainers = findDriverContainers();

    // If no containers found with data-drupal-selector, try finding by form structure
    if (Object.keys(driverContainers).length === 0) {
      // Find all fieldsets after the driver selection that might contain driver settings
      var form = document.querySelector('form');
      if (form) {
        var fieldsets = form.querySelectorAll('fieldset');
        fieldsets.forEach(function(fieldset) {
          var selector = fieldset.getAttribute('data-drupal-selector') || '';
          driverRadios.forEach(function(radio) {
            var driverValue = radio.value;
            var pattern = driverValue.toLowerCase().replace(/\\/g, '');
            if (selector.indexOf(pattern) > -1) {
              driverContainers[driverValue] = fieldset;
            }
          });
        });
      }
    }

    function updateDriverVisibility() {
      var selectedDriver = document.querySelector('input[name="driver"]:checked');
      if (!selectedDriver) return;

      var selectedValue = selectedDriver.value;

      // Hide all driver containers, show only the selected one
      Object.keys(driverContainers).forEach(function(driverValue) {
        var container = driverContainers[driverValue];
        if (container) {
          if (driverValue === selectedValue) {
            container.style.display = '';
          } else {
            container.style.display = 'none';
          }
        }
      });
    }

    // Run immediately and on change
    updateDriverVisibility();

    driverRadios.forEach(function(radio) {
      radio.addEventListener('change', updateDriverVisibility);
    });
  }

  /**
   * Initialize card-style checkbox/radio selection.
   */
  function initCardSelection() {
    // Handle checkbox cards
    document.querySelectorAll('.checkbox-card, .form-checkboxes .form-item, .form-radios .form-item').forEach(function (card) {
      var checkbox = card.querySelector('input[type="checkbox"], input[type="radio"]');
      if (!checkbox) return;

      // Update visual state on load
      updateCardState(card, checkbox);

      // Handle click on the card
      card.addEventListener('click', function (e) {
        if (e.target === checkbox) return;
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      });

      // Handle change event
      checkbox.addEventListener('change', function () {
        updateCardState(card, checkbox);
      });
    });
  }

  /**
   * Update card visual state based on checkbox state.
   */
  function updateCardState(card, checkbox) {
    if (checkbox.checked) {
      card.classList.add('checkbox-card--checked', 'border-blue-500', 'bg-blue-50/50');
      card.classList.remove('border-gray-200');
    } else {
      card.classList.remove('checkbox-card--checked', 'border-blue-500', 'bg-blue-50/50');
      card.classList.add('border-gray-200');
    }
  }

  /**
   * Scroll to first error message on page load.
   */
  function initScrollToErrors() {
    var errorMessage = document.querySelector('.messages--error');
    if (errorMessage) {
      errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  /**
   * Add focus styles to input elements.
   */
  function initInputFocus() {
    document.querySelectorAll('input, select, textarea').forEach(function (input) {
      input.addEventListener('focus', function () {
        var formItem = this.closest('.form-item');
        if (formItem) {
          formItem.classList.add('form-item--focused');
        }
      });

      input.addEventListener('blur', function () {
        var formItem = this.closest('.form-item');
        if (formItem) {
          formItem.classList.remove('form-item--focused');
        }
      });
    });
  }

  /**
   * Initialize progress bar animations.
   */
  function initProgressAnimation() {
    var progressBars = document.querySelectorAll('.progress__bar, .progress-bar__fill');
    progressBars.forEach(function (bar) {
      // Trigger animation by reading offsetHeight
      bar.offsetHeight;
      bar.style.transition = 'width 0.5s ease-out';
    });
  }

  /**
   * Show loading state on form submit.
   * Only applies to the clicked button, not all submit buttons.
   */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('input[type="submit"], button[type="submit"]');
    if (!btn || !btn.form) return;

    // Store reference to clicked button
    btn.form._clickedButton = btn;
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches('form')) return;

    // Only modify the button that was clicked
    var submitBtn = form._clickedButton;

    if (submitBtn && !submitBtn.hasAttribute('formnovalidate')) {
      // Only show processing on primary buttons, not back buttons
      submitBtn.classList.add('opacity-75', 'cursor-wait');
      if (submitBtn.tagName === 'INPUT') {
        submitBtn.value = submitBtn.value + '...';
      }
    }

    // Clear the reference
    form._clickedButton = null;
  });

  /**
   * Handle multilingual checkbox visibility toggle.
   * Replaces Drupal's states system which doesn't work during installation.
   */
  function initMultilingualToggle() {
    var multilingualCheckbox = document.querySelector('input[data-multilingual-toggle="true"]');
    if (!multilingualCheckbox) return;

    var settingsWrapper = document.getElementById('multilingual-settings-wrapper');
    if (!settingsWrapper) return;

    function toggleMultilingualFields() {
      settingsWrapper.style.display = multilingualCheckbox.checked ? '' : 'none';
    }

    // Listen for changes
    multilingualCheckbox.addEventListener('change', toggleMultilingualFields);
  }

})();
