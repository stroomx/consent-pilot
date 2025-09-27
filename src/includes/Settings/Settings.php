<?php
/**
 * Settings registration and rendering.
 *
 * @package ConsentPilot
 */

namespace Frogrammer\ConsentPilot\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Plugin settings handler.
 */
class Settings {

	/**
	 * Default option values.
	 *
	 * @var array<string, mixed>
	 */
	private static $default = array(
		'gtm'              => '',
		'log_ip'           => 1,
		'consent_notice'   => '',
		'consent_validity' => 90,
		'enable_darkmode'  => 0,
		'color_accent'     => '#3C2BFF',
		'color_url'        => '#759EFF',
	);

	/**
	 * Current option values.
	 *
	 * @var array<string, mixed>
	 */
	public static $options;

        /**
         * Initialize defaults and load saved options.
         *
         * @return void
         */
        public static function init() {
		self::$default['consent_notice'] = __( 'We use cookies to enhance your experience, analyze traffic, and personalize content. You can accept all cookies, reject non-essential ones, or manage preferences.', 'consent-pilot' );

		// Load saved options or fall back to defaults.
		self::$options = get_option( 'consentpilot_settings', self::$default );
		if ( ! is_array( self::$options ) ) {
			self::$options = self::$default;
		}
	}

        /**
         * Register WP hooks.
         *
         * @return void
         */
        public function register_hooks() {
		self::init();
		add_action( 'admin_init', array( $this, 'register' ) );
	}

        /**
         * Register settings, sections, and fields.
         *
         * @return void
         */
        public function register() {

		register_setting(
			CONSENTPILOT_PREFIX,	 // Option group.
			'consentpilot_settings', // Option name.
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::$default,
			)
		);

		add_settings_section(
			'default',        // ID.
			'',               // Title.
			'',               // Callback.
			CONSENTPILOT_SLUG // Page.
		);

		add_settings_field(
			'consentpilot_gtm',
			__( 'GTM container ID', 'consent-pilot' ),
                        /**
                         * @return void
                         */
                        function () {
				?>
				<input
					type="text"
					class="regular-text"
					name="consentpilot_settings[gtm]"
					value="<?php echo esc_attr( (string) ( self::$options['gtm'] ?? self::$default['gtm'] ) ); ?>"
					placeholder="GTM-XXXXXXX"
				/>
				<p class="description">
					<?php esc_html_e( 'Use Google Tag Manager to load all tracking scripts on your website.', 'consent-pilot' ); ?>
				</p>
				<?php
			},
			CONSENTPILOT_SLUG
		);

		add_settings_field(
			'consentpilot_log_ip',
			__( 'Log settings', 'consent-pilot' ),
                        /**
                         * @return void
                         */
                        function () {
				$checked = (int) ( self::$options['log_ip'] ?? 0 );
				?>
				<label for="consentpilot_log_ip">
					<input
						type="checkbox"
						name="consentpilot_settings[log_ip]"
						id="consentpilot_log_ip"
						value="1"
						<?php checked( 1, $checked ); ?>
					/>
					<?php esc_html_e( 'Record the IP address when users give their consent', 'consent-pilot' ); ?>
				</label>
				<?php
			},
			CONSENTPILOT_SLUG
		);

		add_settings_field(
			'consentpilot_consent_notice',
			__( 'Consent notice text', 'consent-pilot' ),
                        /**
                         * @return void
                         */
                        function () {
				$val = (string) ( self::$options['consent_notice'] ?? self::$default['consent_notice'] );
				?>
				<textarea
					class="large-text"
					name="consentpilot_settings[consent_notice]"
					rows="5"
				><?php echo esc_textarea( $val ); ?></textarea>
				<?php
			},
			CONSENTPILOT_SLUG
		);

		add_settings_field(
			'consentpilot_consent_validity',
			__( 'Consent validity', 'consent-pilot' ),
                        /**
                         * @return void
                         */
                        function () {
				?>
				<input
					type="number"
					class="small-text"
					name="consentpilot_settings[consent_validity]"
					value="<?php echo esc_attr( (string) ( self::$options['consent_validity'] ?? self::$default['consent_validity'] ) ); ?>"
					min="1"
					max="180"
					step="1"
					required
				/>
				<p class="description">
					<?php esc_html_e( 'Number of days consent is considered valid.', 'consent-pilot' ); ?>
				</p>
				<?php
			},
			CONSENTPILOT_SLUG
		);

		add_settings_field(
			'consentpilot_enable_darkmode',
			__( 'Dark mode settings', 'consent-pilot' ),
                        /**
                         * @return void
                         */
                        function () {
				$checked = (int) ( self::$options['enable_darkmode'] ?? 0 );
				?>
				<label for="consentpilot_enable_darkmode">
					<input
						type="checkbox"
						name="consentpilot_settings[enable_darkmode]"
						id="consentpilot_enable_darkmode"
						value="1"
						<?php checked( 1, $checked ); ?>
					/>
					<?php esc_html_e( "Enable dark mode when the user's browser is set to dark mode", 'consent-pilot' ); ?>
				</label>
				<?php
			},
			CONSENTPILOT_SLUG
		);

		add_settings_field(
			'consentpilot_color_accent',
			__( 'Accent color', 'consent-pilot' ),
                        /**
                         * @return void
                         */
                        function () {
				$val     = (string) ( self::$options['color_accent'] ?? self::$default['color_accent'] );
				$default = (string) self::$default['color_accent'];
				?>
				<input
					type="text"
					class="color-picker"
					name="consentpilot_settings[color_accent]"
					value="<?php echo esc_attr( $val ); ?>"
					data-default-color="<?php echo esc_attr( $default ); ?>"
				/>
				<?php
			},
			CONSENTPILOT_SLUG
		);

		add_settings_field(
			'consentpilot_color_url',
			__( 'Link color', 'consent-pilot' ),
                        /**
                         * @return void
                         */
                        function () {
				$val     = (string) ( self::$options['color_url'] ?? self::$default['color_url'] );
				$default = (string) self::$default['color_url'];
				?>
				<input
					type="text"
					class="color-picker"
					name="consentpilot_settings[color_url]"
					value="<?php echo esc_attr( $val ); ?>"
					data-default-color="<?php echo esc_attr( $default ); ?>"
				/>
				<?php
			},
			CONSENTPILOT_SLUG
		);
	}

	/**
	 * Sanitize settings prior to saving.
	 *
	 * @param mixed $input Raw input from the form.
	 * @return array<string, mixed> Sanitized values merged with defaults.
	 */
	public function sanitize_settings( $input ): array {
		$input = (array) $input;

		$out = wp_parse_args( $input, self::$default );

		// Booleans.
		$out['log_ip']          = ! empty( $input['log_ip'] ) ? 1 : 0;
		$out['enable_darkmode'] = ! empty( $input['enable_darkmode'] ) ? 1 : 0;

		// Strings.
		$out['gtm'] = isset( $input['gtm'] ) ? sanitize_text_field( (string) $input['gtm'] ) : self::$default['gtm'];

		// Textarea (no HTML).
		$out['consent_notice'] = isset( $input['consent_notice'] )
			? sanitize_textarea_field( (string) $input['consent_notice'] )
			: self::$default['consent_notice'];

		// Numbers
		$out['consent_validity'] = isset( $input['consent_validity'] )
			? absint( (int) $input['consent_validity'] )
			: self::$default['consent_validity'];
		
		// Colors (validate hex, fall back to defaults).
		$accent = isset( $input['color_accent'] ) ? sanitize_hex_color( (string) $input['color_accent'] ) : null;
		$url    = isset( $input['color_url'] ) ? sanitize_hex_color( (string) $input['color_url'] ) : null;

		$out['color_accent'] = $accent ? $accent : self::$default['color_accent'];
		$out['color_url']    = $url ? $url : self::$default['color_url'];

		return $out;
	}
}
