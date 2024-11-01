<?php
/**
 * woo-points.php
 *
 * Copyright (c) 2011,2017 Antonio Blanco http://www.eggemplo.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco (eggemplo)
 * @package points
 * @since points 1.0
 *
 * Plugin Name: Woo Points
 * Plugin URI: https://www.eggemplo.com/plugin/woocommerce-points/
 * Description: Woocommerce points system.
 * Version: 1.1.0
 * Author: ablancodev
 * Author URI: https://www.eggemplo.com
 * Text Domain: woocommerce-points
 * Domain Path: /languages
 * License: GPLv3
 */

define( 'WOO_POINTS_FILE', __FILE__ );
define( 'WOO_POINTS_PLUGIN_BASENAME', plugin_basename( WOO_POINTS_FILE ) );

if ( !defined( 'WOO_POINTS_CORE_DIR' ) ) {
	define( 'WOO_POINTS_CORE_DIR', WP_PLUGIN_DIR . '/woo-points' );
}
if ( !defined( 'WOO_POINTS_CORE_LIB' ) ) {
	define( 'WOO_POINTS_CORE_LIB', WOO_POINTS_CORE_DIR . '/lib/core' );
}

if ( !defined( 'WOO_POINTS_CORE_LIB_EXT' ) ) {
	define( 'WOO_POINTS_CORE_LIB_EXT', WOO_POINTS_CORE_DIR . '/lib/ext' );
}

if ( !defined( 'WOO_POINTS_CORE_VERSION' ) ) {
	define( 'WOO_POINTS_CORE_VERSION', '1.1' );
}

define( 'WOO_POINTS_PLUGIN_URL', plugin_dir_url( WOO_POINTS_FILE ) );

define( 'WOO_POINTS_DEFAULT_POINTS_LABEL', 'points' );

class WoocommercePoints_Plugin {

	private static $notices = array();

	public static function init() {

		load_plugin_textdomain( 'woo-points', null, 'woo-points/languages' );

		register_activation_hook( WOO_POINTS_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( WOO_POINTS_FILE, array( __CLASS__, 'deactivate' ) );
		register_uninstall_hook( WOO_POINTS_FILE, array( __CLASS__, 'uninstall' ) );

		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'widgets_init', array( __CLASS__,'woo_points_widgets_init' ) );

		// @todo check if woocommerce is installed
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-woocommerce.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-woocommerce-views.php' );

		require_once ( WOO_POINTS_CORE_LIB . '/constants.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-database.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-shortcodes.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-widget.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-admin.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-table.php' );
		require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-notifications.php' );

		// Ajax
		add_action( 'wp_ajax_nopriv_get_users', array( __CLASS__,'ajax_get_users' ) );
		add_action( 'wp_ajax_get_users', array( __CLASS__,'ajax_get_users' ) );

	}

	public static function wp_init() {

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'woo_points_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'woo_points_admin_enqueue_scripts' ) );

		WooPoints_Admin::init();

	}

	public static function woo_points_admin_enqueue_scripts() {
		// CSS
		wp_register_style( 'woo-points-admin-css', WOO_POINTS_PLUGIN_URL . 'css/woo-points-admin.css' );
		wp_enqueue_style ('woo-points-admin-css');

		//wp_register_style('ui-datepicker',WOO_POINTS_PLUGIN_URL . 'css/jquery.datetimepicker.css', array(), '1.0');
		//wp_enqueue_style( 'ui-datepicker' );

		wp_register_style('jquery-auto-complete', WOO_POINTS_PLUGIN_URL . 'css/jquery.auto-complete.css', array(), '1.0.7' );
		wp_enqueue_style( 'jquery-auto-complete' );

		// javascript
		wp_enqueue_script ( 'auto-complete', WOO_POINTS_PLUGIN_URL . 'js/jquery.auto-complete.min.js', array ('jquery', 'jquery-ui-core' ) );

		wp_register_script('woo-points-admin-script', WOO_POINTS_PLUGIN_URL . 'js/admin-scripts.js', array('jquery'),'1.0', true);
		//wp_enqueue_script( 'datepicker', WOO_POINTS_PLUGIN_URL . 'js/jquery.datetimepicker.full.min.js', array( 'jquery', 'jquery-ui-core' ) );

		wp_enqueue_script('woo-points-admin-script');
	}

	public static function woo_points_enqueue_scripts() {
		wp_register_style( 'woo-points-css', WOO_POINTS_PLUGIN_URL . 'css/woo-points.css' );
		wp_enqueue_style ('woo-points-css');
	}

	public static function woo_points_widgets_init() {
		register_widget( 'WooPoints_Widget' );
	}

	/**
	 * Plugin activation work.
	 * 
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		if ( !class_exists( 'WooPoints_Database' ) ) {
			require_once ( WOO_POINTS_CORE_LIB . '/class-woo-points-database.php' );
		}
		// create tables
		$points_users_table = WooPoints_Database::get_table( 'users' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$points_users_table'" ) != $points_users_table ) {
			$queries[] = "CREATE TABLE $points_users_table (
			point_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			points   BIGINT(20) DEFAULT 0,
			datetime     DATETIME DEFAULT NULL,
			description  varchar(5000),
			ip           int(10) unsigned default NULL,
			ipv6         decimal(39,0) unsigned default NULL,
			data         longtext default NULL,
			ref_id       int(10) default NULL,
			status       varchar(10) NOT NULL DEFAULT '" . WOO_POINTS_STATUS_ACCEPTED . "',
			type         varchar(10) NULL,
			PRIMARY KEY   (point_id)
			) $charset_collate;";
		}
		if ( !empty( $queries ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $queries );
		}
	}

	/**
	 * Plugin deactivation.
	 *
	 */
	public static function deactivate() {

	}

	/**
	 * Plugin uninstall. Delete database table.
	 *
	 */
	public static function uninstall() {

	}

	/**
	 * Ajax: get users
	 */
	public static function ajax_get_users() {
		global $wpdb; //get access to the WordPress database object variable
	
		$users = get_users( array( 'search' => '*' . esc_sql( $_POST['name'] ) . '*' ) );
	
		$usernames = array();
		if ( sizeof( $users ) > 0 ) {
			foreach ( $users as $user ) {
				$usernames[] = $user->user_login;
			}
		}
	
		echo json_encode($usernames);
	
		die();
	}

}
WoocommercePoints_Plugin::init();
