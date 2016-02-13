<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2016 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function initialize_realtime_step_and_window() {
	if (!isset($_SESSION['sess_realtime_dsstep'])) {
		$_SESSION['sess_realtime_dsstep'] = read_config_option('realtime_interval');
	}
	if (!isset($_SESSION['sess_realtime_window'])) {
		$_SESSION['sess_realtime_window'] = read_config_option('realtime_gwindow');
	}
}

function set_default_graph_action() {
	if (!isset_request_var('action')) {
		/* setup the default action */
		if (!isset($_SESSION['sess_graph_view_action'])) {
			switch(read_graph_config_option('default_view_mode')) {
			case '1':
				set_request_var('action', 'tree');
				break;
			case '2':
				set_request_var('action', 'list');
				break;
			case '3':
				set_request_var('action', 'preview');
				break;
			default:
				break;
			}
		}elseif (in_array($_SESSION['sess_graph_view_action'], array('tree', 'list', 'preview'))) {
			set_request_var('action', $_SESSION['sess_graph_view_action']);
		} else {
			set_request_var('action', 'tree');
		}
	}

	if (get_nfilter_request_var('action') != 'get_node') {
		$_SESSION['sess_graph_view_action'] = get_nfilter_request_var('action');
	}
}

function html_graph_validate_preview_request_vars() {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'graphs' => array(
			'filter' => FILTER_VALIDATE_INT, 
			'pageset' => true,
			'default' => read_graph_config_option('preview_graphs_per_page')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT, 
			'default' => '1'
			),
		'graph_template_id' => array(
			'filter' => FILTER_VALIDATE_INT, 
			'pageset' => true,
			'default' => '0'
			),
		'columns' => array(
			'filter' => FILTER_VALIDATE_INT, 
			'pageset' => true,
			'default' => read_graph_config_option('num_columns')
			),
		'host_id' => array(
			'filter' => FILTER_VALIDATE_INT, 
			'pageset' => true,
			'default' => '-1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK, 
			'pageset' => true,
			'default' => '', 
			'options' => array('options' => 'sanitize_search_string')
			),
		'thumbnails' => array(
			'filter' => FILTER_VALIDATE_REGEXP, 
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => read_graph_config_option('thumbnail_section_preview') == 'on' ? 'true':'false'
			),
		'graph_list' => array(
			'filter' => FILTER_VALIDATE_REGEXP, 
			'options' => array('options' => array('regexp' => '/^([\,0-9]+)$/')),
			'pageset' => true,
			'default' => read_graph_config_option('thumbnail_section_preview') == 'on' ? 'true':'false'
			),
		'graph_add' => array(
			'filter' => FILTER_VALIDATE_REGEXP, 
			'options' => array('options' => array('regexp' => '/^([\,0-9]+)$/')),
			'pageset' => true,
			'default' => read_graph_config_option('thumbnail_section_preview') == 'on' ? 'true':'false'
			),
		'graph_remove' => array(
			'filter' => FILTER_VALIDATE_REGEXP, 
			'options' => array('options' => array('regexp' => '/^([\,0-9]+)$/')),
			'pageset' => true,
			'default' => read_graph_config_option('thumbnail_section_preview') == 'on' ? 'true':'false'
			),
		'style' => array(
			'filter' => FILTER_DEFAULT,
			'default' => ''
			)
	);

	validate_store_request_vars($filters, 'sess_grview');
	/* ================= input validation ================= */
}

function html_graph_preview_filter($page, $action, $devices_where = '', $templates_where = '') {
	global $graphs_per_page, $realtime_window, $realtime_refresh, $graph_timeshifts, $graph_timespans, $config;

	?>
	<tr class='even noprint'>
		<td class='noprint'>
		<form id='form_graph_view' method='post' action='<?php print $page;?>?action=preview'>
			<table id='device' class='filterTable'>
				<tr>
					<?php print html_host_filter(get_request_var('host_id'), 'applyGraphFilter');?>
					<td>
						Template
					</td>
					<td>
						<select id='graph_template_id' onChange='applyGraphFilter()'>
							<option value='0'<?php if (get_request_var('graph_template_id') == '0') {?> selected<?php }?>>Any</option>
							<?php

							$graph_templates = get_allowed_graph_templates($templates_where);

							if (sizeof($graph_templates) > 0) {
								foreach ($graph_templates as $template) {
									print "<option value='" . $template['id'] . "'"; if (get_request_var('graph_template_id') == $template['id']) { print ' selected'; } print '>' . htmlspecialchars($template['name']) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='button' id='refresh' value='Go' title='Set/Refresh Filters' onClick='applyGraphFilter()'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters' onClick='clearGraphFilter()'>
					</td>
					<?php if (is_view_allowed('graph_settings')) {?>
					<td>
						<input type='button' id='save' value='Save' title='Save the current Graphs, Columns, Thumbnail, Preset, and Timeshift preferences to your profile' onClick='saveGraphFilter("preview")'>
					</td>
					<td id='text'></td>
					<?php }?>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print htmlspecialchars(get_request_var('filter'));?>' onChange='applyGraphFilter()'>
					</td>
					<td>
						Graphs
					</td>
					<td>
						<select id='graphs' onChange='applyGraphFilter()'>
							<?php
							if (sizeof($graphs_per_page)) {
							foreach ($graphs_per_page as $key => $value) {
								print "<option value='" . $key . "'"; if (get_request_var('graphs') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td>
						Columns
					</td>
					<td>
						<select id='columns' onChange='applyGraphFilter()'>
							<option value='1'<?php if (get_request_var('columns') == '1') {?> selected<?php }?>>1 Column</option>
							<option value='2'<?php if (get_request_var('columns') == '2') {?> selected<?php }?>>2 Columns</option>
							<option value='3'<?php if (get_request_var('columns') == '3') {?> selected<?php }?>>3 Columns</option>
							<option value='4'<?php if (get_request_var('columns') == '4') {?> selected<?php }?>>4 Columns</option>
							<option value='5'<?php if (get_request_var('columns') == '5') {?> selected<?php }?>>5 Columns</option>
							<option value='6'<?php if (get_request_var('columns') == '6') {?> selected<?php }?>>6 Columns</option>
						</select>
					</td>
					<td>
						<label for='thumbnails'>Thumbnails</label>
					</td>
					<td>
						<input id='thumbnails' type='checkbox' onClick='applyGraphFilter()' <?php print ((get_request_var('thumbnails') == 'true') ? 'checked':'');?>>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<script type='text/javascript'>
	var graph_start=<?php print get_current_graph_start();?>;
	var graph_end=<?php print get_current_graph_end();?>;
	var timeOffset=<?php print date('Z');?>;
	var pageAction ='<?php print $action;?>';
	var graphPage  = '<?php print $page;?>';
	var date1Open = false;
	var date2Open = false;

	$(function() {
		$('#startDate').click(function() {
			if (date1Open) {
				date1Open = false;
				$('#date1').datetimepicker('hide');
			}else{
				date1Open = true;
				$('#date1').datetimepicker('show');
			}
		});

		$('#endDate').click(function() {
			if (date2Open) {
				date2Open = false;
				$('#date2').datetimepicker('hide');
			}else{
				date2Open = true;
				$('#date2').datetimepicker('show');
			}
		});

		$('#date1').datetimepicker({
			minuteGrid: 10,
			stepMinute: 1,
			showAnim: 'slideDown',
			numberOfMonths: 1,
			timeFormat: 'HH:mm',
			dateFormat: 'yy-mm-dd',
			showButtonPanel: false
		});

		$('#date2').datetimepicker({
			minuteGrid: 10,
			stepMinute: 1,
			showAnim: 'slideDown',
			numberOfMonths: 1,
			timeFormat: 'HH:mm',
			dateFormat: 'yy-mm-dd',
			showButtonPanel: false
		});

		initializeGraphs();
	});

	</script>
	<?php

	/* include time span selector */
	if (read_graph_config_option('timespan_sel') == 'on') {
		?>
		<tr class='even noprint'>
			<td class='noprint'>
			<form id='form_timespan_selector' action='<?php print $page;?>?action=preview' method='post' action='<?php print $page;?>'>
				<table class='filterTable'>
					<tr id='timespan'>
						<td>
							Presets
						</td>
						<td>
							<select id='predefined_timespan' onChange='applyGraphTimespan()'>
								<?php
								if ($_SESSION['custom']) {
									$graph_timespans[GT_CUSTOM] = 'Custom';
									$start_val = 0;
									$end_val = sizeof($graph_timespans);
								} else {
									if (isset($graph_timespans[GT_CUSTOM])) {
										asort($graph_timespans);
										array_shift($graph_timespans);
									}
									$start_val = 1;
									$end_val = sizeof($graph_timespans)+1;
								}

								if (sizeof($graph_timespans) > 0) {
									for ($value=$start_val; $value < $end_val; $value++) {
										print "<option value='$value'"; if ($_SESSION['sess_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($graph_timespans[$value], 40) . "</option>\n";
									}
								}
								?>
							</select>
						</td>
						<td>
							From
						</td>
						<td>
							<input type='text' id='date1' title='Graph Begin Timestamp' size='18' value='<?php print (isset($_SESSION['sess_current_date1']) ? $_SESSION['sess_current_date1'] : '');?>'>
						</td>
						<td>
							<i id='startDate' class='calendar fa fa-calendar' title='Start Date Selector'></i>
						</td>
						<td>
							To
						</td>
						<td>
							<input type='text' id='date2' title='Graph End Timestamp' size='18' value='<?php print (isset($_SESSION['sess_current_date2']) ? $_SESSION['sess_current_date2'] : '');?>'>
						</td>
						<td>
							<i id='endDate' class='calendar fa fa-calendar' title='End Date Selector'></i>
						</td>
						<td>
							<i class='shiftArrow fa fa-backward' onClick='timeshiftGraphFilterLeft()' title='Shift Time Backward'></i>
						</td>
						<td>
							<select id='predefined_timeshift' name='predefined_timeshift' title='Define Shifting Interval'>
								<?php
								$start_val = 1;
								$end_val = sizeof($graph_timeshifts)+1;
								if (sizeof($graph_timeshifts) > 0) {
									for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
										print "<option value='$shift_value'"; if ($_SESSION['sess_current_timeshift'] == $shift_value) { print ' selected'; } print '>' . title_trim($graph_timeshifts[$shift_value], 40) . "</option>\n";
									}
								}
								?>
							</select>
						</td>
						<td>
							<i class='shiftArrow fa fa-forward' onClick='timeshiftGraphFilterRight()' title='Shift Time Forward'></i>
						</td>
						<td>
							<input type='button' value='Refresh' name='button_refresh_x' title='Refresh selected time span' onClick='refreshGraphTimespanFilter()'>
						</td>
						<td>
							<input type='button' value='Clear' title='Return to the default time span' onClick='clearGraphTimespanFilter()'>
						</td>
					</tr>
					<tr id='realtime' style='display:none;'>
						<td>
							Window
						</td>
						<td>
							<select name='graph_start' id='graph_start' onChange='self.imageOptionsChanged("timespan")'>
							<?php
							foreach ($realtime_window as $interval => $text) {
								printf('<option value="%d"%s>%s</option>', $interval, $interval == $_SESSION['sess_realtime_window'] ? 'selected="selected"' : '', $text);
							}
							?>
							</select>
						</td>
						<td>
							Interval
						</td>
						<td>
							<select name='ds_step' id='ds_step' onChange="self.imageOptionsChanged('interval')">
								<?php
								foreach ($realtime_refresh as $interval => $text) {
									printf('<option value="%d"%s>%s</option>', $interval, $interval == $_SESSION['sess_realtime_dsstep'] ? ' selected="selected"' : '', $text);
								}
								?>
							</select>
						</td>
						<td>
							<input type='button' id='realtimeoff' value='Stop'>
						</td>
						<td align='center' colspan='6'>
							<span id='countdown'></span>
						</td>
					</tr>
				</table>
			</form>
			</td>
		</tr>
		<?php
	}
}

