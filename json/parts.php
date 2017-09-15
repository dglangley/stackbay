<?php
	include_once $_SERVER['ROOT_DIR']."/inc/dbconnect.php";
	include_once $_SERVER['ROOT_DIR']."/inc/parts.php";
	header('Content-Type: application/json');
    
    $action = grab('action');
    $partid = grab('partid');
    $part_arr = array(
      "name" => grab('name'),
      "heci" => grab('heci'),
      "desc" => grab('desc'),
      "manf" => grab('manf'),
      "system" => grab('system'),
      "class" => grab('class')
      );
    echo json_encode(part_action($action,$partid,$part_arr));
?>
