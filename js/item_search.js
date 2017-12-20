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

			$(this).closest("tr").find("input[type=text]").not(".line-number,.delivery-date").val("");
		});
		$("#item-search").on('keyup',function(e) {
			e.preventDefault();
			var key = e.which;

			if (key == 13) {
				$(this).search();
			}
		});
		$("#btn-search").on('click',function() {
			$("#item-search").search();
		});
		$(".dropdown-searchtype li").on('click', function() {
			var v = $(this).text();
			var pc = $(this).closest(".part-container");

			if (v=='Site') {
				$(".input-search").removeClass('hidden').addClass('hidden');
				pc.find(".address-neighbor").removeClass('hidden');
				pc.find(".address-selector").selectize();
				pc.find(".address-selector").removeClass('hidden').addClass('select2');
				// remove previously-found parts, if any
				$(this).closest("tbody").find(".found_parts").remove();
			} else if (v=='Part') {
				$(".input-search").removeClass('hidden');
				pc.find(".address-neighbor").removeClass('hidden').addClass('hidden');
				pc.find(".address-selector").select2("destroy");
				pc.find(".address-selector").removeClass('select2').addClass('hidden');
			}
		});

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

			// set qty of new row to qty of user-specified qty on revision found
			cloned_row.find(".item-qty").val(qty);
			var part = cloned_row.find(".part-selector");
			var partid = qty_field.data('partid');
			var descr = e.find(".part").find(".descr-label").html();
			part.populateSelected(partid, descr);
			part.selectize();
			part.show();

			var addr = cloned_row.find(".address-selector");
			// update cloned addr with a new id so we can update and save addresses uniquely
			var addr_id = addr.prop('id')+Math.random();
			addr.prop('id',addr_id);
			//needs to be 'attr' here because 'data()' and 'prop()' can't update data tags, thanks jquery dom
			addr.closest("div").find(".address-neighbor").attr('data-name',addr_id);
			// see comment on orig_addr below, this must be updated here post-clone due to jQuery bug
			addr.val(orig_addr.val());
			addr.selectize();

			// position here is CRITICAL! needs to be below the cloned addr above so we can update its selection
			// from the original due to a jQuery BUG (see https://stackoverflow.com/questions/742810/clone-isnt-cloning-select-values)
			orig_addr.val(0);//reset selection before selectizing
			orig_addr.selectize();

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
			if ($(this).find(".item-qty:not([readonly])").length==0) {
				$(this).find(".ext-amount").text('');
				return;
			}
			var qty = $(this).find(".item-qty").val().trim();
			if (! qty) { qty = 0; }
			var amount = $(this).find(".item-amount").val().trim();
			if (! amount) { amount = 0; }
			var ext_amount = qty*amount;

			$(this).find(".ext-amount").text('$ '+ext_amount.formatMoney());
			return (ext_amount);
		};
	});
	function updateTotals() {
		var total = 0;
		$(".item-row").each(function() {
			var row_total = $(this).calcRowTotal();
			total += row_total;
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
