	if (typeof RESULTS_MODE === 'undefined') { RESULTS_MODE = 0; }
	var companyid = 0;
	var scope = 'Sale';
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
			var modal_target = $(this).data('target');
			$("#"+modal_target).modal('hide');

			var first = $(this).closest(".product-results").siblings(".first");
			if (first.length==0) {
				var items_row = $(this).closest(".items-row");
				var items_ln = items_row.data('ln');
				first = $("#row_"+items_ln);
			}

			var results_mode = '0';//default

			var productSearch = '';
			if(first.find(".product-search").val()) {
				productSearch = first.find(".product-search").val().toUpperCase();
			} else {
				productSearch = $(this).closest(".found_parts_quote").find(".part_description .descr-label").text().toUpperCase().trim();
//dgl 2-21-18
//				results_mode = 1;
			}
			var partids = '';
			if ($(this).closest(".market-table").length>0) {
				partids = $(this).closest(".market-table").data('partids');
			} else if ($(this).closest(".items-row").find(".table-items tr").length>0) {
				partids = getCheckedPartids($(this).closest(".items-row").find(".table-items tr"));
			}

			var ln = '';
			if ($(this).closest(".market-results").length>0) {
				ln = $(this).closest(".market-results").data('ln');
			} else {
				ln = $(this).closest(".items-row").data('ln');
			}
			var results_title = $(this).data('title');
			var results_type = $(this).data('type');

			var type = 'modal'; //Used for sales ajax to getRecord()

			var modalBody = $("#"+modal_target+" .modal-body");
			modalBody.attr('data-ln',ln);//data('ln',ln);
			var rowHtml = addResultsRow(results_type);//row,actionBox,rfqFlag,sources,search_str,price,inputDis);

			$(this).closest(".product-results").find(".btn-resultsmode").find(".btn").each(function() {
				if (! $(this).hasClass('btn-primary')) { return; }
				results_mode = $(this).data('results');
			});

			if (results_mode=='1') { results_title += ' - Prices Only'; }
			else if (results_mode=='2') { results_title += ' - Ghosted Inventories'; }
			else { results_title += ' - All'; }
            $("#"+modal_target+" .modal-title").html(results_title);
			// reset html so when it pops open, there's no old data
			$("#"+modal_target+" .modal-body").html('<div class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-5x"></i></div>');

            if(results_type.toLowerCase() == 'supply' || results_type.toLowerCase() == 'demand') {

//	            console.log(window.location.origin+"/json/availability.php?attempt=0&partids="+partids+"&detail=1&results_mode="+results_mode+'&type='+results_type);

	            $.ajax({
	                url: 'json/availability.php',
	                type: 'get',
	                data: {'attempt': '0', 'partids': partids, 'results_mode': results_mode, 'detail': '1', 'type': results_type.toLowerCase()},
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
									var source_img = '';
									if (source_lower=='email') {
										source_img = '<i class="fa fa-envelope-o"></i>';
									} else if (source_lower=='import') {
										source_img = '<i class="fa fa-database"></i>';
									} else {
										source_img = '<img src="img/'+source_lower+'.png" class="bot-icon" />';
									}
									if (row.lns[source_lower]) {
										sources += '<a href="http://'+row.lns[source_lower]+'" target="_new">'+source_img+'</a> ';
									} else {
										sources += source_img+' ';
									}
								});
								if (sources=='') { sources = '&nbsp;'; }
								/***** END FIELDS SETUP *****/

								rowHtml += addResultsRow(results_type,row,actionBox,rfqFlag,sources,search_str,price,inputDis);
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
						modalBody.closest('.modal-content').find('.modal-footer').show();//.find('.text-left').show();
						//modalBody.closest('.modal-content').find('.modal-footer').find('#modal-submit').show();
						$("#"+modal_target).find(".companies-selector").selectize('/json/companies.php');

						$("#"+modal_target).modal('show');
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

			        		if(results_type.toLowerCase() == 'purchases' || results_type == 'Purchase') {
			        			abbrev = 'PO';
			        		}  else if(results_type.toLowerCase() == 'sales' || results_type == 'Sale') {
			        			abbrev = 'SO';
			        		} else {
			        			abbrev = 'RO';
			        		}    	
				        	console.log(result);
				        	modalBody.closest('.modal-content').find('.modal-footer').hide();//.find('.text-left').hide();
				        	//modalBody.closest('.modal-content').find('.modal-footer').find('#modal-submit').hide();
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
											<a href="/company.php?companyid='+row.cid+'" target="_new">'+row.name+'</a>\
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

							$("#"+modal_target).modal('show');
						}
			        },
			        error: function(xhr, desc, err) {
			            console.log("Details: " + desc + "\nError:" + err);
			        }
			    });
    			// rowHtml = $(this).parent().find('.market-body').html();
    			// modalBody.html(rowHtml);
    		}
        });

		$(".btn-suspend").on('click',function() {
			var f = $(this).closest("form");
			f.find("input[name=suspend]").val('1');
			var submit = f.submit();
			f.find("input[name=suspend]").val('');//reset
		});
		$(".modal-form").submit(function(e) {
			$('#loader-message').html('Please wait while your RFQ is being sent...');
			$('#loader').show();
			$('#modal-submit').prop('disabled',true);

			var modalForm = $(this);
			$.ajax({
				type: "GET",
				url: $(this).prop("action"),
				data: $(this).serialize(), // serializes the form's elements.
				dataType: 'json',
                success: function(json, status) {
					$('#loader').hide();
					$('#modal-submit').prop('disabled',false);

					if (json.message=='Success') {
						toggleLoader("RFQ sent successfully");
						modalForm.closest(".modal").modal("toggle");
						$("#s").focus();
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

//	        console.log(window.location.origin+"/json/notes.php");
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

		/* dropdown menu for input-group-btn groups that have hidden form elements for selected value */
		$(".dropdown-button li").on('click', function() {
			var v = $(this).text();
			var toggleln = $(this).closest(".dropdown").find(".dropdown-toggle");
			toggleln.find("input[type='hidden']").val(v);
			toggleln.find(".btn-dropdown").html(v);
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

		if ($("body").data('scope')) { scope = $("body").data('scope'); }

	/**** Invoke all select2() modules *****/
	if (!!$.prototype.select2) {

	    $(".company-selector").select2({
	    	placeholder: '- Select a Company -',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/companies.php",
	            dataType: 'json',
	            data: function (params) {
	                return {
						noreset: $(this).data('noreset'),
	                    order_type: ($(this).data('scope') ? $(this).data('scope') : scope),
	                    add_custom: add_custom,
	                    q: params.term,//search term
						page: params.page
	                };
	            },
				allowClear: true,
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
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

		$(".tech-selector").select2({
			placeholder: '- Select a Tech -',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/techs.php",
	            type: 'get',
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
	                return {
	                    q: params.term,//search term
						page: params.page,
						'type' : $('body').data('order-type')
	                };
	            },
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
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

		$(".address-selector").selectize('/json/addresses.php','- Select an Address -');
		$(".warranty-selector").selectize('/json/warranties.php');
		$(".condition-selector").selectize('/json/conditions.php');
		$(".contact-selector").selectize('/json/contacts.php','- Select a Contact -');
		$(".class-selector").selectize('/json/classes.php','- All Classes -');
		$(".user-selector").selectize('/json/users.php','- User -');
		$(".category-selector").selectize('/json/categories.php','- Category -');
		$(".companies-selector").selectize('/json/companies.php','- Company -');
		$(".invstatus-selector").selectize('/json/invstatuses.php','- Status -');

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

		$(".repair-task-selector").select2({
	        width: '100%',
			placeholder: '- Select a Task -',
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/json/tasks.php",
				dataType: 'json',
				data: function (params) {
					return {
						noreset: $(this).data('noreset'),
						q: params.term,//search term
						page: params.page,
						order_type: 'repair',
						userid: $('body').data('techid'),
					};
				},
				allowClear: true,
				processResults: function (data, params) { // parse the results into the format expected by Select2.
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

	    $(".service-task-selector").select2({
	        width: '100%',
			placeholder: '- Select a Task -',
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
				url: "/json/tasks.php",
				dataType: 'json',
				data: function (params) {
					return {
						noreset: $(this).data('noreset'),
						q: params.term,//search term
						page: params.page,
						order_type: 'service',
						userid: $('body').data('techid'),
					};
				},
				allowClear: true,
				processResults: function (data, params) { // parse the results into the format expected by Select2.
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

	    $(".terms-select2").select2({
		});
		$("select.select2").select2({
		});

		companyid = $(".sidebar select[name='companyid']").val();
		$(".sidebar select[name='companyid']").on('change', function() {
			var sidebar = $(this).closest(".sidebar");
			sidebar.find("select[name='bill_to_id']").select2('val', '');
			sidebar.find("select[name='ship_to_id']").select2('val', '');

			companyid = $(this).val();

			$("#bill_to_id").val('');
			termsid = 0;
			$("#termsid").val(12);//default to Credit Card
			carrierid = 0;
			$("#carrierid").val(1);//default to UPS
			$("#freight_account_id").populateSelected("","PREPAID");
			$("#ship_to_id").val('');

			orderOptionsWaterfall();
		});

		var carrierid = 0;
		var termsid = 0;
		function orderOptionsWaterfall() {
			var bill_to_id = 0;
			var ship_to_id = 0;
			var freight_account_id = 0;

			// only proceed with a real company selected
			if (! companyid || companyid==0) { return; }

			var params = '?companyid='+companyid;
			if (scope) {
				params += '&order_type='+scope;
			}
			if ($("#bill_to_id").val()>0) {
				bill_to_id = $("#bill_to_id").val();
				params += '&bill_to_id='+bill_to_id;
			}
			if ($("#ship_to_id").val()>0) {
				ship_to_id = $("#ship_to_id").val();
				params += '&ship_to_id='+ship_to_id;
			}
			if (termsid>0) {
				params += '&termsid='+termsid;
			}
			if (carrierid>0) {
				params += '&carrierid='+carrierid;
			}
			if ($("#freight_account_id").val()>0) {
				freight_account_id = $("#freight_account_id").val();
				params += '&freight_account_id='+freight_account_id;
			}

            console.log(window.location.origin+"/json/company_defaults.php"+params);
            $.ajax({
                url: 'json/company_defaults.php',
                type: 'get',
                data: {'companyid': companyid, 'order_type': scope, 'bill_to_id': bill_to_id, 'ship_to_id': ship_to_id, 'termsid': termsid, 'carrierid': carrierid, 'freight_account_id': freight_account_id},
                success: function(json, status) {
					if (json.bill_to_id>0) {
						$("#bill_to_id").populateSelected(json.bill_to_id,json.bill_to_address);
					}
					if (json.termsid>0) {
						$("#termsid").populateSelected(json.termsid,json.terms);
					}
					if (json.ship_to_id>0) {
						$("#ship_to_id").populateSelected(json.ship_to_id,json.ship_to_address);
					}
					if (json.carrierid>0) {
						$("#carrierid").val(json.carrierid).trigger('change');
					}
					$("#freight_account_id").populateSelected(json.freight_account_id,json.freight_account);
					if (json.conditionid>0) {
						$(".search-row").find(".condition-selector").populateSelected(json.conditionid,json.condition);
					}
					if (json.warrantyid>0) {
						$(".search-row").find(".warranty-selector").populateSelected(json.warrantyid,json.warranty);
					}

					partSearch('','',companyid,scope);
//					$("#item-table").populateItems(json.items);
				},
                error: function(xhr, desc, err) {
//					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
                }
            }); // end ajax call
		}

		$("#carrierid").on('change', function() {
			carrierid = $(this).val();

			$("#freight_account_id").val('').trigger('change');

			// reset freight services selector
			$("#freight_services_id").select2('val', '');
		});
		$("#termsid").on('change', function() {
			termsid = $(this).val();
		});
		$("#termsid").selectize('/json/terms.php','- Select -');
		$("#freight_account_id").select2({
			placeholder: 'PREPAID',
			ajax: {
				type: 'POST',
				url: '/json/freight_accounts.php',
				dataType: 'json',
	            data: function (params) {
	                return {
	                    companyid: companyid,
	                    order_type: scope,
	                    carrierid: $("#carrierid").val(),
						q: params.term,//search term
	                };
	            },
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
					};
				},
				cache: false
			},
		});
		$("#freight_services_id").selectize('/json/freight_services.php','- Select Freight Service -');
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
				if (cookie_val=='Avail' && $("#upload-slider").hasClass('on')) {
					$("#upload-slider").removeClass("on").trigger('click');
				} else if (cookie_val=='Req' && $("#upload-slider").hasClass('off')) {
					$("#upload-slider").removeClass("on").addClass("on").trigger('click');
				}
			}
		});

		$(document).on("shown.bs.modal",".modal",function(e) {
			var first_field = $(this).find("[autofocus]")[0];
			if (! first_field) {
				first_field = $(this).find("input[type=text]")[0];
			}
			if (first_field) { first_field.focus(); }
		});
//		$('.modal').on('shown.bs.modal', function () {
//			$('input[type=text]:first').focus();
//		});
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

			// add active class to selected tab, remove all others
			$(this).closest("header.navbar").find("ul li.dropdown.active").each(function() {
				$(this).removeClass('active');
			});
			$(this).closest("li").removeClass('active').addClass('active');

			// wrap the link into the wrapping form so we can post search data to the switched tab
			var form = $(this).closest("form");
			form.prop('action',$(this).prop('href'));
			// if the user is holding 'cmd' or 'ctrl' keys, open to new tab; otherwise, reset to here
			if (e.metaKey || e.ctrlKey) {
				form.prop('target','_newtab');
			} else {
				form.prop('target','');
			}
			//post-load because otherwise the focus event gets interrupted by html/class changes above
			setTimeout("$('#s').focus()",100);

			if ($(this).hasClass("tab-submit")) {
				form.submit();
			}
		});

		$(".btn-favorites").click(function() {
			if ($(this).hasClass('btn-default')) {
				$(this).removeClass('btn-default').addClass('btn-danger');
				$(this).closest("div").find("input[name=favorites]").prop('checked',true);
				//$("#favorites").prop('checked',true);
			} else {
				$(this).removeClass('btn-danger').addClass('btn-default');
				$(this).closest("div").find("input[name=favorites]").prop('checked',false);
				//$("#favorites").prop('checked',false);
			}
		});
		$(document).on("click", ".fav-icon", function() {
			if ($(this).data('partid')) {
				var partid = $(this).data('partid');
			} else {
				var partid = $(this).closest('tr').data('partid');
			}

			if ($(this).hasClass('fa-star-half-o')) {
				modalAlertShow("Favorites Alert","You are removing this from someone else's favorites! Do you really want to proceed?",true,'toggleFav',partid);
			} else {
				toggleFav(partid);
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
			var ln = false;
			if ($(this).closest(".modal-body")) {//.data('ln')) {
				ln = $(this).closest(".modal-body").data('ln');
			}
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

						if (ln!==false && ln!=='') { $("#results").partResults('',ln); }
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
			$('#loader-message').html('Authenticating remote session...');
			$('#loader').show();

			var remote = $('#remote-activate').data('remote');
			var remote_login = $("#remote-login").val();
			var remote_password = $("#remote-password").val();

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

						// NOW LEGACY: request all market results to reload now with the activated remote
				        $(".market-results").each(function() {
							$(this).loadResults(0);
						});
						// NEW METHOD
						$(".bg-market").each(function() {
							$(this).marketResults(0);
						});
					}
					$('#loader').hide();
				},
                error: function(xhr, desc, err) {
					$('#loader').hide();
                },
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
					//window.open('/invoice.php?invoice='+$(this).val(),'_blank');
					document.location.href = '/invoice.php?invoice='+$(this).val();
				} else if ($(this).data('type') == 'Bill') {
					window.open('/docs/Bill'+$(this).val()+'.pdf','_blank');
				} else if($(this).data('type') != 'RMA' && $(this).data('type') != 'RO') {
					document.location.href = '/'+$(this).data('type')+$(this).val();
				} else if($(this).data('type') == 'RO'){
					document.location.href = '/order.php?order_type='+$(this).data('type')+'&order_number='+$(this).val();
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
				window.open('/invoice.php?invoice='+search_field.val(),'_blank');
			} else if (search_field.data('type') == 'Bill') {
				window.open('/docs/Bill'+search_field.val()+'.pdf','_blank');
			} else if(search_field.data('type') == 'BO') {
				document.location.href = '/builds_management.php?on='+search_field.val();
			} else if(search_field.data('type') != 'RMA') {// && search_field.data('type') != 'RO') {
				document.location.href = '/'+search_field.data('type')+search_field.val();
//			} else if(search_field.data('type') == 'RO') {
//				document.location.href = '/order.php?order_type='+search_field.data('type')+'&order_number='+search_field.val();
			} else {
				document.location.href = '/rma.php?rma='+search_field.val();
			}
			
		});

		/* took the function below and globalized it */
		$(document).on("click",".btn-file",function() {
			var e = $(this).data("id");
			$("#"+e).click();
		});
		var uploadFiles;
		$(document).on("change","input.file-upload",function(e) {
			uploadFiles = e.target.files;

			// get new upload file name
			var upload_file = $(this).val().replace("C:\\fakepath\\","");

			// change icon on upload button as additional indicator of successful selection
			$(".btn-file").removeClass('btn-default').addClass('btn-info').html('<i class="fa fa-file-text"></i> '+upload_file);
		});


		/* David, Flame Broiler is at stake */
		$(document).on("click",".btn-order-upload",function() {
			$("#order-upload").click();
		});
		var orderUploadFiles;
		$(document).on("change","input#order-upload",function(e) {
			orderUploadFiles = e.target.files;

			// get new upload file name
			var upload_file = $(this).val().replace("C:\\fakepath\\","");

			// change "Customer Order" label with name of upload file, and color with primary text
//			var order_label = $("#order-label");//$("#customer_order").find("label[for='assoc']");
//			order_label.html(order_label.text()+' <span class="text-info"><i class="fa fa-download"></i></span>');

			// change icon on upload button as additional indicator of successful selection
			$(".btn-order-upload").html('<i class="fa fa-file-text"></i>');
		});


		$(document).on('click', ".upload_link", function(e){
	        e.preventDefault();

	        $(this).closest(".file_container").find(".upload").trigger("click");
	        // $("#upload:hidden").trigger('click');
	    });

		// $(document).on("change", ".upload", function(){
		// 	var f_file =  $(this).val();
		//     var fileName = f_file.match(/[^\/\\]+$/);

		// 	$(this).closest(".file_container").find(".file_name").text(fileName);
		// });

		$(document).on("change", ".upload", function(){
		    var fileNames = [];
		    for (var i = 0; i < $(this).get(0).files.length; ++i) {
		    	var f_file =  $(this).get(0).files[i].name;
		    	var fileName = f_file.match(/[^\/\\]+$/);

		        fileNames.push(fileName);
		    }

		    if(fileNames.length > 1) {
		    	$(this).closest(".file_container").find(".file_name").text("Multiple Files");
		    } else {
		    	$(this).closest(".file_container").find(".file_name").text(fileNames);
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

		/* initialize upload slider  to set to 'off' (availability) position by default */
//		setSlider($("#upload-slider"));
		$('.slider-mode').each(function() {
			setSlider($(this));
		});

		$('.slider-button').click(function() {
			setSlider($(this));
		});

		/* initialize results sliders and set to 'off' position, which we're using as on */
		$(".slider-box .slider-button").each(function() {
			setSlider($(this));
		});

		$("body").on('click','.purchase-request',function() {
			var width = 550;
			var top_pos = $(this).offset().top - $(window).scrollTop() - 200;
			var left_pos = $(this).offset().left - (width/2);//position().left;

			var modal = $("#modalCustom");
			modal.reposition(top_pos, left_pos, width);

			// header / title
			modal.find(".modal-title").html('Purchase Request');

			var row = $(this).closest('tr');
			var ln = row.data('ln');
			var items_row = $("#items_"+ln);
			var table_items = items_row.find(".table-items tr");
			var partids = getCheckedPartids(table_items);
			var partid_array = partids.split(',');
			var partid = partid_array[0];
			var header_row = $("#row_"+ln);
			var qty = header_row.find(".list-qty").val();
			var id = header_row.data('id');
			var label = header_row.data('label');
			var parts = 'Qty '+qty+'- &nbsp; '+$("#"+partid+"-"+ln).find(".product-details .part_text").html();

			// body
			var body_html = '\
				<form>\
					<h5>Your request will be submitted for the following item:</h5>\
					<div class="row">\
						<div class="col-sm-1"></div>\
						<div class="col-sm-10" style="border:1px solid #ccc; background-color:#fafafa; margin-top:12px; margin-bottom:18px; border-radius:4px; padding:5px">\
							'+parts+'\
						</div>\
						<div class="col-sm-1"></div>\
					</div>\
					<div class="row">\
						<div class="col-sm-12">\
							<textarea class="form-control" name="notes" style="width:100%" rows="3" placeholder="Purchase Instructions..."></textarea>\
						</div>\
					</div>\
				</form>\
				<br/>\
				<span class="info">\
					<i class="fa fa-info-circle"></i> For batch purchase requests of all selected materials, click the dropdown arrow (<i class="fa fa-caret-down"></i>)\
					next to the green Save button on the taskbar and select <strong><i class="fa fa-share-square"></i> Request</strong>\
				</span>\
			';
			modal.find(".modal-body").html(body_html);

			// footer
			var footer_html = '\
				<div class="row">\
					<form>\
					<div class="col-sm-12">\
						<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>\
						<button type="button" class="btn btn-primary btn-md btn-request" data-partid="'+partid+'" data-qty="'+qty+'" data-taskid="'+id+'" data-tasklabel="'+label+'"><i class="fa fa-share-square"></i> Send Request</button>\
					</div>\
					</form>\
				</div>\
			';
			modal.find(".modal-footer").html(footer_html);

			modal.modal("show");
		});

		$("body").on('click','.btn-request',function() {
			var modal = $(this).closest(".modal");

			var partid = $(this).data('partid');
			var qty = $(this).data('qty');
			var taskid = $(this).data('taskid');
			var task_label = $(this).data('tasklabel');
			var notes = modal.find("textarea[name=notes]").val();

			$('#loader-message').html('Please wait while your request is being sent...');
			$('#loader').show();

			$.ajax({
				url: 'json/save-requests.php',
				type: 'get',
				data: { 'partid': partid, 'qty': qty, 'taskid': taskid, 'task_label': task_label, 'notes': notes, },
				settings: {async:true},
				error: function(xhr, desc, err) { },
				success: function(json, status) {
					if (json.message && json.message!='Success') {
						modalAlertShow('Error',json.message,false);
						return;
					}

					toggleLoader('Request successfully sent!');
				},
				complete: function(result) {
					modal.modal("hide");
					$('#loader').hide();

					if (task_label=='Repair') {
						document.location.href = 'service.php?order_type='+task_label+'&taskid='+taskid+'&tab=materials';
						exit;
					}
				},
			});
		});


		$("body").on('click','.modal-avgcost-tag',function() {
			var width = 550;
			var top_pos = $(this).offset().top - $(window).scrollTop() + 40;
			var left_pos = $(this).offset().left - (width/2);//position().left;
			var url = $(this).data('url');
			var row = $(this).closest('tr');
			var ln = row.data('ln');
			var items_row = $("#items_"+ln);
			var partids = getCheckedPartids(items_row.find(".table-items tr"));

			var modal = $("#modalCustom");
			modal.reposition(top_pos, left_pos, width);

			// header / title
			modal.find(".modal-title").html('Average Cost Details');

			// body
			modal.find(".modal-body").html('<div class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-5x"></i></div>');

			var dis = '';
			var title = '';
			var partid_array = partids.toString().split(',');
			if (partid_array.length>1) {
				dis = ' disabled';
				title = "Please select only one item to edit its Average Cost";
			}

			// footer
			var footer_html = '\
				<div class="row">\
					<form>\
					<div class="col-sm-5">\
						<input type="text" class="form-control input-sm pull-left" name="average_cost" value="" placeholder="0.00">\
					</div>\
					<div class="col-sm-7">\
						<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>\
						<button type="button" class="btn btn-success btn-md btn-avgcost" data-cost="" data-partids="'+partids+'" data-ln="'+ln+'"><i class="fa fa-save"></i> Save</button>\
					</div>\
					</form>\
				</div>\
			';
			modal.find(".modal-footer").html(footer_html);

			modal.modal("show");

			var html = '';
			$.ajax({
				type: "GET",
				url: url,
				data: {
					'partids' : partids,
				},
				dataType: 'json',
				success: function(c) {
					console.log(c);

					$.each(c, function(k, r) {
						html += '\
				<div class="row">\
					<div class="col-sm-5 text-right">'+r.dt+'</div><div class="col-sm-3 text-right">$ '+r.amount+'</div><div class="col-sm-4"> </div>\
				</div>\
						';
					});

					modal.find(".modal-body").html(html);
				},
				error: function(xhr, status, err) {
					modal.modal("hide");
					alert(err+" | "+status+" | "+xhr);
				},
			});
		});

		$("body").on('click','.btn-avgcost',function() {
			var average_cost = $(this).closest("form").find("input[name=average_cost]").val();
			var partids = $(this).data('partids');

			var modal = $(this).closest(".modal");
			modal.modal("hide");

			var partid_array = partids.toString().split(',');
			if (partid_array.length>1) {
				modalAlertShow('Average Cost','Please select only one item to edit the Average Cost!',false);
				return;
			}

			$(this).attr('data-cost',average_cost);

			var msg = 'You are about to permanently modify the average cost for this item:<br/><br/>'+
				'<strong>'+Number(average_cost.replace(/[^0-9\.-]+/g,"")).toFixed(4)+'</strong><br/><br/>'+
				'This has far-reaching implications, and cannot be reversed. Are you really sure???';
			modalAlertShow('Average Cost',msg,true,'setAverageCost',$(this));
		});

		$('#modal-alert').on('hidden.bs.modal', function () {
			$("#alert-continue").html("Continue");
		});

		// Class to disable a button after a click
		$('.btn-1-click').click(function() {
			// $(this).prop('disabled', true);
		});

    });/* close $(document).ready */

	jQuery.fn.reposition = function(top,left,width) {
		if (! width) { var width = 400; }

		$(this).find(".modal-content").css({
			top: top+"px",
			left: left+"px",
			width: width,
		});
	};

	/***** David and Andrew's global solution for portable select2 invocations *****/
	jQuery.fn.selectize = function(remote_url,placeholder) {
		if ($(this).data('url')) { var remote_url = $(this).data('url'); }
		if (! remote_url) { return; }
		if (! placeholder) { var placeholder = false; }
		// This is tailored to pages that dont use companyid as a select
		if(! companyid) { companyid = $(this).data('companyid'); }

		console.log(remote_url);

		$(this).select2({
			placeholder: placeholder,
			width: '100%',
			ajax: {
				url: remote_url,/*'/json/conditions.php',*/
				dataType: 'json',
				data: function (params) {
					return {
						q: params.term,//search term
						heci: $(this).attr('data-heci'),
						companyid: companyid,
						order_type: scope,
						fieldid: $(this).attr('id'),
	                    carrierid: $("#carrierid").val(),
						noreset: $(this).data('noreset'),
					};
				},
				processResults: function (data, params) {// parse the results into the format expected by Select2.
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
	};


	jQuery.fn.populateSelected = function(id, text) {
		if (! id) { var id = 0; }

		// is this item already loaded in the select2?
		if ($(this).find("option[value='"+id+"']").length>0) {
			// select the option with the id
			$(this).val(id);
			// update the text of the identified <option> in case it's changed
			var opt = $(this).find("option[value='"+id+"']");
			opt.text(text);

			// trigger change for select2
			$(this).trigger('change');

			// don't continue beyond this point
			return;
		}

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

		var hr = false;
		var init = true;

        console.log(window.location.origin+"/json/availability.php?attempt="+attempt+"&partids="+partids+"&ln="+ln+"&results_mode="+results_mode+"&type="+type);

        $.ajax({
            url: 'json/availability.php',
            type: 'get',
            data: {'attempt': attempt, 'partids': partids, 'ln': ln, 'results_mode': results_mode, 'type': type},
			settings: {async:true},
            success: function(json, status) {
                $.each(json.results, function(dateKey, item) {
                	var rowDate = '';

                	var cls1 = '';
                	var cls2 = '';


                	//Set the first date to the first record, getSupply function already orders the records by date DESC
                	if(init) {
                		//Get the first date from the item array (probably a way better way to implement this feature)
                		$.each(item, function(key, row) {
                			var dateParts = row.date.split("-");
                			curDate = new Date();
							date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);

							return false;
						});

						if(date) {
	                		//Based on the first date of entries found
	                		var curDate = new Date();
							last_month = curDate.setMonth(curDate.getMonth() - 1, 1);

							var curDate = new Date();
							last_year = curDate.setMonth(curDate.getMonth() - 11, 1);

							var curDate = new Date();
							last_week = new Date(curDate.setDate((curDate.getDate() - curDate.getDay()) - 7));

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
                            '<a href="/company.php?companyid='+row.cid+'" class="market-company">'+row.company+'</a> &nbsp; ';
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

					if(last_week != '' && Date.parse(rowDate) >= last_week) { 
						cls1 += '<span class="last_week">';
						cls2 += '</span>';
					} 

					if(type == 'demand') {

						//console.log((last_year) + ' > ' + Date.parse(rowDate));

						if(Date.parse(rowDate) < last_year) { 
							//alert('hi');
							if(hr) {
								cls1 = '<hr>';
								hr = false;
							}
							cls1 += '<span class="archives">';
							cls2 += '</span>';
						} else if (Date.parse(rowDate) < last_month) {
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
	                    if(init) {
	                    	hr = true;
	                    	init = false;
	                    }
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

	function setAverageCost(e) {
		var partids = e.data('partids');
		var average_cost = e.data('cost');
		var ln = e.data('ln');

		$.ajax({
			url: 'json/save-cost.php',
			type: 'get',
			data: { 'partid': partids, 'average_cost': average_cost },
			settings: {async:true},
			error: function(xhr, desc, err) { },
			success: function(json, status) {
				if (json.message && json.message!='Success') {
					modalAlertShow('Error',json.message,false);
					return;
				}

				$("#avg-cost-"+ln).val(json.cost);
				toggleLoader('Average Cost Updated!');
			},
		});
	}

	function addResultsRow(results_type,row,actionBox,rfqFlag,sources,search_str,price,inputDis) {
		if (! actionBox) { var actionBox = '&nbsp;'; }
		if (! rfqFlag) { var rfqFlag = ''; }
		if (! search_str) { var search_str = '&nbsp;'; }
		if (! price) { var price = ''; }
		var qty = '&nbsp;';
		var company = '';
		var src = '&nbsp;';
		var date = '';
		var cid = '';
		var html = '';

		if (row) {
			qty = row.qty;
			company = row.company;
			src = sources;
			cid = row.cid;
			date = row.date;

			html = '\
		<div class="row">\
			<div class="col-sm-1">\
				'+actionBox+rfqFlag+'\
			</div>\
			<div class="col-sm-1">\
				<strong>'+qty+'</strong>\
			</div>\
			<div class="col-sm-4 company-name">\
				'+company+'\
			</div>\
			<div class="col-sm-2">\
				'+src+'\
			</div><!-- col-sm -->\
			<div class="col-sm-2">\
				'+search_str+'\
			</div><!-- col-sm -->\
			<div class="col-sm-2">\
				<input type="text" value="'+price+'" class="form-control input-xs market-price" data-type="'+results_type+'" data-date="'+date+'" data-cid="'+cid+'" size="4" onFocus="this.select()"'+inputDis+'/>\
			</div><!-- col-sm -->\
		</div><!-- row -->\
			';
		} else if (results_type=='Supply') {
			company = '<select name="companyids[]" size="1" class="form-control companies-selector" data-placeholder="- Select Company for RFQ -"></select>';
			inputDis = ' disabled';

			html = '\
		<div class="row">\
			<div class="col-sm-1" style="background-color:white"> </div>\
			<div class="col-sm-8" style="background-color:white">\
				'+company+'\
			</div>\
			<div class="col-sm-3" style="background-color:white"> </div>\
		</div>\
			';
		}

		return (html);
	}
	function submitConflict(e) {
		$('.results-form').submit();
	}

	function getCheckedPartids(e,c) {
		if (! c) { var c = '.item-check'; }
		var partids = '';

		e.each(function() {
//		$(this).closest(".items-row").find(".table-items tr").each(function() {
//			if ($(this).hasClass('sub')) { return; }
			if ($(this).find(c+":checkbox, "+c+":radio").length==0 || $(this).find(c+":checkbox, "+c+":radio").prop('checked')===false || $(this).find(c+":checkbox, "+c+":radio").prop('disabled')===true || ! $(this).data("partid")) { return; }

			if (partids!='') { partids += ','; }
			partids += $(this).data("partid");
		});

		return (partids);
	}

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
	function mergeParts(items) {
		var ln = items.data('ln');
		var rows = items.find(".item-check:checked");
		var partids = [];
		rows.each(function() {
			partids.push($(this).val());
		});
        $.ajax({
            url: 'json/merge-parts.php',
            type: 'get',
            data: {'partids': partids},
			dataType: 'json',
            success: function(json, status) {
				if (json.message && json.message!='Success') {
					alert(json.message);
					return;
				}
				toggleLoader('Parts Merged Successfully');

				// reload html row
				if ($("#results")) {
					$("#results").partResults(false,ln);
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
						$(".row-"+partid+" .fav-icon").removeClass('fa-star-half-o fa-star-o text-danger').addClass('fa-star text-danger');
					} else {
						$("#row-"+partid+" .fav-icon").removeClass('fa-star-half-o fa-star text-danger').addClass('fa-star-o');
						$(".row-"+partid+" .fav-icon").removeClass('fa-star-half-o fa-star text-danger').addClass('fa-star-o');
					}
					toggleLoader("Favorites updated");
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

		if (add_notes!='') {
			e.find("i.fa").removeClass('text-danger fa-warning fa-lg').addClass('text-danger fa-sticky-note');
		} else {
			e.find("i.fa").removeClass('text-danger fa-warning fa-lg').addClass('text-warning fa-sticky-note');
		}

		// Adding a special case for mobile
		// Not using a modal but using a block instead to run this
		var mobile = e.closest(".notes_container").data("mobile");

		var pos = e.position();
		var outerBody = e.closest(".descr-row");
		if (outerBody.length==0) {
			outerBody = e.closest(".product-row");
			var partid = outerBody.data('partid');
			var productBody = outerBody.find(".product-details:first");
		} else {
			var productBody = outerBody.find(".product-descr:first");
			var partid = productBody.data('partid');
		}
		var width = outerBody.outerWidth();
		/* save partids to the button for when the user saves the notes */
		if (e.closest(".product-results").length==0) {
			$("#save-notes-btn").attr("data-refid",e.closest(".product-row").prop("id"));
		} else {
			$("#save-notes-btn").attr("data-refid",e.closest(".product-results").prop("id"));
		}

		// set the modal stage
		var eTop = productBody.offset().top - $(window).scrollTop();
		$("#modalNotes").reposition((eTop+40),(outerBody.position().left),width);

        $.ajax({
            url: 'json/notes.php',
            type: 'get',
            data: {'partid': partid, 'add_notes': escape(add_notes)},
			dataType: 'json',
            success: function(json, status) {
				if (json.results) {
					if(mobile) {
						updateMobileNotes(json.results);
					} else {
						updateNotes(json.results);
					}
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
		if (modalBody.html()==results) { return; }

//		modalBody.html('<center><i class="fa fa-circle-o-notch fa-spin fa-5x"></i></center>');
		// clear textarea for next entry upon successful results
		$("#modalNotes").find("textarea[name='user_notes']").val("");


		modalBody.html(table_html);
	}

	function updateMobileNotes(results) {
		// Clear out the previous data
		$('#detail_notes .container .notes_container').remove();

		var html = '';

		$.each(results, function(dateKey, row) {

			if(html == '') {
				html += "<BR>";
			} else {
				html += "<HR>";
			}

			user = '';
			if (row.user!='') user = '- <strong>'+row.user+'</strong>, ';
			/* process each item's data */
			html += '<div class="row notes_container"><div class="col-xs-7">'+row.note+'</div> <div class="col-xs-5 remove-pad"><div class="source">'+user+row.date+'</div></div></div>';
		});

		html += '<BR>';

		$('#detail_notes .container').append(html);

		$('.summary_block').hide();
		$('#detail_notes').show();

	}
function setSlider(e) {
	var buttonText = '';
	var sliderFrame = e.closest(".slider-frame");

	// use a default 'success' class but change if a data tag exists for it
	var onClass = 'success';
	if (sliderFrame.data('onclass')) { onClass = sliderFrame.data('onclass'); }
	var offClass = 'warning';
	if (sliderFrame.data('offclass')) { offClass = sliderFrame.data('offclass'); }

	if (e.hasClass("on")) {//currently ON, turning OFF
		sliderFrame.removeClass(onClass).addClass(offClass);
		buttonText = e.data("off-text");
		e.removeClass('on').addClass('off');
	} else if (e.hasClass("off")) {//turning ON
		sliderFrame.removeClass(offClass).addClass(onClass);
		buttonText = e.data("on-text");
		e.removeClass('off').addClass('on');
	} else {
		// discover on/off based on checked radio's
		buttonText = e.html();//sliderFrame.find("input[type='radio']:checked").val();
		if (buttonText==e.data("on-text")) {//set to ON
			sliderFrame.removeClass(offClass).addClass(onClass);
			e.removeClass('on').removeClass('off').addClass('on');//.html(e.data("on-text"));
		} else if (buttonText==e.data("off-text")) {//set to OFF
			sliderFrame.removeClass(onClass).addClass(offClass);
			e.removeClass('off').removeClass('on').addClass('off');
		}
		//$(this).trigger('change');
		return;
	}
	e.html(buttonText);

	sliderFrame.find("input[type='radio']").each(function() {
//		console.log('button text: '+buttonText+' = this val: '+$(this).val()+':'+$(this).prop('checked'));
		if (buttonText==$(this).val()) { $(this).prop('checked',true); }
		else { $(this).prop('checked',false); }
		// trigger the change event; without this, our radio button 'checked' changes above
		// don't trigger any js events attached to them
		$(this).trigger('change');
	});
}
	function viewNotification(messageid,search, link) {
		// this function gets all notifications only for the purpose of marking them as "clicked", then sends user to that search results page
//		console.log(window.location.origin+"/json/notes.php?messageid="+messageid);

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
	function setCategory(category) {
		if (! category) { var category = ''; }

		$(".btn-category").each(function() {
			if (category!='') {//set selected value
				if ($(this).text()==category) { $(this).addClass('active'); }
				else { $(this).removeClass('active'); }
			} else if ($(this).hasClass('active')) {//get selected value
				category = $(this).text();
			}
		});

		return (category);
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
					sales.append('<li><a href="/order.php?order_type=Sale&order_number='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});
				var purchases = $("#purchase-orders-list");
				purchases.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.purchases, function(key, order) {
					purchases.append('<li><a href="/order.php?order_type=Purchase&order_number='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});
				var repairs = $("#repair-orders-list");
				repairs.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.repairs, function(key, order) {
					repairs.append('<li><a href="/order.php?order_type=Repair&order_number='+order.number+'">'+order.number+' '+order.company+'</a></li>');
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

				var services = $("#service-orders-list");
				services.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.services, function(key, order) {
					services.append('<li><a href="/service.php?order_type=Service&order_number='+order.number+'">'+order.class+' '+order.number+' '+order.company+'</a></li>');
				});

				var service_quotes = $("#service-quotes-list");
				service_quotes.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.service_quotes, function(key, order) {
					service_quotes.append('<li><a href="/manage_quote.php?order_type=service_quote&order_number='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});

				var invoices = $("#invoices-list");
				invoices.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.invoices, function(key, order) {
					invoices.append('<li><a href="/invoice.php?invoice='+order.number+'">'+order.number+' '+order.company+'</a></li>');
				});

				var bills = $("#bills-list");
				bills.find("li").each(function() {
					$(this).remove();
				});
				$.each(json.bills, function(key, order) {
					bills.append('<li><a href="/bill.php?bill='+order.number+'">'+order.number+' '+order.company+'</a></li>');
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
