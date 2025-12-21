/**
 * @file
 * Mobile menu functionality.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.constructorMobileMenu = {
    attach: function (context) {
      once('mobile-menu-init', 'body', context).forEach(function () {
        const openBtn = document.getElementById('open-mobile-menu');
        const closeBtn = document.getElementById('close-mobile-menu');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileOverlay = document.getElementById('mobile-overlay');

        function openMobileMenu() {
          if (mobileMenu) {
            mobileMenu.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
          }
          if (mobileOverlay) {
            mobileOverlay.classList.remove('hidden');
          }
          if (openBtn) {
            openBtn.setAttribute('aria-expanded', 'true');
          }
        }

        function closeMobileMenu() {
          if (mobileMenu) {
            mobileMenu.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
          }
          if (mobileOverlay) {
            mobileOverlay.classList.add('hidden');
          }
          if (openBtn) {
            openBtn.setAttribute('aria-expanded', 'false');
          }
        }

        // Open menu button
        if (openBtn) {
          openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openMobileMenu();
          });
        }

        // Close menu button
        if (closeBtn) {
          closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeMobileMenu();
          });
        }

        // Close on overlay click
        if (mobileOverlay) {
          mobileOverlay.addEventListener('click', function () {
            closeMobileMenu();
          });
        }

        // Close on ESC key
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && mobileMenu && !mobileMenu.classList.contains('hidden')) {
            closeMobileMenu();
          }
        });

        // Close menu on window resize (if switching to desktop)
        window.addEventListener('resize', function () {
          if (window.innerWidth >= 1024 && mobileMenu && !mobileMenu.classList.contains('hidden')) {
            closeMobileMenu();
          }
        });
      });
    }
  };

})(Drupal, once);
