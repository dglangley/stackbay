<?php
	include_once 'getDefaultEmail.php';
	include_once 'send_gmail.php';

	function sendCompanyRFQ($cid,$message_body,$sbj,$contactid=0) {
		global $SEND_ERR,$DEV_ENV,$DEBUG;

		$intro = '';

		if ($DEV_ENV OR $DEBUG) {
			$names = explode(' ','David Langley');
			$to = array('davidglangley@gmail.com',$names[0]);
		} else {
			if ($contactid) {
				$names = explode(' ',getContact($contactid,'id','name'));
				$e = array(getContact($contactid,'id','email'),$names[0]);
			} else {
				$e = getDefaultEmail($cid);
			}
			if ($e===false) { continue; }

			$to = array($e);
		}

		// if name is not already used in message body
		if (count($to)==2 AND substr($message_body,0,3)<>'Hi ' AND stristr($message_body,$to[1])===false) {
			$intro = "Hi ".$to[1].",<br/><br/>";
		}

		if ($DEV_ENV) {
			send_gmail($intro.$message_body,$sbj,$to);
		} else {
			send_gmail($intro.$message_body,$sbj,$to,'david@ven-tel.com');
		}
	}
?>