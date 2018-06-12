(function($){
	function toggleLoader(msg) {
		if ($("#loading-bar").is(':visible')) {
			$("#loading-bar").fadeOut('fast');
		} else {
			if (! msg) { msg = 'Loading'; }
			$("#loading-bar").html(msg);

			$("#loading-bar").show();
			setTimeout("toggleLoader()",1000);
		}
	}
	jQuery.fn.initSelect2 = function(load_url,holder,args,active){ 
		console.log("init initSelect2: "+load_url);
		$(this).select2({
			placeholder: holder,
	        minimumInputLength: 0,
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: load_url,
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
					var q = '',page = '';
					if (params.term) { q = params.term; }
					if (params.page) { page = params.page; }
					var log_url = load_url+"?q="+q+"&page="+page;

					// The following section updated by David 2/13/17 to accommodate multiple arguments being
					// passed in under 'args' (formerly 'limit'); we're honoring 'limit' as the default variable
					// sent by this function's data parameter, but if multiple arguments are sent in then it's
					// handled as an object of key/value pairs.
					var formObject = {
						q: q,
						page: page,
					}

					// if 'args' is passed in as a single variable/element, add to 'formObject'; if already an
					// object, append all elements (respecting key names) to 'formObject'
					if (typeof args === 'object') {
						for (var key in args) {
							formObject[key] = args[key];
							log_url += "&"+key+"="+args[key];
						}
					} else if (args) {
						formObject['limit'] = args;//single element, intended to be sent as 'limit' variable
						log_url += "&limit="+args;
					}
					console.log("initSelect2: "+log_url);

					// append addl args to form data
					return formObject;
/*
	                return {
	                    q: params.term,//search term
						page: params.page,
						limit: limiter
	                };
*/
	            },
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							//alert(obj.text);
							return { 
								id: obj.id, 
								text: obj.text
							};
						})
/*
						results: data.results,
						pagination: {
							more: (params.page * 30) < data.total_count
						}
*/
					};
				},
				allowClear: true,
				cache: true
	        },
			escapeMarkup: function (markup) { return markup; }//let our custom formatter work
	    });
	}
	jQuery.fn.setDefault = function (string,id){
		var option = $('<option></option>').
			prop('selected',true).
			text(string).
			val(id);
		// alert($(this).html());
		$(this).html(option);//insert pre-selected option into select menu
		// initialize the change so it takes effect
		$(this).trigger("change");
	}

	$("#pm-manf").initSelect2("/json/manfs.php","Manf");
	$("#pm-system").initSelect2("/json/systems.php","System");
	function part_open(partid, ln){

//		$("#modalPartsBody").attr("data-partid",partid); //Loads incorrectly if I use .data(); attr('data..') used instead
		$("#modalPartsBody").data("partid",partid); //Loads incorrectly if I use .data(); attr('data..') used instead
//		$("#modalPartsBody").attr("data-ln",ln);
		$("#modalPartsBody").data("ln",ln);
		// reset all form fields that aren't select menus
		$(".pm-field").val('');
		$("#pm-manfid").selectize('/json/manfs.php','- Manfs -');
		$("#pm-systemid").selectize('/json/systems.php','- Systems -')

		if(! partid) {
			var classification = 'equipment';
			$("#pm-class").val(classification).trigger('change');
			$("#pm-manfid").populateSelected('','');
			$("#pm-systemid").populateSelected('','');
			$("#modalPartsTitle").text("Creating Part");
			$("#pm-part").val($("#row_"+ln).find(".product-search").val());

			$("#modal-parts").modal('show');

			return;
		}

		$.ajax({
			type: "GET",
			url: '/json/parts.php',
			data: {
				"partid" : partid
			},
			success: function(json){
				if (json.message && json.message!='') {
					modalAlertShow('Error',json.message,false);
					return;
                }

				var res = json.results;

				$("#pm-part").val(res.Part);
				$("#pm-heci").val(res.heci);
				$("#pm-descr").val(res.description);
				$("#pm-manfid").populateSelected(res.manfid,res.manf);
				$("#pm-systemid").populateSelected(res.systemid,res.system);
				$("#pm-class").val(res.classification).trigger('change');
				$("#modalPartsTitle").text(res.primary_part);

				$("#modal-parts").modal('show');
			},
			error: function(xhr, status, error) {
				modalAlertShow('Error! Please notify admin immediately.',error);
			}
		});
	}
	
	function part_submit() {
		var ln = $("#modalPartsBody").data("ln");
		var partid = $("#modalPartsBody").data("partid");

		var part = $("#pm-part").val();
		var heci = $("#pm-heci").val();
		var descr = $("#pm-descr").val();
		var manfid = $("#pm-manfid").val();
		var systemid = $("#pm-systemid").val();
		var classification = $("#pm-class").val();
		
		$.ajax({
			type: "GET",
			url: '/json/parts.php',
			data: {
				"partid": partid,
				"part" : part,
				"heci" : heci,
				"descr" : descr,
				"manfid" : manfid,
				"systemid" : systemid,
				"class" : classification
			},
			dataType: 'json',
			success: function(json) {
				if (json.message && json.message!='') {
					modalAlertShow('Error',json.message,false);
					return;
                }
				toggleLoader("Part updated successfully!");
			},
			error: function(xhr, status, error) {
				modalAlertShow('Error! Please notify admin immediately.',error);
			},
			complete: function(json){
				// for new Market view
				if ($("#results").length>0) {
					$("#results").partResults(false,ln);
					return;
				}

				location.reload();
			}
		});
	}

	$(document).on("click", ".part-modal-show, .edit-part, .add-part", function(){
		var id = '';
		var line = '';
		if($(this).data("partid")){
			id = $(this).data("partid");
		}

		if($(this).data("ln") || $(this).data("ln") == 0){
			line = $(this).data("ln");
		}

		part_open(id, line);
	});
	$("#parts-continue").click(function(){
		part_submit();
	});
	
})(jQuery);
