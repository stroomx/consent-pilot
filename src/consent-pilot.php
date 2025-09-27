<?php
/**
 * Plugin Name: Consent Pilot
 * Plugin URI: https://wordpress.org/plugins/consent-pilot
 * Description: Consent Manager for WP.
 * Version: 1.0.0
 * Author: Frogrammer
 * Author URI: https://profiles.wordpress.org/frogrammer/
 * License: GPLv2 or later
 * Text Domain: consent-pilot
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Plugin constants.
 */
define( 'CONSENTPILOT_VERSION', '1.0.0' );
define( 'CONSENTPILOT_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONSENTPILOT_URL', plugin_dir_url( __FILE__ ) );
define( 'CONSENTPILOT_DONATE', 'https://www.paypal.com/donate/?hosted_button_id=YPNHSNZH5UNC8' );
define( 'CONSENTPILOT_SLUG', 'consent-pilot' );
define( 'CONSENTPILOT_PREFIX', 'consentpilot' );

/**
 * Load the autoloader.
 */
require_once CONSENTPILOT_DIR . 'includes/Autoloader.php';
Frogrammer\ConsentPilot\Autoloader::register( 'Frogrammer\ConsentPilot\\', CONSENTPILOT_DIR . 'includes/' );

/**
 * Plugin bootstrap on 'init'.
 *
 * Ensures dependencies are available, loads text domain, then boots the plugin.
 *
 * @return void
 */
function consentpilot_init() {
	// Ensure classes and functions are available.
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	// Kick off the plugin.
	$plugin = Frogrammer\ConsentPilot\Plugin::instance();
	$plugin->run();
}
add_action( 'init', 'consentpilot_init' );

/**
 * Activation callback.
 *
 * @return void
 */
function consentpilot_activate( $network_wide ) {
	Frogrammer\ConsentPilot\Plugin::activation( $network_wide );
}
register_activation_hook( __FILE__, 'consentpilot_activate' );

/**
 * Deactivation callback.
 *
 * @return void
 */
function consentpilot_uninstall() {
	Frogrammer\ConsentPilot\Plugin::uninstall();
}
register_uninstall_hook( __FILE__, 'consentpilot_uninstall' );

/**
 * Add action links under the plugin on the Plugins screen.
 *
 * @param string[] $links Existing action links.
 * @return string[] Filtered links.
 */
function consentpilot_plugin_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . esc_url( menu_page_url( CONSENTPILOT_SLUG, false ) ) . '">' . esc_html__( 'Settings', 'consent-pilot' ) . '</a>',
		'<a href="' . esc_url( CONSENTPILOT_DONATE ) . '" target="_blank" rel="noopener">' . esc_html__( 'Support me', 'consent-pilot' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'consentpilot_plugin_action_links' );
