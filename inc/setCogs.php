<?php
	function setCogs($inventoryid=0,$taskid=0,$task_label='',$cogs_avg=0,$cogs_actual=0,$invoice=0,$invoice_item_id=0) {
		$query = "SELECT id, cogs_avg FROM sales_cogs WHERE inventoryid ";
		if ($inventoryid) { $query .= "= '".res($inventoryid)."' "; } else { $query .= "IS NULL "; }
		$query .= "AND taskid ";
		if ($taskid) { $query .= "= '".res($taskid)."' "; } else { $query .= "IS NULL "; }
		$query .= "AND task_label ";
		if ($task_label) { $query .= "= '".res($task_label)."' "; } else { $query .= "IS NULL "; }
		if ($invoice AND $invoice_item_id) {
			$query .= "AND invoice_no = '".res($invoice)."' AND invoice_item_id = '".res($invoice_item_id)."' ";
		}
		$query .= "; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			if ($r['cogs_avg']==$cogs_avg) { return ($r['id']); }

			$query2 = "UPDATE sales_cogs SET cogs_avg = '".res($cogs_avg)."', cogs_actual = ".fres($cogs_actual)." ";
			$query2 .= "AND invoice_no = ".fres($invoice)." AND invoice_item_id = ".fres($invoice_item_id)." ";
			$query2 .= "WHERE id = '".$r['id']."'; ";
			$result2 = qedb($query2);
			return ($r['id']);
		}

		$cogsid = addCOGS($inventoryid,$taskid,$task_label,$cogs_avg,$cogs_actual,$invoice,$invoice_item_id);
		return ($cogsid);
	}

	function addCOGS($inventoryid=0,$taskid=0,$task_label='',$cogs_avg=0,$cogs_actual=0,$invoice=0,$invoice_item_id=0) {
		$query = "INSERT INTO sales_cogs (inventoryid, taskid, task_label, cogs_avg, cogs_actual, invoice_no, invoice_item_id) ";
		$query .= "VALUES (".fres($inventoryid).",".fres($taskid).",".fres($task_label).",";
		$query .= fres($cogs_avg).",".fres($cogs_actual).",".fres($invoice).",".fres($invoice_item_id)."); ";
		$result = qedb($query);
		if ($GLOBALS['DEBUG']) { $cogsid = 999999; } else { $cogsid = qid(); }
		return ($cogsid);
	}
?>
