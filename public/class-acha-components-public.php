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
class Acha_Components_Public {

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
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function schedule_builder_shortcode($atts) {
		$schedule_data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
		$schedule_url_arr = [];
		foreach ($schedule_data->form_data as $schedule) {
			if($atts['title'] === $schedule->scheduleName){
				foreach ($schedule->url as $url) {
				array_push($schedule_url_arr, $url);
				} 
			} else {
				echo 'make sure the shortcode title matches the schedule title';
				continue;
			}
		}
		$schedule = new Acha_Components_Schedule($schedule_url_arr, $schedule_data->style);
		if($schedule_data->style->type === '1') {
			return $schedule->buildPillSchedule();
		} elseif ($schedule_data->style->type === '2') {
			return $schedule->buildDropdownSchedule();
		}
	}

	public function roster_builder_shortcode($atts) {
		$roster_data = json_decode(stripslashes(get_option('admin_roster_form_data')));
		foreach ($roster_data->form_data as $roster) {
			if($atts['title'] === $roster->rosterName){
				$roster_obj = new Acha_Components_Roster($atts['title']);
				if($roster_data->style->type === '1') {
					return $roster_obj->buildPlayerCardRoster();
				} elseif ($roster_data->style->type === '2') {
					return $roster_obj->buildPlayerCardRoster();
				}
			} else {
				echo 'make sure the shortcode title matches the schedule title';
				continue;
			}
		}
	}

	public function game_slider_shortcode($atts) {
		$schedule_data = json_decode(stripslashes(get_option('admin_schedule_form_data')));
		echo '<script>console.log('. json_encode($schedule_data) .')</script>';
		echo '<script>console.log('. json_encode($atts) .')</script>';
		$schedule_url_arr = [];
		foreach ($schedule_data->form_data as $schedule) {
			if($atts['title'] === $schedule->scheduleName){
				foreach ($schedule->url as $url) {
				array_push($schedule_url_arr, $url);
				} 
			} else {
				echo 'make sure the shortcode title matches the schedule title';
				continue;
			}
		}
		$schedule = new Acha_Components_Game_Slider($schedule_url_arr);
		//$shc_data = $schedule->schedule_arr;
		if($schedule_data->style === '1') {
			return $schedule->buildGameSlider();
		} elseif ($schedule_data->style === '2') {
			return $schedule->buildGameSlider();
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/acha-components-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/acha-components-public.js', array( 'jquery' ), $this->version, false );

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
