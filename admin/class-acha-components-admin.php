<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Components
 * @subpackage Acha_Components/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Acha_Components
 * @subpackage Acha_Components/admin
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Components_Admin
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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action('update_db', [$this, 'test']);
		$this->load_dependencies();
		$this->check_for_updates();
	}

	private function load_dependencies()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-acha-components-schedule.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-acha-components-game-slider.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-acha-components-roster.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-acha-components-playerStat-page.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-acha-components-schedule-admin-form.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-acha-components-roster-admin-form.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/update.php';
	}

	public function ac_admin_menu()
	{
		add_menu_page(
			"Connor's ACHA Tools", // page title
			'ACHA Tools', // menu title
			'manage_options', // capability
			'acha-tools', // menu slug
			array($this, 'ac_options_page') // callback function
		);
	}

	public function ac_settings_init()
	{

		add_option('admin_schedule_form_data');
		add_option('admin_roster_form_data');

	}

	public function ac_options_page()
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		//Get the active tab from the $_GET param
		$default_tab = null;
		$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

		?>
		<!-- Our admin page content should all be inside .wrap -->
		<div class="wrap">
			<!-- Print the page title -->
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<!-- Here are our tabs -->
			<nav class="nav-tab-wrapper">
			<a href="?page=acha-tools" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Schedule</a>
			<a href="?page=acha-tools&tab=roster" class="nav-tab <?php if($tab==='roster'):?>nav-tab-active<?php endif; ?>">Roster</a>
			<a href="?page=acha-tools&tab=tools" class="nav-tab <?php if($tab==='tools'):?>nav-tab-active<?php endif; ?>">Whatevs next</a>
			</nav>

			<div class="tab-content">
			<?php switch($tab) :
			case 'tools':
				echo 'Settings'; //Put your HTML here
				break;
			case 'roster':
				echo $this->rosterSettingsHtml();
				break;
			default:
				echo $this->scheduleSettingsHtml();
				break;
			endswitch; ?>
			</div>
		</div>
		<?php
		echo '</div>';
	}

	private function scheduleSettingsHtml() {
		$content = '<div id="spinner-div" class="pt-5">
				<div class="spinner-border text-primary" role="status">
				</div>
			</div>
			<div class="container-fluid">
			<h2> Schedule Settings </h2>
			';
		$schedule_form = new Acha_Schedule_Admin_Form;
		$content .= $schedule_form->admin_form_html;

		//if data already exists
		if (get_option('admin_schedule_form_data')) {
			$schedule_arr = json_decode(stripslashes(get_option('admin_schedule_form_data')));
			$schedule_url_arr = array();
			foreach ($schedule_arr->form_data as $schedule) {
				foreach ($schedule->url as $url) {
					array_push($schedule_url_arr, $url);
				}
			}

			$schedule = new Acha_Components_Schedule($schedule_url_arr);
			if($schedule->errors) {
				$content .= $this->array_to_table($schedule->errors);
			}
			$content .= $schedule->build_admin_schedule_html_table();
		}
	return $content;
	}

	private function rosterSettingsHtml() {
		$content = '<div id="spinner-div" class="pt-5">
				<div class="spinner-border text-primary" role="status">
				</div>
			</div>
			<div class="container-fluid">
			<h2>Roster Settings </h2>
			';
		$roster_form = new Acha_Roster_Admin_Form;
		$content .= $roster_form->admin_form_html;

		//if data already exists
		if (get_option('admin_roster_form_data')) {
			$roster_arr = json_decode(stripslashes(get_option('admin_roster_form_data')));
			$roster_url_arr = array();
			foreach ($roster_arr->form_data as $roster) {
				array_push($roster_url_arr, $roster->url);
			}

			$schedule = new Acha_Components_Roster();
			if($schedule->errors) {
				$content .= $this->array_to_table($schedule->errors);
			}
			$content .= $schedule->build_admin_roster_html_table();
		}
	return $content;
	}

	public function updateScheduleDB()
	{
		if (!wp_verify_nonce($_REQUEST['nonce'], "update_schedule_db_nonce")) {
			exit("No naughty business please");
		}
		global $wpdb;

		$table_name = $wpdb->prefix . "schedule";
		$game_id = $_POST['game_id'];
		$text = $_POST['text_input'];
		$header = $_POST['header_input'];
		$img_value = $_POST['img_input'];

		$response = '';
		//prevents from populating db when inputs are empty
		if ($text === '' && $header === '' && $img_value === '') {
			$response = $wpdb->delete($table_name, array('game_id' => $game_id));
		} else {
			$response = $wpdb->replace(
				$table_name,
				array(
					'game_id' => $game_id,
					'text' => $text,
					'header_text' => $header,
					'img_link' => $img_value
				),
				array(
					'%d', '%s', '%s', '%s'
				)
			);
		}
		echo $response;

		wp_die();
	}
	public function updateAdminScheduleDB()
	{
		if (!wp_verify_nonce($_REQUEST['nonce'], "update_admin_schedule_db_nonce")) {
			exit("No naughty business please");
		}
		$opt_value = $_POST['schedule_data'];
		$opt_name = 'admin_schedule_form_data';
		$existing_val = get_option($opt_name);
		if (false !== $existing_val) {
			// option exist
			if ($existing_val === $opt_value) {
				//echo "new value is same as old.";
			} else {
				update_option($opt_name, $opt_value);
				if ($opt_value) {
					$schedule_data = json_decode(stripslashes($opt_value))->form_data;
					$schedule_url_arr = array();
					foreach ($schedule_data as $schedule) {
						foreach ($schedule->url as $url) {
							array_push($schedule_url_arr, $url);
						}
					}
		
					$schedule = new Acha_Components_Schedule($schedule_url_arr);
					if($schedule->errors) {
						echo $this->array_to_table($schedule->errors);
					}
					echo $schedule->build_admin_schedule_html_table();
				}

			}
		} else {
			// option not exist
			add_option($opt_name, $opt_value);
		}

		wp_die();
	}

	public function updateRosterDB()
	{
		if (!wp_verify_nonce($_REQUEST['nonce'], "update_roster_db_nonce")) {
			exit("No naughty business please");
		}
		global $wpdb;

		$table_name = $wpdb->prefix . "roster";
		$player_id = $_POST['player_id'];
		$last_team = $_POST['last_team_input'];
		$year_in_school = $_POST['year_in_school_input'];


		$response = '';
		//prevents from populating db when inputs are empty
		if ($last_team === '' && $year_in_school === '') {
			$response = $wpdb->delete($table_name, array('player_id' => $player_id));
		} else {
			$response = $wpdb->replace(
				$table_name,
				array(
					'player_id' => $player_id,
					'last_team' => $last_team,
					'year_in_school' => $year_in_school
				),
				array(
					'%d', '%s', '%s'
				)
			);
		}
		echo $response;

		wp_die();
	}
	public function updateAdminRosterDB()
	{
		if (!wp_verify_nonce($_REQUEST['nonce'], "update_admin_roster_db_nonce")) {
			exit("No naughty business please");
		}

		$opt_value = $_POST['roster_data'];
		$opt_name = 'admin_roster_form_data';
		$existing_val = get_option($opt_name);
		if (false !== $existing_val) {
			// option exist
			if ($existing_val === $opt_value) {
				//echo "new value is same as old.";
			} else {
				update_option($opt_name, $opt_value);
				if ($opt_value) {
					$schedule = new Acha_Components_Roster();
					if($schedule->errors) {
						echo $this->array_to_table($schedule->errors);
					}
					echo $schedule->build_admin_roster_html_table();
				}
			}
		} else {
			// option not exist
			add_option($opt_name, $opt_value);
		}

		wp_die();
	}

	/**
	 * The code that runs to check for updates
	 * This action is documented in includes/update.php
	 */
	function check_for_updates()
	{
		/**
		 * The class responsible for updating the plugin
		 */
		$updater = new PDUpdater(PLUGIN_DIR_PATH .'/acha-components.php');
		$updater->set_username('connormesec');
		$updater->set_repository('acha-components-plugin');

		$updater->initialize();
	}


	private function array_to_table($data)
	{
		$html = "<table id='error_table'>";
		foreach ($data as $row) {
			$html .= "<tr>";
				$html .= "<td>" . $row . "</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
		return $html;
	}

	/**
	 * Register the stylesheets for the admin area.
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
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/acha-components-admin.css', array(), $this->version, 'all');
		wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');

		
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Acha_Components_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Acha_Components_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/acha-components-admin.js', array('jquery'), $this->version, false);
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
