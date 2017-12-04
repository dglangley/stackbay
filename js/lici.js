(function($){
	// var refresh = false;

	// generalize an object to be clicked or changed
	// $(document).on("click", ".clock", function() {

	// });

	// Function to concide with the select2 mechanism to load the next page and clock in/out of the new task and the old task
	$(document).on("change", ".task_selection", function(){
		modalLiciAlert("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Please Confirm you want to log out of the current task.", true);
	});

	function loadTask(task_label, order_number){
		task_label = task_label.toLowerCase().replace(/\b[a-z]/g, function(letter) {
		    return letter.toUpperCase();
		});
		window.location.href = "/service.php?order_type="+task_label+"&order_number="+order_number;
		console.log("/service.php?order_type="+task_label+"&order_number="+order_number);
	}

	// Initiates the initial login if the user is assigned to the task
	var taskid = $('body').data('taskid');
	var task_label = '';

	if($('body').data('order-type') == 'repair' || $('body').data('order-type') == 'Repair') {
		task_label = 'repair_item_id';
	} else {
		task_label = 'service_item_id';
	}

	if(task_label != 'quote') {
		// alert(task_label);
		clock(taskid, task_label, 'init');
	}
	// window.onbeforeunload = clockout(taskid, task_label);

	//function clockout(taskid, task_label, type) {
	function clock(taskid, task_label, type, redirect) {	
		console.log(window.location.origin+"/json/lici.php?taskid="+escape(taskid)+"&task_label="+escape(task_label)+"&type="+escape(type));
		$.ajax({
	        url: 'json/lici.php',
	        type: 'get',
	        dataType: "json",
	        async: false,
	        data: {'taskid': taskid, 'task_label': task_label, 'type' : type},
	        success: function(data) {
	        	var taskid = $('body').data('taskid');
				var task_label = $('body').data('order-type').toLowerCase();
						
	        	if(redirect) {
	        		loadTask(task_label, data);
	        	}

	        	// if(! $.isNumeric(data)) {
	        	// 	// Consider the data as an error, otherwise it should be the order number to invoke the redirect
	        	// 	modalLiciAlert("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", data, false);
	        	// }

	        	 console.log(data);
	        	//return order_number;
			}
		});
	}

	function modalLiciAlert(header,body, redirect){
		$('#modalLiciAlertTitle').html(header);
		$('#modalLiciAlertBody').html(body);

		if(redirect) {
			$('#alert-travel').attr("data-redirect", "true");
			$('#alert-clock').attr("data-redirect", "true");
		} else {
			$('#alert-travel').attr("data-redirect", "");
			$('#alert-clock').attr("data-redirect", "");
		}

       	$('#modal-lici-alert').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	$(document).on('click', '#alert-travel', function(e){
		var redirect = '';

		if($(this).attr("data-redirect")) {
			redirect = true;

			// Value is predicted to be the taskid
			var newTask = $('.task_selection').val();

			// Make sure the change on the select2 is actually a new task and not the current task
			if(newTask != taskid) {
				// alert(newTask + ' ' + task_label);
				// Clock out from the current task
				clock(taskid, task_label, 'out');

				// Clock into the new task selected and redirect the user to the respective page
				clock(newTask, task_label, 'travel', true);
			}
		} else {
			clock('', '', 'out');
			clock(taskid, task_label, 'travel', redirect);
		}
	});

	$(document).on('click', '#alert-clock', function(e){
		var redirect = '';

		if($(this).attr("data-redirect")) {
			redirect = true;

			// Value is predicted to be the taskid
			var newTask = $('.task_selection').val();

			// Make sure the change on the select2 is actually a new task and not the current task
			if(newTask != taskid) {
				// alert(newTask + ' ' + task_label);
				// Clock out from the current task
				clock(taskid, task_label, 'out');

				// Clock into the new task selected and redirect the user to the respective page
				clock(newTask, task_label, 'clockin', true);
			}
		} else {
			clock('', '', 'out');
			clock(taskid, task_label, 'clockin', redirect);
		}
	});

	$(document).on('click', '#cancel-lici', function(e){
		window.location.href = "/operations.php";
	});

})(jQuery);