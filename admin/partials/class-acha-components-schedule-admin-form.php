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
			$schedule_form_data = json_decode(stripslashes('{\"style\":{\"type\":\"1\",\"primaryColor\":\"\",\"secondaryColor\":\"\",\"textColor\":\"\",\"pillBackgroundColor\":\"\"},\"form_data\":[{\"scheduleName\":\"\",\"url\":[\"\"]}]}')); //todo: make this handle no data better
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
					<div class="col-md-10">
						<div class="form-group">
							<form name="add_name" id="add_name">
								<table class="table table-bordered" id="dynamic_field">
									<tr class="title">
										<td><h4>Schedule Name</h4></td>
										<td><h4>Schedule URL</h4></td>
										<td><h4>Schedule Shortcode</h4></td>
										<td><h4>Game Slider Shortcode</h4></td>
										<td><h4>Actions</h4></td>
									</tr>
		HTML;
        $i = 1;
        $j = 1;
        foreach($schedule_arr as $row){
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
											<input name="" id="row_url_input_1_1" style="width:100%" type="text" placeholder="Enter schedule URL" class="name_list required-entry" value="{$first_url}">
											<td><button class="btn btn-small btn-success add" id="row_1" type="button">Add</button></td>
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
							<input name="" id="row_url_input_{$j}_{$i}" style="width:100%" type="text" placeholder="Enter schedule URL" class="required-entry" value="{$url}">
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
							<input type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-game-slider title="{$schedule_name}"]'>
						</td>
						<td>
							<button type="button" name="add" id="add" class="btn btn-primary">Add Schedule</button>
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
											<input name="" id="row_url_input_{$j}_{$i}" style="width:100%" type="text" placeholder="Enter schedule URL" class="required-entry" value="{$first_url}">
											<td><button class="btn btn-small btn-success add" id="row_{$j}" type="button">Add</button></td>
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
                            <input name="" id="row_url_input_{$j}_{$i}" type="text" style="width:100%" placeholder="Enter schedule URL" class="required-entry" value="{$url}">
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
							<input type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-game-slider title="{$schedule_name}"]'>
						</td>
						<td>
							<button type="button" name="remove" id="{$j}" class="btn btn-danger btn_remove">X</button>
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
								<input type="submit" class="btn btn-success" name="submit" id="schedule_admin_submit" value="Submit">
							</form>
						</div>
					</div>
					<div class="col-md-2">
					<table class="table table-bordered" id="style_field">
						<tr class="title">
							<th>
								<h4>Style</h4>
							</th>
						</tr>
						<tr>
							<td>
								<select class="custom-select" id="scheduleStyleSelect">
									<option {$pill} value="1">Pill</option>
									<option {$dropdown} value="2">Dropdown</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="primary_color">Primary Color:</label>
								<input type="color" id="primary_color" name="primary_color" value="{$primary_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="secondary_color">Secondary Color:</label>
								<input type="color" id="secondary_color" name="secondary_color" value="{$secondary_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="header_text_color">Month Text Color:</label>
								<input type="color" id="header_text_color" name="header_text_color" value="{$header_text_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="text_color">Text Color:</label>
								<input type="color" id="text_color" name="text_color" value="{$text_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="container_bg_color">Container Color:</label>
								<input type="color" id="container_bg_color" name="container_bg_color" value="{$container_bg_color}">
							</td>
						</tr>
					</table>
					
					</div>
				</div>
			</div>
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
						'</td><td><input type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[ac-game-slider title=""]">' +
						'</td><td><button type="button" name="remove" id="{$j}" class="btn btn-danger btn_remove">X</button></td>' +
						'<button type="button" name="remove" id="'+j+'" class="btn btn-danger btn_remove">X</button></td></tr>');
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
					const url = jQuery(this).find('input[placeholder="Enter schedule URL"]').map(function() { return jQuery(this).val() }).get();
					data_arr.push(
						{ 
						scheduleName, 
						url 
					});
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
				console.log(data)
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

			});	
		</script>	
		JS;
		return $js;
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
