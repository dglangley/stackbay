<?php
    function mapJob($BDB_jid) {
        $service_item_id = 0;

        $query = "SELECT service_item_id FROM maps_job WHERE BDB_jid = ".res($BDB_jid).";";
        $result = qdb($query) OR die(qe() . '<BR>' . $query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);

            $service_item_id = $r['service_item_id'];
        }

        return $service_item_id;
    }
?>
