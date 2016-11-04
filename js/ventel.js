    $(document).ready(function() {
		$('#loader').hide();
		if ($("#s:focus") && $(".profile-body").length==0 && $(".accounts-body").length==0) { $("#s").select(); }
		//toggleLoader();

		// adjust height dynamically to size of the rows within section
		$(".market-table").each(function() {
			var parentBody = $(this).closest("tbody");
			var marketTable = $(this);
			var tableHeight = 0;
			parentBody.find(".product-results").each(function() {
				tableHeight += $(this).height();
			});
			if (tableHeight>marketTable.css('min-height').replace('px','')) {
				marketTable.height(tableHeight);
			}
		});

        $("body").on('click','a.modal-results',function(e) {
			var productSearch = $(this).closest(".product-results").siblings(".first").find(".product-search").val().toUpperCase();
			var partids = $(this).closest(".market-results").data('partids');
			var ln = $(this).closest(".market-results").data('ln');
			var pricing_only = $(this).data('pricing');
            console.log(window.location.origin+"/json/availability.php?attempt=0&partids="+partids+"&detail=1&pricing_only="+pricing_only);
            $.ajax({
                url: 'json/availability.php',
                type: 'get',
                data: {'attempt': '0', 'partids': partids, 'pricing_only': pricing_only, 'detail': '1'},
                success: function(json, status) {
					rowHtml = '';
                    $.each(json.results, function(dateKey, item) {
	                    rowHtml += '<div class="check-group">'+
							'<div class="row bg-success"><div class="col-sm-2"><input type="checkbox" class="checkTargetAll" data-target=".check-group"/></div>'+
							'<div class="col-sm-10">'+dateKey+'</div></div>';
                        /* process each item's data */
                        $.each(item, function(key, row) {
							rowHtml += '<div class="row"><div class="col-sm-2"><input type="checkbox" class="item-check" name="companyids[]" value="'+row.cid+'"/>';
							if (row.rfq && row.rfq!='') {
								rowHtml += ' <i class="fa fa-paper-plane text-primary" title="'+row.rfq+'"></i>';
							}
							rowHtml += '</div><div class="col-sm-2"><strong>'+row.qty+'</strong></div>'+
								'<div class="col-sm-6">'+row.company+'</div>'+
								'<div class="col-sm-2">';
                            $.each(row.sources, function(i, src) {
								var source_lower = src.toLowerCase();
								var source_img = '<img src="img/'+source_lower+'.png" class="bot-icon" />';
								if (row.lns[source_lower]) {
									rowHtml += '<a href="http://'+row.lns[source_lower]+'" target="_new">'+source_img+'</a> ';
								} else {
									rowHtml += source_img+' ';
								}
							});
							rowHtml += '</div></div>';
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
                    var modalBody = $("#marketModal .modal-body");
					modalBody.html(rowHtml);

					// alert the user when there are errors with any/all remotes by unhiding alert buttons
					$.each(json.err, function(i, remote) {
						$("#remote-"+remote).removeClass('hidden');
					});
                },
                error: function(xhr, desc, err) {
                    console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
            }); // end ajax call

			$("#"+$(this).data('target')).modal('toggle');
        });

		$(".modal-form").submit(function(e) {
			$('#loader-message').html('Please wait while your RFQ is being sent...');
			$('#loader').show();
			$('#modal-submit').prop('disabled',true);

			var modalForm = $(this);
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
							modalForm.closest(".modal").modal("toggle");
						}
					}
				},
	            error: function(xhr, desc, err) {
					$('#loader').hide();
					$('#modal-submit').prop('disabled',false);

					toggleLoader("Error sending RFQ! Details: " + desc + "<br/>Error:" + err);
					modalForm.closest(".modal").modal("toggle");

	                console.log(xhr);
	                console.log("Details: " + desc + "\nError:" + err);
	            }
			});
			e.preventDefault();
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
		$(".item-notes").click(function() {
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
			$(this).find(".count").css({display:'none',visibility:'hidden'});

	        console.log(window.location.origin+"/json/notes.php");
	        $.ajax({
				url: 'json/notes.php',
				type: 'get',
				dataType: 'json',
				success: function(json, status) {
					if (json.results) {
						var notif_html = '';

	                	$.each(json.results, function(i, row) {
							var read_class = '';
							if (row.read=='') { read_class = ' unread'; }
							else if (row.viewed=='') { read_class = ' unviewed'; }

							notif_html += '<a href="javascript:viewNotification(\''+row.partid+'\',\''+row.search+'\')" class="item'+read_class+'">'+
								'<div class="user fa-stack fa-lg">'+
									'<i class="fa fa-user fa-stack-2x text-warning"></i><span class="fa-stack-1x user-text">'+row.name+'</span>'+
								'</div> '+
								'<span class="time pull-right"><i class="fa fa-clock-o"></i> '+row.since+'</span>'+
								'<div class="note"><strong>'+row.part_label+'</strong><br/>'+row.note+'</div> '+
								'</a>';
						})

						notif.html(notif_html);
					} else {
						var message = 'There was an error processing your request!';
						if (json.message) { message = json.message; } // show response from the php script.
						alert(message);
					}
				},
				error: function(xhr, desc, err) {
					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		});

        $(".checkAll").on('click',function(){
            jQuery(this).closest('tbody').find('.item-check:checkbox').not(this).prop('checked', this.checked);
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

		$(".price-control").change(function() {
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
			var pdescr = $(this).closest(".product-descr");
			var psearch = pdescr.find(".product-search:first").val();
			modalAlertShow('Create a New Part','Be sure this string ("'+psearch+'") is a Part# (NOT a HECI!), and then click Continue!',true,'addPart',psearch);
		});
		$(".parts-index").click(function() {
			var pdescr = $(this).closest(".product-descr");
			var psearch = pdescr.find(".product-search:first").val();
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
                    console.log(xhr);
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
			$(this).loadResults(0);
		});
		$(".market-download").click(function() {
			var mr = $(this).closest(".bg-availability").find(".market-results:first");
			mr.loadResults(2);
		});

		$(".marketpricing-toggle").click(function() {
			var mr = $(this).closest("tbody").find(".market-results:first");
			$(this).find(".fa").each(function() {
				if ($(this).hasClass('fa-toggle-off')) {
					$(this).removeClass('fa-toggle-off').addClass('fa-toggle-on');
					mr.loadResults(1,1);
				} else {
					$(this).removeClass('fa-toggle-on').addClass('fa-toggle-off');
					mr.loadResults(0);
				}
			});
			$(this).blur();
		});

	    // select2 plugin for select elements
		var add_custom = 1;
		if ($(".accounts-body").length>0) { add_custom = 0; }
		
		$(document).on(".company-selector")
	/**** Invoke all select2() modules *****/
	if (!!$.prototype.select2) {
	    $(".company-selector").select2({
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
				allowClear: true,
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
/*
		$(".upload-type").change(function() {
			var utype = $(this).data('target');
			$(".datetime-picker").each(function() {
				$(this).removeClass('hidden');
				if ($(this).id!=utype) {
					$(this).addClass('hidden');
				}
			});
		});
*/
		$('.slider-button').click(function() {
			setUploadSlider($(this));
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
		$("#s").focus(function() {
			if (! $("#advanced-search-options").hasClass('hidden')) {
				$("#advanced-search-options").toggleClass('hidden');
				$("#s").val($("#s2").val().replace(/\r\n|\r|\n/g," "));
			} else {
			}
		});
		$("#s").change(function() {
				$("#s2").val("");
		});
		$("#btn-range-options").hover(function() {
			$("#date-ranges").toggleClass('hidden');
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
		$(".fav-icon").click(function() {
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
			});
		});
		
		$(".btn-expdate").click(function() {
			$("#exp-date").val($(this).data('date'));
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
	
		$(".results-form").submit(function() {
			var cid = $("#companyid").val();
			if (! cid) {
				$('#alert-continue').data('form',$(this));
				modalAlertShow("Company Alert","Your data will not be saved without a company selected! Do you really want to proceed?",true);
			} else {
				$(this).data('form').submit();
			}
	
			event.preventDefault();
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
			document.location.href = '/?listid='+$(this).data('listid')+'&pg='+$(this).data('pg');
		});

		$(".product-img img").click(function() {
			$("#modal-prod-img").attr('src',$(this).attr('src'));
			$("#prod-image-title").text($(this).data('part'));
			$("#image-modal").modal('toggle');
		});

		setUploadSlider($(".slider-button:first"));

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
                    console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
			});
		});

		$(".toggle-results a").on('click',function() {
			$(this).closest("tbody").find(".product-results").each(function() {
				if ($(this).is(':visible')) {
					$(this).fadeOut('fast');
				} else {
					$(this).fadeIn('fast');
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

    });/* close $(document).ready */

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

    // build jquery plugin for remote ajax call
    jQuery.fn.loadResults = function(attempt, pricing_only) {
		if (! pricing_only) { var pricing_only = ''; }
        var newHtml = '';
        var rowHtml = '';
        var qtyTotal = 0;
        var container = $(this);
        var ln = $(this).data('ln');
		var thisId = container.prop('id');
		if (attempt==2) {
            container.html('<i class="fa fa-circle-o-notch fa-spin"></i>');
		}
		var doneFlag = '';

        console.log(window.location.origin+"/json/availability.php?attempt="+attempt+"&partids="+$(this).data('partids')+"&ln="+ln+"&pricing_only="+pricing_only);
        $.ajax({
            url: 'json/availability.php',
            type: 'get',
            data: {'attempt': attempt, 'partids': $(this).data('partids'), 'ln': ln, 'pricing_only': pricing_only},
			settings: {async:true},
            success: function(json, status) {
                $.each(json.results, function(dateKey, item) {
                    qtyTotal = 0;

                    rowHtml = '';
                    /* process each item's data */
                    $.each(item, function(key, row) {
                        qtyTotal += parseInt(row.qty,10);
                        rowHtml += '<div class="market-data"><div class="pa">'+row.qty+'</div> <i class="fa fa-'+row.changeFlag+'"></i> '+
                            '<a href="/profile.php?companyid='+row.cid+'" class="market-company">'+row.company+'</a> &nbsp; ';
                        $.each(row.sources, function(i, src) {
                            rowHtml += '<img src="img/'+src.toLowerCase()+'.png" class="bot-icon" />';
                        });
                        if (row.price) {
                            rowHtml += '&nbsp; <span class="pa">'+row.price+'</span>';
                        }
                        rowHtml += '</div>';
                    });

					doneFlag = json.done;

                    /* add section header of date and qty total */
                    newHtml += addDateGroup(dateKey,qtyTotal,doneFlag,pricing_only)+rowHtml;
                });
                container.html(newHtml);

				// alert the user when there are errors with any/all remotes by unhiding alert buttons
				$.each(json.err, function(i, remote) {
					$("#remote-"+remote).removeClass('hidden');
				});

				// reset market pricing amounts and toggle, and shelflife
				$("#marketpricing-"+ln).closest("tbody").find(".marketpricing-toggle").addClass('hidden');
				$("#marketpricing-"+ln).html('');
//					$("#shelflife-"+ln).html('');

                if (! json.done && attempt==0) {
                    //setTimeout("$('#market-results').loadResults()",1000);
					setTimeout("$('#"+container.prop('id')+"').loadResults("+(attempt+1)+")",1000);
                } else if (json.done==1) {
					// after done loading the market results, show the market pricing summary and toggle
					var price_range = '';
					var pr = json.price_range;
					if (pr.min && pr.max) {
						if (pr.min==pr.max) {
							price_range = '$'+pr.min;
						} else {
							price_range = '$'+pr.min+' - $'+pr.max;
						}
						$("#marketpricing-"+ln).closest("tbody").find(".marketpricing-toggle").removeClass('hidden');
					} else {
						$("#marketpricing-"+ln).closest("tbody").find(".marketpricing-toggle").addClass('hidden');
					}
					$("#marketpricing-"+ln).html(price_range);
//						$("#shelflife-"+ln).html(json.shelflife);
				}
            },
            error: function(xhr, desc, err) {
                console.log(xhr);
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
	function setUploadSlider(e) {
		var buttonText = '';
		if (e.hasClass("on")) {
			e.closest(".slider-frame").removeClass("warning").addClass("success");
			e.removeClass('on').html(e.data("off-text"));   
			buttonText = e.data("off-text");
		} else {
			e.closest(".slider-frame").removeClass("success").addClass("warning");
			e.addClass('on').html(e.data("on-text"));
			buttonText = e.data("on-text");
		}
		$("input[name='upload_type']").each(function() {
			if (buttonText==$(this).val()) { $(this).prop('checked',true); }
			else { $(this).prop('checked',false); }
		});
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
					console.log(xhr);
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
                console.log(xhr);
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
                console.log(xhr);
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
                console.log(xhr);
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
                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call

		return;
	}
    function addDateGroup(dateKey,qtyTotal,doneFlag,pricing_only) {
        var groupStr = '<div class="date-group"><a href="javascript:void(0);" class="modal-results" data-target="marketModal" data-pricing="'+pricing_only+'">'+
            dateKey+': qty '+qtyTotal+' <i class="fa fa-list-alt"></i></a> ';
        if (! doneFlag && dateKey=='Today') {
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
		var itemRow = $("#"+$("#modalNotes").find("#save-notes-btn").data('refid'));
		var itemObj = itemRow.find(".item-notes:first");

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
		$("#save-notes-btn").data("refid",e.closest(".product-results").prop("id"));

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
                console.log(xhr);
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
	function viewNotification(partid,search) {
		// this function gets all notifications only for the purpose of marking them as "clicked", then sends user to that search results page
        console.log(window.location.origin+"/json/notes.php?partid="+partid);

        $.ajax({
            url: 'json/notes.php',
            type: 'get',
            data: {'partid': partid},
			dataType: 'json',
            success: function(json, status) {
				if (json.results) {
					document.location.href = '/?s='+search;
				} else {
					var message = 'There was an error processing your request!';
					if (json.message) { message = json.message; } // show response from the php script.
					alert(message);
				}
            },
            error: function(xhr, desc, err) {
                console.log(xhr);
                console.log("Details: " + desc + "\nError:" + err);
            }
        }); // end ajax call
	}
