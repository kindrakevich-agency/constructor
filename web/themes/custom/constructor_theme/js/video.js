/**
 * @file
 * Video player initialization using Plyr.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.constructorVideo = {
    attach: function (context) {
      // Initialize Plyr for #plyr-player (YouTube embed)
      var plyrPlayer = document.getElementById('plyr-player');
      if (plyrPlayer && !plyrPlayer.classList.contains('plyr-initialized')) {
        plyrPlayer.classList.add('plyr-initialized');
        new Plyr('#plyr-player', {
          controls: [
            'play-large',
            'play',
            'progress',
            'current-time',
            'mute',
            'volume',
            'captions',
            'settings',
            'pip',
            'airplay',
            'fullscreen',
          ],
          youtube: {
            noCookie: true,
            rel: 0,
            showinfo: 0,
            modestbranding: 1
          }
        });
      }

      // Standard video elements
      once('plyr-video', '.plyr-video', context).forEach(function (el) {
        new Plyr(el, {
          controls: [
            'play-large',
            'play',
            'progress',
            'current-time',
            'mute',
            'volume',
            'captions',
            'settings',
            'pip',
            'airplay',
            'fullscreen',
          ],
          ratio: '16:9',
        });
      });

      // YouTube/Vimeo embeds with class
      once('plyr-embed', '.plyr-embed', context).forEach(function (el) {
        new Plyr(el, {
          controls: [
            'play-large',
            'play',
            'progress',
            'current-time',
            'mute',
            'volume',
            'fullscreen',
          ],
          youtube: {
            noCookie: true,
            rel: 0,
            showinfo: 0,
            modestbranding: 1
          }
        });
      });
    }
  };

})(Drupal, once);
