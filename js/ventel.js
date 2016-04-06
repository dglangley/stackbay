    $(document).ready(function() {
		$('#loader').hide();
		if ($("#s:focus") && $("#accounts-search").length==0) { $("#s").select(); }
		toggleLoader();

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
            console.log(window.location.origin+"/json/availability.php?attempt=0&partids="+partids+"...");
            $.ajax({
                url: 'json/availability.php',
                type: 'get',
                data: {'attempt': '0', 'partids': partids},
                success: function(json, status) {
					rowHtml = '';
                    $.each(json.results, function(dateKey, item) {
	                    rowHtml += '<div class="check-group">'+
							'<div class="row bg-success"><div class="col-sm-2"><input type="checkbox" class="checkTargetAll" data-target=".check-group"/></div>'+
							'<div class="col-sm-10">'+dateKey+'</div></div>';
                        /* process each item's data */
                        $.each(item, function(key, row) {
							rowHtml += '<div class="row"><div class="col-sm-2"><input type="checkbox" class="item-check" name="companyids[]" value="'+row.cid+'"/></div>'+
								'<div class="col-sm-2"><strong>'+row.qty+'</strong></div>'+
								'<div class="col-sm-6">'+row.company+'</div>'+
								'<div class="col-sm-2">';
                            $.each(row.sources, function(i, src) {
								rowHtml += '<img src="img/'+src.toLowerCase()+'.png" class="bot-icon" /> ';
							});
							rowHtml += '</div></div>';
                        });
						rowHtml += '</div>';/*end check-group*/
                    });
					rowHtml += '<br/><textarea name="message_body" style="width:100%" rows="5">Please quote:\n\n'+productSearch+'</textarea>';
                    $("#marketModal .modal-body").html(rowHtml);

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
		/* toggle notes on input focus and blur */
        $("input.price-control").each(function() {
			$(this).click(function() {
				toggleNotes($(this));

				$(this).focusout(function() {
					setTimeout("closeNotes()",100);
				});

				$(this).select();
			});
		});
		// close notes when switching to a diff input text field
        $('input[type="text"]').focus(function() {
        	if (! $(this).hasClass('price-control')) {
				closeModal($("#modalNotes"));
			}
		});
		$(".item-notes").click(function() {
			toggleNotes($(this));
		});
		jQuery.expr[':'].focus = function(elem) {
		  return elem === document.activeElement && (elem.type || elem.href);
		};
		$(".notes-close").on('click',function() {
			closeModal($(this).closest(".modalNotes"));
		});
/*
		$(".modal-close").on('click',function() {
			closeModal($(this).closest(".modal"));
		});
*/

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
				$(this).closest(".product-descr").find("."+$(this).data('field')+"-label").html($(this).select2('data')[0].text);
			} else {
				$(this).closest(".product-descr").find("."+$(this).data('field')+"-label").html($(this).val());
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

	    // select2 plugin for select elements
		var add_custom = 1;
		if ($(".accounts-body").length>0) { add_custom = 0; }
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
	        minimumInputLength: 2
	    });
		$(".accounts-body #companyid, .profile-body #companyid").change(function() {
			if ($.isNumeric($(this).val())) {
				$(this).closest("form").submit();
			}
		});
	    $(".lists-selector").select2({
			placeholder: 'Upload or Select...',
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
			var buttonText = '';
			if ($(this).hasClass("on")) {
				$(this).closest(".slider-frame").removeClass("warning").addClass("success");
				$(this).removeClass('on').html($(this).data("off-text"));   
				buttonText = $(this).data("off-text");
			} else {
				$(this).closest(".slider-frame").removeClass("success").addClass("warning");
				$(this).addClass('on').html($(this).data("on-text"));
				buttonText = $(this).data("on-text");
			}
			$("input[name='upload_type']").each(function() {
				if (buttonText==$(this).val()) { $(this).prop('checked',true); }
				else { $(this).prop('checked',false); }
			});
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
/*
	    $('.datepicker-date').each(function() {
			$(this).datepicker().on('changeDate', function(ev){
		        if (ev.date.valueOf() > endDate.valueOf()) {
		            $('#alert').show().find('strong').text('The start date can not be greater than the end date');
		        } else {
		            $('#alert').hide();
		            startDate = new Date(ev.date);
		            $(this).find("span:first").text($(this).data('date'));
		            $($(this).data('target')).val($(this).data('date'));
		        }
		        $(this).datepicker('hide');
			});
	    });
*/
		$('.datetime-picker').each(function() {
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
			});
		});
		$(".btn-expdate").click(function() {
			$("#exp-date").val($(this).data('date'));
		});
		$(".btn-upload").click(function() {
			var form = $(this).closest("form");
			form.prop('action','/upload.php');
			form.submit();
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
					$('#loader-message').html('Please wait while your RFQ is being sent...');
					$('#loader').hide();
					$('#modal-submit').prop('disabled',false);

					if (json.message=='Success') {
						toggleLoader("RFQ sent successfully");
						modalForm.closest(".modal").modal("toggle");
					} else {
						if (json.confirm && json.confirm=='1') {
							var user_conf = confirm(json.message);
							if (user_conf===true) {
								document.location.href = json.url;
							}
						} else {
							alert(json.message); // show response from the php script.
						}
					}
				},
	            error: function(xhr, desc, err) {
					$('#loader-message').html('Please wait while your RFQ is being sent...');
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
	
    });/* close $(document).ready */

        // build jquery plugin for remote ajax call
        jQuery.fn.loadResults = function(attempt) {
            var newHtml = '';
            var rowHtml = '';
            var qtyTotal = 0;
            var container = $(this);
			var thisId = container.prop('id');
			var doneFlag = '';

            console.log(window.location.origin+"/json/availability.php?attempt="+attempt+"&partids="+$(this).data('partids')+"&ln="+$(this).data('ln')+"...");
            $.ajax({
                url: 'json/availability.php',
                type: 'get',
                data: {'attempt': attempt, 'partids': $(this).data('partids'), 'ln': $(this).data('ln')},
                success: function(json, status) {
                    $.each(json.results, function(dateKey, item) {
                        qtyTotal = 0;

                        rowHtml = '';
                        /* process each item's data */
                        $.each(item, function(key, row) {
                            qtyTotal += parseInt(row.qty,10);
                            rowHtml += '<div class="market-data"><div class="pa">'+row.qty+'</div> <i class="fa fa-'+row.changeFlag+'"></i> '+
                                '<a href="/accounts.php?companyid='+row.cid+'" class="market-company">'+row.company+'</a> &nbsp; ';
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
                        newHtml += addDateGroup(dateKey,qtyTotal,doneFlag)+rowHtml;
                    });
                    container.html(newHtml);

					// alert the user when there are errors with any/all remotes by unhiding alert buttons
					$.each(json.err, function(i, remote) {
						$("#remote-"+remote).removeClass('hidden');
					});

                    if (! json.done && attempt==0) {
                        //setTimeout("$('#market-results').loadResults()",1000);
						setTimeout("$('#"+container.prop('id')+"').loadResults("+(attempt+1)+")",1000);
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

	function uploadFile(e) {
		if (! e.val()) { return; }

//		var dataArray = [];
		var upload = false;
		e.find("option:selected").each(function() {
//			console.log($(this).val());
			if ($(this).val()=='upload') {
				upload = true;
			} else {
//				dataArray.push($(this));
			}
		});

		if (upload===true) {
			e.val("");//reset selection
			$("#upload-file").click();//show().focus().click().hide();
			$(".upload-options").removeClass('hidden');
		}
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
		if (show_continue===true) { $('#alert-continue').removeClass('hidden'); }
		else { $('#alert-continue').removeClass('hidden').addClass('hidden'); }
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
    function addDateGroup(dateKey,qtyTotal,doneFlag) {
        var groupStr = '<div class="date-group"><a href="javascript:void(0);" class="modal-results" data-target="marketModal">'+
            dateKey+': qty '+qtyTotal+' <i class="fa fa-list-alt"></i></a> ';
        if (! doneFlag && dateKey=='Today') {
            groupStr += '<i class="fa fa-circle-o-notch fa-spin"></i>';
        }
        groupStr += '</div>';
        return (groupStr);
    }
	function closeNotes() {
		if ($("#modalNotes").has(document.activeElement).length == 0) {
			$("#modalNotes").fadeOut(100);
		}
	}
	function toggleNotes(e) {
		var notes = $("#modalNotes");
//           notes.modal('toggle');

		var parentBody = e.closest("tbody");
		var marketBody = parentBody.find(".market-table:first");
		var pos = marketBody.position();
		var width = marketBody.outerWidth();
		var height = marketBody.outerHeight();

		notes.css({
			display: "block",
			visibility: "visible",
			top:pos.top+"px",
			left:pos.left+"px",
			width: width,
			height: height,
		}).show();
	}
	function closeModal(e) {
		e.css({
			display: "none",
			visibility: "hidden",
		});
	}
