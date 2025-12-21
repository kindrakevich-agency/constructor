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
    initCardSelection();
    initScrollToErrors();
    initInputFocus();
    initProgressAnimation();
  });

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
   */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches('form')) return;

    var submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.classList.add('opacity-75', 'cursor-wait');

      // Add loading spinner
      var originalValue = submitBtn.value || submitBtn.textContent;
      if (submitBtn.tagName === 'INPUT') {
        submitBtn.value = 'Processing...';
      } else {
        submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>' + originalValue;
      }
    }
  });

})();
