//==============================================================================
//================================== PACKAGES ==================================
//==============================================================================

//Open Modal
	
	$(document).on("click",".box_edit", function(){
		var package_number = $(".box_selector.active").text();
		box_edit(package_number);
	});

//Kill Package

	$(document).on("click",".delete_box", function(event){
		event.preventDefault();

		if(confirm('Are you sure you want to delete the current box?')) {
			var id = $("#package-modal-body").attr("data-modal-id");

			$.ajax({
				type: "POST",
				url: '/json/packages.php',
				data: {
					"action": "delete_package",
					"id": id,
				},
				dataType: 'json',
				success: function(update) {
					location.reload();
					console.log("JSON packages.php: Success");
					console.log(update);
				},
				error: function(xhr, status, error) {
					alert(error+" | "+status+" | "+xhr);
					console.log("JSON packages.php: Error");
				},				
				
			});
		}
	});

//Submit Modal
	$(document).on("click","#package-continue", function(){
			
		//Set redundant-ish variables for easier access
		var width = $("#modal-width").val();
		var height = $("#modal-height").val();
		var length = $("#modal-length").val();
		var weight = $("#modal-weight").val();
		var tracking = $("#modal-tracking").val();
		var freight = $("#modal-freight").val();
		var id = $("#package-modal-body").attr("data-modal-id");
		var order_type = $("#order_body").attr("data-order-type");
		
		//Update the Data tags on the page
		$(".box_selector.active").attr("data-width",width);
		$(".box_selector.active").attr("data-h",height);
		$(".box_selector.active").attr("data-l",length);
		$(".box_selector.active").attr("data-weight",weight);
		$(".box_selector.active").attr("data-tracking",tracking);
		$(".box_selector.active").attr("data-row-freight",freight);

		
		$.ajax({
			type: "POST",
			url: '/json/packages.php',
			data: {
				"action": "update",
				"width": width,
				"height": height,
				"length": length,
				"weight": weight,
				"tracking": tracking,
				"freight": freight,
				"type":order_type,
				"id": id,
			},
			dataType: 'json',
			success: function(update) {
				console.log("JSON packages.php: Success");
				console.log(update);
			},
			error: function(xhr, status, error) {
							alert(error+" | "+status+" | "+xhr);
				console.log("JSON packages.php: Error");
				console.log(window.location.origin+"/json/packages.php?"+"action="+"update&"+"&width="+width+"&height="+height+"&length="+length+"&weight="+weight+"&tracking="+tracking+"&freight="+freight+"&type="+order_type+"&id="+id);
			},				
			
		});
	
	});
			
//Add New Box
		$(document).on("click",".box_addition", function(){
			//Automatically build the name for the button
				var $button = $(this);
				$button.prop('disabled', true);
				var final = $(this).siblings(".box_selector").last();
				var autoinc = parseInt(final.text());
				autoinc++;
				// var updatedtext = final.text();
				// updatedtext = updatedtext.slice(0,-2)+" "+autoinc;
				var order_number = $("body").attr("data-order-number");
				console.log("Order Number: "+ order_number);
				// console.log("Updated Text: "+ updatedtext);

				var order_type = $("body").attr("data-order-type");

			//Submit this new name as a record in the database
			$.ajax({
				type: "POST",
				url: '/json/packages.php',
				data: {
					action: "addition",
					order: order_number,
					type: order_type,
					name: autoinc
				},
				dataType: 'json',
				success: function(id) {
					$(".box_selector").removeClass("active");
				//Finally, output the button
					final.clone().text(autoinc).insertAfter(final)
					.attr("data-row-id",id).attr("data-box-shipped", '')
					.addClass("active").removeClass('btn-grey');
					// $(".box_drop").each(function(){
					// 	$(this).children("option").last().after("<option data-boxno="+autoinc+" value='"+id+"'>Box "+autoinc+"</option>");
					// });
					$(".active_box_selector").each(function(){
						$(this).children("option").last().after("<option data-boxno="+autoinc+" value='"+id+"'>Box "+autoinc+"</option>");		
					});
					$(".box_selector").each(function(){
						$(this).children("option").last().after("<option data-boxno="+autoinc+" value='"+id+"'>Box "+autoinc+"</option>");		
					});
					$(".active_box_selector").val(id);
					
					$button.prop('disabled', false);
					
					console.log("JSON package addition packages.php: Success");
				},
				error: function(xhr, status, error) {
									alert(error+" | "+status+" | "+xhr);
					console.log("JSON package addition packages.php: Error");
					console.log("/packages.php?action=addition&order="+order_number+"&name="+autoinc+"&type="+order_type);
				}
			});
			
		});
		
//Change Selected Box
		$(document).on("click",".box_selector",function() {
			$(this).siblings(".box_selector").removeClass("active");
			$(this).addClass("active");
			var num = $(this).attr("data-row-id");
			if ($(".active_box_selector").find("option[value="+num+"]").is(':enabled')){
				$(".active_box_selector").val(num);
			}
		});
		
//Change of a dropdown
		$(document).on("change",".box_drop",function() {
		    var assoc = $(this).data("associated");
		    var pack = $(this).val();
				$.ajax({
					type: "POST",
					url: '/json/packages.php',
					data: {
						"action" : "change",
						"assoc" : assoc,
						"package" : pack
					},
					dataType: 'json',
					success: function(id) {
						console.log("JSON package change packages.php: Success");
						console.log("Package "+assoc+" Set to box id "+pack)
					},
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON package change packages.php: Error");
					}
				});
		});

		function box_edit(package_number){
			var order_number = $("body").attr('data-order-number');
			var origin = $(".box_selector:contains('"+package_number+"')");
			var order_type = $("body").data("order-type");
			if (package_number){
				$("#package_title").text("Editing Box #"+package_number);
				$("#alert_title").text("Box #"+package_number);
				$("#modal-width").val(origin.attr("data-width"));
				$("#modal-height").val(origin.attr("data-h"));
				$("#modal-length").val(origin.attr("data-l"));
				$("#modal-weight").val(origin.attr("data-weight"));
				$("#modal-tracking").val(origin.attr("data-tracking"));
				$("#modal-freight").val(origin.attr("data-row-freight"));
				$("#package-modal-body").attr("data-modal-id",origin.attr("data-row-id"));
				
				var status = origin.attr('data-box-shipped');
				
				if(status && order_type !='Purchase') {
					$("#alert_message").show();
				} else {
					$("#alert_message").hide();
				}
				$.ajax({
					type: "POST",
					url: '/json/package_contents.php',
					data: {
						"order_number": order_number,
						"package_number": package_number
					},
					dataType: 'json',
					success: function(data) {
						console.log('/json/package_contents.php?order_number='+order_number+"&package_number="+package_number);
						console.log(data);
						$('.modal-packing').empty();
						if (data){
							$.each( data, function( i, val ) {
								$.each(val, function(it,serial){
										var element = "<tr>\
												<td>"+ i +"</td>\
												<td>"+ serial +"</td>\
											</tr>";
										$('.modal-packing').append( element );
									});
								});
								// for(var k = 0; k < val.length; k++) {
						}
							
						//After the edit modal has been set with the proper data, show it
						if(!data && order_type == 'Sale') {
							$('.delete_box').attr("package", package_number);
							$('.delete_box').attr("order_number", order_number);
							$('.delete_box').attr("order_type", order_type);
							$('.delete_box').show();
						}

						$("#modal-package").modal("show");
					},
					error: function(xhr, status, error) {
										alert(error+" | "+status+" | "+xhr);
						console.log("JSON packages_contents.php: Error");
						submitProblem("SYSTEM",'/json/package_contents.php?order_number='+order_number+"&package_number="+package_number);
						console.log('/json/package_contents.php?order_number='+order_number+"&package_number="+package_number);
					},				
					complete: function(){
						$("#modal-tracking").focus();
					}
				});
			}
			else{
				alert('Please select a box before editing');
			}
	}
	function package_delete(pack, serialid){
		$.ajax({
			type: "POST",
			url: '/json/packages.php',
			data: {
				"action" : "delete",
				"assoc" : serialid,
				"package" : pack
			},
			dataType: 'json',
			success: function(id) {
				console.log("JSON Package Delete | packages.php: Success");
			},
			error: function(xhr, status, error) {
				alert(error+" | "+status+" | "+xhr);
				submitProblem("SYSTEM","JSON Package Delete | packages.php: Error");
				console.log("JSON Package Delete | packages.php: Error");
			}
		});
	}