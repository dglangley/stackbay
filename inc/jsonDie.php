<?php
	function jsonDie($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}
?>
