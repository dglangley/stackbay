<?php 
        header('Content-Type: application/json');
        
        $rootdir = $_SERVER['ROOT_DIR'];
        include_once $rootdir.'/inc/dropPop.php';
        include_once $rootdir.'/inc/form_handle.php';
        
        $field = grab('field');
        $selected = grab('selected');
        $limit = grab('limit');
        $size = grab('size');
        $label = grab('label');
        $id = grab('id');
        
        echo json_encode(dropdown($field, $selected, $limit, $size, $label,$id));
    
?>