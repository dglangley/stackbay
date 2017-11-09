		/* edits contact in sidebar */
		$(".contact-editor").on('click', function() {
			var contactid = $("#contactid").val();

			$("#modal-contact").populateContact(contactid);
		});
		$(".contact-selector").on('change', function() {
			var contactid = $("#contactid").val();

			var str = $(this).find("option:selected").text();
			if (str.indexOf('Add')==-1) { return; }
			str = str.replace('Add ','').replace('...','');

			$("#modal-contact").populateContact(contactid,str);
		});

		$("#save-contact").on('click', function() {
			var contact = $(".modal");
//			var contactid = contact.find(".contact-id").val();
			var contactid = $("#contactid").val();
			var name = contact.find(".contact-name").val().trim();
			var title = contact.find(".contact-title").val().trim();
			var phone = contact.find(".contact-phone").val().trim();
			var email = contact.find(".contact-email").val().trim();
			var notes = contact.find(".contact-notes").val().trim();

			console.log(window.location.origin+"/json/save-contact.php?contactid="+contactid+"&name="+escape(name)+"&title="+escape(title)+"&phone="+escape(phone)+"&email="+escape(email)+"&notes="+escape(notes)+"&companyid="+companyid);
			$.ajax({
				url: 'json/save-contact.php',
				type: 'get',
				data: {
					'contactid': contactid,
					'name': name,
					'title': title,
					'phone': phone,
					'email': email,
					'notes': notes,
					'companyid': companyid,
				},
				dataType: 'json',
				success: function(json, status) {
					if (json.message && json.message!='Success') { alert(json.message); return; }

					$("#contactid").populateSelected(json.contactid,json.name);
					contact.modal('hide');
					toggleLoader("Contact successfully saved");
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		});
		jQuery.fn.populateContact = function(contactid,str) {
			var contact = $(this);
			if (! contactid) { var contactid = 0; }
			if (! str) { var str = ''; }

			/* defaults */
			contact.find(".contact-name").val(str);
			contact.find(".contact-title").val('');
			contact.find(".contact-email").val('');
			contact.find(".contact-notes").val('');
//			contact.find(".contact-id").val(contactid);

			if (contactid>0) {
				console.log(window.location.origin+"/json/contact.php?contactid="+contactid);
				$.ajax({
					url: 'json/contact.php',
					type: 'get',
					data: {'contactid': contactid},
					dataType: 'json',
					success: function(json, status) {
						if (json.message && json.message!='Success') { alert(json.message); return; }

						contact.find(".contact-name").val(json.name);
						contact.find(".contact-title").val(json.title);
						contact.find(".contact-email").val(json.email);
						contact.find(".contact-notes").val(json.notes);

						contact.modal('show');
					},
					error: function(xhr, desc, err) {
//						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
			} else {
				contact.modal('show');
			}
		};
