<?php

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/indexer.php';
	include_once $rootdir.'/inc/jsonDie.php';

    function part_action($action, $partid = '', $parr = array()){
        $result = array('success'=>'','data'=>array(),'error'=>'');
        if($action == "populate"){
            $result['data'] = current(hecidb($partid,"id"));
            $result['success'] = true;
        } else if ($action == "update") {
			if (! $parr['name']) {
				$result['error'] = "Missing part number! Cannot continue";
			} else if (! $parr['manf']) {
				$result['error'] = "Missing manufacturer! Cannot continue";
			} else {
	            if(!$partid){
					$insert = "
						INSERT INTO `parts` (`part`, `rel`, `heci`, `manfid`, `systemid`, `description`, `classification`) VALUES 
						(".prep($parr['name']).", NULL,".prep($parr['heci'])." , ".prep($parr['manf']).", ".prep($parr['system']).", ".prep($parr['desc']).", ".prep($parr['class']).") ";
					$result['insert'] = $insert;
					qdb($insert) or jsonDie(qe()." $insert");
					$result['partid'] = qid();
				} else if (is_numeric($partid)){
					$update = "
						UPDATE `parts` SET 
						`part` = ".prep($parr['name']).",
						`rel`  = NULL,
						`heci` =  ".prep($parr['heci'])." ,
						`manfid` = ".prep($parr['manf']).",
						`systemid` = ".prep($parr['system']).",
						`description` = ".prep($parr['desc']).",
						`classification` = ".prep($parr['class'])."
						WHERE `id` = ".prep($partid).";";
					$result['update'] = $update;
					qdb($update) or jsonDie(qe()." | $update");
					$result['partid'] = $partid;
				} else {
					$result['error'] = "Incorrect Partid Passed in to the part_action function: $partid";
				}
				indexer($partid);
			}
        } 
        return $result;
    }
?>
