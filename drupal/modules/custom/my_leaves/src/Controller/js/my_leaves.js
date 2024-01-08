(function ($, Drupal) {
    Drupal.behaviors.myLeavesModal = {
      attach: function (context, settings) {
        // Ensure the Drupal AJAX library is available.
        if (typeof Drupal.ajax !== 'undefined') {
          // Attach AJAX behaviors to links with the 'use-ajax' class.
          Drupal.ajax.bindAjaxLinks(context);
        }
      }
    };
  })(jQuery, Drupal);
  