<?php
	// include_once $_SERVER["ROOT_DIR"].'/inc/calcTime.php';

	class Payroll extends DateTime {

		// Set the start date of the payroll calculations
		// AKA the start date of the pay period will ONLY work here
		// The Year / The week / The Start Date (Unless its Not Monday OR Sunday leave it @ 1)
		const REF_START = '2013-W42-1';

		// These variables are used to set the workstart date and work end date
		private $WORKDAY_START = 0;
		private $WORKDAY_END = 23;

		private $LABOR_COST = 1.13;//change this to include certain amount of payroll taxes or other associated labor costs

		// All the variables that will be set by the admin
		// Private means only this function can access it and a get function is required for external use
		private $hours_per_period = 336;
		private $hour_of_start = 19; // How many hours do you want to add to 00:00:00 ?
		private $day_of_start = 7;

		/* ---------Initialize and set set functions ------------------------*/

		function setHours($hours_per_period) {
			$this->hours_per_period = $hours_per_period;
		}

		function setStartDay($day_of_start) {
			$this->day_of_start = $day_of_start;
		}

		function setHourStart($hour_of_start) {
			$this->hour_of_start = $hour_of_start;
		}

		function setDayStart($day_of_start) {
			$this->day_of_start = $day_of_start;
		}

		function setLaborCost($labor_cost) {
			$this->LABOR_COST = $labor_cost;
		}

		function setWorkStart($work_start) {
			$work_end = 0; 
			$this->WORKDAY_START = $work_start;

			// Determine the workend day based on the work start date
			if ($work_start==0) { 
				$work_end = 23; 
			} else { 
				$work_end = $work_start-1; 
			}

			$this->WORKDAY_END = $work_end;
		}

		/* ---------Initialize and set get functions ------------------------*/

		// function getHours() {
		// 	return $this->hours_per_period;
		// }

		/* --------- Begin the craziness and functions we need ------------------------*/

		// Adjust the weeks depending on if the pay period lands on weird dates
		protected function isOdd(DateTime $pay_period) {
			$ref = new DateTime(self::REF_START);
			return floor($pay_period->diff($ref)->days / 7) % 2 == 0;
		}

		public function getCurrentPeriodStart() {
			$pay_period = new DateTime($this->format('o-\WW-' . $this->day_of_start));
			if (!$this->isOdd($pay_period)) {
				$pay_period->modify('-1 week');
			}

			// This offsets the dates if the user decides that the start day isn't a Monday but a Sunday Instead or maybe a Saturday?
			if($this->day_of_start > 1) {
				$pay_period->modify('-1 week');
			}

			$pay_period->modify('+'.$this->hour_of_start.' hours');

			return $pay_period;
		}

		public function getCurrentPeriodEnd() {
			$pay_period = new DateTime($this->format('o-\WW-'  . $this->day_of_start));
			if ($this->isOdd($pay_period)) {
				$pay_period->modify('+1 week');
			}

			$pay_period->modify('+'.$this->hour_of_start.' hours');
			// -1 second to offset the pay period
			$pay_period->modify('-1 seconds');

			return $pay_period;
		}

		// Changing the past variable makes it so you can iterate through previous pay dates infinitely
		public function getPreviousPeriodStart($past = 1) {
			$pay_period = $this->getCurrentPeriodStart();
			while($past > 0) {
				$pay_period->modify('-'.$this->hours_per_period.' hours');
				$past--;
			}

			return $pay_period;
		}

		public function getPreviousPeriodEnd($past = 1) {
			$pay_period = $this->getCurrentPeriodEnd();
			while($past > 0) {
				$pay_period->modify('-'.$this->hours_per_period.' hours');
				$past--;
			}

			return $pay_period;
		}

		function getUserRate($userid) {
			$rate = 0;

			$query = "SELECT hourly_rate FROM users WHERE id = ".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if(mysqli_num_rows($result)) {
				$r = mysqli_fetch_assoc($result);
				$rate = $r['hourly_rate'];
			}

			return $rate;
		}

		function getTimesheets($userid, $user_admin, $start = '', $end = '', $taskid=0, $task_label='') {
			$timesheets = array();

			if($user_admin) {
				$query = "SELECT * FROM timesheets";
				if($start && $end) {
					$query .= " WHERE clockin >= " . fres($start) . " AND clockout <= " . fres($end);
				}
				if ($taskid AND $task_label) {
					$query .= " AND taskid = '".res($taskid)."' AND task_label = '".res($task_label)."' ";
				}
				$query .= " ORDER by clockin DESC;";

				$result = qdb($query) OR die(qe() . ' ' . $query);

				while($r = mysqli_fetch_assoc($result)) {
					$timesheets[] = $r;
				}
			} else {
				$query = "SELECT * FROM timesheets WHERE userid = ".res($userid);
				if($start && $end) {
					$query .= " AND clockin >= " . fres($start) . " AND clockout <= " . fres($end);
				}
				if ($taskid AND $task_label) {
					$query .= " AND taskid = '".res($taskid)."' AND task_label = '".res($task_label)."' ";
				}
				$query .= " ORDER by clockin DESC;";

				$result = qdb($query) OR die(qe() . ' ' . $query);

				while($r = mysqli_fetch_assoc($result)) {
					$timesheets[] = $r;
				}
			}

			return $timesheets;
		}
	}
