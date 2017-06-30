<?php
    include_once 'getContact.php';

    function getUser($search,$input_field='id',$output_field='name') {
        $search = strtolower($search);

        if ($input_field=='id') { $input_field = 'userid'; }
        if ($output_field=='id') { $output_field = 'userid'; }

        return (getContact($search,$input_field,$output_field));
    }
?>