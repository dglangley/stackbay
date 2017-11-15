<?php
	function getSpreadsheetRows($filename) {
		$lines = array();

		if (strstr($filename,'.xlsx')) {
			include_once $_SERVER["ROOT_DIR"].'/inc/simplexlsx.class.php';//awesome class used for xlsx-only

			$xlsx = new SimpleXLSX($filename);
			$lines = $xlsx->rows();

		} else if (strstr($filename,'.xls')) {
			include_once $_SERVER["ROOT_DIR"].'/inc/php-excel-reader/excel_reader2.php';//specifically for parsing xls files

			$xls = new Spreadsheet_Excel_Reader($filename,false);
			$sheets = $xls->sheets;
			foreach ($sheets as $sheet) {
				$lines = $sheet["cells"];
				break;//end after first worksheet found
			}

		} else if (strstr($filename,'.csv')) {
			//$handle = fopen($file['tmp_name'],"r");
			$handle = fopen($filename,"r");

			while (($data = fgetcsv($handle)) !== false) {
				$lines[] = $data;
			}
		}

		return ($lines);
	}
?>
