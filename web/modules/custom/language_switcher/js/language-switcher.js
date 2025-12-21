/**
 * @file
 * Language Switcher functionality.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.languageSwitcher = {
    attach: function (context) {
      // Elements
      const overlay = document.querySelector('.language-modal-overlay');
      const desktopModal = document.querySelector('.language-modal-desktop');
      const mobileDrawer = document.querySelector('.language-drawer');

      // Desktop modal triggers
      once('language-modal-open', '.open-language-modal', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          openDesktopModal();
        });
      });

      // Mobile drawer triggers
      once('language-drawer-open', '.open-language-drawer', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          openMobileDrawer();
        });
      });

      // Close modal buttons
      once('language-modal-close', '.close-language-modal', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          closeAll();
        });
      });

      // Overlay click to close
      once('language-overlay-close', '.language-modal-overlay', context).forEach(function (el) {
        el.addEventListener('click', function () {
          closeAll();
        });
      });

      // Desktop modal click outside
      once('language-modal-outside', '.language-modal-desktop', context).forEach(function (el) {
        el.addEventListener('click', function (e) {
          if (e.target === el) {
            closeAll();
          }
        });
      });

      // Mobile drawer drag to close
      once('language-drawer-drag', '.language-drawer-handle', context).forEach(function (handle) {
        let startY = 0;
        let currentY = 0;
        let isDragging = false;

        handle.addEventListener('touchstart', function (e) {
          startY = e.touches[0].clientY;
          isDragging = true;
          mobileDrawer.classList.add('dragging');
        }, { passive: true });

        document.addEventListener('touchmove', function (e) {
          if (!isDragging) return;

          currentY = e.touches[0].clientY;
          const diff = currentY - startY;

          if (diff > 0) {
            const drawerContent = mobileDrawer.querySelector('.language-drawer-content');
            drawerContent.style.transform = 'translateY(' + diff + 'px)';
          }
        }, { passive: true });

        document.addEventListener('touchend', function () {
          if (!isDragging) return;

          isDragging = false;
          mobileDrawer.classList.remove('dragging');

          const diff = currentY - startY;
          const drawerContent = mobileDrawer.querySelector('.language-drawer-content');
          drawerContent.style.transform = '';

          if (diff > 100) {
            closeAll();
          }
        });
      });

      // ESC key to close
      once('language-esc-close', 'body', context).forEach(function () {
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            closeAll();
          }
        });
      });

      // Helper functions
      function openDesktopModal() {
        if (overlay) overlay.classList.add('active');
        if (overlay) overlay.classList.remove('hidden');
        if (desktopModal) desktopModal.classList.add('active');
        if (desktopModal) desktopModal.classList.remove('hidden');
        document.body.classList.add('drawer-open');
      }

      function openMobileDrawer() {
        if (overlay) overlay.classList.add('active');
        if (overlay) overlay.classList.remove('hidden');
        if (mobileDrawer) mobileDrawer.classList.add('active');
        if (mobileDrawer) mobileDrawer.classList.remove('hidden');
        document.body.classList.add('drawer-open', 'drawer-scaled');
      }

      function closeAll() {
        if (overlay) {
          overlay.classList.remove('active');
          setTimeout(function () {
            overlay.classList.add('hidden');
          }, 300);
        }
        if (desktopModal) {
          desktopModal.classList.remove('active');
          setTimeout(function () {
            desktopModal.classList.add('hidden');
          }, 300);
        }
        if (mobileDrawer) {
          mobileDrawer.classList.remove('active');
          setTimeout(function () {
            mobileDrawer.classList.add('hidden');
          }, 500);
        }
        document.body.classList.remove('drawer-open', 'drawer-scaled');
      }
    }
  };

})(Drupal, once);
