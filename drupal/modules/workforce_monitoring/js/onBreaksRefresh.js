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
            closestTR.css("background-color", "#FFC4FB");
          } else if (text.indexOf('LUNCH') === 0) {
            closestTR.css("background-color", "#A8FECA");
          }
        });
      }

      // Function to handle blinking background color at specific minutes
      function handleBlinking() {
        // Get current time in minutes
        var currentDate = new Date();
        var currentMinutes = currentDate.getMinutes();

        // Apply blinking background color at 55 minutes and 13 minutes with red color
        if (currentMinutes === 55 || currentMinutes === 13) {
          $('td').each(function() {
            var closestTR = $(this).closest('tr');
            closestTR.css("background-color", "#FF3535"); // Add a class to trigger red blinking animation
          });
        } else {
          $('td').each(function() {
            var closestTR = $(this).closest('tr');
            closestTR.css("background-color", "#FF3535"); // Remove red blinking class if not at 55 or 13 minutes
          });
        }
      }

      // Load content initially
      loadOnBreaksContent();

      // Refresh content and handle blinking at regular intervals
      setInterval(function () {
        loadOnBreaksContent();
        handleBlinking();
      }, 1000); // Set the interval time in milliseconds (e.g., 1 second)
    }
  };
})(jQuery);
