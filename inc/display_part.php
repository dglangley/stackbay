<?php
    $rootdir = $_SERVER['ROOT_DIR'];
    include_once $root_dir."/inc/dictionary.php";

    function display_part($parts){
        $name = "<span class = 'descr-label'>".$parts['part']." &nbsp; ".$parts['heci']."</span>";
        $name .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($parts['manf'])." &nbsp; ".dictionary($parts['system']).'</span> <span class="description-label">'.dictionary($parts['description']).'</span></div>';
        return $name;
    }
?>