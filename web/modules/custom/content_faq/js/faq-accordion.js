/**
 * @file
 * FAQ Accordion functionality for the FAQ Block.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.faqAccordion = {
    attach: function (context) {
      // FAQ Accordion
      once('faq-accordion', '.faq-accordion-btn', context).forEach(function (btn) {
        // Mark as initialized to prevent theme's fallback JS from double-binding
        btn.setAttribute('data-faq-initialized', 'true');

        btn.addEventListener('click', function () {
          const accordion = btn.closest('.faq-accordion');
          const content = accordion.querySelector('.faq-accordion-content');
          const icon = btn.querySelector('svg');
          const isCurrentlyOpen = !content.classList.contains('hidden');

          // Close all other FAQ accordions in the same block
          const block = btn.closest('[data-block-type="faq"]');
          if (block) {
            block.querySelectorAll('.faq-accordion').forEach(function (other) {
              if (other !== accordion) {
                const otherContent = other.querySelector('.faq-accordion-content');
                const otherIcon = other.querySelector('.faq-accordion-btn svg');
                const otherBtn = other.querySelector('.faq-accordion-btn');

                if (otherContent) {
                  otherContent.classList.add('hidden');
                }
                if (otherIcon) {
                  otherIcon.classList.remove('rotate-180');
                  // Reset to plus icon
                  otherIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>';
                }
                if (otherBtn) {
                  otherBtn.removeAttribute('data-active');
                }
              }
            });
          }

          // Toggle current accordion
          if (content) {
            content.classList.toggle('hidden');
          }

          if (icon) {
            icon.classList.toggle('rotate-180');
            // Toggle between plus and chevron icons
            if (content && !content.classList.contains('hidden')) {
              icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>';
              btn.setAttribute('data-active', 'true');
            } else {
              icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>';
              btn.removeAttribute('data-active');
            }
          }

          // Toggle aria-expanded for accessibility
          btn.setAttribute('aria-expanded', !isCurrentlyOpen);
        });
      });
    }
  };

})(Drupal, once);
