var taskid,task_label;
(function($){
	// Initiates the initial login if the user is assigned to the task
	taskid = $('body').data('taskid');
	task_label = '';

	if($('body').data('order-type') == 'repair' || $('body').data('order-type') == 'Repair') {
		task_label = 'repair_item_id';
	} else {
		task_label = 'service_item_id';
	}

	if(task_label != 'quote') {
		// alert(task_label);
		clock(taskid, task_label, 'init');
	}

	// Function to concide with the select2 mechanism to load the next page and clock in/out of the new task and the old task
	$(document).on("change", ".task_selection", function(){
		loadTask($(this).val());
	});

	$(document).on('click', '.btn-clock', function(e) {
		var clock_type = $(this).data('type');

		// Clock into the new task selected and redirect the user to the respective page
		var clockid = clock(taskid, task_label, clock_type);
		loadTask(clockid);
	});

})(jQuery);

	function loadTask(order_number){
		var order_type = $('body').data('order-type').toLowerCase().replace(/\b[a-z]/g, function(letter) {
		    return letter.toUpperCase();
		});
		console.log("/service.php?order_type="+order_type+"&order_number="+order_number);

		if (! order_number || order_number===false | order_number==='false') {
			window.location.href = "/services.php";
		} else {
			window.location.href = "/service.php?order_type="+order_type+"&order_number="+order_number;
		}
	}

	function clock(taskid, task_label, type) {	
		var clockid = false;

		console.log(window.location.origin+"/json/lici.php?taskid="+escape(taskid)+"&task_label="+escape(task_label)+"&type="+escape(type));
		$.ajax({
	        url: 'json/lici.php',
	        type: 'get',
	        dataType: "json",
	        async: false,
	        data: {'taskid': taskid, 'task_label': task_label, 'type' : type},
	        success: function(arr) {
				if (arr.message && ! arr.id) {
	        		modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", arr.message);
					return;
				}

	        	if (arr.message && arr.message!='') {
	        		// Consider the message as an error, otherwise it should be the order number to invoke the redirect
	        		modalLiciAlert("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", arr.message, false);
	        	} else if (arr.id && arr.id!='') {
					clockid = arr.id;
				}
			},
			error: function(xhr, desc, err) {
				console.log("Details: " + desc + "\nError:" + err);
			},
		});
		return clockid;
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
