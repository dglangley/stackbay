	$(document).ready(function() {
		/* editor for inline address selector for item charges */
		// instead of using direct-editing using .address-editor class, this friendly neighbor editor uses its nearby address
		// select menu as a way of getting the id and name of its task
		$(".address-neighbor").on('click', function() {
			var addr = $(this).closest(".part-container").find(".address-selector");
			//var idname = addr.prop('name');
			var idname = addr.prop('id');
			var addressid = addr.val();

			$("#modal-address").populateAddress(addressid,idname);
		});

		/* address editor for addresses in sidebar */
		$(".address-editor").on('click', function() {
			var idname = $(this).data('name');
			if (! idname) { return; }

			var addressid = $("#"+idname).val();
			if (! addressid) {
				toggleLoader("Select an address to edit");
				return;
			}

			$("#modal-address").populateAddress(addressid,idname);
		});

		$(".address-selector").on('change', function() {
			var idname = $(this).prop('id');
			var str = $(this).find("option:selected").text();
			if (str.indexOf('Add')==-1) { return; }
			str = str.replace('Add ','').replace('...','');

			if (! companyid) {
				modalAlertShow("Address Error","Please select a company before adding a new address");
				return;
			}

			$("#modal-address").populateAddress(0,idname,str);
		});

		$("#save-address").on('click', function() {
			var address = $(".modal");
			var addressid = address.find(".address-modal").data('oldid');
			var idname = address.find(".address-modal").data('idname');
			var name = address.find(".address-name").val().trim();
			var street = address.find(".address-street").val().trim();
			var addr2 = address.find(".address-addr2").val().trim();
			var city = address.find(".address-city").val().trim();
			var state = address.find(".address-state").val().trim();
			var postal_code = address.find(".address-postal_code").val().trim();
			var nickname = address.find(".address-nickname").val().trim();
			var alias = address.find(".address-alias").val().trim();
			var contactid = address.find(".address-contactid").val();
			if (contactid==null) { contactid = 0; }
			var code = address.find(".address-code").val().trim();
			var notes = address.find(".address-notes").val().trim();

			var params = "addressid="+addressid+"&name="+escape(name)+"&street="+escape(street)+"&addr2="+escape(addr2)+
						"&city="+escape(city)+"&state="+escape(state)+"&postal_code="+escape(postal_code)+
						"&nickname="+escape(nickname)+"&alias="+escape(alias)+"&contactid="+escape(contactid)+
						"&code="+escape(code)+"&notes="+escape(notes);
			console.log(window.location.origin+"/json/save-address.php?"+params);
			$.ajax({
				url: 'json/save-address.php',
				type: 'get',
				data: {
					'addressid': addressid,
					'name': name,
					'street': street,
					'addr2': addr2,
					'city': city,
					'state': state,
					'postal_code': postal_code,
					'companyid': companyid,
					'nickname': nickname,
					'alias': alias,
					'contactid': contactid,
					'code': code,
					'notes': notes,
				},
				dataType: 'json',
				success: function(json, status) {
					if (json.message) { alert(json.message); return; }

					$("#"+idname).populateSelected(json.id,json.text);
					address.modal('hide');
					toggleLoader("Address successfully saved");
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		});

		jQuery.fn.populateAddress = function(addressid,idname,str) {
			var address = $(this);
			if (! addressid) { var addressid = 0; }
			if (! str) { var str = ''; }

			/* defaults */
			address.find(".modal-title").text("Add New Address");
			address.find(".address-name").val('');
			address.find(".address-street").val(str);
			address.find(".address-addr2").val('');
			address.find(".address-city").val('');
			address.find(".address-state").val('');
			address.find(".address-postal_code").val('');
			address.find(".address-nickname").val('');
			address.find(".address-alias").val('');
			//reset contacts list
			address.find(".address-contactid").val('').trigger('change');
			//rebuild with updated info (i.e., companyid if changed)
			address.find(".address-contactid").selectize();
			address.find(".address-code").val('');
			address.find(".address-notes").val('');
			address.find(".address-modal").data('oldid',addressid);
			address.find(".address-modal").data('idname',idname);

			if (addressid>0) {
				console.log(window.location.origin+"/json/address.php?addressid="+addressid+"&companyid="+companyid);
				$.ajax({
					url: 'json/address.php',
					type: 'get',
					data: {'addressid': addressid, 'companyid': companyid},
					dataType: 'json',
					success: function(json, status) {
						if (json.message) { alert(json.message); return; }

						address.find(".modal-title").text(json.title);
						address.find(".address-name").val(json.name);
						address.find(".address-street").val(json.street);
						address.find(".address-addr2").val(json.addr2);
						address.find(".address-city").val(json.city);
						address.find(".address-state").val(json.state);
						address.find(".address-postal_code").val(json.postal_code);
						address.find(".address-nickname").val(json.nickname);
						address.find(".address-alias").val(json.alias);
						if (json.contactid>0) {
							address.find(".address-contactid").populateSelected(json.contactid, json.contact);
						}
						address.find(".address-code").val(json.code);
						address.find(".address-notes").val(json.notes);

						address.modal('show');
					},
					error: function(xhr, desc, err) {
//						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
			} else {
				address.modal('show');
			}
		};
	});



	function addressSearch(search) {
		console.log(window.location.origin+"/json/addresses.php?q="+search);
		$.ajax({
			url: 'json/addresses.php',
			type: 'get',
			data: {'q': search},
			dataType: 'json',
			success: function(json, status) {
				if (json.message && json.message!='Success') { alert(json.message); return; }

				var html = '';
				$.each(json, function(key, row) {
					html += '<tr><td colspan="6">'+row.text+'</td></tr>';
				});

				$('#search_input').append(html);
			},
			error: function(xhr, desc, err) {
//				console.log(xhr);
				console.log("Details: " + desc + "\nError:" + err);
			},
		}); // end ajax call
	}
