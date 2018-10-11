<?php
	include_once 'getDefaultEmail.php';
	include_once 'getContact.php';
	include_once 'getCompany.php';
	include_once 'send_gmail.php';

	if (! isset($SEND_ERR)) { $SEND_ERR = ''; }

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
			if ($e===false) {
				if ($SEND_ERR) { $SEND_ERR .= '<BR>'; }
				$SEND_ERR .= getCompany($cid).' has no available email, please add then retry.';
				return false;
			}

			$to = array($e);
		}

		// if name is not already used in message body
//		if (count($to)==2 AND substr($message_body,0,3)<>'Hi ' AND stristr($message_body,$to[1])===false) {
//			$intro = "Hi ".$to[1].",<br/><br/>";
//		}

		if ($DEV_ENV) {
			send_gmail($intro.$message_body,$sbj,$to);
		} else {
if ($GLOBALS['GMAIL_USERID']==1) {
                        send_gmail($intro.$message_body,$sbj,$to,'','','','cydney@ven-tel.com');
} else {
                        send_gmail($intro.$message_body,$sbj,$to,'david@ven-tel.com');
}

			return true;
		}

		return false;
	}
?>
