<?php 
	//include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
?>

<link href="css/jquery.bxslider.css" rel="stylesheet" />

<style type="text/css">
	.bx-wrapper .bx-viewport {
		box-shadow: none;
		border: 0;
		left: 0;
	}

	.dropImage > i {
	    position:relative;
	    top: calc(50% - 10px); /* 50% - 3/4 of icon height */
	}
</style>

<ul id="bxslider-pager">
	<li data-slideIndex="0">
		<a href="">
			<div class="dropImage" style="width: 250px; height: 250px; background: #E9E9E9;">
				<i class="fa fa-plus-circle" aria-hidden="true"></i>
			</div>
		</a>
	</li>
	<li data-slideIndex="1"><a href=""><img src="http://dummyimage.com/250x250/000/ff0099.png"></a></li>
	<li data-slideIndex="2"><a href=""><img src="http://dummyimage.com/250x250/000/ff0000.png"></a></li>
	<li data-slideIndex="3"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="4"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="5"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="6"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="7"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="8"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="9"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="10"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="11"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="12"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="13"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
	<li data-slideIndex="14"><a href=""><img src="http://dummyimage.com/250x250/000/fff000.png"></a></li>
</ul>

<script src="js/jquery.min.js"></script>
<script src="js/jquery.bxslider.min.js"></script>

<script type="text/javascript">

	var slider;

	var oBxSettings = {
	  	minSlides: 4,
		maxSlides: 4,
		slideWidth: 250,
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
	  if ((window.outerWidth<430 && window.prevWidth>=430)
	    || (window.outerWidth>=430 && window.prevWidth<430)) {
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
</script>