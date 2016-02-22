    $(document).ready(function() {
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

        $("body").on('click','a.modal-results',function() {
            $('#myModal').modal('toggle');
        });
        $("body").on('focus','input.price-control',function() {
			toggleNotes($(this));
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
		function toggleNotes(e) {
			var notes = $("#modalNotes");
//            notes.modal('toggle');

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
		function closeModal(e) {
			e.css({
				display: "none",
				visibility: "hidden",
			});
		}

        $(".checkAll").on('click',function(){
            jQuery(this).closest('tbody').find('.item-check:checkbox').not(this).prop('checked', this.checked);
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
		$(".control-toggle").click(function() {
			$(this).find(".fa").each(function() {
				if ($(this).hasClass('fa-lock')) { $(this).removeClass('fa-lock').addClass('fa-unlock'); }
				else { $(this).removeClass('fa-unlock').addClass('fa-lock'); }
			});
		});

        // build jquery plugin for remote ajax call
        var attempt = 0;
        jQuery.fn.loadResults = function() {
			if ($("#market-results").length==0) { return; }

            console.log("Getting results, attempt "+attempt+"...");
            var newHtml = '';
            var rowHtml = '';
            var qtyTotal = 0;
            var container = $(this);
            $.ajax({
                url: 'json/availability.php',
                type: 'get',
                data: {'attempt': attempt, 'id': 5},
                success: function(json, status) {
                    $.each(json.results, function(dateKey, item) {
                        qtyTotal = 0;

                        rowHtml = '';
                        /* process each item's data */
                        $.each(item, function(key, row) {
                            qtyTotal += parseInt(row.qty,10);
                            rowHtml += '<div class="market-data"><span class="pa">'+row.qty+'</span> <i class="fa fa-'+row.changeFlag+'"></i> &nbsp;'+
                                '<a href="#">'+row.company+'</a> &nbsp; ';
                            $.each(row.sources, function(i, src) {
                                rowHtml += '<img src="img/'+src+'.png" class="bot-icon" />';
                            });
                            if (row.price) {
                                rowHtml += '&nbsp; <span class="pa">$'+row.price+'</span>';
                            }
                            rowHtml += '</div>';
                        });

                        /* add section header of date and qty total */
                        newHtml += addDateGroup(dateKey,qtyTotal,json.done)+rowHtml;
                    });
                    container.html(newHtml);
                    if (! json.done) {
                        attempt++;
                        setTimeout("$('#market-results').loadResults()",3000);
                    }
                },
                error: function(xhr, desc, err) {
                    console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
            }); // end ajax call
            return;
        };
        $("#market-results").loadResults();

    });
    function addDateGroup(dateKey,qtyTotal,doneFlag) {
        var groupStr = '<div class="date-group"><a href="#" class="modal-results">'+
            dateKey+': '+qtyTotal+' <i class="fa fa-list-alt"></i></a> ';
        if (! doneFlag && dateKey=='Today') {
            groupStr += '<i class="fa fa-circle-o-notch fa-spin"></i>';
        }
        groupStr += '</div>';
        return (groupStr);
    }

    // select2 plugin for select elements
    $("#companyid").select2({
/*
        initSelection : function (element, callback) {
            var data = [];
            $(element.val().split(',')).each(function(i) {
                if (this.indexOf(':')==-1) { return; }
                var item = this.split(':');
                data.push({
                    id: item[0],
                    text: item[1]
                });
            });
            callback(data);
        },
        multiple: false,
*/
        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
            url: "/json/companies.php",
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
        minimumInputLength: 2
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

    $('#dp1').datepicker().on('changeDate', function(ev){
        if (ev.date.valueOf() > endDate.valueOf()){
            $('#alert').show().find('strong').text('The start date can not be greater than the end date');
        } else {
            $('#alert').hide();
            startDate = new Date(ev.date);
            $('#startDateLabel').text($('#dp1').data('date'));
            $('#startDate').val($('#dp1').data('date'));
        }
        $('#dp1').datepicker('hide');
    });
    $('#dp2').datepicker().on('changeDate', function(ev){
        if (ev.date.valueOf() < startDate.valueOf()){
            $('#alert').show().find('strong').text('The end date can not be less than the start date');
        } else {
            $('#alert').hide();
            endDate = new Date(ev.date);
            $('#endDateLabel').text($('#dp2').data('date'));
            $('#endDate').val($('#dp2').data('date'));
        }
        $('#dp2').datepicker('hide');
    });

	$(".results-form").submit(function() {
		var cid = $("#companyid").val();
		if (! cid) {
	        $('#modal-alert').modal('toggle');
			$('#alert-continue').data('form',$(this));
		} else {
			$(this).data('form').submit();
		}

		event.preventDefault();
	});
	$('#alert-continue').click(function() {
		$(this).data('form').submit();
	});
	$(".qty input[type='text']").click(function() {
		$(this).select();
	});
