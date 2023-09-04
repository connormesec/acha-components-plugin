<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Roster
 * @subpackage Acha_Roster/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Acha_Roster
 * @subpackage Acha_Roster/admin
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Schedule_Admin_Form
{
	public $admin_form_html;
	public function __construct()
	{
		if (get_option('admin_schedule_form_data')) {
			$schedule_arr = json_decode(stripslashes(get_option('admin_schedule_form_data')));
			$this->admin_form_html = $this->buildCustomAdminForm($schedule_arr);
		} else {
            $this->admin_form_html = $this->buildCustomAdminForm();
        }
    }

    private function buildCustomAdminForm($schedule_form_data = null){
		//if null set form data to an empty object so form will render properly
		if ($schedule_form_data === null) {
			$schedule_form_data = json_decode(stripslashes('{\"style\":{\"type\":\"1\",\"primaryColor\":\"#000000\",\"secondaryColor\":\"#000000\",\"textColor\":\"#000000\",\"gameContainerColor\":\"#000000\",\"headerTextColor\":\"#000000\"},\"form_data\":[{\"scheduleName\":\"\",\"url\":[\"\"]}]}')); //todo: make this handle no data better
		}
		
		$schedule_arr = $schedule_form_data->form_data;
		$pill = '';
		$dropdown = '';
		if ($schedule_form_data->style->type === '1') {
			$pill = 'selected';
		} elseif ($schedule_form_data->style->type === '2') {
			$dropdown = 'selected';
		}
		$html = <<<HTML
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
								<table class="table table-bordered" id="dynamic_field">
									<tr class="title">
										<td><h4>Schedule Name</h4></td>
										<td><h4>Schedule URL</h4></td>
										<td><h4>Schedule Shortcode</h4></td>
										<td><h4>Actions</h4></td>
									</tr>
		HTML;
        $i = 1;
        $j = 1;
        foreach($schedule_arr as $row){
        
			//handle csv values
			$csvButtonClassVals = 'uploadButton';
			$encodedData = '';
			$buttonText = 'Upload CSV';
			$disabled = '';
			if($row->csvData){
				$csvButtonClassVals = 'btn-danger removeCsvButton';
				$encodedData = $this->encodeURIComponent($row->csvData);
				$buttonText = 'Remove CSV';
				$disabled = 'disabled';
			}
			
			if ($j === 1) {
                $schedule_name = ($row->scheduleName) ? $row->scheduleName : '';
                $first_url = ($row->url[0]) ? $row->url[0] : '';
                $html .= <<<HTML
					<tr class="row_item">
						<td>
							<input type="text" name="schedule_name_input_1" placeholder="Enter schedule name" class="form-control name_list" value="{$schedule_name}"/>
						</td>
						<td>
							<div id="attributes">
								<table class="attr table table-borderless" id="dynamic_url_input_field_row_1">
									<tr>
										<td>
											<input name="" id="row_url_input_1_1" style="width:100%" type="text" placeholder="Enter schedule URL" class="name_list required-entry {$disabled}" value="{$first_url}">
											<td><button class="btn btn-small btn-success add {$disabled}" id="row_1" type="button">Add</button></td>
										</td>
									</tr>
				HTML;

                foreach($row->url as $sch_url) {
					if($i===1) {
						$i++;
						continue;
					}
                    $url = ($sch_url) ? $sch_url : '';
					$html .= <<<HTML
					<tr id="row_url_{$j}_{$i}">
						<td>
							<input name="" id="row_url_input_{$j}_{$i}" style="width:100%" type="text" placeholder="Enter schedule URL" class="required-entry {$disabled}" value="{$url}">
							<td><button class="btn btn-danger remove" id="url_{$j}_{$i}" type="button">X</button></td>
						</td>
					</tr>
					HTML;
					$i++;
                }
				$html .= <<<HTML
								</table>
							</div>
						</td>
						<td>
							<input	type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-schedule title="{$schedule_name}"]'>
						</td>
						<td>
							<button type="button" name="add" id="add" class="btn btn-primary">Add Schedule</button> <button type="button" id="row_1" data="{$encodedData}" class="{$csvButtonClassVals} btn btn-secondary btn-sm">{$buttonText}</button><input type="file" id="row_1" class="csvFileInput" style="display: none;" accept=".csv">
						</td>
					</tr>
					HTML;

                $j++;
            } else {
                $schedule_name = ($row->scheduleName) ? $row->scheduleName : '';
                $first_url = ($row->url[0]) ? $row->url[0] : '';
				$html .= <<<HTML
					<tr class="row_item" id="row{$j}">
						<td>
							<input type="text" name="schedule_name_input_{$j}" placeholder="Enter schedule name" class="form-control name_list" value="{$schedule_name}"/>
						</td>
						<td>
							<div id="attributes">
								<table class="attr table table-borderless" id="dynamic_url_input_field_row_{$j}">
									<tr>
										<td>
											<input name="" id="row_url_input_{$j}_{$i}" style="width:100%" type="text" placeholder="Enter schedule URL" class="required-entry {$disabled}" value="{$first_url}">
											<td><button class="btn btn-small btn-success add {$disabled}" id="row_{$j}" type="button">Add</button></td>
										</td>
									</tr>
				HTML;

                foreach($row->url as $sch_url) {
                    if($first_url === $sch_url) {
						$i++;
						continue;
					}
                    $url = ($sch_url) ? $sch_url : '';
                    $html .= <<<HTML
                    <tr id="row_url_{$j}_{$i}">
                        <td>
                            <input name="" id="row_url_input_{$j}_{$i}" type="text" style="width:100%" placeholder="Enter schedule URL" class="required-entry {$disabled}" value="{$url}">
                            <td><button class="btn btn-danger remove" id="url_{$j}_{$i}" type="button">X</button></td>
                        </td>
                    </tr>
                    HTML;
					$i++;
                }
                $html .= <<<HTML
									
								</table>
							</div>
						</td>
						<td>
							<input	type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-schedule title="{$schedule_name}"]'>
						</td>
						<td>
							<button type="button" name="remove" id="{$j}" class="btn btn-danger btn_remove">X</button> <button type="button" id="row_1" data="{$encodedData}" class="{$csvButtonClassVals} btn btn-secondary btn-sm">{$buttonText}</button><input type="file" id="row_1" class="csvFileInput" style="display: none;" accept=".csv">
						</td>
					</tr>
				HTML;
                $j++;
            }
            
        }
		$primary_color = '';
		if($primary_color = $schedule_form_data->style->primaryColor){
			$primary_color = $schedule_form_data->style->primaryColor;
		}
		$secondary_color = '';
		if($secondary_color = $schedule_form_data->style->secondaryColor){
			$secondary_color = $schedule_form_data->style->secondaryColor;
		}
		$header_text_color = '';
		if($header_text_color = $schedule_form_data->style->headerTextColor){
			$header_text_color = $schedule_form_data->style->headerTextColor;
		}
		$text_color = '';
		if($schedule_form_data->style->textColor){
			$text_color = $schedule_form_data->style->textColor;
		}
		$container_bg_color = '';
		if($schedule_form_data->style->gameContainerColor){
			$container_bg_color = $schedule_form_data->style->gameContainerColor;
		}
        $html .= <<<HTML
								</table>
								
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
					<table class="table table-bordered" id="style_field">
						<tr class="title">
							<th>
								<h4>Style</h4>
							</th>
							<th>
								<h5>Primary Color</h5>
							</th>
							<th>
								<h5>Secondary Color</h5>
							</th>
							<th>
								<h5>Month Color</h5>
							</th>
							<th>
								<h5>Text Color</h5>
							</th>
							<th>
								<h5>Container Color</h5>
							</th>
						</tr>
						<tr>
							<td>
								<select class="custom-select" id="scheduleStyleSelect">
									<option {$pill} value="1">Pill</option>
									<option {$dropdown} value="2">Dropdown</option>
								</select>
							</td>
							<td>
								<input type="color" class="m-auto form-control form-control-color" id="primary_color" name="primary_color" value="{$primary_color}">
							</td>
							<td>
								<input type="color" class="m-auto form-control form-control-color" id="secondary_color" name="secondary_color" value="{$secondary_color}">
							</td>
							<td>
								<input type="color" class="m-auto form-control form-control-color" id="header_text_color" name="header_text_color" value="{$header_text_color}">
							</td>
							<td>
								<input type="color" class="m-auto form-control form-control-color" id="text_color" name="text_color" value="{$text_color}">
							</td>
							<td>
								<input type="color" class="m-auto form-control form-control-color" id="container_bg_color" name="container_bg_color" value="{$container_bg_color}">
							</td>
						</tr>
					</table>
					
					</div>
				</div>
				<input type="submit" class="btn btn-success" name="submit" id="schedule_admin_submit" value="Submit">
			</div>
			<div style="height:2rem"></div>
		HTML;
		
		$html .= $this->formJs($i, $j);

        return $html;
    }

	private function formJs($i, $j) {
		$nonce = wp_create_nonce("update_admin_schedule_db_nonce");
		$js = <<<JS
			<script>
			jQuery('#spinner-div').show();
			jQuery(document).ready(function () {
				jQuery('#spinner-div').hide();
				var i = {$i}; //schedule input row iterator
				var j = {$j}; //row iterator

			jQuery("#add").click(function () {
				j++;
				jQuery("#dynamic_field").append('<tr id="row'+j+'" class="row_item">' +
						'<td><input type="text" name="schedule_name_input_'+i+'" placeholder="Enter schedule name" class="form-control name_list"/>' +
						'</td><td><div id="attributes"><table class="attr table table-borderless" id="dynamic_url_input_field_row_'+i+'">' +
						'<tr id="row_url_'+j+'_'+i+'"><td><input name="row_url_input_'+j+'_'+i+'" id="" type="text" style="width:100%" placeholder="Enter schedule URL" class="required-entry">' +
						'<td><button class="btn btn-small btn-success add" id="row_'+i+'" type="button">Add</button></td></td></tr></table>' +
						'</div></td><td><input	type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[ac-schedule title=""]">' +
						'</td><td><button type="button" name="remove" id="{$j}" class="btn btn-danger btn_remove">X</button> ' +
						'<button type="button" id="row_{$j}" class="uploadButton btn btn-secondary btn-sm">Upload CSV</button><input type="file" class="csvFileInput" id="row_{$j}" style="display: none;" accept=".csv"></td></tr>');
			});

			jQuery(document).on("click", ".btn_remove", function () {
				j++;
				var button_id = jQuery(this).attr("id");
				jQuery(this).parent().closest('tr').remove();
			});
			
			jQuery("#schedule_admin_submit").click(function() {
				let data_arr = [];
				jQuery('.row_item').map(function () {
					const scheduleName = jQuery(this).find('input[placeholder="Enter schedule name"]').val();
					//remember: we change the url value to the name of the csv so we will use this value to check localstorage
					const url = jQuery(this).find('input[placeholder="Enter schedule URL"]').map(function() { return jQuery(this).val() }).get();
					const csvButtonDataAttr = jQuery(this).find('.removeCsvButton').attr('data');
					let csvData = null;
					if (csvButtonDataAttr){
						csvData = decodeURIComponent(csvButtonDataAttr);
					}
					data_arr.push(
						{
							scheduleName, 
							url,
							csvData
						}
					);
				});
				const style = jQuery('#scheduleStyleSelect').val();
				const primaryColor = jQuery('#primary_color').val();
				const secondaryColor = jQuery('#secondary_color').val();
				const textColor	= jQuery('#text_color').val();
				const gameContainerColor = jQuery('#container_bg_color').val();
				const headerTextColor = jQuery('#header_text_color').val();
			
				event.preventDefault();
				
				var data = {
				'action'   : 'updateAdminScheduleDB', // the name of your PHP function!
				'schedule_data' : JSON.stringify({
					'style' : {
						'type' : style,
						'primaryColor' : primaryColor,
						'secondaryColor' : secondaryColor,
						'textColor' : textColor,
						'gameContainerColor' : gameContainerColor,
						'headerTextColor' : headerTextColor
					},
					'form_data' : data_arr
				}),
				'nonce' : '{$nonce}'
				};
				jQuery('#spinner-div').show();
				jQuery.post(ajaxurl, data, function(response) {
					if (response) {
						jQuery("#error_table").remove();
						jQuery("#schedule_edit_table").replaceWith(response);
					}
					jQuery('#spinner-div').hide();
				});
			});

			jQuery(document).on('click', '.add', function(){  
			var button_id = jQuery(this).attr("id");
			addRow(button_id);  
			});

			function addRow(button_id) {
			i++;
			jQuery('#dynamic_url_input_field_'+button_id).append('<tr id="row_url_'+j+'_'+i+'"><td><input name="" id="" type="text" style="width:100%" placeholder="Enter schedule URL" class="required-entry"><td><button class="btn btn-danger remove" id="url_'+j+'_'+i+'" type="button">X</button></td></td></tr>');  
			}

			jQuery(document).on('click', '.remove', function(){  
				var button_id = jQuery(this).attr("id");
				jQuery('#row_'+button_id+'').remove();
			});

			jQuery('#dynamic_field').on('input propertychange paste ', '.form-control', function () {
				jQuery(this).parent().next('td').next('td').find('#ac-shortcode').val('[ac-schedule title="' + jQuery(this).val() +'"]');
				jQuery(this).parent().next('td').next('td').next('td').find('#ac-shortcode').val('[ac-schedule title="' + jQuery(this).val() +'"]');
			});

			//csv upload button
			jQuery(document).on('click', '.uploadButton', function() {
				alert('Make sure your CSV is formatted properly...\\n' +
						'Header row needs to match these values exactly! \\n' +
						'month,date,time,opponent,home_or_away,notes\\n' +
						'Example Row:\\n' +
						'September,\"Friday, Sep 22nd\",6:30 PM,Boise State,Home,Ressler Rink');
				jQuery(this).closest('td').find('input').click();
			});
			// Handle the file input change event
			jQuery(document).on('change', '.csvFileInput', function() {
				let row = jQuery(this).attr('id');
				// Get the selected file
				const selectedFile = this.files[0];
				let changeAddCsvButton = jQuery(this).closest('td').find('.uploadButton');
			
				// Check if a file was selected
				if (selectedFile) {
					const reader = new FileReader();
					let data;
					reader.onload = function(e) {
						const csvText = e.target.result;
						const jsonData = csvToJson(csvText);
						// You can display the JSON data or perform further actions here
						data = JSON.stringify(jsonData);
						
						changeAddCsvButton.attr('data', encodeURIComponent(data));
					}
					reader.readAsText(selectedFile);

					let scheduleCell = jQuery(this).closest('tr').find('.required-entry');
						scheduleCell.val(selectedFile.name);
						scheduleCell.addClass('disabled');
					
					jQuery(this).closest('tr').find('.add').addClass('disabled');
					//make button red
						changeAddCsvButton.html("Remove CSV");
						changeAddCsvButton.removeClass('btn-seconary uploadButton');
						changeAddCsvButton.addClass('btn-danger removeCsvButton');
					
				}

				function csvToJson(csv) {
					//sketchy way of getting main logo via favicon
					const logoUrl = jQuery('link[rel="icon"]').attr('href');

					const lines = csv.replace('\\r', '').split('\\n');
					const result = [];
					const headers = lines[0].split(',');
					for (let i = 1; i < lines.length; i++) {
						const obj = {logoUrl : logoUrl};
						const currentLine = lines[i].split(',');
						let currentIndex = 0;
						
						for (let j = 0; j < headers.length; j++) {
							// Check if the value contains a double quote
							if (currentLine[currentIndex].startsWith('"')) {
								let combinedValue = currentLine[currentIndex].replace(/^"/, '');

								while (!currentLine[currentIndex].endsWith('"')) {
									currentIndex++;
									combinedValue += ',' + currentLine[currentIndex];
								}

								combinedValue = combinedValue.replace(/"$/, ''); // Remove trailing double quote
								currentIndex++;
								obj[headers[j]] = combinedValue.replace('\\r', '');
							} else {
								obj[headers[j]] = currentLine[currentIndex].replace('\\r', '');
								currentIndex++;
							}
						}

						result.push(obj);
					}
					return result;
				}
			});

			jQuery(document).on('click', '.removeCsvButton', function() {
				let scheduleCell = jQuery(this).closest('tr').find('.required-entry');
					scheduleCell.removeClass('disabled');
					scheduleCell.val('');
				jQuery(this).closest('tr').find('.add').removeClass('disabled');
				//make button secondary again
				let changeAddCsvButton = jQuery(this);
					changeAddCsvButton.html("Upload CSV");
					changeAddCsvButton.addClass('btn-seconary uploadButton');
					changeAddCsvButton.removeClass('btn-danger removeCsvButton');
					changeAddCsvButton.attr('data','');
				//remove file from hidden input
				jQuery(this).closest('td').find('input').val('');
			});
		});
		</script>	
		JS;
		return $js;
	}

	//https://stackoverflow.com/questions/1734250/what-is-the-equivalent-of-javascripts-encodeuricomponent-in-php
	private function encodeURIComponent($str) {
		$revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
		return strtr(rawurlencode($str), $revert);
	}

	function console_log($output, $with_script_tags = true)
    {
        $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
            ');';
        if ($with_script_tags) {
            $js_code = '<script>' . $js_code . '</script>';
        }
        echo $js_code;
    }
}
