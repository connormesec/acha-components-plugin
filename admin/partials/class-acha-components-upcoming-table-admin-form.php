<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Game_Slider
 * @subpackage Acha_Game_Slider/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Acha_Game_Slider
 * @subpackage Acha_Game_Slider/admin
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Upcoming_Games_Table_Admin_Form
{
	public $admin_form_html;
	private $schedule_data;
	private $table_style_data;
	public function __construct()
	{
		if (get_option('admin_schedule_form_data')) {
			$data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
			$this->schedule_data = $data->form_data;
		} else {
			$this->schedule_data = null;
		}
		
		if (get_option('admin_upcoming_games_form_data')) {
			$data = json_decode(stripslashes(get_option('admin_upcoming_games_form_data')));
			$this->table_style_data = $data->style;
		} else {
			$this->table_style_data = null;
		}

		$this->admin_form_html = $this->buildCustomAdminForm();
	}

	private function buildCustomAdminForm(){
		//if null set form data to an empty object so form will render properly
		$schedule_arr = $this->schedule_data;
		$html = <<<HTML
				<div class="row">
					<div class="col-md-10">
						<div class="form-group">
							<form name="add_name" id="add_name">
								<table class="table table-bordered" id="dynamic_field">
									<tr class="title">
										<td><h4>Schedule Name</h4></td>
										<td><h4>Game Table Shortcode</h4></td>
									</tr>
		HTML;
		if ($schedule_arr){
			foreach($schedule_arr as $row){
				$schedule_name = ($row->scheduleName) ? $row->scheduleName : '';
				$html .= <<<HTML
							<tr class="row_item">
								<td>
									<h3>{$schedule_name}</h3>
								</td>
								<td>
									<input type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-upcoming-games-table title="{$schedule_name}"]'>
								</td>
							</tr>
						HTML;
			}
		}else{
			$html .= <<<HTML
						<tr class="row_item">
								<td>
									<h3>Please create a schedule first</h3>
								</td>
								<td>
									<input type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value=''>
								</td>
							</tr>
					HTML;
		}
		$logo_bg_color = '#ffffff';
		$primary_text_color = '#1d2327';
		$body_bg_color = '#dadada';
		$promo_header_text_color = '#ffffff';
		$promo_header_bg_color = '#8B2332';
		$promo_ticket_button_bg_color = '#9dd7e3';
		if($this->table_style_data) {
			if($this->table_style_data->logo_bg_color){
				$logo_bg_color = $this->table_style_data->logo_bg_color;
			}
			if($this->table_style_data->primary_text_color){
				$primary_text_color = $this->table_style_data->primary_text_color;
			}
			if($this->table_style_data->body_bg_color){
				$body_bg_color = $this->table_style_data->body_bg_color;
			}
			if($this->table_style_data->promo_header_text_color){
				$promo_header_text_color = $this->table_style_data->promo_header_text_color;
			}
			if($this->table_style_data->promo_header_bg_color){
				$promo_header_bg_color = $this->table_style_data->promo_header_bg_color;
			}
			if($this->table_style_data->promo_ticket_button_bg_color){
				$promo_ticket_button_bg_color = $this->table_style_data->promo_ticket_button_bg_color;
			}
		}
		$html .= <<<HTML
								</table>
								<input type="submit" class="btn btn-success" name="submit" id="games_table_admin_submit" value="Submit">
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
								<label for="logo_bg_color">Logo BG Color:</label>
								<input type="color" id="logo_bg_color" name="logo_bg_color" value="{$logo_bg_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="primary_text_color">Primary Text Color:</label>
								<input type="color" id="primary_text_color" name="primary_text_color" value="{$primary_text_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="body_bg_color">Body BG Color:</label>
								<input type="color" id="body_bg_color" name="body_bg_color" value="{$body_bg_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="promo_header_text_color">Promo Header Text Color:</label>
								<input type="color" id="promo_header_text_color" name="promo_header_text_color" value="{$promo_header_text_color}">
							</td>
							</tr>
							<tr>
								<td>
									<label for="promo_header_bg_color">Promo Header BG Color:</label>
									<input type="color" id="promo_header_bg_color" name="promo_header_bg_color" value="{$promo_header_bg_color}">
								</td>
							</tr>
							<tr>
								<td>
									<label for="promo_ticket_button_bg_color">Promo Ticket Button Color:</label>
									<input type="color" id="promo_ticket_button_bg_color" name="promo_ticket_button_bg_color" value="{$promo_ticket_button_bg_color}">
								</td>
							</tr>
						</table>
						<input type="submit" class="btn btn-info btn-sm" name="restore_defaults" id="restore_defaults_button" value="Restore Default Values">
						</div>
					</div>
				</div>
		HTML;
		$tableHtml = $this->buildDemoTable();
		$html .= '<div class="upcoming_game_table_admin" style="width: 80%;">' . $tableHtml . '</div>';

		
		$html .= $this->formJs();

		return $html;
	}

	public function buildDemoTable(){
		$schedule_data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
		$style = json_decode(stripslashes(get_option('admin_upcoming_games_form_data')));
		$schedule_url_arr = [];
		foreach ($schedule_data->form_data as $schedule) {
			foreach ($schedule->url as $url) {
				array_push($schedule_url_arr, $url);
			}
		}
		$schedule = new Acha_Components_Upcoming_Games_Table($schedule_url_arr, $style->style);
		return $schedule->buildUpcomingGameTable();
	}

	private function formJs() {
		$nonce = wp_create_nonce("upcoming_games_form_nonce");
		$js = <<<JS
				<script>
				jQuery('#spinner-div').show();
				jQuery(document).ready(function () {
					jQuery('#spinner-div').hide();
				
					jQuery("#games_table_admin_submit").click(function() {

						const logo_bg_color = jQuery('#logo_bg_color').val();
						const primary_text_color = jQuery('#primary_text_color').val();
						const body_bg_color	= jQuery('#body_bg_color').val();
						const promo_header_text_color = jQuery('#promo_header_text_color').val();
						const promo_header_bg_color = jQuery('#promo_header_bg_color').val();
						const promo_ticket_button_bg_color = jQuery('#promo_ticket_button_bg_color').val();
					
						event.preventDefault();
						
						var data = {
						'action'   : 'updateUpcomingGamesTableOptions', // the name of your PHP function!
						'upcoming_games_table_data' : JSON.stringify({
							'style' : {
								'logo_bg_color' : logo_bg_color,
								'primary_text_color' : primary_text_color,
								'body_bg_color' : body_bg_color,
								'promo_header_text_color' : promo_header_text_color,
								'promo_header_bg_color' : promo_header_bg_color,
								'promo_ticket_button_bg_color' : promo_ticket_button_bg_color
							}
						}),
						'nonce' : '{$nonce}'
						};
						jQuery.post(ajaxurl, data, function(response) {
							location.reload(); // Refresh the page
						});
					});
					
					jQuery("#restore_defaults_button").click(function() {
						jQuery('#logo_bg_color').val('#ffffff');
						jQuery('#primary_text_color').val('#1d2327');
						jQuery('#body_bg_color').val('#dadada');
						jQuery('#promo_header_text_color').val('#ffffff');
						jQuery('#promo_header_bg_color').val('#8B2332');
						jQuery('#promo_ticket_button_bg_color').val('#9dd7e3');
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
