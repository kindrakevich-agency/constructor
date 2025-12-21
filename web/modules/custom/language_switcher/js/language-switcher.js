/**
 * @file
 * Language Switcher functionality.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.languageSwitcher = {
    attach: function (context) {
      // Move modal elements to body to escape header's backdrop-filter containing block
      // This ensures fixed positioning works relative to viewport
      once('language-modal-move', '.language-modal-overlay, .language-modal-desktop, .language-drawer', context).forEach(function (el) {
        document.body.appendChild(el);
      });

      // Get ALL instances of modals/drawers (there may be duplicates)
      const overlays = document.querySelectorAll('.language-modal-overlay');
      const desktopModals = document.querySelectorAll('.language-modal-desktop');
      const mobileDrawers = document.querySelectorAll('.language-drawer');

      // Desktop modal triggers
      once('language-modal-open', '.open-language-modal', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          openDesktopModal();
        });
      });

      // Mobile drawer triggers
      once('language-drawer-open', '.open-language-drawer', context).forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
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
        const drawer = handle.closest('.language-drawer');

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
            const drawerContent = drawer.querySelector('.language-drawer-content');
            if (drawerContent) drawerContent.style.transform = 'translateY(' + diff + 'px)';
          }
        }, { passive: true });

        document.addEventListener('touchend', function () {
          if (!isDragging) return;

          isDragging = false;
          if (drawer) {
            drawer.classList.remove('dragging');
            const drawerContent = drawer.querySelector('.language-drawer-content');
            if (drawerContent) drawerContent.style.transform = '';
          }

          const diff = currentY - startY;
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

      // Helper functions - apply to ALL instances
      function openDesktopModal() {
        overlays.forEach(function (el) { el.classList.add('active'); });
        desktopModals.forEach(function (el) { el.classList.add('active'); });
        document.body.classList.add('drawer-open');
      }

      function openMobileDrawer() {
        overlays.forEach(function (el) { el.classList.add('active'); });
        mobileDrawers.forEach(function (el) { el.classList.add('active'); });
        document.body.classList.add('drawer-open', 'drawer-scaled');
      }

      function closeAll() {
        overlays.forEach(function (el) { el.classList.remove('active'); });
        desktopModals.forEach(function (el) { el.classList.remove('active'); });
        mobileDrawers.forEach(function (el) { el.classList.remove('active'); });
        document.body.classList.remove('drawer-open', 'drawer-scaled');
      }
    }
  };

})(Drupal, once);
