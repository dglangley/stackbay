<?php
	if (! isset($startDate)) { $startDate = ''; }
	if (! isset($endDate)) { $endDate = ''; }
?>
				<div class="input-group">
					<div style="display:inline-block">
							<div class="input-group date datetime-picker datepicker-date" data-format="MM/DD/YYYY">
					            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
					            <span class="input-group-addon">
					                <span class="fa fa-calendar"></span>
					            </span>
					        </div>
					</div>
					<div style="display:inline-block">
							<div class="input-group date datetime-picker datepicker-date" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
					            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
					            <span class="input-group-addon">
					                <span class="fa fa-calendar"></span>
					            </span>
						    </div>
					</div>
					<div class="valign-top" style="display:inline-block">
						<button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-filter" aria-hidden="true"></i></button>
						<div class="btn-group" id="dateRanges">
							<div id="btn-range-options">
								<button class="btn btn-default btn-sm">&gt;</button>
								<div class="animated fadeIn hidden" id="date-ranges" style = 'width:217px;'>
							        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
					    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>
								</div>
							</div>
						</div>
					</div>
				</div>
