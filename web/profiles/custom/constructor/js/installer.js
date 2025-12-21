/**
 * @file
 * Constructor Profile Installer JavaScript.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Initialize installer functionality.
   */
  Drupal.behaviors.constructorInstaller = {
    attach: function (context, settings) {
      // Initialize select2-like enhancement for select elements if needed.
      $(context).find('select').once('constructor-select').each(function () {
        // Future enhancement: Add select2 or similar.
      });

      // Initialize content type cards.
      $(context).find('.content-type-item').once('constructor-card').each(function () {
        var $card = $(this);
        var $checkbox = $card.find('input[type="checkbox"]');

        $card.on('click', function (e) {
          if (!$(e.target).is('input')) {
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
          }
        });

        $checkbox.on('change', function () {
          $card.toggleClass('selected', $(this).is(':checked'));
        });
      });

      // Initialize module cards.
      $(context).find('.modules-grid .form-item').once('constructor-module').each(function () {
        var $item = $(this);
        var $checkbox = $item.find('input[type="checkbox"]');

        if ($checkbox.is(':disabled')) {
          $item.addClass('required-module');
        }
      });

      // API key visibility toggle.
      $(context).find('input[name="api_key"]').once('constructor-apikey').each(function () {
        var $input = $(this);
        var $wrapper = $input.parent();

        var $toggle = $('<button type="button" class="apikey-toggle">Show</button>');
        $wrapper.css('position', 'relative');
        $toggle.css({
          position: 'absolute',
          right: '10px',
          top: '50%',
          transform: 'translateY(-50%)',
          background: 'transparent',
          border: 'none',
          color: '#64748b',
          cursor: 'pointer',
          fontSize: '12px'
        });

        $wrapper.append($toggle);

        $toggle.on('click', function () {
          if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $toggle.text('Hide');
          } else {
            $input.attr('type', 'password');
            $toggle.text('Show');
          }
        });

        // Initially hide the API key.
        $input.attr('type', 'password');
      });

      // Form validation enhancement.
      $(context).find('form').once('constructor-validate').on('submit', function () {
        var $form = $(this);
        var isValid = true;

        $form.find('[required]').each(function () {
          var $field = $(this);
          if (!$field.val()) {
            isValid = false;
            $field.addClass('error');
          } else {
            $field.removeClass('error');
          }
        });

        return isValid;
      });

      // Remove error class on input.
      $(context).find('input, select, textarea').on('input change', function () {
        $(this).removeClass('error');
      });
    }
  };

  /**
   * Smooth scroll to messages.
   */
  Drupal.behaviors.constructorMessages = {
    attach: function (context) {
      $(context).find('.messages').once('constructor-messages').each(function () {
        $('html, body').animate({
          scrollTop: $(this).offset().top - 100
        }, 300);
      });
    }
  };

})(jQuery, Drupal);
