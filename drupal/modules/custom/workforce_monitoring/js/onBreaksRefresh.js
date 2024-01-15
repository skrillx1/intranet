(function ($) {
  Drupal.behaviors.workforce_monitoring = {
    attach: function (context, settings) {
      // Function to load content without refreshing the whole page
      function loadOnBreaksContent() {
        $('#onBreaksContent').load('/workforce-monitoring/onBreaks #onBreaksContent', function () {
          applyBackgroundColors();
        });
      }

      // Function to apply background color based on text content
      function applyBackgroundColors() {
        $('td').each(function() {
          var text = $(this).text();
          var closestTR = $(this).closest('tr');
         

          if (text.indexOf('1ST BREAK') === 0) {
            closestTR.css("background-color", "#FFFDC9");
          
          } else if (text.indexOf('2ND BREAK') === 0) {
            closestTR.css("background-color", "#FFE7A0");
         
          } else if (text.indexOf('LUNCH') === 0) {
            closestTR.css("background-color", "#D6FFE6");
            
          }
        });
      }

      function handlingblink() {
        $('td:last-child').each(function() {
          var text = $(this).text();
          var closestTR = $(this).closest('tr');
          
          if (text.indexOf('1') === 0) {
              closestTR.addClass('break-blink');
            }
        });
      }

      // Load content initially
      loadOnBreaksContent();

      // Refresh content and handle blinking at regular intervals
      setInterval(function () {
       loadOnBreaksContent();
        handlingblink();
      }, 1000); // Set the interval time in milliseconds (e.g., 1 second)
    }
  };
})(jQuery);
