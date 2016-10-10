<?php
	include_once 'getContact.php';

	function getUser($search,$input_field='id',$output_field='name') {
		$search = strtolower($search);

		return (getContact($search,'userid','name'));
	}
?>
