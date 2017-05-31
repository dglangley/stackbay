<?php 
        header('Content-Type: application/json');
        
        $rootdir = $_SERVER['ROOT_DIR'];
        include_once $rootdir.'/inc/check_received.php';
        include_once $rootdir.'/inc/form_handle.php';
        
        $type = grab("type");
        $line = grab("line");
        
        echo json_encode(num_received($type,$line));
    
?>