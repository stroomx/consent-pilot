<?php
namespace Frogrammer\ConsentPilot;

use Frogrammer\ConsentPilot\Assets\Assets;
use Frogrammer\ConsentPilot\Admin\Admin;
use Frogrammer\ConsentPilot\PublicSite\PublicSite;
use Frogrammer\ConsentPilot\REST\RESTController;
use Frogrammer\ConsentPilot\Settings\Settings;
use Frogrammer\ConsentPilot\Shortcodes\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Main plugin bootstrap.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Registered service objects.
	 *
	 * @var array
	 */
	private $services = array();

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation hook for plugin.
	 *
	 * @return void
	 */
	public static function activation( $network_wide ) {
		if ( is_multisite() && $network_wide ) {
			// Get site IDs
			$site_ids = get_sites( array(
				'fields' => 'ids',
				'number' => 0,
			) );
			
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::create_plugin_table();
				restore_current_blog();
			}

		} else {
			self::create_plugin_table();
		}
	}

	/**
	 * Install table for plugin.
	 *
	 * @return void
	 */
	private static function create_plugin_table() {

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		global $wpdb;
		$table = $wpdb->prefix . CONSENTPILOT_PREFIX;

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			consent_id VARCHAR(64) DEFAULT NULL,
			consent LONGTEXT NOT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent TEXT DEFAULT NULL,
			consent_date DATETIME NOT NULL,
			consent_expiry DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY consent_id (consent_id),
			KEY consent_date (consent_date),
			KEY consent_expiry (consent_expiry)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Deactivation hook for plugin.
	 *
	 * @return void
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			// Get site IDs
			$site_ids = get_sites( array(
				'fields' => 'ids',
				'number' => 0,
			) );
			
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::remove_plugin_data();
				restore_current_blog();
			}

		} else {
			self::remove_plugin_data();
		}
	}

	/**
	 * Uninstall plugin data.
	 *
	 * @return void
	 */
	private static function remove_plugin_data() {
		// Delete options
		delete_option( 'consentpilot_settings' );

		global $wpdb;
		$table = $wpdb->prefix . CONSENTPILOT_PREFIX;
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	/**
	 * Boot the plugin: register services and their hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->register_services();

		foreach ( $this->services as $service ) {
			if ( method_exists( $service, 'register_hooks' ) ) {
				$service->register_hooks();
			}
		}
	}

	/**
	 * Instantiate and store service classes.
	 *
	 * @return void
	 */
	private function register_services() {
		$this->services = array(
			new Settings(),
			new Shortcodes(),
			new Assets(),
			new PublicSite(),
			new Admin(),
			new RESTController(),
		);
	}
}
