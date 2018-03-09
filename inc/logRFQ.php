<?php
	function logRFQ($partid,$cid) {
		$query = "INSERT INTO rfqs (partid, companyid, datetime, userid) ";
		$query .= "VALUES ('".res($partid)."','".res($cid)."',".fres($GLOBALS['now']).",'".$GLOBALS['U']['id']."'); ";
		$result = qedb($query);
//		$SEND_ERR .= $query;

		return (qid());
	}
?>
