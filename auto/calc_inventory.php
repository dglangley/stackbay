<?php
// ALTER TABLE  `qtys` ADD  `hidden_qty` INT( 9 ) UNSIGNED NULL ,
// ADD  `visible_qty` INT( 9 ) UNSIGNED NULL ;

//Standard includes section
$rootdir = $_SERVER['ROOT_DIR'];
include_once $rootdir.'/inc/dbconnect.php';
include_once $rootdir.'/inc/form_handle.php';

$SELECT = "
SELECT SUM(`qty`) total, `partid` 
FROM `inventory` 
WHERE `qty` > 0 
and partid is not null 
AND `condition` > 0
AND `status` = 'shelved'
GROUP BY partid;";
$res = qdb($SELECT) or die(qe()." $SELECT");
foreach($res as $r){
    //vardec
    $up = '';
    $ptotal = '';
    $insert = '';
    $ptotal = prep($r['total'],0);
    
    //Check to see if there is a qtys record
    $check = "SELECT * FROM `qtys` WHERE partid = ".prep($r['partid']).";";
    $result = qdb($check) or die(qe()." | $check");
    if(!mysqli_num_rows($result)){
        //if not, new part and create one
        $insert = "INSERT INTO `qtys`(`partid`, `qty`, `hidden_qty`, `visible_qty`) VALUES (".$r['partid'].", ".$r['qty'].", NULL, ".$r['qty'].");";
        qdb($insert) or die(qe()." | $insert");
    } else {
        //otherwise, update the old value
        $up = "UPDATE `qtys`SET 
        `qty` = $ptotal,  
        `visible_qty` = CASE
             WHEN ((`hidden_qty` = 0 AND `hidden_qty` is not null) OR (`hidden_qty` >= $ptotal)) THEN 0
             ELSE ($ptotal - ifnull(`hidden_qty`,0))
           END
           WHERE `partid` = ".prep($r['partid'])."
        ;";
        qdb($up) or die(qe());
    }
}

?>