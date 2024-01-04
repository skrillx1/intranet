$(document).ready(function () {
  
    $("block-sos-hrleavepage li a").click(function (){
      $(this).addClass("active").siblings().removeClass("active");
    });
  });