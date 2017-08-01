<?php
//=============================================================================
//================================= INV ADD OUT ===============================
//=============================================================================

	header('Content-Type: application/json');
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
		
		
	//Collect the information from the inital 'add record' submission
	$part = 'erb3';
	$serial = 'NEW SERIAL HOO HAH';
	$qty = 36;
	$output_number = ($lot) ? 1 : $qty; 
	
	//Initialize the output
	$lines = '';
	
	for ($i = 0; $i < $output_number; $i++){
		$lines .= "<tr class = 'output_row'>
	            <td class='part'>
	            	<div style='max-width:inherit;'>
						$part
					</div>
				</td>
				<td class ='serial'>
		            <input class='form-control input-sm' type='text' name = 'Newitem' value = '$serial' placeholder='Serial'>
				</td>
	            <td>
				    <input type='text' class='form-control' value='$qty'>
			    </td>
	            <td>
                        <div class='ui-select' style='width:100%;'>
                            <select>
                                <option selected=''>Warehouse 12</option>
                                <option>Warehouse 12</option>
                                <option>Warehouse 12</option>
                            </select>
                        </div>
                </td>
	            <td>
                    <div class='ui-select' style='width:100%;'>
                        <select>
                            <option selected=''>Warehouse 12</option>
                            <option>Warehouse 12</option>
                            <option>Warehouse 12</option>
                        </select>
                    </div>
                </td>
	            <td>
		           	<button class='btn btn-success'>
                		N
                	</button>     
                	<button class='btn btn-danger'>
                		U
                	</button>
                	<button class='btn btn-primary'>
                		R
                	</button>
                </td>
                <td>
					DELETE ROW
                </td>
		    </tr>";
	}
	
	//Return the lines created
	return json_encode($lines);
	exit;
?>
