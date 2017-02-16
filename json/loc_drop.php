<?php 
        header('Content-Type: application/json');
        
        $rootdir = $_SERVER['ROOT_DIR'];
        include_once $rootdir.'/inc/locations.php';
        include_once $rootdir.'/inc/form_handle.php';
        
        $type = grab('type');
        $selected = grab('selected');
        $limit = grab('limit');
        // $warehouse = grab('warehouse');
        
        echo json_encode(loc_dropdowns($type, $selected,$limit));
    
?>