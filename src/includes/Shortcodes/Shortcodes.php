<?php
/**
 * Shortcodes for ConsentPilot.
 *
 * @package ConsentPilot
 */

namespace Frogrammer\ConsentPilot\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Class Shortcodes
 *
 * Registers and handles plugin shortcodes.
 */
class Shortcodes {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
        public function register_hooks() {
		add_shortcode( 'consentpilot-preferences', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Content enclosed within shortcode (if any).
	 * @return string
	 */
	public function render( array $atts = [], string $content = '' ): string {
		$link_text = esc_html__( 'Consent Preferences', 'consent-pilot' );

		return sprintf(
			'<a class="consentpilot-preferences" href="#" aria-haspopup="dialog" aria-controls="modal">%s</a>',
			$link_text
		);
	}
}
