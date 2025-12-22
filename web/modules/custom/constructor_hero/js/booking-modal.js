/**
 * @file
 * Booking Modal functionality.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.bookingModal = {
    attach: function (context) {
      // Move modal elements to body to escape containing blocks
      once('booking-modal-move', '.booking-modal-overlay, .booking-modal-desktop, .booking-drawer', context).forEach(function (el) {
        document.body.appendChild(el);
      });

      // Move header button to the header if it exists
      once('booking-header-button-move', '[data-booking-header-button]', context).forEach(function (wrapper) {
        const button = wrapper.querySelector('.open-booking-modal');
        if (button) {
          // Find the header's right section (before mobile menu button)
          const headerButton = document.querySelector('header .open-booking-modal');
          if (!headerButton) {
            // Clone button to header if not already there
            const headerRightSection = document.querySelector('header .flex.items-center.gap-4');
            if (headerRightSection) {
              const clonedButton = button.cloneNode(true);
              clonedButton.classList.add('hidden', 'lg:inline-flex');
              // Insert before mobile menu button
              const mobileMenuBtn = headerRightSection.querySelector('#open-mobile-menu');
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
      const overlay = document.querySelector('.booking-modal-overlay');
      const desktopModal = document.querySelector('.booking-modal-desktop');
      const mobileDrawer = document.querySelector('.booking-drawer');

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
        let startY = 0;
        let currentY = 0;
        let isDragging = false;
        const drawer = handle.closest('.booking-drawer');

        handle.addEventListener('touchstart', function (e) {
          startY = e.touches[0].clientY;
          isDragging = true;
          if (drawer) drawer.classList.add('dragging');
        }, { passive: true });

        document.addEventListener('touchmove', function (e) {
          if (!isDragging) return;

          currentY = e.touches[0].clientY;
          const diff = currentY - startY;

          if (diff > 0 && drawer) {
            const drawerContent = drawer.querySelector('.booking-drawer-content');
            if (drawerContent) drawerContent.style.transform = 'translateY(' + diff + 'px)';
          }
        }, { passive: true });

        document.addEventListener('touchend', function () {
          if (!isDragging) return;

          isDragging = false;
          if (drawer) {
            drawer.classList.remove('dragging');
            const drawerContent = drawer.querySelector('.booking-drawer-content');
            if (drawerContent) drawerContent.style.transform = '';
          }

          const diff = currentY - startY;
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
          }
        });
      });

      // Form submission handler
      once('booking-form-submit', '[data-booking-form], [data-booking-form-mobile]', context).forEach(function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          // TODO: Handle form submission (AJAX, etc.)
          alert(Drupal.t('Thank you! We will contact you shortly.'));
          closeBookingModal();
          form.reset();
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
      }

      function closeBookingModal() {
        if (overlay) overlay.classList.remove('active');
        if (desktopModal) desktopModal.classList.remove('active');
        if (mobileDrawer) mobileDrawer.classList.remove('active');
        document.body.classList.remove('booking-modal-open');
      }
    }
  };

})(Drupal, once);
