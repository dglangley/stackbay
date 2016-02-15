<?php
	include_once 'inc/dbconnect.php';
?>
<!DOCTYPE html>
<html>
<head>
	<title>VenTel Market Manager</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
    <!-- bootstrap -->
    <link href="css/bootstrap/bootstrap.css" rel="stylesheet" />
    <link href="css/bootstrap/bootstrap-overrides.css" type="text/css" rel="stylesheet" />

    <!-- libraries -->
    <link href="css/lib/jquery-ui-1.10.2.custom.css" rel="stylesheet" type="text/css" />
    <link href="css/lib/font-awesome.min.css" type="text/css" rel="stylesheet" />

    <!-- global styles -->
    <link rel="stylesheet" type="text/css" href="css/compiled/layout.css" />
    <link rel="stylesheet" type="text/css" href="css/compiled/elements.css" />
    <link rel="stylesheet" type="text/css" href="css/compiled/icons.css" />
    <link rel="stylesheet" type="text/css" href="css/lib/select2.min.css" />

    <!-- this page specific styles -->
    <link rel="stylesheet" href="css/compiled/index.css" type="text/css" media="screen" />

	<!-- development overrides -->
    <link rel="stylesheet" type="text/css" href="css/overrides.css?id=<?php echo $V; ?>" />

    <!-- open sans font -->
<!--
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css' />
-->
    <link href='css/OpenSans.css' rel='stylesheet' type='text/css' />

    <!-- lato font -->
    <link href='css/Lato.css' rel='stylesheet' type='text/css' />

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>
<body class="index">

	<?php include 'inc/modal-results.php'; ?>
	<?php include 'inc/navbar.php'; ?>
	<?php include 'inc/keywords.php'; ?>

	<form class="form-inline">

        <table class="table table-header">
			<tr>
				<td class="col-md-2">
               		<i class="fa fa-star-o fa-lg"></i> &nbsp;
               		<i class="fa fa-pencil fa-lg"></i>
				</td>
				<td class="text-center col-md-6">
					<div class="form-group">
						<input type="text" name="list_name" class="input-xs form-control" value="" placeholder="List Name" />
					</div>
					<label for="inventory-file">Choose your file, then click "Upload Now"...</label>
					<input name="invfile" type="file" id="inventory-file">
				</td>
				<td class="col-md-3">
					<div class="pull-right form-group">
						<select name="companyid" id="companyid" style="width:280px">
							<option value="">- Select a Company -</option>
						</select>
						<button class="btn btn-primary btn-sm" type="button">Submit Data</button>
					</div>
				</td>
			</tr>
		</table>

        <div id="pad-wrapper">

            <!-- table sample -->
            <!-- the script for the toggle all checkboxes from header is located in js/theme.js -->
            <div class="table-products">
                <div class="row">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="col-md-4">
                                    <input type="checkbox" id="checkAll" checked>
									<span class="qty-header">Qty</span>
                                    Product Description
									<span class="price-header">Sell</span>
                                </th>
                                <th class="col-md-6 text-center">
                                    <span class="line"></span>Market
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
									Buy
									<span class="pull-right">Actions</span>
                                </th>
                            </tr>
                        </thead>
<?php
	if (! $s) { $s = 'UN375F'.chr(10).'090-42140-13'.chr(10).'IXCON'; }
	$lines = explode(chr(10),$s);
	foreach ($lines as $n => $line) {
		$terms = preg_split('/[[:space:]]+/',$line);
		$search_str = trim($terms[$search_index]);

		$results = hecidb($search_str);
		$num_results = count($results);
		$s = '';
		if ($num_results<>1) { $s = 's'; }
?>
                        <tbody>
                            <!-- row -->
                            <tr class="first">
                                <td>
									<input type="text" value="<?php echo $search_str; ?>" class="product-search text-primary" /><br/>
									<?php echo $num_results.' result'.$s; ?>
								</td>
                                <td>
									<div class="row">
										<div class="col-sm-3 text-center"><?php echo rand(0,200); ?> day(s)<br/><span class="info">shelflife</span></div>
										<div class="col-sm-3 text-center"><?php echo rand(1,9); ?>:1<br/><span class="info">quotes-to-sale</span></div>
										<div class="col-sm-3 text-center">$ 2,087.41<br/><span class="info">avg cost</span></div>
										<div class="col-sm-3 text-center"><?php echo '$'.rand(200,400).'-$'.rand(550,1300); ?><br/><span class="info">market pricing</span></div>
									</div>
								</td>
                                <td> </td>
							</tr>
<?php
		$k = 0;
		foreach ($results as $partid => $P) {
//			print "<pre>".print_r($P,true)."</pre>";
//                                        <img src="/products/images/echo format_part($P['part']).jpg" alt="pic" class="img" />
?>
                            <!-- row -->
                            <tr class="product-results">
                                <td class="descr-row">
                                    <input type="checkbox" checked>
									<div class="qty">
										<div class="form-group">
											<input type="text" value="1" size="2" placeholder="Qty" class="input-xs form-control" />
										</div>
									</div>
                                    <div class="product-img">
                                        <img src="/products/images/090-42140-13.jpg" alt="pic" class="img" />
                                    </div>
                                    <div class="product-descr">
										<?php echo $P['Part']; ?> &nbsp; <?php echo $P['HECI']; ?><br/>
                                    	<div class="description"><?php echo $P['manf'].' '.$P['system'].' '.$P['description']; ?></div>
									</div>
									<div class="price">
										<div class="form-group">
											<div class="input-group sell">
												<span class="input-group-btn">
													<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
												</span>
												<input type="text" value="1200.00" size="6" placeholder="Sell" class="input-xs form-control price-control" />
											</div>
										</div>
									</div>
                                </td>
<?php
			// if on the first result, build out the market column that runs down all rows of results
			if ($k==0) {
?>
								<!-- market-row for all items within search result section -->
                                <td rowspan="<?php echo ($num_results+1); ?>" class="market-row">
									<table class="table market-table">
										<tr>
											<td class="col-sm-3 bg-sales">
												<a href="#" class="market-title">Sales</a>
												<div class="date-group"><a href="#" class="modal-results">Jan 14: 2 <i class="fa fa-list-alt"></i></a></div>
												<div class="market-data"><span class="pa">2</span> &nbsp; <a href="#">Jupiter</a> <span class="pa">$29.50</span></div>
											</td>
											<td class="col-sm-3 bg-demand">
												<a href="#" class="market-title">Demand</a>
												<div class="date-group"><a href="#" class="modal-results">Jan 12: 2 <i class="fa fa-list-alt"></i></a></div>
												<div class="market-data"><span class="pa">2</span> &nbsp; <a href="#">Jupiter</a> <span class="pa">$35.00</span></div>
												<hr>
												<div class="market-data"><span class="pa">2x</span> &nbsp; Dec</div>
												<div class="market-data"><span class="pa">1x</span> &nbsp; Aug</div>
											</td>
											<td class="col-sm-3 bg-purchases">
												<a href="#" class="market-title">Purchases</a>
												<div class="date-group"><a href="#" class="modal-results">Dec 3: 2 <i class="fa fa-list-alt"></i></a></div>
												<div class="market-data"><span class="pa">2</span> &nbsp; <a href="#">WestWorld</a> <span class="pa">$12.00</span></div>
											</td>
											<td class="col-sm-3 bg-availability">
												<a href="#" class="market-title">Availability</a>
												<div id="market-results"></div>
											</td>
										</tr>
									</table>
                                </td>
<?php
			}
			$hl_flag = 'star-o';
if ($k==1) { $hl_flag = 'star text-danger'; }
else if ($k==2) { $hl_flag = 'star-half-o text-danger'; }

			$k++;
?>
                                <td class="product-actions">
									<div class="price">
										<div class="form-group">
											<div class="input-group buy">
												<span class="input-group-btn">
													<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
												</span>
												<input type="text" value="350.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
											</div>
										</div>
									</div>
                                    <ul class="actions">
                                        <li><i class="fa fa-sticky-note-o fa-lg"></i></li>
                                        <li><i class="fa fa-<?php echo $hl_flag; ?> fa-lg"></i></li>
                                        <li class="last"><i class="fa fa-pencil fa-lg"></i></li>
                                    </ul>
                                </td>
                            </tr>
<?php
		}
?>
                            <!-- row -->
                            <tr>
                                <td> </td>
                                <td> </td>
                            </tr>
                        </tbody>
<?php
	}
?>
                    </table>
                </div>
                <ul class="pagination">
                    <li><a href="#">&laquo;</a></li>
                    <li class="active"><a href="#">1</a></li>
                    <li><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#">4</a></li>
                    <li><a href="#">&raquo;</a></li>
                </ul>
            </div>
            <!-- end table sample -->
        </div>

	</form>


	<!-- scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery-ui-1.10.2.custom.min.js"></script>
    <!-- knob -->
    <script src="js/jquery.knob.js"></script>
    <!-- flot charts -->
    <script src="js/jquery.flot.js"></script>
    <script src="js/jquery.flot.stack.js"></script>
    <script src="js/jquery.flot.resize.js"></script>
    <script src="js/theme.js"></script>
    <script src="js/select2.min.js"></script>
    <script src="js/ventel.js"></script>

    <script type="text/javascript">
        $(function () {

            // jQuery Knobs
            $(".knob").knob();



            // jQuery UI Sliders
            $(".slider-sample1").slider({
                value: 100,
                min: 1,
                max: 500
            });
            $(".slider-sample2").slider({
                range: "min",
                value: 130,
                min: 1,
                max: 500
            });
            $(".slider-sample3").slider({
                range: true,
                min: 0,
                max: 500,
                values: [ 40, 170 ],
            });

            

            // jQuery Flot Chart
            var visits = [[1, 50], [2, 40], [3, 45], [4, 23],[5, 55],[6, 65],[7, 61],[8, 70],[9, 65],[10, 75],[11, 57],[12, 59]];
            var visitors = [[1, 25], [2, 50], [3, 23], [4, 48],[5, 38],[6, 40],[7, 47],[8, 55],[9, 43],[10,50],[11,47],[12, 39]];

            var plot = $.plot($("#statsChart"),
                [ { data: visits, label: "Signups"},
                 { data: visitors, label: "Visits" }], {
                    series: {
                        lines: { show: true,
                                lineWidth: 1,
                                fill: true, 
                                fillColor: { colors: [ { opacity: 0.1 }, { opacity: 0.13 } ] }
                             },
                        points: { show: true, 
                                 lineWidth: 2,
                                 radius: 3
                             },
                        shadowSize: 0,
                        stack: true
                    },
                    grid: { hoverable: true, 
                           clickable: true, 
                           tickColor: "#f9f9f9",
                           borderWidth: 0
                        },
                    legend: {
                            // show: false
                            labelBoxBorderColor: "#fff"
                        },  
                    colors: ["#a7b5c5", "#30a0eb"],
                    xaxis: {
                        ticks: [[1, "JAN"], [2, "FEB"], [3, "MAR"], [4,"APR"], [5,"MAY"], [6,"JUN"], 
                               [7,"JUL"], [8,"AUG"], [9,"SEP"], [10,"OCT"], [11,"NOV"], [12,"DEC"]],
                        font: {
                            size: 12,
                            family: "Open Sans, Arial",
                            variant: "small-caps",
                            color: "#697695"
                        }
                    },
                    yaxis: {
                        ticks:3, 
                        tickDecimals: 0,
                        font: {size:12, color: "#9da3a9"}
                    }
                 });

            function showTooltip(x, y, contents) {
                $('<div id="tooltip">' + contents + '</div>').css( {
                    position: 'absolute',
                    display: 'none',
                    top: y - 30,
                    left: x - 50,
                    color: "#fff",
                    padding: '2px 5px',
                    'border-radius': '6px',
                    'background-color': '#000',
                    opacity: 0.80
                }).appendTo("body").fadeIn(200);
            }

            var previousPoint = null;
            $("#statsChart").bind("plothover", function (event, pos, item) {
                if (item) {
                    if (previousPoint != item.dataIndex) {
                        previousPoint = item.dataIndex;

                        $("#tooltip").remove();
                        var x = item.datapoint[0].toFixed(0),
                            y = item.datapoint[1].toFixed(0);

                        var month = item.series.xaxis.ticks[item.dataIndex].label;

                        showTooltip(item.pageX, item.pageY,
                                    item.series.label + " of " + month + ": " + y);
                    }
                }
                else {
                    $("#tooltip").remove();
                    previousPoint = null;
                }
            });
        });
    </script>
</body>
</html>
