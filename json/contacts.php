<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/keywords.php';
	include_once '../inc/getContact.php';
	include_once '../inc/getContacts.php';
	include_once '../inc/form_handle.php';



	$q = '';
	   
	
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
    $q = grab('q');

    
    //$companyid = (isset($_REQUEST['limit']))? trim($_REQUEST['limit']) : '0'; 
    $companyid = prep(grab('limit'));
    $output = array();
    
    $query = "SELECT * FROM `contacts` WHERE `name` LIKE '%$q%' AND `companyid` = $companyid;";
    $primary = qdb($query);
    
    // $output[] = array(
    //     'id' => 'co',
    //     'text' => $companyid
    //     );
    
    if (isset($primary)){
        foreach($primary as $id => $row){
            $line = array(
                'id' => $row['id'], 
                'text' => $row['name']
            );
            $output[] = $line;
        }
        
    }   
    $output[] = array(
        'id' => 'NULL', 
        'text' => "--------------------------------"
        );
    
    //Then append the rest of the contacts ordered by alphabetical
    $secondary = " SELECT DISTINCT * FROM `contacts`
    WHERE `companyid` != $companyid AND `name` LIKE '%$q%' 
    ORDER BY `name`;";
    $second = qdb($secondary);

    if (isset($second)){
        foreach($second as $id => $row){
            $line = array(
                'id' => $row['id'], 
                'text' => $row['name']
            );
            $output[] = $line;
        }
    }
    
    $output[] = array(
        'id' => 'new',
        'text' => "Add New: $q"
        );
    
    
//	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));
/*    
    $items = array();
    if (strlen($q) > 1){
         $results = (hecidb($qlower));
         foreach($results as $id=> $row){
             $name = $row['part']." &nbsp; ".$row['heci'].' &nbsp; '.$row['Manf'].' '.$row['system'].' '.$row['Descr'];
             $items[] = array('id' => $id, 'text' => $name);
         }
    }
*/    


    
	echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>
