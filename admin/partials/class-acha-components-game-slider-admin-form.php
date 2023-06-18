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
class Acha_Game_Slider_Admin_Form
{
	public $admin_form_html;
    private $schedule_data;
    private $slider_style_data;
	public function __construct()
	{
		if (get_option('admin_schedule_form_data')) {
			$data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
            $this->schedule_data = $data->form_data;
        } else {
            $this->schedule_data = null;
        }
        
        if (get_option('admin_game_slider_form_data')) {
			$data = json_decode(stripslashes(get_option('admin_game_slider_form_data')));
            $this->slider_style_data = $data->style;
		} else {
            $this->slider_style_data = null;
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
										<td><h4>Game Slider Shortcode</h4></td>
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
                                    <input type="text" id="ac-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value='[ac-game-slider title="{$schedule_name}"]'>
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
		$header_bg_color = '';
        $header_text_color = '';
        $body_bg_color = '#ffffff';
        $body_text_color = '#52596c';
        $nav_arrow_color = '';
        if($this->slider_style_data) {
            if($this->slider_style_data->header_bg_color){
                $header_bg_color = $this->slider_style_data->header_bg_color;
            }
            if($this->slider_style_data->header_text_color){
                $header_text_color = $this->slider_style_data->header_text_color;
            }
            if($this->slider_style_data->body_bg_color){
                $body_bg_color = $this->slider_style_data->body_bg_color;
            }
            if($this->slider_style_data->body_text_color){
                $body_text_color = $this->slider_style_data->body_text_color;
            }
            if($this->slider_style_data->nav_arrow_color){
                $nav_arrow_color = $this->slider_style_data->nav_arrow_color;
            }
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
								<label for="primary_color">Header BG Color:</label>
								<input type="color" id="header_bg_color" name="header_bg_color" value="{$header_bg_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="header_text_color">Header Text Color:</label>
								<input type="color" id="header_text_color" name="header_text_color" value="{$header_text_color}">
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
								<label for="body_text_color">Body Text Color:</label>
								<input type="color" id="body_text_color" name="body_text_color" value="{$body_text_color}">
							</td>
						</tr>
						<tr>
							<td>
								<label for="nav_arrow_color">Nav Arrow Color:</label>
								<input type="color" id="nav_arrow_color" name="nav_arrow_color" value="{$nav_arrow_color}">
							</td>
						</tr>
					</table>
					
					</div>
				</div>
			</div>
		HTML;
		
		$html .= $this->formJs();

        return $html;
    }

	private function formJs() {
		$nonce = wp_create_nonce("update_admin_game_slider_db_nonce");
		$js = <<<JS
                <script>
                jQuery('#spinner-div').show();
                jQuery(document).ready(function () {
                    jQuery('#spinner-div').hide();
                
                    jQuery("#schedule_admin_submit").click(function() {

                        const header_bg_color = jQuery('#header_bg_color').val();
                        const header_text_color = jQuery('#header_text_color').val();
                        const body_bg_color	= jQuery('#body_bg_color').val();
                        const body_text_color = jQuery('#body_text_color').val();
                        const nav_arrow_color = jQuery('#nav_arrow_color').val();
                    
                        event.preventDefault();
                        
                        var data = {
                        'action'   : 'updateAdminGameSliderDB', // the name of your PHP function!
                        'game_slider_data' : JSON.stringify({
                            'style' : {
                                'header_bg_color' : header_bg_color,
                                'header_text_color' : header_text_color,
                                'body_bg_color' : body_bg_color,
                                'body_text_color' : body_text_color,
                                'nav_arrow_color' : nav_arrow_color
                            }
                        }),
                        'nonce' : '{$nonce}'
                        };
                        console.log(data)
                        jQuery('#spinner-div').show();
                        jQuery.post(ajaxurl, data, function(response) {
                            jQuery('#spinner-div').hide();
                        });
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
