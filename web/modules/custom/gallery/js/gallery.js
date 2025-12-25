/**
 * @file
 * Gallery module functionality.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.galleryModule = {
    attach: function (context) {
      // Initialize PhotoSwipe for gallery grids.
      once('gallery-pswp', '.pswp-gallery', context).forEach(function (gallery) {
        // Dynamic import of PhotoSwipe.
        Promise.all([
          import('https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe-lightbox.esm.min.js'),
          import('https://cdn.jsdelivr.net/npm/photoswipe@5.4.4/dist/photoswipe.esm.min.js')
        ]).then(function (modules) {
          var PhotoSwipeLightbox = modules[0].default;
          var PhotoSwipe = modules[1].default;

          var lightbox = new PhotoSwipeLightbox({
            gallery: gallery,
            children: 'a.gallery-item',
            pswpModule: PhotoSwipe,
            bgOpacity: 0.9,
            showHideAnimationType: 'zoom',
            padding: { top: 20, bottom: 20, left: 20, right: 20 },
          });

          lightbox.init();
        }).catch(function (error) {
          console.error('Failed to load PhotoSwipe:', error);
        });
      });
    }
  };

})(Drupal, once);
