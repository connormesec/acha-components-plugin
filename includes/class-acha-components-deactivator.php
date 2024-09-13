<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Components
 * @subpackage Acha_Components/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Acha_Components
 * @subpackage Acha_Components/includes
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Components_Deactivator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate()
	{

		function delete_custom_table_plugin()
		{
			global $wpdb;
			$schedule_table_name = $wpdb->prefix . 'schedule';
			$roster_table_name = $wpdb->prefix . 'roster';
			$wpdb->query("DROP TABLE IF EXISTS $schedule_table_name");
			$wpdb->query("DROP TABLE IF EXISTS $roster_table_name");
		}
		function delete_user_author()
		{
			$user_name = 'ac-api-user';
			$user_id = username_exists($user_name);
			if ($user_id) {
				wp_delete_user($user_id);
			}
		}
		function cron_job_delete()
		{
			if (wp_next_scheduled('acha_tools_auto_post_cron')) {
				wp_clear_scheduled_hook('acha_tools_auto_post_cron');
			}
		}
		cron_job_delete();
		delete_user_author();
		delete_custom_table_plugin();
	}
}
