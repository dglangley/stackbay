(function($){
	var refresh = false;

	// generalize an object to be clicked or changed
	// $(document).on("click", ".clock", function() {

	// });

	// Function to concide with the select2 mechanism to load the next page and clock in/out of the new task and the old task
	$(document).on("change", ".task_selection", function(){
		if (confirm("Please Confirm you want to log out of the current task.")) {
			// Value is predicted to be the taskid
			var newTask = $(this).val();
			
			// Projected within each option is the type of the task
			// var newLabel = $(this).data("type");

			// Pull from the body the current task the user is on
			var taskid = $('body').data('taskid');
			var task_label = $('body').data('order-type').toLowerCase();

			// Make sure the change on the select2 is actually a new task and not the current task
			if(newTask != taskid) {
				// alert(newTask + ' ' + task_label);
				// Clock out from the current task
				clock(taskid, task_label, 'out');

				// Clock into the new task selected and redirect the user to the respective page
				clock(newTask, task_label, 'in');
			}
		}
	});

	function loadTask(task_label, order_number){
		window.location.href = "/task_view.php?type="+task_label+"&order="+order_number;
	}

	// Initiates the initial login if the user is assigned to the task
	var taskid = $('body').data('taskid');
	var task_label = $('body').data('order-type');

	if(task_label != 'quote') {
		clock(taskid, task_label, 'init');
	}
	// window.onbeforeunload = clockout(taskid, task_label);

	//function clockout(taskid, task_label, type) {
	function clock(taskid, task_label, type) {	
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
						
	        	if(type == 'in') {
	        		loadTask(task_label, data);
	        	}

	        	if(! $.isNumeric(data)) {
	        		// Consider the data as an error, otherwise it should be the order number to invoke the redirect
	        		if(confirm(data)) {
	        			clock('', '', 'out');
						clock(taskid, task_label, 'init');
	        		}
	        	}

	        	 console.log(data);
	        	//return order_number;
			}
		});
	}

})(jQuery);