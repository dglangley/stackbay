function validation(e, formCase, type) {
	var validation, $element;
	
	//Store all error messages here
	var $error = new Array();
	
	$('.general-form-error').remove();
	
	//Generic Clear past elements if page has multiple forms
	$('.required').css('border-color', '');
	$('.required').siblings('.select2').find('.select2-selection--single').css('border-color', '');

	//If the case is just a standard form then...
	if($.type(formCase) == 'object') {
		$element = formCase;
		//var $selector = formCase;
	} else {
		//In general this should never happen, but have it here as a catch
		return false;
	}
	
	$element.find('.required').each(function(){
		var selected,forTag;

		//Make sure the required field is tied to some form input field
		if($(this).is('select')) {
			selected = $('option:selected', this).text();
		} else if($(this).is('input') || $(this).is('textarea')) {
			selected = $(this).val();
		} else {
			//Catch if required is used on a non-form item continue
			return;
		}
		
		//If the element is required, a form element, and is empty, then stop further movement and call an error
		if(selected == '' || selected == null) {
			//Not being used but grabs the closest label to the element as uses for error purposes possibly
			var label = $(this).siblings('label').text().replace(/[^a-z0-9\s]/gi, '');
			
			$error.push($(this));
			validation = true;
			//return false; 
		} else {
			$(this).css('border-color', '');
			$(this).siblings('.select2').find('.select2-selection--single').css('border-color', '');
			if ($("[for='"+$(this).prop('name')+"']").length) {
				forTag = $("[for='"+$(this).prop('name')+"']");
				if (forTag.prop('tagName')!='LABEL') { forTag.css('border-color',''); }
			}
		}
	});
  
	if (validation) {
		var forTag;
        
        $element.find('.required').first().focus();
        
        //Run thru all the fields with an error and mark them up
        for(var i = 0; i < $error.length; i++) {
        	$error[i].css('border-color', '#d9534f');
        	$error[i].siblings('.select2').find('.select2-selection--single').css('border-color', '#d9534f');
			if ($("[for='"+$error[i].prop('name')+"']").length) {
				forTag = $("[for='"+$error[i].prop('name')+"']");
				if (forTag.prop('tagName')=='LABEL') { continue; }
				forTag.css('border-color','#d9534f');
			}
        }
        
        //Pop some sort of alert here...
        var message = '<div id="row" class="general-form-error alert alert-danger fade in text-center" style="margin-bottom: 0;">\
		    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>\
		    <strong>Error!</strong> Missing Fields\
		    \
		</div>';
		if(type != 'modal') {
			$element.closest('body').find('.table-header').after(message);
		} else {
			$element.closest('.modal').find('.modal-header').after(message).delay(5000).queue(function(){
			    //$('.general-form-error').remove(); 
			});;
		}
				
        return false;
    }
    else {
        //Everything seems good here so lets
        //Get all the required field values and the name of the field
        //Ajax callback 
        var isValid = true;
        var errors = new Array();
        
        ajax_callback($element, function(data) {
			for (var key in data) {
				if (data.hasOwnProperty(key)) {
					if(data[key] !== true) {
						errors.push(data[key]);
						$('input[name=' + key +']').css('border-color', '#d9534f');
						$('textarea[name=' + key +']').css('border-color', '#d9534f');
						isValid = false;
					}
					//console.log(data[key]);
				}
			}
			
			//If everything is correct then continue with the intended process
			if(isValid) {
				$element.unbind("submit").submit();
			} else {
				errors = unique(errors);
				//alert('An error happened');
				var message = '<div id="row" class="general-form-error alert alert-danger fade in text-center" style="margin-bottom: 0;">\
				    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>\
				    <strong>Error!</strong> ' + errors + '\
				</div>';
				$element.closest('body').find('.table-header').after(message);
				
				return isValid;
			}
			
		});
		
        return isValid;
    }
    
}

function unique(list) {
	var result = [];
	
	$.each(list, function(i, e) {
		if ($.inArray(e, result) == -1) result.push(e);
	});
	
	return result;
}

function ajax_callback($element, callback) {  
	$.ajax({
		type: "POST",
		url: '/json/validation.php',
		data: $element.find('.required').serialize(), // serializes the form's elements.
		dataType: 'json',
		async: false, //blocks window close
		success: function(data) {
	        callback(data);
		}
	}); 
}  

function stopAll(e) {
    //This stops a form submit
    e.preventDefault();
}

//Special case for non-form elements
//onClick of defined class Validation
function nonFormCase($element, e, type = '') {
	var classHolder = $element.data('validation');
	
	var $obj = $element.closest('body').find('.' + classHolder)
	
	//console.log(validation(e, $obj) + ' result');
	
	return validation(e, $obj, type);
}
	
/* //dgl 1-20-17 because this was interrupting ALL forms globally
(function($){
	//Based on all inputs with the "required" class
	//Any form submit, stop it first
	$('form').on('submit', function(e) {
		stopAll(e);
		validation(e, $(this));
	});
})(jQuery);
*/
