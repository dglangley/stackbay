	$(document).ready(function() {
		/* any changes related to qty and/or prices/amounts and/or freight should invoke the function to update totals */
		$(".item-qty, .item-amount, .input-freight").on('change keyup',function() {
			updateTotals();
		});

		$(".item-row .part-selector").selectize();

		$(".btn-saveitem").on('click', function() {
			var row = $(this).closest("tr");
			var found_parts = $(this).closest("tbody").find(".found_parts");
			found_parts.each(function() {
				row.saveItem($(this));
			});
			if (found_parts.length==0 && row.find(".search-type").val()=='Site') {
				row.saveItem(row);
			}

			$(this).closest("tbody").find(".found_parts").remove();

			var ln = row.find(".line-number");
			var new_ln = parseInt(ln.val());
			//if (! new_ln) { new_ln = 0; }
			new_ln++;
			ln.val(new_ln);

			// reset values in all fields in row besides line number and delivery date
			$(this).closest("tr").find("input[type=text]").not(".line-number,.delivery-date,input[readonly]").val("");
		});
		$("#item-search").on('keydown',function(e) {
			var key = e.which;

			if (key == 13) {
				e.preventDefault();
				$(this).search();
			}
		});
		$("#btn-search").on('click',function() {
			$("#item-search").search();
		});
		$(".dropdown-searchtype li").on('click', function() {
			var v = $(this).text();
			var pc = $(this).closest(".part-container");
			var page = $('body').data('scope');

			if (v=='Site') {
				$(".input-search").removeClass('hidden').addClass('hidden');
				pc.find(".address-neighbor").removeClass('hidden');

				if (page == 'Service') {
					pc.find(".part-selector").select2("destroy");
					pc.find(".part-selector").removeClass('select2').addClass('hidden');
					pc.find(".part-selector").prop('disabled', true);
					pc.find(".address-selector").prop('disabled', false);
				}

				pc.find(".address-selector").selectize();
				pc.find(".address-selector").removeClass('hidden').addClass('select2');
				// remove previously-found parts, if any
				$(this).closest("tbody").find(".found_parts").remove();
			} else if (v=='Part') {
				$(".input-search").removeClass('hidden');
				pc.find(".address-neighbor").removeClass('hidden').addClass('hidden');
				pc.find(".address-selector").select2("destroy");
				pc.find(".address-selector").removeClass('select2').addClass('hidden');

				if (page == 'Service') {
					pc.find(".part-selector").selectize();
					pc.find(".part-selector").removeClass('hidden').addClass('select2');
					pc.find(".part-selector").prop('disabled', false);
					pc.find(".address-selector").prop('disabled', true);
				}
			}
		});

		// This section destroys the select2 that shouldn't exist and creates the correct one
		var page = $('body').data('scope');
		// Only run this is it is on the services page aka the tech view
		if (page == 'Service' || page == 'Materials' || page == 'Receiving') {
			var type = $(".dropdown-searchtype button").text();
			var container = $(".dropdown-searchtype button").closest(".part-container");

			if (type=='Site') {
				$(".input-search").removeClass('hidden').addClass('hidden');
				container.find(".address-neighbor").removeClass('hidden');
				container.find(".part-selector").select2("destroy");
				container.find(".part-selector").removeClass('select2').addClass('hidden');
				container.find(".part-selector").prop('disabled', true);

				container.find(".address-selector").selectize();
				container.find(".address-selector").removeClass('hidden').addClass('select2');
				container.find(".address-selector").prop('disabled', false);
			} else if (type=='Part' || page == 'Materials' || page == 'Receiving') {
				$(".input-search").removeClass('hidden');
				container.find(".address-neighbor").removeClass('hidden').addClass('hidden');
				container.find(".address-selector").select2("destroy");
				container.find(".address-selector").removeClass('select2').addClass('hidden');
				container.find(".address-selector").prop('disabled', true);

				if(page == 'Materials' || page == 'Receiving') {
					$(".part-selector").selectize();
				}

				container.find(".part-selector").selectize();
				container.find(".part-selector").removeClass('hidden').addClass('select2');
				container.find(".part-selector").prop('disabled', false);
			}
		}

		jQuery.fn.search = function(e) {
			var type = $(this).find(".search-type").val();
			if (! type || type.val()=='Part') {
				partSearch($("#item-search").val());
			} else {
				addressSearch($("#item-search").val());
			}
		};
		jQuery.fn.saveItem = function(e) {
			var qty_field = e.find(".part_qty");
			var qty = 1;
			if (qty_field.length>0) {
				qty = qty_field.val().trim();
				if (qty == '' || qty == '0') { return; }
			}

			var original_row = $(this);

			var orig_cond = original_row.find(".condition-selector");
			var cond_id = orig_cond.val();
			var cond_text = orig_cond.text();

			var orig_warr = original_row.find(".warranty-selector");
			var warr_id = orig_warr.val();
			var warr_text = orig_warr.text();

			original_row.find("select.form-control:not(.hidden)").each(function() {
				$(this).select2("destroy");
			});

			var cloned_row = original_row.clone(true);//'true' carries event triggers over to cloned row

			// identify orig_addr here but don't do anything with it yet (selectizing) because we need
			// to find its value and update cloned row below first; see notes below on orig_addr / jQuery bug
			var orig_addr = original_row.find(".address-selector");
			original_row.find(".condition-selector").selectize();
			original_row.find(".warranty-selector").selectize();

			original_row.find("textarea.form-control").val('');

			var st = original_row.find(".search-type");
			var stype = st.val();

			// set qty of new row to qty of user-specified qty on revision found
			cloned_row.find(".item-qty").val(qty);
			var part = cloned_row.find(".part-selector");
			var addr = cloned_row.find(".address-selector");
			if (stype!='Site' && part.length==0 && addr.length>0) {
				part = addr.removeClass('address-selector').addClass('part-selector');
				part.data('url','/json/parts-dropdown.php');
				// reset so we don't use an 'addr' object below
				addr = new Array();
			}

			var partid = qty_field.data('partid');
			var descr = e.find(".part").find(".descr-label").html();
			part.populateSelected(partid, descr);
			part.selectize();
			part.show();

			// update cloned addr with a new id so we can update and save addresses uniquely
			if (addr.length>0) {
				var addr_id = addr.prop('id')+Math.random();
				addr.prop('id',addr_id);
				//needs to be 'attr' here because 'data()' and 'prop()' can't update data tags, thanks jquery dom
				addr.closest("div").find(".address-neighbor").attr('data-name',addr_id);
				// see comment on orig_addr below, this must be updated here post-clone due to jQuery bug
				addr.val(orig_addr.val());
				if (stype=='Site') {
					addr.selectize();
//					addr.populateSelected(orig_addr.val(),orig_addr.text());
				}
			}

			// position here is CRITICAL! needs to be below the cloned addr above so we can update its selection
			// from the original due to a jQuery BUG (see https://stackoverflow.com/questions/742810/clone-isnt-cloning-select-values)
			orig_addr.val(0);//reset selection before selectizing
			if (stype=='Site') {
				orig_addr.selectize();
			}

			var cloned_cond = cloned_row.find(".condition-selector");
			cloned_cond.selectize();
			cloned_cond.populateSelected(cond_id,cond_text);

			var cloned_warr = cloned_row.find(".warranty-selector");
			cloned_warr.selectize();
			cloned_warr.populateSelected(warr_id,warr_text);

			// do not want this new row confused with the original search row
			cloned_row.removeClass('search-row').addClass('item-row');
			// remove search field from new cloned row
			cloned_row.find(".input-search").remove();
			// remove save button
			cloned_row.find(".btn-saveitem").remove();
			// remove readonly status on qty field
			cloned_row.find(".item-qty").prop('readonly',false);

			cloned_row.find(".dropdown .dropdown-toggle.dropdown-searchtype").addClass('hidden');

			cloned_row.insertBefore(original_row);

			updateTotals();
//			var row_total = cloned_row.calcRowTotal();
		};
		jQuery.fn.calcRowTotal = function() {
			var ext_amount = 0;
			if ($(this).find(".order-item").length>0) {
				var order_item = $(this).find(".order-item");
				if (order_item.is(":disabled") || ! order_item.is(":checked")) {
					return (ext_amount);
				}
			}

			if ($(this).find(".item-qty:not([readonly])").length==0) {
				$(this).find(".ext-amount").text('');
				return (ext_amount);
			}
			var qty = 0;
			if ($(this).find(".item-qty").length>0) { qty = $(this).find(".item-qty").val().trim(); }
			if (! qty) { qty = 0; }

			var amount = 0;
			if ($(this).find(".item-amount").length>0) { amount = $(this).find(".item-amount").val().trim(); }
			if (! amount) { amount = 0; }

//			var qty = $(this).find(".item-qty").val().trim();
//			var amount = $(this).find(".item-amount").val().trim();

			ext_amount = qty*amount;

			$(this).find(".ext-amount").text('$ '+ext_amount.formatMoney());
			return (ext_amount);
		};
	});
	function updateTotals() {
		var total = 0.00;
		$(".item-row").each(function() {
			var row_total = $(this).calcRowTotal();
			total += parseFloat(row_total);
		});
		$("#subtotal").text('$ '+total.formatMoney());

		// add freight to Total but not Subtotal above
		$(".input-freight").each(function() {
			var freight = parseFloat($(this).val().trim());
			total += freight;
		});
		$(".input-tax").each(function() {
			var tax = parseFloat($(this).val().trim());
			total += tax;
		});
		$("#total").text('$ '+total.formatMoney());
	}
