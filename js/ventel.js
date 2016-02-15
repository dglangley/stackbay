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
        $(".checkAll").on('click',function(){
            jQuery(this).closest('form').find(':checkbox').not(this).prop('checked', this.checked);
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
		$(".control-toggle").click(function() {
			$(this).find(".fa").each(function() {
				if ($(this).hasClass('fa-lock')) { $(this).removeClass('fa-lock').addClass('fa-unlock'); }
				else { $(this).removeClass('fa-unlock').addClass('fa-lock'); }
			});
		});

        // build jquery plugin for remote ajax call
        var attempt = 0;
        jQuery.fn.loadResults = function() {
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

	$("#checkAll").click(function(){
	    $('input:checkbox').not(this).prop('checked', this.checked);
	});
