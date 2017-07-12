<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/invoice.php';

$debug = 1;

	$sel = 'select * from inventory_invoice i
	where i.date >= "2017-05-01" and amount > 0;';
	$res = qdb($sel,"PIPE") or die(qe("PIPE")." | $sel");
	echo("\t |\tNew Inv\t |\tOld Inv\t\t|\tRep No\t\t|<br>");
	foreach($res as $count => $r){
		$return = create_invoice($r['ref_no'],$r['date']." 00:00:00", "Repair");
		echo("$count\t |\t".$return."\t\t |\t".$r['id']."\t\t|\t".$r['ref_no']."\t\t|<br>");
	}
?>
