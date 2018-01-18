<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function format_task($taskid, $task_label) {
		$T = order_type($task_label);

		$task = '';

		$query = "SELECT * FROM ".$T['orders']." o, ".$T['items']." i ";
		$query .= "WHERE o.".$T['order']." = i.".$T['order']." AND i.id = '".res($taskid)."'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		if(mysqli_num_rows($result)==0) { return ($task); }

		$r = mysqli_fetch_assoc($result);

		$class = '';
		if (isset($r['task_name'])) {
			$class = $r['task_name'];
		}
		if (! $class AND isset($r['classid'])) {
			$class = getClass($r['classid']);
		}
		if ($class) { $class .= ' '; }
		$ln = $r['line_number'];
		$order_number = $r[$T['order']];

		return $class.$order_number.($ln ? '-'.$ln : '');//.'-'.$ln;
	}
?>
