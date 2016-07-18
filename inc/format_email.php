<?php
	$EMAIL_CSS = '';
	function format_email($subject='rfq',$main_body='',$teaser='') {
		$U = $GLOBALS['U'];
		$css = $GLOBALS['EMAIL_CSS'];

		$phone = '';
		if ($U['phone']) { $phone = $U['phone'].' &#8226; '; }

		if ($teaser) {
			$teaser = '
		<div class="row" id="teaser">
			'.$teaser.'
		</div>
			';
		}

		$email_contents = '
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>'.$subject.'</title>

    <!-- CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet" media="screen">

	<!-- My style additions -->
	<style type="text/css">
		table {
			max-width:98%;
		}
		table.table-striped tr:first-child th, table.table-striped tr th {
			/*background-color:#ffb644;*/
			background-color:#002368;
			color:#eee;
		}
		#teaser {
			margin-top:14px;
			margin-bottom:20px;
		}
		#teaser, footer {
			font-size:100%;
		}
		#message-body {
			margin-bottom:24px;
		}
		th, td {
			max-width:200px;
			text-overflow:ellipsis;
			overflow:hidden;
			white-space:nowrap;
		}
		.container {
			margin:6px;
			padding:6px;
			margin-left:12px;
			padding-left:12px;
		}
		.container .row {
			font-size:12px;
		}
		'.$css.'
	</style>
  </head>
<body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; color:#526273; background-color:#fff; font-family:\'Open Sans\', sans-serif; font-size:13px">

	<div class="container">
		'.$teaser.'
		<div class="row" id="message-body">
			'.$main_body.'
		</div>
	</div>

	<div class="container">
	  <div class="row">
		  <p style="line-height:16px; font-size:12px; padding:0px; margin:0px">
			<br/><br/>--<br/>
			<strong>'.$U['name'].'</strong>
		  </p>
		  <p style="padding-bottom:12px; margin:0px; font-size:10px">'.$phone.' <a href="http://www.ven-tel.com">www.ven-tel.com</a></p>
		  <p>
			<strong>Ventura Telephone, LLC</strong><br/>
			3037 Golf Course Dr Suite 4<br/>
			Ventura, CA 93003
		  </p>
		  <p>
			<strong>Service-Disabled Veteran-Owned Business<br/>ISO 9001:2008 Certified</strong>
		  </p>
		</div>
    </div> <!-- /container -->

  </body>
</html>
		';

		return ($email_contents);
	}
?>
