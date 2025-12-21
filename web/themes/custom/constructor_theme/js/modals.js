/**
 * @file
 * Modal and drawer functionality for mobile menu, language, and booking modals.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.constructorModals = {
    attach: function (context) {
      // ========================================
      // Mobile Menu Toggle
      // ========================================
      once('mobile-menu-init', 'body', context).forEach(function () {
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileOverlay = document.getElementById('mobile-overlay');
        const openMobileMenuBtn = document.getElementById('open-mobile-menu');
        const closeMobileMenuBtn = document.getElementById('close-mobile-menu');

        if (mobileMenu && mobileOverlay && openMobileMenuBtn && closeMobileMenuBtn) {
          function openMobileMenu() {
            mobileMenu.classList.remove('hidden');
            mobileMenu.classList.add('active');
            mobileOverlay.classList.remove('hidden');
            mobileOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
          }

          function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            mobileMenu.classList.add('hidden');
            mobileOverlay.classList.remove('active');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = '';
          }

          openMobileMenuBtn.addEventListener('click', openMobileMenu);
          closeMobileMenuBtn.addEventListener('click', closeMobileMenu);
          mobileOverlay.addEventListener('click', closeMobileMenu);

          // Close mobile menu on escape key
          document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
              closeMobileMenu();
            }
          });

          // Close mobile menu on window resize (if desktop)
          window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024) {
              closeMobileMenu();
            }
          });
        }
      });

      // ========================================
      // Language Modal/Drawer
      // ========================================
      once('language-modal-init', 'body', context).forEach(function () {
        const modalOverlay = document.getElementById('language-modal-overlay');
        const modalDesktop = document.getElementById('language-modal-desktop');
        const drawer = document.getElementById('language-drawer');
        const openBtnMobile = document.getElementById('open-language-drawer');
        const openBtnsDesktop = document.querySelectorAll('.open-language-modal');
        const closeBtns = document.querySelectorAll('.close-language-modal');
        const allLanguageOptions = document.querySelectorAll('.language-option');
        const selectedLanguageEl = document.getElementById('selected-language');
        const selectedLanguageDesktopEl = document.getElementById('selected-language-desktop');

        if (!modalOverlay || !modalDesktop || !drawer) {
          return;
        }

        const handle = drawer.querySelector('.language-drawer-handle');
        const content = drawer.querySelector('.language-drawer-content');

        let isDragging = false;
        let startY = 0;
        let currentY = 0;
        let drawerHeight = 0;

        function isDesktop() {
          return window.matchMedia('(min-width: 1024px)').matches;
        }

        // Set initial selected language
        const savedLang = localStorage.getItem('selectedLanguage') || 'English';
        setSelectedLanguage(savedLang);

        function openLanguageModal() {
          modalOverlay.classList.remove('hidden');
          modalOverlay.classList.add('active');
          document.body.style.overflow = 'hidden';

          if (isDesktop()) {
            modalDesktop.classList.remove('hidden');
            modalDesktop.classList.add('active');
            drawer.classList.remove('active');
            drawer.classList.add('hidden');
          } else {
            drawer.classList.remove('hidden');
            drawer.classList.add('active');
            modalDesktop.classList.remove('active');
            modalDesktop.classList.add('hidden');
            if (content) {
              drawerHeight = content.offsetHeight;
            }
          }
        }

        function closeLanguageModal() {
          modalOverlay.classList.remove('active');
          modalOverlay.classList.add('hidden');
          modalDesktop.classList.remove('active');
          modalDesktop.classList.add('hidden');
          drawer.classList.remove('active');
          drawer.classList.add('hidden');
          document.body.style.overflow = '';
          drawer.style.transform = '';
          modalOverlay.style.opacity = '';
        }

        function setSelectedLanguage(lang) {
          allLanguageOptions.forEach(function (opt) {
            const check = opt.querySelector('.language-check');
            if (opt.dataset.lang === lang) {
              opt.classList.add('selected');
              if (check) check.style.opacity = '1';
            } else {
              opt.classList.remove('selected');
              if (check) check.style.opacity = '0';
            }
          });
          // Update both mobile and desktop language displays
          if (selectedLanguageEl) {
            selectedLanguageEl.textContent = lang;
          }
          if (selectedLanguageDesktopEl) {
            selectedLanguageDesktopEl.textContent = lang;
          }
          localStorage.setItem('selectedLanguage', lang);
        }

        // Open modal/drawer - Mobile button
        if (openBtnMobile) {
          openBtnMobile.addEventListener('click', openLanguageModal);
        }

        // Open modal - Desktop buttons
        openBtnsDesktop.forEach(function (btn) {
          btn.addEventListener('click', openLanguageModal);
        });

        // Close buttons
        closeBtns.forEach(function (btn) {
          btn.addEventListener('click', closeLanguageModal);
        });

        // Close on overlay click
        modalOverlay.addEventListener('click', closeLanguageModal);

        // Close on escape key
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
            closeLanguageModal();
          }
        });

        // Language selection
        allLanguageOptions.forEach(function (option) {
          option.addEventListener('click', function () {
            setSelectedLanguage(option.dataset.lang);
            setTimeout(closeLanguageModal, 150);
          });
        });

        // Mobile drawer drag to dismiss functionality
        function handleDragStart(e) {
          if (e.target.closest('.language-option')) return;
          isDragging = true;
          startY = e.type === 'touchstart' ? e.touches[0].clientY : e.clientY;
          currentY = startY;
          drawer.classList.add('dragging');
        }

        function handleDragMove(e) {
          if (!isDragging) return;
          currentY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
          const deltaY = currentY - startY;

          // Only allow dragging down
          if (deltaY > 0) {
            drawer.style.transform = 'translateY(' + deltaY + 'px)';
            const opacity = Math.max(0, 1 - (deltaY / drawerHeight));
            modalOverlay.style.opacity = opacity;
          }
        }

        function handleDragEnd() {
          if (!isDragging) return;
          isDragging = false;
          drawer.classList.remove('dragging');

          const deltaY = currentY - startY;
          const threshold = drawerHeight * 0.3;

          if (deltaY > threshold) {
            closeLanguageModal();
          } else {
            drawer.style.transform = '';
            modalOverlay.style.opacity = '';
          }
        }

        // Touch events for mobile drawer
        if (handle) {
          handle.addEventListener('touchstart', handleDragStart, { passive: true });
          handle.addEventListener('mousedown', handleDragStart);
        }
        if (content) {
          content.addEventListener('touchstart', handleDragStart, { passive: true });
        }

        document.addEventListener('touchmove', handleDragMove, { passive: true });
        document.addEventListener('touchend', handleDragEnd);
        document.addEventListener('mousemove', handleDragMove);
        document.addEventListener('mouseup', handleDragEnd);

        // Prevent body scroll when dragging
        drawer.addEventListener('touchmove', function (e) {
          if (isDragging) {
            e.preventDefault();
          }
        }, { passive: false });

        // Handle window resize
        window.addEventListener('resize', function () {
          if (modalOverlay.classList.contains('active')) {
            if (isDesktop()) {
              drawer.classList.remove('active');
              drawer.classList.add('hidden');
              modalDesktop.classList.remove('hidden');
              modalDesktop.classList.add('active');
            } else {
              modalDesktop.classList.remove('active');
              modalDesktop.classList.add('hidden');
              drawer.classList.remove('hidden');
              drawer.classList.add('active');
            }
          }
        });
      });

      // ========================================
      // Booking Modal/Drawer
      // ========================================
      once('booking-modal-init', 'body', context).forEach(function () {
        const modalOverlay = document.getElementById('booking-modal-overlay');
        const modalDesktop = document.getElementById('booking-modal-desktop');
        const drawerMobile = document.getElementById('booking-drawer-mobile');
        const openBtns = document.querySelectorAll('.open-booking-modal');
        const closeBtns = document.querySelectorAll('.close-booking-modal');

        if (!modalOverlay || !modalDesktop || !drawerMobile) {
          return;
        }

        let isDragging = false;
        let startY = 0;
        let currentY = 0;
        let drawerHeight = 0;

        function isDesktop() {
          return window.matchMedia('(min-width: 1024px)').matches;
        }

        function openBookingModal() {
          modalOverlay.classList.remove('hidden');
          modalOverlay.classList.add('active');
          document.body.style.overflow = 'hidden';

          if (isDesktop()) {
            modalDesktop.classList.remove('hidden');
            modalDesktop.classList.add('active');
            drawerMobile.classList.remove('active');
            drawerMobile.classList.add('hidden');
          } else {
            drawerMobile.classList.remove('hidden');
            drawerMobile.classList.add('active');
            modalDesktop.classList.remove('active');
            modalDesktop.classList.add('hidden');
            const content = drawerMobile.querySelector('.booking-drawer-content');
            if (content) {
              drawerHeight = content.offsetHeight;
            }
          }
        }

        function closeBookingModal() {
          modalOverlay.classList.remove('active');
          modalOverlay.classList.add('hidden');
          modalDesktop.classList.remove('active');
          modalDesktop.classList.add('hidden');
          drawerMobile.classList.remove('active');
          drawerMobile.classList.add('hidden');
          document.body.style.overflow = '';
          drawerMobile.style.transform = '';
          modalOverlay.style.opacity = '';
        }

        // Open modal buttons
        openBtns.forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.preventDefault();
            openBookingModal();
          });
        });

        // Close buttons
        closeBtns.forEach(function (btn) {
          btn.addEventListener('click', closeBookingModal);
        });

        // Close on overlay click
        modalOverlay.addEventListener('click', closeBookingModal);

        // Close on escape key
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
            closeBookingModal();
          }
        });

        // Mobile drawer drag-to-dismiss
        const mobileHandle = drawerMobile.querySelector('.booking-drawer-handle');
        const mobileContent = drawerMobile.querySelector('.booking-drawer-content');

        function handleDragStart(e) {
          if (e.target.closest('input, select, button, textarea')) return;
          isDragging = true;
          startY = e.type === 'touchstart' ? e.touches[0].clientY : e.clientY;
          currentY = startY;
          drawerMobile.classList.add('dragging');
        }

        function handleDragMove(e) {
          if (!isDragging) return;
          currentY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
          const deltaY = currentY - startY;

          // Only allow dragging down
          if (deltaY > 0) {
            drawerMobile.style.transform = 'translateY(' + deltaY + 'px)';
            const opacity = Math.max(0, 1 - (deltaY / drawerHeight));
            modalOverlay.style.opacity = opacity;
          }
        }

        function handleDragEnd() {
          if (!isDragging) return;
          isDragging = false;
          drawerMobile.classList.remove('dragging');

          const deltaY = currentY - startY;
          const threshold = drawerHeight * 0.3;

          if (deltaY > threshold) {
            closeBookingModal();
          } else {
            drawerMobile.style.transform = '';
            modalOverlay.style.opacity = '';
          }
        }

        if (mobileHandle) {
          mobileHandle.addEventListener('touchstart', handleDragStart, { passive: true });
          mobileHandle.addEventListener('mousedown', handleDragStart);
        }
        if (mobileContent) {
          mobileContent.addEventListener('touchstart', handleDragStart, { passive: true });
        }

        document.addEventListener('touchmove', handleDragMove, { passive: true });
        document.addEventListener('touchend', handleDragEnd);
        document.addEventListener('mousemove', handleDragMove);
        document.addEventListener('mouseup', handleDragEnd);

        // Prevent body scroll when dragging
        drawerMobile.addEventListener('touchmove', function (e) {
          if (isDragging) {
            e.preventDefault();
          }
        }, { passive: false });

        // Handle window resize
        window.addEventListener('resize', function () {
          if (modalOverlay.classList.contains('active')) {
            if (isDesktop()) {
              drawerMobile.classList.remove('active');
              drawerMobile.classList.add('hidden');
              modalDesktop.classList.remove('hidden');
              modalDesktop.classList.add('active');
            } else {
              modalDesktop.classList.remove('active');
              modalDesktop.classList.add('hidden');
              drawerMobile.classList.remove('hidden');
              drawerMobile.classList.add('active');
            }
          }
        });
      });
    }
  };

})(Drupal, once);
