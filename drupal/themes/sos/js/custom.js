// Add active class to the current button (highlight it)
var header = document.getElementById("primary_menu");
var btns = header.getElementsByClassName("icon-link");
for (var i = 0; i < btns.length; i++) {
  btns[i].addEventListener("click", function() {
  var current = document.getElementsByClassName("active");
  current[0].className = current[0].className.replace(" active", "");
  this.className += " active";
  });
}