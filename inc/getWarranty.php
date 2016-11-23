<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	
	function getWarranty($id = '%', $field = 'all'){
    
        $id = prep($id);
        $select = "Select * FROM warranties Where id LIKE $id;";
        $results = qdb($select);
        
        
        
        if ($field == 'all'){
            return $results;
        }
        elseif($field == 'warranty' || $field == 'name'){
            if ($id != '%'){
                $name = '';
                foreach ($results as $r){
                    $name = $r['warranty'];
                }
            }
            else{
                foreach ($results as $r){
                    $name = array();
                    $name[] = $r['warranty'];
                }
            }
            return $name;
        }
        elseif('idkyet'){
            return 'something';
        }
        
	}
