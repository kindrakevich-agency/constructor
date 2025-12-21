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

        // Initialize Swiper.
        new Swiper(element, {
          slidesPerView: 1.2,
          spaceBetween: 16,
          grabCursor: true,
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
              slidesPerView: 2.2,
              spaceBetween: 24,
            },
            1024: {
              slidesPerView: 2.5,
              spaceBetween: 24,
            },
            1280: {
              slidesPerView: 3,
              spaceBetween: 24,
            },
            1536: {
              slidesPerView: 3.5,
              spaceBetween: 32,
            },
            1920: {
              slidesPerView: 4,
              spaceBetween: 32,
            },
          },
        });
      });
    }
  };

})(Drupal, once);
