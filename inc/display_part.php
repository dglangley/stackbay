<?php
    $rootdir = $_SERVER['ROOT_DIR'];
    include_once $root_dir."/inc/dictionary.php";

    function display_part($parts, $partid = false, $desc = true){
        if($partid){
            $parts = current(hecidb($parts,"id"));
        }

        //If hecidb fails to pull out the needed material then output nothing
        if($parts['part']) {
            $name = "<span class = 'descr-label'>".$parts['part']." &nbsp; ".$parts['heci']."</span>";
            if($desc){
                $name .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($parts['manf'])." &nbsp; ".dictionary($parts['system']).'</span> <span class="description-label">'.dictionary($parts['description']).'</span></div>';
            }
        }
        return $name;
    }
?>