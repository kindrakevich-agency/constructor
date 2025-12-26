/**
 * @file
 * Dark mode toggle functionality.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.constructorDarkMode = {
    attach: function (context) {
      once('dark-mode-init', 'body', context).forEach(function () {
        const themeToggleDesktop = document.getElementById('theme-toggle');
        const themeToggleMobile = document.getElementById('theme-toggle-mobile');
        const html = document.documentElement;

        // Check for saved theme preference or default to system preference
        function getThemePreference() {
          const savedTheme = localStorage.getItem('theme');
          if (savedTheme) {
            return savedTheme;
          }
          return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        // Apply theme
        function applyTheme(theme) {
          if (theme === 'dark') {
            html.classList.remove('light');
            html.classList.add('dark');
          } else {
            html.classList.remove('dark');
            html.classList.add('light');
          }
          localStorage.setItem('theme', theme);
        }

        // Toggle theme handler
        function toggleTheme() {
          const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
          const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
          applyTheme(newTheme);
        }

        // Initialize theme on page load
        applyTheme(getThemePreference());

        // Toggle theme on desktop button click
        if (themeToggleDesktop) {
          themeToggleDesktop.addEventListener('click', toggleTheme);
        }

        // Toggle theme on mobile button click
        if (themeToggleMobile) {
          themeToggleMobile.addEventListener('click', toggleTheme);
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
          if (!localStorage.getItem('theme')) {
            applyTheme(e.matches ? 'dark' : 'light');
          }
        });
      });
    }
  };

})(Drupal, once);
