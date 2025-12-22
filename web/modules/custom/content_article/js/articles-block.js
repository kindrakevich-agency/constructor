/**
 * @file
 * JavaScript for Articles Block.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.articlesBlock = {
    attach: function (context) {
      // Initialize Plyr for article video players.
      const plyrContainers = context.querySelectorAll('.plyr-container [data-plyr-provider]');
      if (plyrContainers.length > 0 && typeof Plyr !== 'undefined') {
        plyrContainers.forEach(function (container) {
          if (!container.classList.contains('plyr--initialized')) {
            new Plyr(container, {
              controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'fullscreen'],
              youtube: {
                noCookie: true,
                rel: 0,
                showinfo: 0,
                modestbranding: 1
              }
            });
            container.classList.add('plyr--initialized');
          }
        });
      }
    }
  };

})(Drupal);
