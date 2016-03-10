<?php
	require_once dirname(__FILE__).'/PHPExcel/Classes/PHPExcel/IOFactory.php';

	function PHPExcel($filename) {
		$bid_col = 3;
		$qty_col = 22;
		$heci_col = 7;
		$part_col = 11;
		$keepers = array();
		$results = array();

		//  Read Excel workbook
		try {
			// identify the type to help the parser
		    $inputFileType = PHPExcel_IOFactory::identify($filename);
		    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
			// load the file
		    $objPHPExcel = $objReader->load($filename);

			foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
				foreach ($worksheet->getRowIterator() as $row) {
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
					$row_num = $row->getRowIndex();
					$bid_num = '';
					$qty = 0;
					$heci = '';
					$part = '';
					foreach ($cellIterator as $cell) {
						if (!is_null($cell)) {
							$columns = explode(chr(9),$cell->getCalculatedValue());
							$keeper_row = array_search($row_num,$keepers);
							if (isset($columns[$bid_col]) AND ($keeper_row!==false OR (trim($columns[$bid_col])<>'' AND preg_match('/^[0-9]+$/',trim($columns[$bid_col]))!==false))) {
								if ($keeper_row===false) {
									$bid_num = $columns[$bid_col];
									$qty = $columns[$qty_col];
								}
								if ($cell->getCoordinate()=='L'.$row_num) {
									$heci = $columns[$heci_col];
									$part = $columns[$part_col];
								}
								if ($keeper_row===false) { $keepers[] = $row_num; }
							} else {
								continue;
							}
//							echo '        Cell - ' , $cell->getCoordinate() , ' - ' , $cell->getCalculatedValue() , '<BR>';
						}
					}
					if (! $heci) { continue; }

					if (! isset($results[$bid_num])) { $results[$bid_num][] = array('heci','part','qty'); }
					$results[$bid_num][] = array($heci,$part,$qty);
//					echo 'Row number - ' . $row_num . ':'.$bid_num . ' = '.$qty.' '.$heci.' '.$part.'<BR>';
				}
			}
		} catch(Exception $e) {
		    die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
		}

		return ($results);
	}
?>
