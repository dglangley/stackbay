<?php
	function format_email($subject='rfq',$main_body='',$teaser='') {
		$U = $GLOBALS['U'];

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
	</style>
  </head>
<body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;">

	<div class="container">
		<div class="row" id="teaser">
			'.$teaser.'
		</div>

		<div class="row" id="message-body">
			'.$main_body.'
		</div>
	</div>

	<div class="container">
      <hr>

      <footer>
		<p style="font-size:14px">
			Thanks,<br/><br/>
			<strong>'.$U['name'].'</strong><br/>
			(805) 212-4959<br/>
			<a href="http://www.ven-tel.com">www.ven-tel.com</a><br/><br/>
		</p>
		<p>
			<img src="http://ven-tel.com/site/templates/images/ventel-logo.png" style="width:160px"><br/>
		</p>
      </footer>
    </div> <!-- /container -->

  </body>
</html>
		';

		return ($email_contents);
	}
?>
