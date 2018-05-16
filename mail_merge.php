<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';

	$filelink = '/tmp/survey_recipients.csv';
	$handle = fopen($filelink,"r");

	$sbj = 'Feedback request';
	$msg = 'Would you take 60-seconds to give us some feedback on how we\'re doing?<br/><br/>'.
		'Please go to: ven-tel.com/survey';//<a href="http://www.ven-tel.com/survey">ven-tel.com/survey</a><br/>';
	while (($data = fgetcsv($handle)) !== false) {
//		print "<pre>".print_r($data,true)."</pre>";

		$name = explode(' ',$data[0])[0];
		$to = $data[1];

		$intro = 'Hi '.$name.',<br/><br/>';
echo 'Name: '.$name.'<br>Email: <a href="mailto:'.$to.'?subject='.$sbj.'&body='.($intro.$msg).'">'.$to.'</a><BR>Subject: '.$sbj.'<BR>'.$intro.$msg.'<BR><BR>';
//		send_gmail($intro.$msg,$sbj,$to,'david@ven-tel.com');
	}
?>
