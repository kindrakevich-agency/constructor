/**
 * @file
 * Accordion component functionality for mobile menu and methods sections.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.constructorAccordion = {
    attach: function (context) {
      // Mobile Accordion
      once('mobile-accordion', '.mobile-accordion-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          const content = btn.nextElementSibling;
          const icon = btn.querySelector('svg');

          if (content) {
            content.classList.toggle('hidden');
          }
          if (icon) {
            icon.classList.toggle('rotate-180');
          }
        });
      });

      // Methods Accordion (exclusive - only one open at a time)
      once('methods-accordion', '.methods-accordion-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          const accordion = btn.closest('.methods-accordion');
          const content = accordion.querySelector('.methods-accordion-content');
          const icon = btn.querySelector('svg');
          const numberSpan = btn.querySelector('span span:first-child');

          // Close all other accordions
          document.querySelectorAll('.methods-accordion').forEach(function (other) {
            if (other !== accordion) {
              const otherContent = other.querySelector('.methods-accordion-content');
              const otherIcon = other.querySelector('svg');
              const otherNumber = other.querySelector('.methods-accordion-btn span span:first-child');

              if (otherContent) otherContent.classList.add('hidden');
              if (otherIcon) otherIcon.classList.remove('rotate-180');
              if (otherNumber) {
                otherNumber.classList.remove('bg-blue-500', 'text-white');
                otherNumber.classList.add('bg-gray-100', 'dark:bg-slate-700', 'text-gray-600', 'dark:text-gray-400');
              }
            }
          });

          // Toggle current accordion
          if (content) content.classList.toggle('hidden');
          if (icon) icon.classList.toggle('rotate-180');

          if (numberSpan) {
            if (content && !content.classList.contains('hidden')) {
              numberSpan.classList.remove('bg-gray-100', 'dark:bg-slate-700', 'text-gray-600', 'dark:text-gray-400');
              numberSpan.classList.add('bg-blue-500', 'text-white');
            } else {
              numberSpan.classList.add('bg-gray-100', 'dark:bg-slate-700', 'text-gray-600', 'dark:text-gray-400');
              numberSpan.classList.remove('bg-blue-500', 'text-white');
            }
          }
        });
      });

      // Initialize first methods accordion as open
      once('methods-accordion-init', 'body', context).forEach(function () {
        const firstAccordion = document.querySelector('.methods-accordion');
        if (firstAccordion) {
          const firstNumber = firstAccordion.querySelector('.methods-accordion-btn span span:first-child');
          if (firstNumber) {
            firstNumber.classList.remove('bg-gray-100', 'dark:bg-slate-700', 'text-gray-600', 'dark:text-gray-400');
            firstNumber.classList.add('bg-blue-500', 'text-white');
          }
        }
      });

      // FAQ Accordion (fallback for example page when content_faq module is not installed)
      // The content_faq module provides its own enhanced version of this functionality.
      once('faq-accordion-theme', '.faq-accordion-btn', context).forEach(function (btn) {
        // Skip if module's JS already handled this
        if (btn.hasAttribute('data-faq-initialized')) {
          return;
        }

        btn.addEventListener('click', function () {
          const accordion = btn.closest('.faq-accordion');
          const content = accordion.querySelector('.faq-accordion-content');
          const icon = btn.querySelector('svg');

          if (content) {
            content.classList.toggle('hidden');
          }
          if (icon) {
            icon.classList.toggle('rotate-180');
          }
        });
      });
    }
  };

})(Drupal, once);
