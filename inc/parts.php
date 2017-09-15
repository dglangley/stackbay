<?php
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
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
			} 
			// Currently not in use (future maybe)
			// else if (! $parr['manf']) {
			// 	$result['error'] = "Missing manufacturer! Cannot continue";
			// }
			 else {
	            if(!$partid){
					$insert = "
						INSERT INTO `parts` (`part`, `rel`, `heci`, `manfid`, `systemid`, `description`, `classification`) VALUES 
						(".prep(strtoupper($parr['name'])).", NULL,".prep(strtoupper($parr['heci']))." , ".prep($parr['manf']).", ".prep($parr['system']).",
						".prep(strtoupper($parr['desc'])).", ".prep(strtolower($parr['class'])).") ";
					//$result['insert'] = $insert;
					qdb($insert) or jsonDie(qe()." $insert");
					$partid = qid();
					$result['partid'] = $partid;
					indexer($partid,'id');
				} else if (is_numeric($partid)){
					$update = "
						UPDATE `parts` SET 
						`part` = ".prep(strtoupper($parr['name'])).",
						`rel`  = NULL,
						`heci` =  ".prep(strtoupper($parr['heci']))." ,
						`manfid` = ".prep($parr['manf']).",
						`systemid` = ".prep($parr['system']).",
						`description` = ".prep(strtoupper($parr['desc'])).",
						`classification` = ".prep(strtolower($parr['class']))."
						WHERE `id` = ".prep($partid).";";
					$result['update'] = $update;
					qdb($update) or jsonDie(qe()." | $update");
					$result['partid'] = $partid;
					indexer($partid,'id');
				} else {
					$result['error'] = "Incorrect Partid Passed in to the part_action function: $partid";
				}
			}
        } 
        return $result;
    }
?>
