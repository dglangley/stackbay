(function($) {
	$('.title_link').click(function(e){
		e.preventDefault();

		var linked = $(this).data('linked');

		if(linked != 'summary') {
			$('.summary_block').hide();
			$('.detail_block').hide();
			$('.form_block').hide();
			$('#'+linked).show();
		} else {
			$('.detail_block').hide();
			$('.form_block').hide();
			$('.summary_block').show();
		}
	});

	$('.expand_toggle').click(function(){
		$(this).closest('section').find('.collapse').collapse('toggle');
	});
})(jQuery);