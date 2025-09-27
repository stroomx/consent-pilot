<?php
/**
 * Assets loader.
 *
 * @package ConsentPilot
 */

namespace Frogrammer\ConsentPilot\Assets;

use Frogrammer\ConsentPilot\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Handles admin and public assets.
 */
class Assets {

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ) );
	}

	/**
	 * Enqueue admin assets for the plugin settings screen.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function admin_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_consent-pilot' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'consentpilot-admin-style',
			CONSENTPILOT_URL . 'assets/admin.css',
			array(),
			CONSENTPILOT_VERSION
		);

		wp_enqueue_script(
			'consentpilot-admin-script',
			CONSENTPILOT_URL . 'assets/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			CONSENTPILOT_VERSION,
			true
		);
	}

	/**
	 * Enqueue public assets and inject dynamic styles and data.
	 */
	public function public_assets(): void {
		$custom_css = '
		.consentpilot {
			--accent: ' . esc_html( Settings::$options['color_accent'] ) . ';
			--url-text: ' . esc_html( Settings::$options['color_url'] ) . ';
		}
		';
		if ( Settings::$options['enable_darkmode'] ) {
			$custom_css .= '
			@media (prefers-color-scheme: dark) {
				.consentpilot {
					--background: #383838;
					--text: #ffffff;
					--muted: #e7e7e7;
					--button-background: #5f5f5f;
					--button-text: #f2f4f8;
					--options-background: #484848;
					--options-hover: #4f4f4f;
					--success-background: #0a7c20;
					--success-text: #e8f7ec;
				}
			}
			';
		}

		wp_enqueue_style(
			'consentpilot-style',
			CONSENTPILOT_URL . 'assets/public.css',
			array(),
			CONSENTPILOT_VERSION
		);

		// Add dynamic CSS variables after the stylesheet so they can be overridden by theme if needed.
		wp_add_inline_style( 'consentpilot-style', $custom_css );

		wp_enqueue_script(
			'consentpilot-script',
			CONSENTPILOT_URL . 'assets/public.js',
			array( 'jquery' ),
			CONSENTPILOT_VERSION,
			true
		);

		$script_data = array(
			'siteurl' => get_bloginfo( 'url' ),
			'gtm'     => esc_html( Settings::$options['gtm'] ),
		);

		// Prefer wp_add_inline_script + wp_json_encode for typed data without localization quirks.
		wp_add_inline_script(
			'consentpilot-script',
			'const consentpilot = ' . wp_json_encode( $script_data ) . ';',
			'before'
		);
	}
}
