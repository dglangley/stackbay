
			function price_format(ext){
				if(isNaN(ext)){
					ext = 0.00;
				} else {
					ext = parseFloat(ext);
				}
				var display = ext.toFixed(2);
				display = display.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			    display = '$ '+(display);
			    return display;
			}
