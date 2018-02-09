var imageSlider;

$(document).ready(function() {
	imageSlider = $('.bxslider').bxSlider({
		adaptiveHeight: true,
		mode: 'fade',
		pagerCustom: '#imagePager',
		// infiniteLoop:false
	});

	// reload bxslider after opening modal because of modal/bxslider conflicts
	//$('#image-modal').on('shown.bs.modal', function (e) {
		// imageSlider.reloadSlider({
		// 	adaptiveHeight: true,
		// 	mode: 'fade',
		// 	pagerCustom: '#imagePager',
		// });
	//});

//	$(".product-img img").click(function() {
	$("body").on('click','.product-img img',function() {
		//$("#modal-prod-img").attr('src',$(this).attr('src'));
		var part = $(this).data('part');
		updateSliderImages(part);
		$("#prod-image-title").text(part);
		// set dropzone data 'id' to the value of this string to match uploads against each part result
		$("#imageDropzone").data('id',part);
		$("#image-modal").modal('toggle');
	});

	$(document).on("click",".imageTools .imageDelete",function() {
		var msg = 'This will delete this picture for ALL matching parts, not just for the currently-selected part. Are you sure?';
		var user_conf = confirm(msg);
		if (user_conf===true) {
			updateSliderImages($("#imageDropzone").data("id"),$(this).data("image"),'delete');
		}
	});

	$(document).on("click",".imageTools .imagePrime",function() {
		updateSliderImages($("#imageDropzone").data("id"),$(this).data("image"),'prime');
	});
});

function updateSliderImages(search,img,imgAction) {
	if (! img) { var img = ''; }
	if (! imgAction) { var imgAction = ''; }

   	console.log(window.location.origin+"/json/images.php?search="+search+"&img="+(img)+"&imgAction="+imgAction);
    $.ajax({
		url: 'json/images.php',
		type: 'get',
		data: {'search': search, 'img': (img), 'imgAction': imgAction },
		dataType: 'json',
		success: function(json, status) {
			if (json.message!='') { alert(json.message); }

			var slider,del,prime,ln,imgSrc,imgPath;
			var prime_image = 0;
			var pager = $("#imagePager");
			var modal = $("#image-modal");
			var count = 0;
			modal.find(".bxslider").each(function() {
				slider = $(this);
				// remove each slider image to clear the slider for new images below
				slider.find("li").each(function() {
					$(this).remove();
				});
				// remove pager images (should correspond to each of the slider images)
				pager.find(".imageTools").each(function() {
					$(this).remove();
				});

				// get 'prime' (showcase) pic, if preset
				if (json.prime && json.prime!='') { prime_image = json.prime; }

				// add each new image pulled for this part / search string
				$.each(json.images, function(i, img) {
					imgSrc = img.path;
					imgName = img.filename;

					// add image list item to slider
					slider.append('<li><img src="'+imgSrc+'"></li>');
					// add image to corresponding pager
					del = '<span class="a imageDelete" data-image="'+imgName+'"><i class="fa fa-close fa-2x text-danger"></i></span>';
					if (i==prime_image) {
						prime = '<i class="fa fa-check-circle fa-2x text-primary"></i>';
					} else {
						prime = '<span class="a imagePrime" data-image="'+imgName+'"><i class="fa fa-circle-thin fa-2x text-primary"></i></span>';
					}
					ln = '<p>'+prime+' '+del+'</p>';
					pager.append('<div class="imageTools"><a data-slide-index="'+i+'" href="javascript:void(0);"><img src="'+imgSrc+'" /></a><br/>'+ln+'</div>');

					count++;
				});

				if(prime_image > (count-1)) {
					prime_image = 0;
				} 

				//alert(count);

				imageSlider.reloadSlider({startSlide:prime_image, mode: 'fade'});
			});
		},
		error: function(xhr, desc, err) {
//				console.log(xhr);
			console.log("Details: " + desc + "\nError:" + err);
		}
	}); // end ajax call
}

Dropzone.autoDiscover = false;
// dropzone class:
if ($('#imageDropzone').length > 0) {
	var imageDropzone = new Dropzone ("div#imageDropzone",{
		url: "json/image-upload.php",
		paramName: "file", // The name that will be used to transfer the file
		maxFilesize: 4, // MB
		uploadMultiple: true,
		clickable: true,
		addRemoveLinks: false,
		dictRemoveFile: "Remove",
		acceptedFiles: ".png, .jpg, .jpeg, .gif",
		dictDefaultMessage: "<h4>Drop File(s) Here or Click to Upload</h4>",
		accept: function(file, done) {//gets the file and does something before sending to url for upload
			if (file.name == "justinbieber.jpg") {
				done("Naha, you don't.");
			} else {
				done();//submit to url
			}
		},
		success: function(file, response) {// sent data to url, got the response back
			if (response!="") { alert(response); }
			updateSliderImages($("#prod-image-title").text());
		},
	});
	//add part string to form request on each image send
	imageDropzone.on("sending", function(file, xhr, formData) {
		var id = $("#imageDropzone").data('id');
		formData.append("search", id);
		var watermark = 0;
		if ($("#watermark").prop('checked')) { watermark = 1; }
		formData.append("watermark", watermark);
	});
}
