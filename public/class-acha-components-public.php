<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Components
 * @subpackage Acha_Components/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Acha_Components
 * @subpackage Acha_Components/public
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Components_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function schedule_builder_shortcode($atts)
	{
		$schedule_data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
		$schedule_url_arr = [];
		foreach ($schedule_data->form_data as $schedule) {
			if ($atts['title'] === $schedule->scheduleName) {
				foreach ($schedule->url as $url) {
					array_push($schedule_url_arr, $url);
				}
			}
		}
		$schedule = new Acha_Components_Schedule($schedule_url_arr, $schedule_data->style, $atts['title']);
		if ($schedule_data->style->type === '1') {
			return $schedule->buildPillSchedule();
		} elseif ($schedule_data->style->type === '2') {
			return $schedule->buildDropdownSchedule();
		}
	}

	public function roster_builder_shortcode($atts)
	{
		$roster_data = json_decode(stripslashes(get_option('admin_roster_form_data')));
		foreach ($roster_data->form_data as $roster) {
			if ($atts['title'] === $roster->rosterName) {
				$roster_obj = new Acha_Components_Roster($atts['title']);
				if ($roster_data->style->type === '1') {
					return $roster_obj->buildPlayerCardRoster();
				} elseif ($roster_data->style->type === '2') {
					return $roster_obj->buildPlayerCardRoster();
				}
			}
		}
	}

	public function game_slider_shortcode($atts)
	{
		$schedule_data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
		$style = json_decode(stripslashes(get_option('admin_game_slider_form_data')));
		$schedule_url_arr = [];
		foreach ($schedule_data->form_data as $schedule) {
			if ($atts['title'] === $schedule->scheduleName) {
				foreach ($schedule->url as $url) {
					array_push($schedule_url_arr, $url);
				}
			}
		}
		$schedule = new Acha_Components_Game_Slider($schedule_url_arr, $style->style, $atts['title']);

		return $schedule->buildGameSlider();
	}

	public function do_thing_hourly()
	{
		$schedule_data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
		$password = json_decode(stripslashes(get_option('admin_game_summary_form')));
		$url = 'https://llygrsc22i.execute-api.us-east-2.amazonaws.com/default/acha-componenets-game-summary-api';
		$data = new stdClass();
		$data->isTest = false;
		$data->admin_form_data = $schedule_data->form_data;
		$data->home_url = home_url();
		$body = json_encode($data);
		$headers = [
			'Content-type: application/json',
			'Authorization: Basic ' . base64_encode($password->options->user_password)
		];
		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/json\r\nAuthorization: Basic " . base64_encode($password->options->user_password),
				'method'  => 'POST',
				'content' => json_encode($data)
			)
		);
		//$context  = stream_context_create($options);
		//$result = file_get_contents($url, false, $context);
		$this->sendNonBlockingPostRequest($headers, $body, $url);
	}

	public function gameSummaryPostTest(){
		if (!wp_verify_nonce($_REQUEST['nonce'], "game_summary_test_nonce")) {
			exit("No naughty business please");
		}
		$opt_value = $_POST['data'];
		$gameId = json_decode(stripslashes($opt_value))->options->gameId;
		$teamId = json_decode(stripslashes($opt_value))->options->targetTeamId;
		$password = json_decode(stripslashes(get_option('admin_game_summary_form')));
		$url = 'https://llygrsc22i.execute-api.us-east-2.amazonaws.com/default/acha-componenets-game-summary-api';
		$data = new stdClass();
		$data->isTest = true;
		$data->gameId = $gameId;
		$data->teamId = $teamId;
		$data->home_url = home_url();
		$body = json_encode($data);
		$headers = [
			'Content-type: application/json',
			'Authorization: Basic ' . base64_encode($password->options->user_password)
		];
		
		$this->sendNonBlockingPostRequest($headers, $body, $url);
	}

	private function sendNonBlockingPostRequest($headers, $body, $url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 1);
		curl_exec($curl);
		curl_close($curl);
	}

	function my_cron_schedules($schedules)
	{
		if (!isset($schedules["1min"])) {
			$schedules["1min"] = array(
				'interval' => 1 * 60,
				'display' => __('Once every 1 minutes')
			);
		}
		if (!isset($schedules["30min"])) {
			$schedules["30min"] = array(
				'interval' => 30 * 60,
				'display' => __('Once every 30 minutes')
			);
		}
		return $schedules;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Acha_Roster_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Acha_Roster_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/acha-components-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Acha_Roster_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Acha_Roster_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/acha-components-public.js', array('jquery'), $this->version, false);
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
