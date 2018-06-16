<?php
    // Formatting tools
    include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
    
    include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

    // Generate a slug for any text aka URL friendlly etc version of text with spaces and special chars
    function slug($text) {
        // replace non letter or digits by _
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');

        // remove duplicate _
        $text = preg_replace('~-+~', '_', $text);
        // lowercase
        $text = strtolower($text);

        return $text;
    }

    function truncateString($string, $limit) {
        if (strlen($string) > $limit) {
            $string = substr($string, 0, $limit) . "...";
        }
        return $string;
    }

    if(! isset($LIMIT) AND ! $LIMIT) { $LIMIT = 3; }
    // Used for unique ID creation
    $COUNTER = 1;

    // Title of the block, the array of data going into the block, and the form elements as optional
    function buildBlock($title = '', $data, $form_elements) {
        global $LIMIT;

        $DETAILS = true;
        // if(count($data) > 1) {
        //     $DETAILS = true;
        // }

        $slug = slug($title);

        // Make this into a section of data to signify each block of data easily
        $blockHTML = '<section class="container-border summary_block" id="'.$slug.'">';

        // Build the title here
        $blockHTML .= '
            <div class="row">
                <div class="col-xs-12">
        ';

        if($DETAILS) {
            $blockHTML .= '
                        <div class="block_title title_link" data-linked="detail_'.$slug.'">'.$title.' <i class="fa fa-angle-right pull-right" aria-hidden="true"></i></div>
            ';
        } else {
            $blockHTML .= '
                        <div class="block_title">'.$title.'</div>
            ';
        }
        $blockHTML .= '
                </div>
            </div>
        ';

        $c = $LIMIT;

        // Run the loop here for all the data parsing
        foreach($data as $key => $r) {

            // reset variables to prevent carryover data
            $info1 = ''; 
            $large_text = ''; 
            $info2 = '';
            $text = '';

            $bypass = false;

            // Bootstrap class for center
            $alignment = 'text-center';

            if($c == 0) {
                break;
            } else if($c != $LIMIT) {
                $blockHTML .= "<hr>";
            }

            if($slug == 'outside_services') {
                $info1 = getCompany($r['companyid']);
                $large_text = '$'.number_format($r['price'] * $r['qty'], 2, '.', '');   
                $info2 = $r['public_notes']; 
            } else if($slug == 'labor') {
                $info1 = getUser($key);
                $year = (! $r['end_datetime'] ? ', Y' : '');
                $large_text = ($r['start_datetime'] ? format_date($r['start_datetime'], 'M j'.$year) : '').($r['end_datetime'] ? ' - '.format_date($r['end_datetime'], 'M j, Y') : '');   
                $info2 = ucwords($r['status']); 
            } else if($slug == 'expense' OR $slug == 'activity') {
                if($r['techid'] OR $r['userid']) {
                    $info1 = getUser($r['techid']?:$r['userid']);
                }
    
                // Set the variables based on defaults and change depending on any needs
                if($r['datetime']) {
                    $large_text = format_date($r['datetime']);
                }
    
                if($r['companyid']) {
                    $info2 = getCompany($r['companyid']);
                }
    
                if($r['notes']) {
                    $text = $r['notes'];
                } else if($r['amount']) {
                    $text = '$'.number_format($r['amount'], 2, '.', '');                
                }
            } else if($slug == 'materials') {

                $avail_qty = 0;

                foreach($r['available'] as $row) {
                    $avail_qty += $row['available'];
                }

                $info1 = "Qty &middot; Installed &middot; Available";
                $large_text = $r['requested'] . " &middot; ".$r['installed']." &middot; ".$avail_qty;
                $text = partDescription($key);
            } else if($slug == 'documentation') {
                $info1 = getUser($r['userid']);
                $info2 = format_date($r['datetime']);
                $large_text = $r['type'];
                $text = $r['notes'];
            } else {
                $text = format_address($r['item_id'], '<br/>', true, '', $r['companyid']);
                $info3 = nl2br(truncateString($r['description'], 300));

                $alignment = 'text-left';
                $bypass = true;
            }

            $blockHTML .= buildSummary($info1, $large_text, $info2, $text, $info3, $alignment);

            $c--;
        }

        // If there is no data for this array then put a no data message on the summary block
        if(empty($data)) {
            $info1 = 'No data';

            $blockHTML .= buildSummary($info1, $large_text, $info2, $text, $info3);
        }

        // Group the array by field specified
        $field = 'datetime';

        if($slug == 'outside_services') {
            $field = 'companyid';
        } else if($slug == 'labor') {
            $field = 'userid';
        } else if($slug == 'materials') {
            $field = 'partid';
        }

        // Bypass allows the data to remain in tack without grouping
        if(! $bypass) {
            $data = groupArray($data, $field, $slug);
        }

        // Make each set of text its own if statement in case one does not exist then skip it
        $blockHTML .= '</section>';

        // If there is more than 1 data element then queue 
        // the details builder

        if($DETAILS) {
            $blockHTML .= '<section class="container-border detail_block" id="detail_'.$slug.'">';
            // Build the return title to the main summary page
            $blockHTML .= '
                <div class="row">
                    <div class="col-xs-12">
                        <div class="block_title"><span class="title_link" data-linked="summary"><i class="fa fa-angle-left pull-left" aria-hidden="true"></i> '.$title.'</span> 
                            <i class="fa fa-expand pull-right expand_toggle" style="font-size: 12px; margin-top: 4px; margin-left: 15px;" aria-hidden="true"></i>
                            '.(! empty($form_elements) ? '<span class="title_link pull-right" data-linked="form_'.$slug.'"><i class="fa '.$form_elements['icon'].'" aria-hidden="true"></i></spa>':'').'
                        </div>
                    </div>
                </div>
            ';
            $blockHTML .= buildDetails($data, $bypass);
            $blockHTML .= '</section>';
        }

        // Build the form section to allow the user to have form inputs
        if(! empty($form_elements)) {
            $blockHTML .= '<section class="container-border form_block" id="form_'.$slug.'">';
            // Build the return title to the main summary page
            $blockHTML .= '
                <div class="row">
                    <div class="col-xs-12">
                        <div class="block_title title_link" data-linked="detail_'.$slug.'"><i class="fa fa-angle-left pull-left" aria-hidden="true"></i> Back</div>
                    </div>
                </div>
            ';
            $blockHTML .= buildForm($form_elements);
            $blockHTML .= '</section>';
        }
        

        return $blockHTML;
    }

    // Group the array by date or name depending on what needs to be done
    function groupArray($data, $field = 'datetime', $slug = '') {
        // datetime grouping
        $header = true;

        $grouped = array();
        foreach($data as $key => $r) {
            if($r['datetime'] OR $r['expense_date']) {
                $identifier = format_date($r['expense_date']?:$r['datetime']);
            } else if($field == 'companyid') {
                $identifier = getCompany($r[$field]);
            } else if($field == 'userid') {
                $identifier = getUser($r[$field]);
            } else if($field == 'partid') {
                $identifier = partDescription($key, false);
            } else {
                $identifier = $r[$field];
            }

            // Generate the 3 columns here
            if($slug == 'outside_services') {
                $r['col_1'] = $r['public_notes'];
                $r['col_2'] = '$'.number_format($r['price']*$r['qty'],2,'.','');
                $r['col_3'] = ($r['os_number']?'<a href="/OS700120"><i class="fa fa-arrow-right"></i></a>':'');

                $r['col_1_size'] = 4;
                $r['col_2_size'] = 5;
                $r['col_3_size'] = 3;
            } else if($slug == 'labor') {
                if(! $r['status']) {
                    continue;
                }

                $r['col_1'] = format_date($r['start_datetime'], 'M d, Y h:ia');
                $r['col_2'] = format_date($r['end_datetime'], 'M d, Y h:ia');
                $r['col_3'] = ucwords($r['status']);

                if($GLOBALS['U']['manager'] == true) {
                    $r['col_2_1'] = ($r['payRate']?:'N/A');
                    $r['col_2_2'] = ($r['totalSeconds']?:'0');
                    $r['col_2_3'] = $r['totalPay'];
                }

                $r['col_1_size'] = 5;
                $r['col_2_size'] = 5;
                $r['col_3_size'] = 2;
            } else if($slug == 'materials') {
                $r['col_1'] = partDescription($key, true, false);
                $r['col_2'] = $r['requested'];
                $r['col_3'] = $r['installed'];

                $r['partid'] = $key;

                $r['col_1_size'] = 8;
                $r['col_2_size'] = 2;
                $r['col_3_size'] = 2;
            } else if($slug == 'documentation') {
                $r['col_1'] = getUser($r['techid']?:$r['userid']);
                $r['col_2'] = $r['notes'];
                $r['col_3'] = ($r['filename']?'<a href="'.$r['filename'].'" target="_new"><i class="fa fa-file-pdf-o"></i></a>':'');

                $r['col_1_size'] = 5;
                $r['col_2_size'] = 5;
                $r['col_3_size'] = 2;
            } else {
                $r['col_1'] = getUser($r['techid']?:$r['userid']);
                $r['col_2'] = ($r['companyid'] ? getCompany($r['companyid']):($r['notes']?:$r['description']));
                $r['col_3'] = ($r['amount']?'$'.number_format($r['amount'],2,'.',''):'');

                $r['col_1_size'] = 3;
                $r['col_2_size'] = 6;
                $r['col_3_size'] = 3;
            }

            if($header) {
                $h = array();

                $h['header'] = true;

                if($slug == 'outside_services') {
                    $h['col_1'] = 'Notes';
                    $h['col_2'] = 'Amount';
                    $h['col_3'] = '';
                } else if($slug == 'labor') {
                    $h['col_1'] = 'Start';
                    $h['col_2'] = 'End';
                    $h['col_3'] = 'Status';

                    if($GLOBALS['U']['manager'] == true) {
                        $h['col_2_1'] = 'Rate';
                        $h['col_2_2'] = 'Total Hours';
                        $h['col_2_3'] = 'Total Pay';
                    }
                } else if($slug == 'materials') {
                    $h['col_1'] = '';
                    $h['col_2'] = 'Req.';
                    $h['col_3'] = 'Inst.';
                } else if($slug == 'documentation') {
                    $h['col_1'] = 'User';
                    $h['col_2'] = 'Notes';
                    $h['col_3'] = 'File';
                } else {
                    $h['col_1'] = 'User';
                    $h['col_2'] = ($r['companyid'] ? 'Company':'Notes');
                    $h['col_3'] = 'Amount';
                }

                $h['col_1_size'] = $r['col_1_size'];
                $h['col_2_size'] = $r['col_2_size'];
                $h['col_3_size'] = $r['col_3_size'];

                $grouped[$identifier][] = $h;

                $header = false;
            }

            $grouped[$identifier][] = $r;
        }

        return  $grouped;
    }

    // Header builds the summary
    function buildSummary($info1 = '', $large_text = '', $info2 = '', $text = '', $info3 = '', $alignment = 'text-center') {

        if(! empty($info1)) {
            $rowHTML .= '
                <div class="row mt-10 mb-10">
                    <div class="col-xs-12">
                        <span class="text-center info" style="display: block;">'.$info1.'</span>
                    </div>
                </div>
            ';
        }

        if(! empty($large_text)) {
            $rowHTML .= '
                <div class="row mt-10 mb-10">
                    <div class="col-xs-12">
                        <h4 class="text-center" style="display: block;">'.$large_text.'</h4>
                    </div>
                </div>
            ';
        }

        if(! empty($info2)) {
            $rowHTML .= '
                <div class="row mt-10 mb-10">
                    <div class="col-xs-12">
                        <span class="text-center info" style="display: block;">'.$info2.'</span>
                    </div>
                </div>
            ';
        }

        if(! empty($text)) {
            $rowHTML .= '
                <div class="row mt-10 mb-10">
                    <div class="col-xs-12">
                        <span class="text-center" style="display: block;">'.$text.'</span>
                    </div>
                </div>
            ';
        }

        if(! empty($info3)) {
            $rowHTML .= '
                <div class="row mt-10 mb-10">
                    <div class="col-xs-12">
                        <span class="'.$alignment.' info" style="display: block; padding-left: 5px; padding-right: 5px;">'.$info3.'</span>
                    </div>
                </div>
            ';
        }

		return $rowHTML;
    }

    // Block builds everything from functionality to full description
    function buildDetails($data, $bypass) {
        global $COUNTER;

        // If bypass then do something else different
        if($bypass) {
            $rowHTML = '<div class="col-sm-12">
                            <BR>
                            <p>
                                '.nl2br(reset($data)['description']).'
                            </p>
                            <BR>
                        </div>
            ';

            return $rowHTML;
        }

        $rowHTML = '<div id="accordion">';

        foreach($data as $title => $rows) {
            $striped = '';

            $partid = reset($rows)['partid'];

            if(! $partid) {
                $partid = $rows[1]['partid'];
            }

			$H = hecidb($partid,'id');
			$P = $H[$partid];
			$def_type = 'Part';

			$parts = explode(' ',$H[$partid]['part']);
			$part_name = $parts[0];

            $rowHTML .= '
                    <div class="card">
                        <div class="card-header" id="heading_'.$COUNTER.'">
                            <h5 class="mb-0">
                                <button class="btn btn-link" data-toggle="collapse" data-target="#collapse_'.$COUNTER.'" aria-expanded="true" aria-controls="collapse_'.$COUNTER.'">
                                    <div class="product-img pull-left"><img class="img" src="/img/parts/'.$part_name.'.jpg" alt="pic" data-part="'.$part_name.'"></div>'.$title.' <i class="fa fa-caret-down" aria-hidden="true"></i>
                                </button>
                            </h5>
                        </div>

                        <div id="collapse_'.$COUNTER.'" class="collapse card-content" aria-labelledby="heading_'.$COUNTER.'" data-parent="#accordion">
                                <div class="card-body">
            ';
            foreach($rows as $r) {
                $rowHTML .= '       
                                    <div class="row '.$striped.'" style="margin: 0; '.($r['header'] ? 'background-color: #f2f5f9; font-weight: bold;' : '').'">
                                        <div class="col-xs-'.$r['col_1_size'].' col_pad_min text-center">
                                            '.$r['col_1'].'
                                        </div>
                                        <div class="col-xs-'.$r['col_2_size'].' col_pad_remove text-center">
                                            '.$r['col_2'].'
                                        </div>
                                        <div class="col-xs-'.$r['col_3_size'].' col_pad_min text-center">
                                            '.$r['col_3'].'
                                        </div>
                                    </div>
                ';

                // Generate a second table below the first
                if($r['col_2_1']) {
                    $rowHTML .= '       
                                    <div class="row '.$striped.'" style="margin: 0; '.($r['header'] ? 'background-color: #f2f5f9; font-weight: bold;' : '').'">
                                        <div class="col-xs-'.$r['col_1_size'].' col_pad_min text-center">
                                            '.$r['col_2_1'].'
                                        </div>
                                        <div class="col-xs-'.$r['col_2_size'].' col_pad_remove text-center">
                                            '.$r['col_2_2'].'
                                        </div>
                                        <div class="col-xs-'.$r['col_3_size'].' col_pad_min text-center">
                                            '.$r['col_2_3'].'
                                        </div>
                                    </div>
                    ';
                }

                if(! $striped) {
                    $striped = 'row_striped';
                } else {
                    $striped = '';
                }
            }
            $rowHTML .= '
                                </div>
                            </div>
                        </div>
            ';
            $COUNTER++;
        }

        $rowHTML .= '</div>';

		return $rowHTML;
    }

    // Generate a form array aka array(array('type','name', 'class', 'placeholder')......)
    function buildForm($form_elements){
        global $T;
        $rowHTML = '
            <div class="col-xs-12 mt-10 mb-10">
                <form class="form-inline" method="post" action="'.$form_elements['action'].'" enctype="multipart/form-data">
                    <input type="hidden" name="taskid" value="'.$GLOBALS['ORDER_DETAILS']['id'].'">
                    <input type="hidden" name="type" value="'.$T['type'].'">
                    <input type="hidden" name="order_number" value="'.$GLOBALS['ORDER_DETAILS'][$T['order']].'">
                    <input type="hidden" name="responsive" value="true">
        ';
        
        foreach($form_elements['fields'] as $r) {
            if($r['type'] == 'hidden') {
                $rowHTML .= '
                    <input class="form-control '.$r['class'].' input-sm mb-10" type="'.$r['type'].'" name="'.$r['name'].'" placeholder="'.$r['placeholder'].'" value="'.$r['value'].'" '.$r['property'].'>
                ';
            }
            if($r['type'] == 'text') {

                if($r['left_icon'] OR $r['right_icon']) {
                    $rowHTML .= '
                        <div class="input-group mb-10">
                    ';
                }

                if($r['left_icon']) {
                    // I'll probably never use this but this allows the code to detect
                    // if the grouped input addon will be an fa icon or allow the user to input a class for dynamic purposes if class exists
                    // Otherwise just slaps the user input into the input group
                    if(strpos($r['left_icon'], 'class') !== false) {
                        $rowHTML .= '
                            <span class="input-group-btn">
                                <button class="btn btn-default input-sm '.$r['left_icon'].'" disabled=""><strong>-</strong></button>
                            </span>
                        ';
                    } else if(strpos($r['left_icon'], 'fa') !== false) {
                        $rowHTML .= '
                            <span class="input-group-addon">
                                <span class="fa '.$r['left_icon'].'"></span>
                            </span>
                        ';
                    } else {
                        $rowHTML .= '
                            <span class="input-group-btn">
                                <button class="btn btn-default input-sm" disabled=""><strong>'.$r['left_icon'].'</strong></button>
                            </span>
                        ';
                    }
                }

                $rowHTML .= '
                    <input class="form-control '.$r['class'].' input-sm mb-10" type="'.$r['type'].'" name="'.$r['name'].'" placeholder="'.$r['placeholder'].'" value="'.$r['value'].'" '.$r['property'].'>
                ';

                if($r['right_icon']) {
                    // I'll probably never use this but this allows the code to detect
                    // if the grouped input addon will be an fa icon or allow the user to input a class for dynamic purposes if class exists
                    // Otherwise just slaps the user input into the input group
                    if(strpos($r['right_icon'], 'class') !== false) {
                        $rowHTML .= '
                            <span class="input-group-btn">
                                <button class="btn btn-default input-sm '.$r['right_icon'].'" disabled=""><strong>-</strong></button>
                            </span>
                        ';
                    } else if(strpos($r['right_icon'], 'fa') !== false) {
                        $rowHTML .= '
                            <span class="input-group-addon">
                                <span class="fa '.$r['right_icon'].'"></span>
                            </span>
                        ';
                    } else {
                        $rowHTML .= '
                            <span class="input-group-btn">
                                <button class="btn btn-default input-sm" disabled=""><strong>'.$r['right_icon'].'</strong></button>
                            </span>
                        ';
                    }
                }

                if($r['left_icon'] OR $r['right_icon']) {
                    $rowHTML .= '
                        </div>
                    ';
                }
            }

            if($r['type'] == 'datepicker') {
                $rowHTML .= '
                    <div class="mb-10 input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
                        <input type="text" name="'.$r['name'].'" class="form-control '.$r['class'].' input-sm" value="'.$r['value'].'" '.$r['property'].'>
                        <span class="input-group-addon">
                            <span class="fa fa-calendar"></span>
                        </span>
                    </div>
                ';
            }

            if($r['type'] == 'select2') {
                $rowHTML .= '
                    <div class="mb-10">
                        <select class="form-control '.($r['class'] ? : 'select2').' input-xs mb-10" name="'.$r['name'].'" '.($r['scope'] ? 'data-scope="'.$r['scope'].'"' : '').' '.$r['property'].'>
                ';
                foreach($r['values'] as $option) {
                    $rowHTML .= '<option value="'.$option['id'].'">'.$option['text'].'</option>';
                }
                $rowHTML .= '
                            </select>
                        </div>
                    ';
            }

            if($r['type'] == 'upload') {
                $rowHTML .= '
                    <div class="file_container">
                        <span class="file_name" style="margin-right: 5px;"><a href="#"></a></span>

                        <input type="file" class="upload" multiple="multiple" name="'.$r['name'].'" accept="'.$r['acceptable'].'" value="">
                        <a href="#" class="upload_link btn btn-default btn-sm">
                            <span style="float: left;"><i class="fa '.$r['icon'].'" aria-hidden="true"></i></span><span class="hidden-xs hidden-sm" style="margin-left: 15px;">...</span>
                        </a>
                    </div>
                ';
            }
        }

        $rowHTML .= '
            <div class="row" style="min-height: 40px;">
                <div class="col-xs-12">
                    <button class="pull-right btn btn-sm btn-primary" type="submit" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Save Entry">
                        <i class="fa fa-save"></i>
                    </button>
                </div>
            </div>
        ';

        $rowHTML .= '
                </form>
            </div>
        ';

		return $rowHTML;
    }