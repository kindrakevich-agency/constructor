/**
 * @file
 * Gallery lightbox functionality using PhotoSwipe.
 */

import PhotoSwipeLightbox from 'https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe-lightbox.esm.min.js';
import PhotoSwipe from 'https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.esm.min.js';

(function (Drupal) {
  'use strict';

  Drupal.behaviors.constructorGallery = {
    attach: function (context) {
      const galleries = context.querySelectorAll('.gallery-grid:not(.pswp-initialized)');

      galleries.forEach(function (gallery) {
        gallery.classList.add('pswp-initialized');

        const lightbox = new PhotoSwipeLightbox({
          gallery: gallery,
          children: 'a',
          pswpModule: PhotoSwipe,
          bgOpacity: 0.9,
          showHideAnimationType: 'zoom',
        });

        lightbox.init();
      });
    }
  };

})(Drupal);
