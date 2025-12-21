/**
 * @file
 * Constructor Theme JavaScript.
 */

(function (Drupal) {
  'use strict';

  /**
   * Mobile menu toggle.
   */
  Drupal.behaviors.constructorMobileMenu = {
    attach: function (context) {
      const menuToggle = context.querySelector('.menu-toggle');
      const mainNav = context.querySelector('.main-nav');

      if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function () {
          const isExpanded = this.getAttribute('aria-expanded') === 'true';
          this.setAttribute('aria-expanded', !isExpanded);
          mainNav.classList.toggle('is-open');
          document.body.classList.toggle('menu-open');
        });
      }
    }
  };

  /**
   * Sticky header.
   */
  Drupal.behaviors.constructorStickyHeader = {
    attach: function (context) {
      const header = context.querySelector('.header');
      if (!header) return;

      let lastScroll = 0;
      const scrollThreshold = 100;

      window.addEventListener('scroll', function () {
        const currentScroll = window.pageYOffset;

        if (currentScroll > scrollThreshold) {
          header.classList.add('is-scrolled');

          if (currentScroll > lastScroll && currentScroll > 200) {
            header.classList.add('is-hidden');
          } else {
            header.classList.remove('is-hidden');
          }
        } else {
          header.classList.remove('is-scrolled');
          header.classList.remove('is-hidden');
        }

        lastScroll = currentScroll;
      });
    }
  };

  /**
   * Smooth scroll for anchor links.
   */
  Drupal.behaviors.constructorSmoothScroll = {
    attach: function (context) {
      const links = context.querySelectorAll('a[href^="#"]:not([href="#"])');

      links.forEach(function (link) {
        link.addEventListener('click', function (e) {
          const targetId = this.getAttribute('href').slice(1);
          const target = document.getElementById(targetId);

          if (target) {
            e.preventDefault();
            const headerOffset = 80;
            const elementPosition = target.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
              top: offsetPosition,
              behavior: 'smooth'
            });
          }
        });
      });
    }
  };

  /**
   * Back to top button.
   */
  Drupal.behaviors.constructorBackToTop = {
    attach: function (context) {
      // Only run once on the document
      if (context !== document) return;

      const button = document.createElement('button');
      button.className = 'back-to-top';
      button.innerHTML = '↑';
      button.setAttribute('aria-label', 'Back to top');
      button.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--color-primary);
        color: white;
        border: none;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
        z-index: 999;
        font-size: 1.25rem;
      `;

      document.body.appendChild(button);

      window.addEventListener('scroll', function () {
        if (window.pageYOffset > 500) {
          button.style.opacity = '1';
          button.style.visibility = 'visible';
        } else {
          button.style.opacity = '0';
          button.style.visibility = 'hidden';
        }
      });

      button.addEventListener('click', function () {
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
    }
  };

  /**
   * External links - open in new tab.
   */
  Drupal.behaviors.constructorExternalLinks = {
    attach: function (context) {
      const links = context.querySelectorAll('a[href^="http"]:not([href*="' + window.location.hostname + '"])');

      links.forEach(function (link) {
        if (!link.hasAttribute('target')) {
          link.setAttribute('target', '_blank');
          link.setAttribute('rel', 'noopener noreferrer');
        }
      });
    }
  };

})(Drupal);
