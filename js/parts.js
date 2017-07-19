(function($){
	//Pieces from other JS Files
    function authenticateTrello(){
        var authenticationSuccess = function() { console.log('Successful authentication'); };
        var authenticationFailure = function() { console.log('Failed authentication'); };
        Trello.authorize({
          type: 'popup',
          name: 'Getting Started Application',
          scope: {
            read: 'true',
            write: 'true' },
          expiration: 'never',
          success: authenticationSuccess,
          error: authenticationFailure
        });
    }
    function submitProblem(user, feedback){
        authenticateTrello();
        var myList = "596d1cc89de495732a9cf1ae";
        var creationSuccess = function(data) {
          console.log('Card created successfully. Data returned:' + JSON.stringify(data));
        };
        var now = new Date();
        var month = now.getMonth() + 1;
        var date = now.getDate();
        var year = now.getFullYear();
        var newCard = {
          name: user+" reported an error on "+month+"/"+date+"/"+year, 
          desc: feedback,
          // Place this card at the top of our list 
          idList: myList,
          pos: 'top',
          labels:"55bfb4b019ad3a5dc2fde0a9",
          urlSource:window.location.href
        };
        Trello.post('/cards/', newCard, creationSuccess);
    }
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

	
	function part_open(id){
		var classification = 'equipment';
		var hdb = '';
		$("#modalPartsBody").attr("data-partid",id); //Loads incorrectly if I use .data(); attr('data..') used instead
		$(".pm-field").val('');
		$("#pm-manf").initSelect2("/json/manfs.php","Manf");
		$("#pm-system").initSelect2("/json/systems.php","System");
		if(id){
			$.ajax({
				type: "POST",
				url: '/json/parts.php',
				data: {
					"action" : "populate",
					"partid" : id
				},
				success: function(data){
					console.log(data);
					if(data['success']){
						hdb = data['data'];
						$("#pm-name").val(hdb['Part']);
						$("#pm-heci").val(hdb['HECI']);
						$("#pm-desc").val(hdb['Descr']);
						$("#pm-manf").setDefault(hdb['manf'],hdb['manfid']);
						$("#pm-system").setDefault(hdb['system'],hdb['systemid']);
						$("#pm-class").val(hdb['classification']);
						$("#modalPartsTitle").text("Editing Part "+hdb['Part']);
					}
				}
			});
		} else {
			$("#modalPartsTitle").text("Creating Part");
			$("#pm-class").val(classification);
		}
		$("#modal-parts").modal('show');
		

	}
	
	function part_submit(){
		var id = '';
		var name = '';
		var heci = '';
		var desc = '';
		var manf = '';
		var system = '';
		var classification = '';
		
		id = $("#modalPartsBody").data("partid");
		name = $("#pm-name").val();
		heci = $("#pm-heci").val();
		desc = $("#pm-desc").val();
		manf = $("#pm-manf").val();
		system = $("#pm-system").val();
		classification = $("#pm-class").val();
		
		$.ajax({
			type: "POST",
			url: '/json/parts.php',
			data: {
				"action": "update",
				"partid": id,
				"name" : name,
				"heci" : heci,
				"desc" : desc,
				"manf" : manf,
				"system" : system,
				"class" : classification
			},
			dataType: 'json',
			success: function(result) {
				if(!result['error']){
					console.log("JSON parts.php: Success");
					console.log(result);
					console.log("partid: "+id+"| name: "+name+"| heci: "+heci+"| desc: "+desc+"| manf: "+manf+"| system: "+system+"| classification: "+classification);
				} else {
					submitProblem("System","Part Save had an error: "+result['error']);
				}
			},
			error: function(xhr, status, error) {
				alert("Part Save had an error: dev team has been notified already");
				submitProblem("System","Part Save had an error: "+error+" | "+xhr+" | "+status);
			},
			complete: function(result){
				var msg = '';
				var res = JSON.parse(result.responseText)
				if(res['insert']){
					msg = " Inserted";
				} else {
					msg = " Updated"
				}
				toggleLoader("Part"+msg);
			}
		});
	}

	$(".part-modal-show").click(function(){
		var id = '';
		if($(this).data("partid")){
			id = $(this).data("partid");
		}
		part_open(id);
	});
	$("#parts-continue").click(function(){
		part_submit();
	});
	
})(jQuery);