var slider;

var oBxSettings = {
  	minSlides: 4,
	maxSlides: 4,
	slideWidth: 200,
	slideMargin: 12,
	moveSlides: 3,
	pager:false,
	speed:600,
	infiniteLoop:false,
	hideControlOnEnd:true,
	nextText:'<span></span>',
	prevText:'<span></span>',
	onSlideBefore:function($slideElement, oldIndex, newIndex){
		/*$("#sliderThumbReal ul .active").removeClass("active");
		$slideElement.addClass("active"); */
	}
};

function init() {
	// Set maxSlides depending on window width
	oBxSettings.maxSlides = window.outerWidth < 430 ? 1 : 7;
}

$(document).ready(function() {
	init();
	// Initial bxSlider setup
	slider = $('ul#bxslider-pager').bxSlider(oBxSettings);
});

$(window).resize(function() {
  // Update bxSlider when window crosses 430px breakpoint
	if ((window.outerWidth<430 && window.prevWidth>=430) || (window.outerWidth>=430 && window.prevWidth<430)) {
		init();
		slider.reloadSlider(oBxSettings);
	}
	window.prevWidth = window.outerWidth;
});

(function($) {	       
    if($("#bxslider-pager li").length < 8){
    	$("#bxslider-pager .bx-next").hide();
    }
})(jQuery);