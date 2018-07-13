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

	$('.landing_block').click(function(e){
		e.preventDefault();

		var title = $(this).find(".block_title").html();

		$('.summary_block:first').find(".block_title").html(title);

		var LN = $(this).data("ln");

		$('.landing_block').hide();
		$('.summary_block').show();

		$('.items-row').hide();
		$('.items_' + LN).show();

		$('.landing_block_back').show();

		$('.title_label').hide();
		$('#avg-cost-' + LN).show();
		$('#shelflife-' + LN).show();
		$('#proj-req-' + LN).show();
		$('#market-label-' + LN).show();
	});

	$('.landing_block_back').click(function(e){
		e.preventDefault();

		$('.landing_block').show();
		$('.summary_block').hide();

		$('.items-row').hide();

		$('.landing_block_back').hide();
	});

	$('.expand_toggle').click(function(){
		$(this).closest('section').find('.collapse').collapse('toggle');
	});
})(jQuery);