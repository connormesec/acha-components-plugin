<?php

/**
 * Fired during plugin activation
 *
 * @link       https://https://github.com/connormesec/
 * @since      1.0.0
 *
 * @package    Acha_Components
 * @subpackage Acha_Components/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Acha_Components
 * @subpackage Acha_Components/includes
 * @author     Connor Mesec <connormesec@gmail.com>
 */
class Acha_Components_Activator
{
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		function create_the_custom_tables()
		{
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			$schedule_table_name = $wpdb->prefix . 'schedule';

			$schedule_sql = "CREATE TABLE " . $schedule_table_name . " (
				game_id int NULL,
				id int(11) NOT NULL AUTO_INCREMENT,
				header_text VARCHAR(200) NULL,
				text TEXT,
				img_link varchar(200),
				UNIQUE  (id),
				PRIMARY KEY game_id (game_id)
			) $charset_collate;";
			dbDelta($schedule_sql);

			$roster_table_name = $wpdb->prefix . 'roster';

			$roster_sql = "CREATE TABLE " . $roster_table_name . " (
				player_id int NULL,
				id int(11) NOT NULL AUTO_INCREMENT,
				last_team VARCHAR(200) NULL,
				year_in_school VARCHAR(200) NULL,
				UNIQUE  (id),
				PRIMARY KEY player_id (player_id)
			) $charset_collate;";
			dbDelta($roster_sql);
		}
		create_the_custom_tables();
	}
}
