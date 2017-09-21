	if (typeof RESULTS_MODE === 'undefined') { RESULTS_MODE = 0; }
    $(document).ready(function() {

		$('#loader').hide();
		if ($("#s:focus") && $(".profile-body").length==0 && $(".accounts-body").length==0) {
			$("#s").select();
		} else {
			$(".auto-select").each(function() {
				$(this).select();
			});
		}
		//toggleLoader();
		loadOrders();

		$(".market-table").each(function() {
			var marketTable = $(this);
			var parentBody = $(this).closest(".product-results").find(".parts-container").height();

			//if (parentBody>marketTable.css('min-height').replace('px','')) {
			marketTable.css("height" ,parentBody);
			//}
		});

        $("body").on('click','a.modal-results',function(e) {
			var first = $(this).closest(".product-results").siblings(".first");
			var productSearch = first.find(".product-search").val().toUpperCase();
			var partids = $(this).closest(".market-table").data('partids');
			var ln = $(this).closest(".market-results").data('ln');
			var results_mode = '0';//default
			var results_title = $(this).data('title');
			var results_type = $(this).data('type');

			var type = 'modal'; //Used for sales ajax to getRecord()

			var modalBody = $("#marketModal .modal-body");
			var rowHtml = '';

			first.find(".btn-resultsmode").find(".btn").each(function() {
				if (! $(this).hasClass('btn-primary')) { return; }
				results_mode = $(this).data('results');
			});

			if (results_mode=='1') { results_title += ' - Prices Only'; }
			else if (results_mode=='2') { results_title += ' - Ghosted Inventories'; }
			else { results_title += ' - All'; }
            $("#marketModal .modal-title").html(results_title);
			// reset html so when it pops open, there's no old data
			$("#marketModal .modal-body").html('<div class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-5x"></i></div>');

            console.log(window.location.origin+"/json/availability.php?attempt=0&partids="+partids+"&detail=1&results_mode="+results_mode+'&type='+results_type);

            if(results_type == 'supply' || results_type == 'demand') {

	            $.ajax({
	                url: 'json/availability.php',
	                type: 'get',
	                data: {'attempt': '0', 'partids': partids, 'results_mode': results_mode, 'detail': '1', 'type': results_type},
	                success: function(json, status) {
						if (json.err!='') {
							alert(json.err);
							return;
						}

						var rfqFlag,actionBox,price,inputDis,sources;
						var searchHeader = 'Listed As';//set for the first header row, but then erased after that; see usage below
						var priceHeader = 'Price';//set for the first header row, but then erased after that; see usage below
	                    $.each(json.results, function(dateKey, item) {
	                    	
						   rowHtml += '<div class="check-group">\
								<div class="row">\
									<div class="col-sm-1">\
										<input type="checkbox" class="checkTargetAll" data-target=".check-group"/>\
									</div>\
									<div class="col-sm-7">\
										'+dateKey+'\
									</div>\
									<div class="col-sm-2">\
										'+searchHeader+'\
									</div>\
									<div class="col-sm-2">\
										'+priceHeader+'\
									</div>\
								</div>';
							priceHeader = '&nbsp;';//reset for all ensuing header rows; see init above
							searchHeader = '&nbsp;';//reset for all ensuing header rows; see init above
	                        /* process each item's data */
	                        $.each(item, function(key, row) {
								/***** SET UP FIELDS FOR USE WITHIN ROW COLUMNS ****/
								// checkbox for rfqing, but disable ebay items
								inputDis = '';
								if (row.cid==34) { inputDis = ' disabled'; }
								actionBox = '<input type="checkbox" class="item-check" name="companyids[]" value="'+row.cid+'"'+inputDis+'/>';
								// set flag when an rfq has been sent
								rfqFlag = '';
								if (row.rfq && row.rfq!='') {
									rfqFlag = ' <i class="fa fa-paper-plane text-primary" title="'+row.rfq+'"></i>';
								}
								price = '';
								if (row.cid!=34 && row.price!="") {
									price = Number(row.price.replace(/[^0-9\.-]+/g,"")).toFixed(2);
								}
								search_str = '&nbsp;';
								if (row.search!='') { search_str = '<span class="info">'+row.search+'</span>'; }
								sources = '';
	                            $.each(row.sources, function(i, src) {
									var source_lower = src.toLowerCase();
									var source_img = '<img src="img/'+source_lower+'.png" class="bot-icon" />';
									if (row.lns[source_lower]) {
										sources += '<a href="http://'+row.lns[source_lower]+'" target="_new">'+source_img+'</a> ';
									} else {
										sources += source_img+' ';
									}
								});
								if (sources=='') { sources = '&nbsp;'; }
								/***** END FIELDS SETUP *****/

								rowHtml += '<div class="row">\
									<div class="col-sm-1">\
										'+actionBox+rfqFlag+'\
									</div>\
									<div class="col-sm-1">\
										<strong>'+row.qty+'</strong>\
									</div>\
									<div class="col-sm-4 company-name">\
										'+row.company+'\
									</div>\
									<div class="col-sm-2">\
										'+sources+'\
									</div><!-- col-sm -->\
									<div class="col-sm-2">\
										'+search_str+'\
									</div><!-- col-sm -->\
									<div class="col-sm-2">\
										<input type="text" value="'+price+'" class="form-control input-xs market-price" data-type="'+results_type+'" data-date="'+row.date+'" data-cid="'+row.cid+'" size="4" onFocus="this.select()"'+inputDis+'/>\
									</div><!-- col-sm -->\
								</div><!-- row -->';
	                        });
							rowHtml += '</div>';/*end check-group*/
	                    });
	/*
						rowHtml += '<br/><textarea name="message_body" style="width:100%" rows="5">Please quote:\n\n'+productSearch+'</textarea>'+
							'<input type="hidden" name="partids" value="'+partids+'">';
	*/
						$('#marketModal .message-body').each(function() {
							$(this).val('Please quote:\n\n'+productSearch);
						});
						$('#marketModal .message-subject').each(function() {
							$(this).val(productSearch);
						});
						rowHtml += '<input type="hidden" name="partids" value="'+partids+'">';
						modalBody.html(rowHtml);
						modalBody.closest('.modal-content').find('.modal-footer').find('.text-left').show();
						modalBody.closest('.modal-content').find('.modal-footer').find('#modal-submit').show();
	                },
	                error: function(xhr, desc, err) {
	//                    console.log(xhr);
	                    console.log("Details: " + desc + "\nError:" + err);
	                }
	            }); // end ajax call
    		} else {
    			$.ajax({
			        url: 'json/sales.php',
			        type: 'get',
			        data: {'search_strs': productSearch, 'partid_csv': partids, 'market_table': results_type, 'type': type},
			        success: function(result) {
			        	if(result) {			   
			        		var abbrev = '';

			        		if(results_type == 'purchases') {
			        			abbrev = 'PO';
			        		}  else if(results_type == 'sales') {
			        			abbrev = 'SO';
			        		} else {
			        			abbrev = 'RO';
			        		}    	
				        	console.log(result);
				        	modalBody.closest('.modal-content').find('.modal-footer').find('.text-left').hide();
				        	modalBody.closest('.modal-content').find('.modal-footer').find('#modal-submit').hide();
				        	$.each(result, function(key, row) {
				        		var price;
				        		var username;
				        		if(row.format_price) {
				        			price = row.format_price;
				        		} else {
				        			price = '$0.00';
				        		}

				        		if(row.username != 'false') {
				        			username;
				        		} else {
				        			username = ' ';
				        		}

					        	rowHtml += '<div class="row">\
										<div class="col-sm-2">\
											'+row.date+'\
										</div>\
										<div class="col-sm-3 company-name">\
											<a href="/">'+row.name+'</a>\
										</div>\
										<div class="col-sm-2">\
											<a href="/'+abbrev+row.order_num+'">'+row.order_num+'</a>\
										</div><!-- col-sm -->\
										<div class="col-sm-1">\
											<strong>'+row.qty+'</strong>\
										</div>\
										<div class="col-sm-2">\
											'+price+'\
										</div><!-- col-sm -->\
										<div class="col-sm-2" style="min-height: 24px;">\
											'+row.username+'\
										</div><!-- col-sm -->\
									</div><!-- row -->';
							});
							modalBody.html(rowHtml);
						}
			        },
			        error: function(xhr, desc, err) {
			            console.log("Details: " + desc + "\nError:" + err);
			        }
			    });
    			// rowHtml = $(this).parent().find('.market-body').html();
    			// modalBody.html(rowHtml);
    		}

			$("#"+$(this).data('target')).modal('toggle');
        });

		$(".modal-form").submit(function(e) {
			$('#loader-message').html('Please wait while your RFQ is being sent...');
			$('#loader').show();
			$('#modal-submit').prop('disabled',true);

			var modalForm = $(this);
            console.log(window.location.origin+"/json/"+$(this).prop('action'));
			$.ajax({
				type: "POST",
				url: $(this).prop("action"),
				data: $(this).serialize(), // serializes the form's elements.
				dataType: 'json',
                success: function(json, status) {
					$('#loader').hide();
					$('#modal-submit').prop('disabled',false);

					if (json.message=='Success') {
						toggleLoader("RFQ sent successfully");
						modalForm.closest(".modal").modal("toggle");
					} else {
						if (json.confirm && json.confirm=='1') {
							var user_conf = confirm(json.message);
							if (user_conf===true && json.url && json.url!='') {
								document.location.href = json.url;
							}
						} else {
							alert(json.message); // show response from the php script.
							//modalForm.closest(".modal").modal("toggle");
						}
					}
				},
	            error: function(xhr, desc, err) {
					$('#loader').hide();
					$('#modal-submit').prop('disabled',false);

					toggleLoader("Error sending RFQ! Details: " + desc + "<br/>Error:" + err);
					modalForm.closest(".modal").modal("toggle");

//	                console.log(xhr);
	                console.log("Details: " + desc + "\nError:" + err);
	            }
			});
			e.preventDefault();
			return false;
		});

		$(document).on("click", "input.price-control", function(){
			$(this).select();
		});

		/* toggle notes on input focus and blur */
        $("input.price-control").each(function() {
			$(this).click(function() {
				/*toggleNotes($(this));*/

/*
				$(this).focusout(function() {
					setTimeout("closeNotes()",100);
				});
*/

				$(this).select();
			});
		});
		$(document).on("click", ".item-notes", function() {
			toggleNotes($(this));
		});
		jQuery.expr[':'].focus = function(elem) {
		  return elem === document.activeElement && (elem.type || elem.href);
		};
		$(".notes-close").on('click',function() {
//dgl 10-10-16
//			closeModal($(this).closest(".modalNotes"));
			// invoke this programmatically so we can include a callback method
//			closeModal($("#modalNotes"));
			$("#modalNotes").modal('hide');
		});
		$('#modalNotes').on('hide.bs.modal', function (e) {
			if (NOTES_SESSION_ID!==false) {
				clearInterval(NOTES_SESSION_ID);
				NOTES_SESSION_ID = false;
			}
		});
		$('.notification-dropdown .trigger').on('click',function(e) {
			var notif = $(this).closest(".notification-dropdown").find(".notifications:first");
			notif.html('<div class="text-center"><i class="fa fa-refresh fa-spin fa-5x fa-fw"></i></div>');

			$(this).find(".count").css({display:'none',visibility:'hidden'});

	        console.log(window.location.origin+"/json/notes.php");
	        $.ajax({
				url: 'json/notes.php',
				type: 'get',
				dataType: 'json',
				success: function(json, status) {
					if (json.results) {
						notif.html("");

	                	$.each(json.results, function(i, row) {
							var read_class = '';
							if (row.read=='') { read_class = ' unread'; }
							else if (row.viewed=='') { read_class = ' unviewed'; }

							if(!row.part_label) {
								var title = (row.note).split(" ");
								row.part_label = title[0] + ' ' + title[1];
								row.note = '';
								for(var i = 2; i < title.length; i++){
									row.note += title[i] + ' ';
								}
							}
							var notif_html = '<a href="javascript:viewNotification(\''+row.messageid+'\',\''+row.search+'\',\''+row.link+'\')" class="item'+read_class+'">'+
								'<div class="user fa-stack fa-lg">'+
									'<i class="fa fa-user fa-stack-2x text-warning"></i><span class="fa-stack-1x user-text">'+row.name+'</span>'+
								'</div> '+
								'<span class="time pull-right"><i class="fa fa-clock-o"></i> '+row.since+'</span>'+
								'<div class="note"><strong>'+row.part_label+'</strong><br/>'+row.note+'</div> '+
								'</a>';
							notif.append(notif_html);
						});

					} else {
						notif.html("");
						var message = 'There was an error processing your request!';
						if (json.message) { message = json.message; } // show response from the php script.
						alert(message);
					}
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		});

        $(".checkAll").on('click',function(){
            jQuery(this).closest('.table').find('.item-check:checkbox').not(this).prop('checked', this.checked);
        });
		$(".checkInner").click(function(){
			$(this).closest('tr').next('tr').find('.item-check:checkbox').not(this).prop('checked', this.checked);
		});
		/* must use this click method for ajax-generated content */
        $("body").on('click','.checkTargetAll',function(){
            jQuery(this).closest($(this).data('target')).find('.item-check:checkbox').not(this).prop('checked', this.checked);
        });

/*
		$(".checkAll").click(function(){
		    $('input:checkbox').not(this).prop('checked', this.checked);
		});
*/
		/***** AMEA *****/
		$(".highlight-word").on('click',function() {
			$("#"+$(this).data("for")).click();

			// get color of selected highlighter
			var color = '';
			$(".highlighter-pen").each(function() {
				if ($(this).hasClass('btn-default')) { return; }// || $(this).hasClass('text-'+$(this).data('color'))) { return; }
				color = $(this).data('color');
				field_type = $(this).data('type');
			});
			$("#"+field_type+"-col").val($(this).data("col"));

			// iterate through all objects of this class (radio's) and change text formatting as per radio selections
			$("input[type='radio'][name='fields']").each(function() {
				var labelobj = $("#"+$(this).data("label"));
				if ($(this).prop('checked')) {
					labelobj.attr('class','highlight-word highlight-selected text-'+color);
				} else if (! labelobj.hasClass('highlight-selected') || labelobj.hasClass('text-'+color)) {
					labelobj.attr('class','highlight-word');
				}
			});

			$(this).blur();
		});
		$(".highlighter-pen").on('click',function() {
			$(this).removeClass('btn-default').addClass('btn-'+$(this).data('color'));
			$(".highlighter-pen").not(this).each(function() {
				$(this).removeClass('btn-'+$(this).data('color')).addClass('btn-default');
			});
		});
		/***** END AMEA *****/


		$(".btn-end").click(function() {
			var aligned = '';
			var btn = $(this);

			// the 'data-for' element tracks whether the alignment is right or left
			$("#"+$(this).data("for")).click();
			if ($("#"+$(this).data("for")).prop('checked')===true) {
				$("#"+$(this).data("input")).css('text-align','right');
				$(this).find("i").removeClass('fa-align-left').addClass('fa-align-right');
				aligned = 'right';
			} else {
				$("#"+$(this).data("input")).css('text-align','left');
				$(this).find("i").removeClass('fa-align-right').addClass('fa-align-left');
				aligned = 'left';
			}
			$("input[type='radio'][name='fields']:checked").each(function() {
				if (aligned=='left') {
					$("#"+btn.data("input")).val($(this).data("col"));
				} else if (aligned=='right') {
					$("#"+btn.data("input")).val($(this).data("end"));
				}
			});
		});

		$(".btn-status").click(function() {
			var status = $(this).data('status');
			$("#status").val(status);
			$(this).closest("form").submit();
		});

		$(document).on("change", ".price-control", function() {
			var priceMaster = $(this);
			// confirm padlock isn't unlocked, which would make this a unique price change
			var priceLocked = false;
			priceMaster.closest(".sell").find(".fa").each(function() {
				if ($(this).hasClass("fa-lock")) { priceLocked = true; }
			});
			if (priceLocked===false) { return; }

			var parentBody = priceMaster.closest("tbody");
			var controlPrice,controlLock;
			var allPrices = parentBody.find(".price-control").not(this).each(function() {
				controlPrice = $(this);
				controlLock = controlPrice.closest(".sell").find(".fa-lock").each(function() {
					controlPrice.val(priceMaster.val().trim());
				});
			});
		});
		$(".add-part").click(function() {
//			var pdescr = $(this).closest(".product-descr");
//			var psearch = pdescr.find(".product-search:first").val();
			var psearch = $(this).closest(".first").find(".product-search:first").val();
			modalAlertShow('Create a New Part','Be sure this string ("'+psearch+'") is a Part# (NOT a HECI!), and then click Continue!',true,'addPart',psearch);
		});
		$(".parts-index").click(function() {
//			var pdescr = $(this).closest(".product-descr");
//			var psearch = pdescr.find(".product-search:first").val();
			var psearch = $(this).closest(".first").find(".product-search:first").val();
			modalAlertShow("Updating DB Keywords Index!","Re-indexing the database for this search term will reload the entire page. Are you ready to proceed?",true,'reindexParts',psearch);
		});
		$(".parts-merge").click(function() {
			var tbody = $(this).closest("tbody");
			var checked_rows = tbody.find(".item-check:checked").length;
			if (checked_rows!=2) {
				modalAlertShow("Parts Merge Alert","You can merge two and only two items at a time!",false);
				return;
			}
			modalAlertShow("Merging Parts is Permanent","You cannot undo this action! This action will also reload your current page. Do you really want to proceed?",true,'mergeParts',tbody.find(".item-check:checked"));
		});
		$(".parts-edit").click(function() {
			var tbody = $(this).closest("tbody");
			var partid;
			tbody.find(".item-check:checked").each(function() {
				partid = $(this).val();
				tbody.find(".product-descr").each(function() {
					if ($(this).data('partid')!=partid) { return; }
					$(this).find(".descr-label").each(function() { $(this).toggleClass("hidden"); });
					$(this).find(".descr-edit").each(function() { $(this).toggleClass("hidden"); });

				    $(this).find(".manf-selector").each(function() {
						$(this).select2({
					        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
					            url: "/json/manfs.php",
					            dataType: 'json',
								/*delay: 250,*/
					            data: function (params) {
					                return {
					                    add_custom: '1',
					                    q: params.term,//search term
										page: params.page
					                };
					            },
						        processResults: function (data, params) { // parse the results into the format expected by Select2.
						            // since we are using custom formatting functions we do not need to alter remote JSON data
									// except to indicate that infinite scrolling can be used
									params.page = params.page || 1;
						            return {
										results: $.map(data, function(obj) {
											return { id: obj.id, text: obj.text };
										})
									};
								},
								cache: true
					        },
							escapeMarkup: function (markup) { return markup; },//let our custom formatter work
					        minimumInputLength: 2
					    });
					});
				    $(this).find(".system-selector").each(function() {
						var manfid = $(this).closest(".product-descr").find(".manf-selector:first").val();
						$(this).select2({
					        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
					            url: "/json/systems.php",
					            dataType: 'json',
								/*delay: 250,*/
					            data: function (params) {
					                return {
					                    q: params.term,//search term
										manfid: manfid,
										page: params.page
					                };
					            },
						        processResults: function (data, params) { // parse the results into the format expected by Select2.
						            // since we are using custom formatting functions we do not need to alter remote JSON data
									// except to indicate that infinite scrolling can be used
									params.page = params.page || 1;
						            return {
										results: $.map(data, function(obj) {
											return { id: obj.id, text: obj.text };
										})
									};
								},
								cache: true
					        },
							escapeMarkup: function (markup) { return markup; },//let our custom formatter work
					        minimumInputLength: 0
					    });
					});
				});
			});
		});
		$(".descr-edit input, .descr-edit select").on("keypress",function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				$(this).blur();
				$(this).closest(".descr-edit").toggleClass('hidden');
				$(this).closest(".product-descr").find(".descr-label").toggleClass('hidden');
			}
		});
		$(".descr-edit input, .descr-edit select").change(function() {
            console.log(window.location.origin+"/json/save-parts.php?partid="+$(this).data('partid')+"&field="+$(this).data('field')+"&new_value="+encodeURIComponent($(this).val()));
			if ($(this).is("select")) {
				$(this).closest(".product-descr").find("."+$(this).data('field')+"-label").html($(this).select2('data')[0].text.toUpperCase());
			} else {
				$(this).closest(".product-descr").find("."+$(this).data('field')+"-label").html($(this).val().toUpperCase());
			}
            $.ajax({
                url: 'json/save-parts.php',
                type: 'get',
                data: {'partid': $(this).data('partid'), 'field': $(this).data('field'), 'new_value': encodeURIComponent($(this).val())},
				dataType: 'json',
                success: function(json, status) {
					if (json.message!='Success') {
						alert(json.message);
					}
                },
                error: function(xhr, desc, err) {
//                    console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
            }); // end ajax call

			return;
		});
		$(".control-toggle").click(function() {
			$(this).find(".fa").each(function() {
				if ($(this).hasClass('fa-lock')) { $(this).removeClass('fa-lock').addClass('fa-unlock'); }
				else { $(this).removeClass('fa-unlock').addClass('fa-lock'); }
			});
		});
        $(".market-results").each(function() {
			$(this).loadResults(0,RESULTS_MODE);
		});

        //Legacy Code
		$(document).on("click", ".market-download", function() {
			var mr = $(this).closest(".bg-availability").find(".market-results:first");

			mr.loadResults(2);
		});

//dgl 11-15-16
/*		$(".marketpricing-toggle").click(function() { */
		$(document).on("click", ".btn-resultsmode .btn", function() {
//			var mr = $(this).closest(".part_info").find(".market-results:first");
			var mr = $(this).closest("tbody, .part_info").find(".market-results:first");

			var this_btn = $(this);
			// reset all market pricing button styles, and depress the selected one
			$(this).closest(".btn-resultsmode").find(".btn").each(function() {
				$(this).removeClass('btn-primary').addClass('btn-default');
			});
			$(this).removeClass('btn-default').addClass('btn-primary');

/* why is this commented? new sales view? dl 8-16-17 */
/*
			$(this).find(".fa").each(function() {
				if ($(this).hasClass('fa-toggle-off')) {
					$(this).removeClass('fa-toggle-off').addClass('fa-toggle-on');
					mr.loadResults(1,1);
				} else {
					$(this).removeClass('fa-toggle-on').addClass('fa-toggle-off');
					mr.loadResults(0);
				}
			});
*/

			// reset html with a loader/spinner while loading new data
            mr.html('<i class="fa fa-circle-o-notch fa-spin"></i>');

			// load new results with selected results mode
			mr.loadResults(1,$(this).data('results'));
			$(this).blur();
		});

	    // select2 plugin for select elements
		var add_custom = 1;
		if ($(".accounts-body").length>0) { add_custom = 0; }

		$(document).on(".company-selector")
	/**** Invoke all select2() modules *****/
	if (!!$.prototype.select2) {
	    $(".company-selector").select2({
	    	placeholder: '- Select a Company -',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/companies.php",
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
	                return {
	                    add_custom: add_custom,
	                    q: params.term,//search term
						page: params.page
	                };
	            },
				allowClear: true,
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
/*
						results: data.results,
						pagination: {
							more: (params.page * 30) < data.total_count
						}
*/
					};
				},
				cache: true
	        },
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
	        minimumInputLength: 0
	    });
	    $(".lists-selector").select2({
			placeholder: 'Upload or Select a List...',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/lists.php",
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
	                return {
	                    q: params.term,//search term
						page: params.page
	                };
	            },
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
					};
				},
				cache: true
	        },
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
	        minimumInputLength: 0
		});
		$(".contact-selector").select2({
			placeholder: '- Select a Contact -',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/contacts.php",
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
	                return {
	                    q: params.term,//search term
						page: params.page
	                };
	            },
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
					};
				},
				cache: true
	        },
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
	        minimumInputLength: 0
		});
		$(".address-selector").select2({
			placeholder: '- Select an Address -',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/address-picker.php",
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
	                return {
	                    q: params.term,//search term
						page: params.page
	                };
	            },
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
					};
				},
				cache: true
	        },
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
	        minimumInputLength: 0
		});

		$(".location-selector").select2({
			width: '100%',
			placeholder: '- Select Location -',
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/json/locations.php",
				dataType: 'json',
				data: function (params) {
					return {
						noreset: $(this).data('noreset'),
						q: params.term,//search term
						page: params.page
					};
				},
				allowClear: true,
				processResults: function (data, params) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
					return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
					};
				},
				cache: true
			},
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
			minimumInputLength: 0
		});

		$('.condition-selector').select2({
			width: '100%',
			ajax: {
				url: '/json/conditions.php',
				dataType: 'json',
				data: function (params) {
					return {
						q: params.term,//search term
					};
				},
				processResults: function (data, params) {// parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
					return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
					};
				},
				cache: true
			},
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
			minimumInputLength: 0
		});

		$('.parts-selector').select2({
			width: '100%',
			ajax: {
				url: '/json/parts-dropdown.php',
				dataType: 'json',
				data: function (params) {
					return {
						partid: $(this).data('partid'),
						q: params.term,//search term
					};
				},
				processResults: function (data, params) {// parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
					return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
					};
				},
				cache: true
			},
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
			minimumInputLength: 0
		});

		$(".task-selector").select2({
	    	placeholder: '- Select a Task -',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/companies.php",
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
	                return {
	                    add_custom: add_custom,
	                    q: params.term,//search term
						page: params.page
	                };
	            },
				allowClear: true,
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
/*
						results: data.results,
						pagination: {
							more: (params.page * 30) < data.total_count
						}
*/
					};
				},
				cache: true
	        },
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
	        minimumInputLength: 0
	    });
	    $(".terms-select2").select2({
		});
	}

		$(".accounts-body #companyid, .profile-body #companyid").change(function() {
			if ($.isNumeric($(this).val())) {
				$(this).closest("form").submit();
			}
		});

	    $(".terms-select2.terms-type").change(function() {
			var selections = [];
			var type;
			$(this).find("option:selected").each(function(k,v) {
				type = $(this).prop("value");
				selections.push(type);
			});
			$(this).closest(".terms-section").find(".terms-select2.terms-selections option").each(function(k,v) {
				if ($.inArray($(this).data("type"),selections)==-1) {
					$(this).prop('disabled',true);
					$(this).prop('selected',false);
				} else {
					$(this).prop('disabled',false);
					$(this).prop('selected',true);
				}
			});
			$(this).closest(".terms-section").find(".terms-select2.terms-selections").trigger('change');
		});
//		$(".lists-selector").bind('change keypress',function(e) {
//			if (e.keyCode && e.keyCode != 13) { return; }
		$(".lists-selector").each(function() {
			$(this).bind('change',function(e) {
				uploadFile($(this));
			});
		});
		$("#upload-companyid").change(function() {
			if (! $(this).val()) { return; }

			if ($.cookie("upload_type."+$(this).val())) {
				var cookie_val = $.cookie("upload_type."+$(this).val());
				// set slider class and then invoke 'click' event to update radio buttons
				if (cookie_val=='Avail') {
					$("#upload-slider").removeClass("on").trigger('click');
				} else {
					$("#upload-slider").removeClass("on").addClass("on").trigger('click');
				}
			}
		});

		$(".advanced-search").click(function() {
			$("#advanced-search-options").toggleClass('hidden');
			$("#s2").focus();
			$("#s2").val($("#s").val());
			$("#s").val('');
			$(this).find('.options-toggle').each(function() {
				if ($(this).hasClass('fa-sort-desc')) { $(this).toggleClass('fa-sort-desc fa-sort-asc'); }
				else { $(this).toggleClass('fa-sort-asc fa-sort-desc'); }
			});
		});
		// focus to navbar search field if there is no class overriding the focus
		if ($(".auto-focus").length==0) {
			$("#s").focus(function() {
				if (! $("#advanced-search-options").hasClass('hidden')) {
					$("#advanced-search-options").toggleClass('hidden');
					$("#s").val($("#s2").val().replace(/\r\n|\r|\n/g," "));
				} else {
					
				}
			});
		} else {
			$(".auto-focus").first().focus();
		}
		$("#s").change(function() {
			$("#s2").val("");
		});
		$("#btn-range-options").hover(function() {
			$("#date-ranges").toggleClass('hidden');
		});
		$(".mode-tab").click(function(e) {
			// prevent normal 'a href=' behavior
			e.preventDefault();

			// remember the user's selection for next time they open their browser
			var action = $(this).prop('href');
			$("#SEARCH_MODE").val(action);
			// save to cookie for later recall after browser is closed and then re-opened
			$.cookie("SEARCH_MODE",action);

			// wrap the link into the wrapping form so we can post search data to the switched tab
			var form = $(this).closest("form");
			form.prop('action',$(this).prop('href'));
			// if the user is holding 'cmd' or 'ctrl' keys, open to new tab; otherwise, reset to here
			if (e.metaKey || e.ctrlKey) {
				form.prop('target','_newtab');
			} else {
				form.prop('target','');
			}
			form.submit();
		});

		$(".btn-favorites").click(function() {
			if ($(this).hasClass('btn-default')) {
				$(this).removeClass('btn-default').addClass('btn-danger');
				$("#favorites").prop('checked',true);
			} else {
				$(this).removeClass('btn-danger').addClass('btn-default');
				$("#favorites").prop('checked',false);
			}
		});
		$(document).on("click", ".fav-icon", function() {
			var partid = $(this).data('partid');
			if ($(this).hasClass('fa-star-half-o')) {
				modalAlertShow("Favorites Alert","You are removing this from someone else's favorites! Do you really want to proceed?",true,'toggleFav',$(this).data('partid'));
			} else {
				toggleFav($(this).data('partid'));
			}
		});

		$('.datetime-picker').each(function() {
			// these settings are optional; if not set in the 'data-' tags, then set to false
			var format = false;
			if ($(this).data('format')) { format = $(this).data('format'); }
			var maxDate = false;
			if ($(this).data('maxdate')) { maxDate = $(this).data('maxdate'); }
			var hPosition = 'auto';
			if ($(this).data('hposition')) { hPosition = $(this).data('hposition'); }
			$(this).datetimepicker({
				/* use font awesome icons instead of glyphicons. because i said so. */
				icons: {
					time: 'fa fa-clock-o',
					date: 'fa fa-calendar',
					up: 'fa fa-chevron-up',
					down: 'fa fa-chevron-down',
					previous: 'fa fa-chevron-left',
					next: 'fa fa-chevron-right',
					today: 'fa fa-screenshot',
					clear: 'fa fa-trash',
					close: 'fa fa-close'
				},
				format: format,
				maxDate: maxDate,
				widgetPositioning: { horizontal: hPosition }
			});
		});
		
		$(".btn-expdate").click(function() {
			$("#exp-date").val($(this).data('date'));
		});
		$(document).on("change",".market-price",function() {
			var cid = $(this).data('cid');/*closest(".row").find(".item-check").val();*/
			var date = $(this).data('date');
			var type = $(this).data('type');
			if ($(this).closest(".market-table").data('partids')) {
				var partids = $(this).closest(".market-table").data('partids');
			} else {
				var partids = $(this).closest(".modal-body").find("input[name='partids']").val();
			}
			var price = $(this).val();
            console.log(window.location.origin+"/json/save-market.php?companyid="+cid+"&date="+date+"&price="+price+"&partids="+partids+"&type="+type);
            $.ajax({
                url: 'json/save-market.php',
                type: 'get',
                data: {'companyid' : cid, 'date' : date, 'price' : price, 'partids' : partids, 'type' : type},
                success: function(json, status) {
					if (json.message=='Success') {
						toggleLoader('Price Updated Successfully');
					} else {
						// alert the user when there are errors
						alert(json.message);
					}
				},
                error: function(xhr, desc, err) {
//                    console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
            }); // end ajax call
		});
		$(document).on("click",".btn-upload",function() {
			if (! $("#upload-companyid").val()) {
				modalAlertShow("Company Alert","You must select a company before uploading a file!",false);
				return;
			}

			if (! $("#upload-listid").val()) {
				modalAlertShow("Upload Alert","You must select a file to upload!",false);
				return;
			}

			//submit form
			var form = $(this).closest("form");
			form.prop('action','/upload.php');
			form.submit();
		});
		$(document).on("click",".btn-search",function() {
			document.location.href = '/?listid='+$("#upload-listid").val();
		});
	
		$(".results-form *[type='submit']").on("click",function(e) {
			var cid = $("#companyid").val();
			if (! cid) {
				// set submitting form on the alert button so we can capture it on user click, and continue to submit that form if they desire
				$('#alert-continue').data('form',$(this).closest("form"));
				modalAlertShow("Company Alert","Your data will not be saved without a company selected! Do you really want to proceed?",true);

				return false;
			} else {
//				$(this).data('form').submit();
				$("#submit_type").val($(this).data('type'));
				return true;
			}
		});
		$('#alert-continue').click(function() {
			if ($(this).data('form')!='') { $(this).data('form').submit(); }
			else if ($(this).data('callback')!='') { window[$(this).data('callback')]($(this).data('element')); }
		});
		$(".qty input[type='text']").click(function() {
			$(this).select();
		});

		$("input#upload-file").change(function() {
			var upload_file = $(this).val().replace("C:\\fakepath\\","");
			//$("#upload-listid").html("<option value='"+upload_file+"' selected>"+upload_file+"</option>");
			//$("#upload-listid").val(upload_file).trigger('change');
			var option = $('<option></option>').
				prop('selected', true).
				text(upload_file).
				val(upload_file);
				/* insert the option (which is already 'selected'!) into the select */
				option.appendTo($("#upload-listid"));
				/* Let select2 do whatever it likes with this */
				$("#upload-listid").trigger('change');
		});

		$(".pagination li a").click(function() {
			var urlstr = '';
			$(".search-filter").each(function() {
				urlstr += '&'+$(this).prop("name")+'='+escape($(this).prop("value"));
			});
			document.location.href = '/?listid='+$(this).data('listid')+'&pg='+$(this).data('pg')+urlstr;
		});


		$('.btn-remote').click(function() {
			var remote = $(this).prop('id').replace('remote-','');
			$('.remote-name').html('<img src="/img/'+remote+'.png"> '+$(this).data('name')+' login');
			$('#remote-activate').data('remote',remote);
			$('#remote-modal').modal('show');
		});
		$('.btn-notes').click(function() {
			setNotes();
		});

		$('#remote-activate').click(function() {
			var remote = $('#remote-activate').data('remote');
			var remote_login = $("#remote-login").val();
			var remote_password = $("#remote-password").val();
            console.log(window.location.origin+"/json/remotes.php?remote="+remote+"&remote_login="+remote_login+"&remote_password="+remote_password);
            $.ajax({
                url: 'json/remotes.php',
                type: 'get',
                data: {'remote': remote, 'remote_login': remote_login, 'remote_password': remote_password},
                success: function(json, status) {
					if (json.err) {
						alert(json.err);
					} else {
						toggleLoader(json.response);
						$('#remote-modal').modal('hide');
						$("#remote-"+remote).addClass('hidden');

						// request all market results to reload now with the activated remote
				        $(".market-results").each(function() {
							$(this).loadResults(0);
						});
					}
				},
                error: function(xhr, desc, err) {
//                    console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
			});
		});
		
		/*Aaron: Function Suite for filter buttons*/
		$('td[id*=Ranges]').children().click(function() {
			$(this).siblings('button[class*=active]').toggleClass("active");
		});
		$(".btn-report").click(function() {
			var start_date = $(this).data('start');
			var end_date = $(this).data('end');
			$("input[name='START_DATE']").val(start_date);
			$("input[name='END_DATE']").val(end_date);
		});
        $('.btn-radio').click(function() {
			var btn = $(this);
            var btnValue = btn.data('value');
            $(this).closest("div").find("input[type=radio]").each(function() {
				if ($(this).val()!=btnValue) { return; }
				$(this).attr('checked',true);
            });
        });
		$('td[id*=Ranges]').children().click(function() {
			$(this).siblings('button[class*=active]').toggleClass("active");
		});
		/*
		$('#shortDateRanges').hover(function(){
			//$(this).parent('td').removeClass("col-md-1");
	    	$(this).removeClass("col-md-1 btn-group");
			$(this).addClass("col-md-2 btn-group");
			$(this).next().removeClass("col-md-2 text-center");
			$(this).next().addClass("col-md-1 text-center");
			$(this).children('button[class*=center]').show();
		},function() {
			$(this).children('button[class*=center]').hide();
			$(this).removeClass("col-md-2 btn-group");
			$(this).addClass("col-md-1 btn-group");
			$(this).next().removeClass("col-md-1 text-center");
			$(this).next().addClass("col-md-2 text-center");
		});
		
		$('#dateRanges').hover(function(){
			$(this).parent().children('button[class*=center]').toggle();
			$(this).parent().removeClass("col-md-2 btn-group");
			$(this).parent().addClass("col-md-1 btn-group");
		});
	*/
		$('#YTD').click(function() {
			var year = new Date().getFullYear();
			var month = new Date().getMonth();
			month++;
			var day = new Date().getDate(2);
			day = ("0" + day).slice(-2);
			month = ("0" + month).slice(-2);
			var today = ''.concat(month).concat('/').concat(day).concat('/').concat(year);
			//alert('Day '.concat(today));
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('01/01/'.concat(year));
			$("input[name='END_DATE']").val(today);
		});
		$('#MTD').click(function() {
			var year = new Date().getFullYear();
			var month = new Date().getMonth();
			month++;
			var day = new Date().getDate();
			day = ("0" + day).slice(-2);
			month = ("0" + month).slice(-2);
			var today = ''.concat(month).concat('/').concat(day).concat('/').concat(year);
			var begin = ''.concat(month).concat('/01/').concat(year);
			
			//alert('Day '.concat(today));
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val(begin);
			$("input[name='END_DATE']").val(today);
			
			//$("input[name='START_DATE']").initDatetimePicker("MM/DD/YYYY");
		});
		$('#Q1').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('01/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('03/31/'.concat(year));
		});
		$('#Q2').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('04/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('06/30/'.concat(year));
		});
		$('#Q3').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('07/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('09/30/'.concat(year));
		});
		$('#Q4').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('10/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('12/31/'.concat(year));
		});
			

	/*Aaron: Function for inventory ghosting*/
		$(".ghost_delete").click(function() {
			$(this).parents("tr").hide();
			$(this).parents("tr").find(".ghost_percent").val(0);
			$(this).parents("#ghost").find("#save_changes").trigger("click");
		});
		
		$(document).on("change",".ghost_value",function(){
			$(this).val();
			var last = $(".ghost_value:last").val();
			if (last > 0){
				$('#ghost tr:last').after('<tr>\
										<td>\
								<select name="companyid" class="company-selector ghost_company">\
									<option value="">- Select a Company -</option>\
									<?php\
									if ($company_filter) {echo "<option value="".$company_filter."" selected>".(getCompany($company_filter))."</option>".chr(10);} \
									else {echo "<option value="">- Select a Company -</option>".chr(10);}\
									?>\
								</select>\
							</td>\
							<td>\
								<div class="input-group">\
	                                <input type="text" class="form-control ghost_value" style="height: 28px; padding-top: 3px;padding-bottom: 3px;">\
	                                <span class="input-group-addon">%</span>\
	                            </div>\
	                        </td>\
				</tr>');
			} 
		});	

		/* clicking in these fields made the dropdown-menu toggle away for some reason, so prevent it */
		$(".order-search").on("click",function(e) {
			e.preventDefault();
			return false;
		});
		$(".order-search").on("keypress",function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				if ($(this).data('type') == 'INV') {
					window.open('/docs/INV'+$(this).val()+'.pdf','_blank');
				} else if($(this).data('type') != 'RMA' && $(this).data('type') != 'RO') {
					document.location.href = '/'+$(this).data('type')+$(this).val();
				} else if($(this).data('type') == 'RO'){
					document.location.href = '/order_form.php?ps='+$(this).data('type')+'&on='+$(this).val();
				} else if($(this).data('type') == 'BO') {
					document.location.href = '/builds_management.php?on='+$(this).val();
				} else {
					document.location.href = '/rma.php?rma='+$(this).val();
				}
			}
		});
		$(".order-search-button").on("click",function(e) {
			e.preventDefault();
			var search_field = $(this).closest(".input-group").find("input[type='text']");
			if (search_field.data('type') == 'INV') {
				window.open('/docs/INV'+search_field.val()+'.pdf','_blank');
			} else if(search_field.data('type') != 'RMA' && search_field.data('type') != 'RO') {
				document.location.href = '/'+search_field.data('type')+search_field.val();
			} else if(search_field.data('type') == 'RO') {
				document.location.href = '/order_form.php?ps='+search_field.data('type')+'&on='+search_field.val();
			} else if(search_field.data('type') == 'BO') {
				document.location.href = '/builds_management.php?on='+search_field.val();
			} else {
				document.location.href = '/rma.php?rma='+search_field.val();
			}
			
		});

//==============================================================================
//=================================== HISTORY ================================== 
//==============================================================================

		$(document).on("click",".btn-history",function() {
			
			var invid = $(this).attr('data-id');
			if(invid){
				$("#history_items").html("");
				//Call the AJAX
				$.ajax({
						type: "POST",
						url: '/json/item_history.php',
						data: {
							'inventory' : invid,
							'mode' : 'display'
						},
						dataType: 'json',
						success: function(lines) {
							$("#history_items").html("");
							//Clear the modal
							$(".history_line").remove();
							console.log(lines);
							//Populate the modal
							$.each(lines, function(i, phrase){
								//$("#history_items").append("<li class = 'history_line'>"+phrase+"</li>");
								$("#history_items").append("\
									<div class='row history_meta'>\
										<div class='col-sm-4' style='vertical-align: middle;'>"+i+"</div>\
										<div class='col-sm-8'>"+phrase+"</div>\
									</div>");
							});
							//Show the modal
							$("#modal-history").modal("show");
							
							console.log("JSON history_modal | Success | /json/item_history.php?inventory="+invid+"&mode=display");
						},
						error: function(xhr, status, error) {
							alert(error+" | "+status+" | "+xhr);
							console.log("JSON history_modal | Failure | /json/item_history.php?inventory="+invid+"&mode=display");
						}
				});
			}
		});
//=================================== END HISTORY ================================== 

    });/* close $(document).ready */

	jQuery.fn.populateSelected = function(id, text) {
		/* build option that will populate the menu */
		var option = $('<option></option>').
			prop('selected', true).
			text(text).
			val(id);
		/* insert the option (which is already 'selected'!) into the select */
		option.appendTo($(this));
		/* Let select2 do whatever it likes with this */
		$(this).trigger('change');
	};/*jQuery.fn.populateSelected*/

	var last_week = '';
    // build jquery plugin for remote ajax call
    jQuery.fn.loadResults = function(attempt, results_mode) {
		if (! results_mode) { var results_mode = '0'; }
        var newHtml = '';
        var rowHtml = '';
        var qtyTotal = 0;
        var inputDis;
        var container = $(this);
        var ln = $(this).data('ln');
		var thisId = container.prop('id');
		if (attempt==2) {
            container.html('<i class="fa fa-circle-o-notch fa-spin"></i>');
		}

		var doneFlag = '';
		var partids = container.closest(".market-table").data('partids');
		var type = container.data('type');

		var date = '';
    	var last_month = '';
    	var last_year = '';
		var init = true;

		var hr = true;

        console.log(window.location.origin+"/json/availability.php?attempt="+attempt+"&partids="+partids+"&ln="+ln+"&results_mode="+results_mode+"&type="+type);

        $.ajax({
            url: 'json/availability.php',
            type: 'get',
            data: {'attempt': attempt, 'partids': partids, 'ln': ln, 'results_mode': results_mode, 'type': type},
			settings: {async:true},
            success: function(json, status) {
                $.each(json.results, function(dateKey, item) {
                	var rowDate = '';
                	var curDate = new Date();

                	var cls1 = '';
                	var cls2 = '';


                	//Set the first date to the first record, getSupply function already orders the records by date DESC
                	if(init) {
                		//Get the first date from the item array (probably a way better way to implement this feature)
                		$.each(item, function(key, row) {
                			var dateParts = row.date.split("-");
							date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);

							return false;
						});

						if(date) {
	                		//Based on the first date of entries found
							last_month = date.setMonth(date.getMonth() - 1, 1);
							last_year = date.setMonth(date.getMonth() - 11, 1);

							last_week = new Date(curDate.setTime(curDate.getTime() - (6 * 24 * 60 * 60 * 1000)));

							init = false;
						}
                	}

                    qtyTotal = 0;

                    rowHtml = '';
                    /* process each item's data */
                    $.each(item, function(key, row) {
						//disable input fields for ebay
						inputDis = '';
						if (row.cid==34) inputDis = ' disabled';

                        qtyTotal += parseInt(row.qty,10);
                        rowHtml += '<div class="market-data market-company-'+row.cid+'"><div class="pa">'+row.qty+'</div> <i class="fa fa-'+row.changeFlag+'"></i> '+
                            '<a href="/profile.php?companyid='+row.cid+'" class="market-company">'+row.company+'</a> &nbsp; ';
                        $.each(row.sources, function(i, src) {
							if (src=='email') {
								rowHtml += '<i class="fa fa-email"></i>';
							} else if (src != 'import') {
                           		rowHtml += '<img src="img/'+src.toLowerCase()+'.png" class="bot-icon" />';
							}
						});
                        rowHtml += '&nbsp; <input type="text" data-type="'+type+'" class="form-control input-xxs market-price" value="'+row.price+'" '+
									'data-date="'+row.date+'" data-cid="'+row.cid+'" onFocus="this.select()"'+inputDis+'/></div>';
                    });

					doneFlag = json.done;

					$.each(item, function(key, row) {
						var dateParts = row.date.split("-");
						rowDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);

						return false;
					});

					if(last_week!='' && rowDate >= last_week) { 
						cls1 += '<span class="last_week">';
						cls2 += '</span>';
					} 

					if(type == 'demand') {

						if(rowDate < last_year) { 
							if(hr) {
								cls1 = '<hr>';
								hr = false;
							}
							cls1 += '<span class="archives">';
							cls2 += '</span>';
						} else if (rowDate < last_month) {
							if(hr) {
								cls1 = '<hr>';
								hr = false;
							}
							cls1 += '<span class="summary">';
							cls2 += '</span>';
						}
					}

                    /* add section header of date and qty total */
                    //if(type == 'supply') {
	                    newHtml += cls1+addDateGroup(dateKey,qtyTotal,doneFlag, type)+rowHtml+cls2;
	                // } else {
	                // 	newHtml += rowHtml;
	                // }
                });
                container.html(newHtml);

				// alert the user when there are errors with any/all remotes by unhiding alert buttons
				$.each(json.err, function(i, remote) {
					$("#remote-"+remote).removeClass('hidden');
				});
				//alert(type);
				if(type == 'supply') {
					$("#marketpricing-"+ln).html('<i class="fa fa-circle-o-notch fa-spin"></i>');

//					$("#shelflife-"+ln).html('');
	                if (! json.done && attempt==0) {
	                    //setTimeout("$('#market-results').loadResults()",1000);
						setTimeout("$('#"+container.prop('id')+"').loadResults("+(attempt+1)+","+RESULTS_MODE+")",1000);
	                } else if (json.done==1) {
						// after done loading the market results, show the market pricing summary and toggle
						var price_range = '';
						var pr = json.price_range;

						// alert(pr.min + ' ' + type);
						if (pr.min && pr.max) {
							if (pr.min==pr.max) {
								price_range = '$'+pr.min;
							} else {
								price_range = '$'+pr.min+' - $'+pr.max;
							}
						} 

						$("#marketpricing-"+ln).html(price_range);
					}
//						$("#shelflife-"+ln).html(json.shelflife);
				}
            },
            error: function(xhr, desc, err) {
//                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call

        return;
    };

	
	
	function toggleLoader(msg) {
		if ($("#loading-bar").is(':visible')) {
			$("#loading-bar").fadeOut('fast');
		} else {
			if (! msg) { var msg = 'Loading'; }
			$("#loading-bar").html(msg);

			$("#loading-bar").show();
			setTimeout("toggleLoader()",1000);
		}
	}

	function uploadFile(e) {
		if (! e.val()) { return; }

//		var dataArray = [];
		var upload = false;
		var listid = 0;
		e.find("option:selected").each(function() {
//			console.log($(this).val());
			if ($(this).val()=='upload') {
				upload = true;
			} else {
//				dataArray.push($(this));
				listid = $(this).val();
			}
		});

		if (upload===true) {
			// if details is toggled on, hide it
			if (! $("#upload-details").hasClass("hidden")) {
				$("#upload-details").addClass("hidden");
			}

			e.val("");//reset selection
			$("#upload-file").click();

			$("#upload-options").find(".content-box-header").html(box_header);
			$("#upload-options").find(".content-box-body").html(box_body);
			$("#upload-options").find(".content-box-footer").html(box_footer);

			// reveal the built html
			$("#upload-options").removeClass('hidden');
		} else if (listid>0) {
			// if options box is toggled on, hide it
			if (! $("#upload-options").hasClass("hidden")) {
				$("#upload-options").addClass("hidden");
			}

			var box_header = '';
			var box_body = '';
			var box_footer = '';
			var processed = '';
			var list_type = '';
        	console.log(window.location.origin+"/json/list-select.php?id="+listid);
	        $.ajax({
				url: 'json/list-select.php',
				type: 'get',
				data: {'id': listid},
				dataType: 'json',
				success: function(json, status) {
					if (json.processed=='') {
						processed = '<i class="fa-li fa fa-times text-danger"></i> not processed';
					} else {
						processed = '<i class="fa-li fa fa-check text-success"></i> processed '+json.processed;
					}
					if (json.type=='availability') {
						list_type = '<i class="fa-li fa fa-info-circle text-warning"></i> Supply';
					} else {
						list_type = '<i class="fa-li fa fa-info-circle text-success"></i> Demand';
					}

					box_header = '<h4 class="content-box-title">'+json.name+'</h4><h5>'+json.filename+'</h5>';
					box_body = '<div class="row text-left">'+
							'<div class="col-sm-6">'+
								'<ul class="fa-ul">'+
									'<li><i class="fa-li fa fa-user text-primary"></i> '+json.user+'</li>'+
									'<li>'+list_type+'</li>'+
									'<li>'+processed+'</li>'+
								'</ul>'+
							'</div><!-- col-sm-6 -->'+
							'<div class="col-sm-6">'+
								'<ul class="fa-ul">'+
									'<li><i class="fa-li fa fa-upload text-muted"></i> <em>'+json.datetime+'</em></li>'+
									'<li><i class="fa-li fa fa-clock-o text-muted"></i> '+json.exp_datetime+'</li>'+
								'</ul>'+
							'</div><!-- col-sm-6 -->'+
						'</div><!-- row -->';
					box_footer = '<a class="btn btn-default btn-action" href="'+json.link+'" title="download this file"><i class="fa fa-download"></i></a>'+
							'<button class="btn btn-primary btn-search btn-action" type="button" title="view search result(s) for this file"><i class="fa fa-search"></i></button>';

					$("#upload-details").find(".content-box-header").html(box_header);
					$("#upload-details").find(".content-box-body").html(box_body);
					$("#upload-details").find(".content-box-footer").html(box_footer);

					// reveal the built html
					$("#upload-details").removeClass('hidden');
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		}
	}
	function addPart(search) {
        console.log(window.location.origin+"/json/addPart.php?search="+search);
        $.ajax({
            url: 'json/addPart.php',
            type: 'get',
            data: {'search': search},
			dataType: 'json',
            success: function(json, status) {
				if (json.message=='Success') {
					//alert(json.message);
					location.reload();
				} else {
					alert(json.message);
				}
            },
            error: function(xhr, desc, err) {
//                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call
	}
	function reindexParts(search) {
        console.log(window.location.origin+"/json/indexer.php?search="+search);
        $.ajax({
            url: 'json/indexer.php',
            type: 'get',
            data: {'search': search},
			dataType: 'json',
            success: function(json, status) {
				if (json.message=='Success') {
					//alert(json.message);
					location.reload();
				} else {
					alert(json.message);
				}
            },
            error: function(xhr, desc, err) {
//                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call
	}
	function mergeParts(rows) {
		var partids = [];
		rows.each(function() {
			partids.push($(this).val());
		});
        console.log(window.location.origin+"/json/merge-parts.php?partids="+partids);
        $.ajax({
            url: 'json/merge-parts.php',
            type: 'get',
            data: {'partids': partids},
			dataType: 'json',
            success: function(json, status) {
				if (json.message=='Success') {
					location.reload();
				} else {
					alert(json.message);
				}
            },
            error: function(xhr, desc, err) {
//                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call
	}
	function modalAlertShow(header,body,show_continue,callback,arg1) {
		$('#modalAlertTitle').html(header);
		$('#modalAlertBody').html(body);
		if (show_continue===true) {
			$('#alert-continue').removeClass('hidden');
			// change verbiage to "Cancel" when there's a Continue button
			$('#modal-alert').find('.btn-dismiss').html('Cancel');
		} else {
			$('#alert-continue').removeClass('hidden').addClass('hidden');
			// change verbiage to "Close" when there's no Continue button
			$('#modal-alert').find('.btn-dismiss').html('Close');
		}
		if (! callback) { var callback = ''; }
		$('#alert-continue').data('callback',callback);
		if (! arg1) { var arg1 = ''; }
		$('#alert-continue').data('element',arg1);
       	$('#modal-alert').modal('toggle');
	}
	function toggleFav(partid) {
        console.log(window.location.origin+"/json/favorites.php?partid="+partid);
        $.ajax({
            url: 'json/favorites.php',
            type: 'get',
            data: {'partid': partid},
			dataType: 'json',
            success: function(json, status) {
				if (json.message=='Success') {
					// change favorites icon
					if (json.favorite==1) {
						$("#row-"+partid+" .fav-icon").removeClass('fa-star-half-o fa-star-o text-danger').addClass('fa-star text-danger');
					} else {
						$("#row-"+partid+" .fav-icon").removeClass('fa-star-half-o fa-star text-danger').addClass('fa-star-o');
					}
				} else {
					alert(json.message);
				}
            },
            error: function(xhr, desc, err) {
//                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call

		return;
	}
    function addDateGroup(dateKey,qtyTotal,doneFlag, type) {
    	var groupStr = ''

        //var groupStr = '<div class="date-group"><a href="javascript:void(0);" class="modal-results" data-target="marketModal">'+
        if (type == 'supply') {
        	groupStr = '<div class="date-group">'+dateKey+': qty '+qtyTotal+' ';
        } else {
        	groupStr = '<div class="date-group">'+dateKey+': qty '+qtyTotal+' ';
        }
        if (! doneFlag && dateKey=='Today' && type == 'supply') {
            groupStr += '<i class="fa fa-circle-o-notch fa-spin"></i>';
        }
        groupStr += '</div>';
        return (groupStr);
    }
	function refreshNotes() {
		// if there are any notes in the textarea, cancel any interval updates
		if ($("#modalNotes").find("textarea[name='user_notes']").val()!='') {
			clearInterval(NOTES_SESSION_ID);
			NOTES_SESSION_ID = false;
			return;
		}
		setNotes();
	}
	function setNotes() {
		// get refid passed into save button, and use it to find object for placement of the notes modal,
		// then get user notes body and pass to toggleNotes() to add new entry and to refresh notes modal
		//var itemRow = $("#"+$(this).data('refid'));
		var itemRow = $("#"+$("#modalNotes").find("#save-notes-btn").attr('data-refid'));
		var itemObj = itemRow.find(".item-notes:first");

		console.log($("#modalNotes").find("#save-notes-btn").attr('data-refid'));

		//var user_textarea = $(this).closest(".notes-body").find("textarea[name='user_notes']");
		var user_textarea = $("#modalNotes").find("textarea[name='user_notes']");
		var notes = user_textarea.val();

		toggleNotes(itemObj,notes);
	}
	var NOTES_SESSION_ID = false;
	function toggleNotes(e,add_notes) {
		if (! add_notes) { var add_notes = ''; }
		e.find("i.fa").removeClass('text-danger fa-warning fa-lg').addClass('text-warning fa-sticky-note');
		var outerBody = e.closest(".descr-row");
		var pos = e.position();
		var width = outerBody.outerWidth();
		var productBody = outerBody.find(".product-descr:first");
		var partid = productBody.data('partid');
		var pipe_ids = productBody.data('pipeids');
		/* save part/pipe ids to the button for when the user saves the notes */
		$("#save-notes-btn").attr("data-refid",e.closest(".product-results").prop("id"));

		//$("#save-notes-btn").attr("data-refid",'row-' + e.closest(".product-descr").data("partid"));

        console.log(window.location.origin+"/json/notes.php?partid="+partid+"&pipe_ids="+pipe_ids+"&add_notes="+escape(add_notes));
        $.ajax({
            url: 'json/notes.php',
            type: 'get',
            data: {'partid': partid, 'pipe_ids': pipe_ids, 'add_notes': escape(add_notes)},
			dataType: 'json',
            success: function(json, status) {
				if (json.results) {
					// clear textarea for next entry upon successful results
					$("#modalNotes").find("textarea[name='user_notes']").val("");

					updateNotes(json.results);
					if (NOTES_SESSION_ID===false) { NOTES_SESSION_ID = setInterval(refreshNotes,5000); }
				} else {
					var message = 'There was an error processing your request!';
					if (json.message) { message = json.message; } // show response from the php script.
					alert(message);
				}
            },
            error: function(xhr, desc, err) {
//                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call

		var eTop = productBody.offset().top - $(window).scrollTop();
		$("#modalNotes .modal-content").css({
			top:(eTop+40)+"px",
			left:(outerBody.position().left)+"px",
			width: width,
		});
		$("#modalNotes").modal('show');
	}
	function closeModal(e) {
		e.modal('hide');
		if (NOTES_SESSION_ID!==false) {
			clearInterval(NOTES_SESSION_ID);
			NOTES_SESSION_ID = false;
		}
	}
	function updateNotes(results) {
		var table_html = '';
		var user;
		$.each(results, function(dateKey, row) {
			user = '';
			if (row.user!='') user = '- <strong>'+row.user+'</strong>, ';
			/* process each item's data */
			table_html += '<tr><td>'+row.note+' <div class="source">'+user+row.date+'</div></td></tr>';
		});

		var modalBody = $("#modalNotes .modal-body:first .table-notes:first");
		modalBody.html(table_html);
	}
	function viewNotification(messageid,search, link) {
		// this function gets all notifications only for the purpose of marking them as "clicked", then sends user to that search results page
        console.log(window.location.origin+"/json/notes.php?messageid="+messageid);

        $.ajax({
            url: 'json/notes.php',
            type: 'get',
            data: {'messageid': messageid},
			dataType: 'json',
            success: function(json, status) {
				if (json.results) {
					document.location.href = link;
				} else {
					var message = 'There was an error processing your request!';
					if (json.message) { message = json.message; } // show response from the php script.
					alert(message);
				}
            },
            error: function(xhr, desc, err) {
//                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call
	}
	function loadOrders() {
		if ($("#purchase-orders-list").length==0 && $("#sales-orders-list").length==0 && $("#repair-orders-list").length==0 && $("#return-orders-list").length==0 && $("#build-orders-list").length==0) { return; }

		// do the thing
		refreshOrders();
		// now load the thing on a schedule for every X mins
		setInterval( refreshOrders,(1000*60*15));//15 mins
	}
	function refreshOrders() {
        console.log(window.location.origin+"/json/orders-list.php");
		$.ajax({
			url: 'json/orders-list.php',
			type: 'get',
			data: {},
			dataType: 'json',
			success: function(json, status) {
				var sales = $("#sales-orders-list");
				sales.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.sales, function(key, order) {
					sales.append('<li><a href="/order_form.php?ps=Sale&on='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});
				var purchases = $("#purchase-orders-list");
				purchases.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.purchases, function(key, order) {
					purchases.append('<li><a href="/order_form.php?ps=Purchase&on='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});
				var repairs = $("#repair-orders-list");
				repairs.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.repairs, function(key, order) {
					repairs.append('<li><a href="/order_form.php?ps=ro&on='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});

				var returns = $("#return-orders-list");
				returns.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.returns, function(key, order) {
					returns.append('<li><a href="/rma.php?rma='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});

				var builds = $("#build-orders-list");
				builds.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.builds, function(key, order) {
					builds.append('<li><a href="/builds_management.php?on='+order.build_number+'">'+order.build_number+' '+order.company+'</a></li>');
				});
			},
			error: function(xhr, desc, err) {
                console.log("Details: " + desc + "\nError:" + err);
			}
		});
	}
	// sets action url on search mode of navbar tabs
	function setSearchMode(action) {
	}

	Number.prototype.formatMoney = function(c, d, t){
		var n = this, 
		c = isNaN(c = Math.abs(c)) ? 2 : c, 
		d = d == undefined ? "." : d, 
		t = t == undefined ? "," : t, 
		s = n < 0 ? "-" : "", 
		i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))), 
		j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	};
