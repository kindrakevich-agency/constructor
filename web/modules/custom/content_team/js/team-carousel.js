/**
 * @file
 * Team Carousel functionality using Swiper.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.teamCarousel = {
    attach: function (context) {
      once('team-carousel-init', '.team-swiper', context).forEach(function (element) {
        // Check if Swiper is loaded.
        if (typeof Swiper === 'undefined') {
          console.warn('Swiper is not loaded. Team carousel will not work.');
          return;
        }

        // Initialize Swiper - matches example page exactly.
        new Swiper(element, {
          slidesPerView: 1.3,
          spaceBetween: 16,
          grabCursor: true,
          loop: true,
          centeredSlides: false,
          navigation: {
            nextEl: '.team-swiper-next',
            prevEl: '.team-swiper-prev',
          },
          breakpoints: {
            480: {
              slidesPerView: 1.5,
              spaceBetween: 20,
            },
            640: {
              slidesPerView: 1.8,
              spaceBetween: 24,
            },
            768: {
              slidesPerView: 2,
              spaceBetween: 24,
            },
            1024: {
              slidesPerView: 2.2,
              spaceBetween: 28,
            },
          },
        });
      });
    }
  };

})(Drupal, once);
