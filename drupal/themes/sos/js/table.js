$(function() {
    $("#onBreaksContent table").find("tr").each(function() {
        $(this).find("td").filter(function() {
            return $(this).text() === "1ST BREAK";
        }).parent().addClass("fade-yellow").css("background-color", "#FEF300");
    });
});
$(function() {
    $("#onBreaksContent table").find("tr").each(function() {
        $(this).find("td").filter(function() {
            return $(this).text() === "2ND BREAK";
        }).parent().addClass("gold-yellow").css("background-color", "#FEC000");
    });
});
$(function() {
    $("#onBreaksContent table").find("tr").each(function() {
        $(this).find("td").filter(function() {
            return $(this).text() === "LUNCH";
        }).parent().addClass("light-green").css("background-color", "#00D500");
    });
});
// $(function() {
//   $("#onBreaksContent table").find("tr").each(function() {
//       if ($(this).text() == "1ST BREAK") {
//           $(this).closest("tr").css("background-color", "#FEF300"); // yellow color
//       }
//       else if ($(this).text() == "2ND BREAK") {
//           $(this).closest("tr").css("background-color", "#FEC000"); // goldyellow color
//       }
//       else if ($(this).text() == "LUNCH") {
//           $(this).closest("tr").css("background-color", "#00D500"); // green color
//       }
//   });
// })(jQuery);


// $(document).ready(function() {
//     $("#onBreaksContent table tr td").each(function() {
//         if ($(this).text() == "1ST BREAK") {
//             $(this).closest("tr").css("background-color", "#FEF300"); // yellow color
//         }
//     });
// });
// $(document).ready(function() {
//     $("#onBreaksContent table tr td").each(function() {
//         if ($(this).text() == "2ND BREAK") {
//             $(this).closest("tr").css("background-color", "#FEC000"); // goldyellow color

//         }
//     });
// });
// $(document).ready(function() {
//     $("#onBreaksContent table tr td").each(function() {
//         if ($(this).text() == "LUNCH") {
//             $(this).closest("tr").css("background-color", "#00D500"); // green color

//         }
//     });
// });