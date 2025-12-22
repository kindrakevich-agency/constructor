/**
 * @file
 * Language Switcher functionality.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.languageSwitcher = {
    attach: function (context) {
      // Create modal/drawer only once
      once('language-modal-create', 'body', context).forEach(function () {
        createLanguageModal();
      });

      // Get modal elements (now guaranteed to be single instances)
      const overlay = document.querySelector('.language-modal-overlay');
      const desktopModal = document.querySelector('.language-modal-desktop');
      const mobileDrawer = document.querySelector('.language-drawer');

      if (!overlay || !desktopModal || !mobileDrawer) {
        return;
      }

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

      // Close modal button
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

      // Helper functions
      function openDesktopModal() {
        overlay.classList.add('active');
        desktopModal.classList.add('active');
        document.body.classList.add('drawer-open');
      }

      function openMobileDrawer() {
        overlay.classList.add('active');
        mobileDrawer.classList.add('active');
        document.body.classList.add('drawer-open', 'drawer-scaled');
      }

      function closeAll() {
        overlay.classList.remove('active');
        desktopModal.classList.remove('active');
        mobileDrawer.classList.remove('active');
        document.body.classList.remove('drawer-open', 'drawer-scaled');
      }
    }
  };

  /**
   * Create the language modal and drawer elements (once).
   */
  function createLanguageModal() {
    const settings = drupalSettings.languageSwitcher;
    if (!settings || !settings.languages) {
      return;
    }

    const languages = settings.languages;
    const currentLang = settings.currentLanguage;

    // Build language options HTML
    let languageOptionsHtml = '';
    for (const langcode in languages) {
      const lang = languages[langcode];
      const isSelected = lang.is_current;
      languageOptionsHtml += `
        <a href="${lang.url}" class="language-option w-full flex items-center gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors ${isSelected ? 'selected bg-blue-50 dark:bg-blue-900/20' : ''}" data-lang="${langcode}">
          <span class="text-2xl">${lang.flag}</span>
          <div class="flex-1 text-left">
            <div class="font-medium text-gray-900 dark:text-white">${lang.native_name}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">${lang.country}</div>
          </div>
          <svg class="w-5 h-5 text-blue-500 ${isSelected ? 'opacity-100' : 'opacity-0'} language-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </a>
      `;
    }

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'language-modal-overlay fixed inset-0 bg-black/40 backdrop-blur-sm z-[60]';
    document.body.appendChild(overlay);

    // Create desktop modal
    const desktopModal = document.createElement('div');
    desktopModal.className = 'language-modal-desktop fixed inset-0 z-[70] items-center justify-center p-4';
    desktopModal.innerHTML = `
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-slate-700">
          <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">${Drupal.t('Select Language')}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${Drupal.t('Choose your preferred language')}</p>
          </div>
          <button class="close-language-modal w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 flex items-center justify-center transition-colors">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="p-4 space-y-2 max-h-[60vh] overflow-y-auto">
          ${languageOptionsHtml}
        </div>
      </div>
    `;
    document.body.appendChild(desktopModal);

    // Create mobile drawer
    const mobileDrawer = document.createElement('div');
    mobileDrawer.className = 'language-drawer fixed inset-x-0 bottom-0 z-[70] lg:hidden';
    mobileDrawer.setAttribute('role', 'dialog');
    mobileDrawer.setAttribute('aria-modal', 'true');
    mobileDrawer.innerHTML = `
      <div class="language-drawer-content bg-white dark:bg-slate-900 rounded-t-[20px] shadow-2xl max-h-[85vh] flex flex-col">
        <div class="language-drawer-handle flex justify-center pt-4 pb-2 cursor-grab active:cursor-grabbing">
          <div class="w-12 h-1.5 bg-gray-300 dark:bg-slate-600 rounded-full"></div>
        </div>
        <div class="px-6 pb-4 border-b border-gray-100 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">${Drupal.t('Select Language')}</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${Drupal.t('Choose your preferred language')}</p>
        </div>
        <div class="p-4 space-y-2 overflow-y-auto">
          ${languageOptionsHtml}
        </div>
        <div class="h-6 bg-white dark:bg-slate-900"></div>
      </div>
    `;
    document.body.appendChild(mobileDrawer);
  }

})(Drupal, drupalSettings, once);
