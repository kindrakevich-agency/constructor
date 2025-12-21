/**
 * @file
 * Carousel/Swiper initialization.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.constructorCarousels = {
    attach: function (context) {
      // Product Carousel Swiper
      once('product-swiper', '.product-swiper', context).forEach(function (el) {
        new Swiper(el, {
          slidesPerView: 1.2,
          spaceBetween: 16,
          grabCursor: true,
          loop: true,
          autoplay: {
            delay: 4000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
          },
          navigation: {
            nextEl: '.swiper-button-next-custom',
            prevEl: '.swiper-button-prev-custom',
          },
          pagination: {
            el: '.swiper-pagination-custom',
            clickable: true,
            renderBullet: function (index, className) {
              return '<span class="' + className + ' w-2 h-2 rounded-full bg-gray-300 dark:bg-slate-600 cursor-pointer transition-all hover:bg-gray-500 dark:hover:bg-slate-400"></span>';
            },
            bulletActiveClass: '!bg-gray-900 dark:!bg-white !w-6',
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
            768: {
              slidesPerView: 3,
              spaceBetween: 24,
            },
            1024: {
              slidesPerView: 4,
              spaceBetween: 32,
            },
          },
        });
      });

      // Team Carousel Swiper
      once('team-swiper', '.team-swiper', context).forEach(function (el) {
        new Swiper(el, {
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

      // Hero slider
      once('hero-slider', '.hero-slider', context).forEach(function (el) {
        new Swiper(el, {
          slidesPerView: 1,
          effect: 'fade',
          autoplay: {
            delay: 6000,
            disableOnInteraction: false,
          },
          pagination: {
            el: el.querySelector('.swiper-pagination'),
            clickable: true,
          },
        });
      });

      // Testimonials carousel
      once('testimonials-swiper', '.testimonials-swiper', context).forEach(function (el) {
        new Swiper(el, {
          slidesPerView: 1,
          spaceBetween: 24,
          autoplay: {
            delay: 5000,
            disableOnInteraction: false,
          },
          pagination: {
            el: el.querySelector('.swiper-pagination'),
            clickable: true,
          },
          breakpoints: {
            768: { slidesPerView: 2 },
            1280: { slidesPerView: 3 },
          },
        });
      });

      // Single Product - Thumbnail Gallery
      once('product-thumbnails', 'body', context).forEach(function () {
        const productThumbnails = document.querySelectorAll('.product-thumbnail');
        const productMainImage = document.getElementById('product-main-image');

        productThumbnails.forEach(function (thumb) {
          thumb.addEventListener('click', function () {
            // Update main image
            if (productMainImage && thumb.dataset.image) {
              productMainImage.src = thumb.dataset.image;
            }
            // Update active thumbnail (use opacity)
            productThumbnails.forEach(function (t) {
              t.classList.remove('opacity-100');
              t.classList.add('opacity-60');
            });
            thumb.classList.remove('opacity-60');
            thumb.classList.add('opacity-100');
          });
        });
      });
    }
  };

})(Drupal, once);
