<?php
namespace Frogrammer\ConsentPilot\Admin;

use Frogrammer\ConsentPilot\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Admin UI and menu.
 */
class Admin {

	/**
	 * Base64-encoded SVG data URL for the menu icon.
	 *
	 * @var string
	 */
	private $icon;

	/**
	 * Settings page URL.
	 *
	 * @var string
	 */
	private $settings_url;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$raw_icon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black"><path d="M21,11C21,16.55 17.16,21.74 12,23C6.84,21.74 3,16.55 3,11V5L12,1L21,5V11M12,21C15.75,20 19,15.54 19,11.22V6.3L12,3.18L5,6.3V11.22C5,15.54 8.25,20 12,21M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9" /></svg>';
		$this->icon = 'data:image/svg+xml;base64,' . base64_encode( $raw_icon ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Register WP hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_consentpilot_export', array( $this, 'export_consent' ) );
	}

	/**
	 * Add the main admin menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Consent Pilot', 'consent-pilot' ), // Page title.
			__( 'Consent Pilot', 'consent-pilot' ), // Menu title.
			'manage_options',                                 // Capability.
			CONSENTPILOT_SLUG,                                // Menu slug.
			array( $this, 'render_page' ),                    // Callback.
			$this->icon,                                      // Icon (data URL).
			65                                                // Position.
		);
	}

	/**
	 * Render the plugin's admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->settings_url = menu_page_url( CONSENTPILOT_SLUG, false );

		$tabs = array(
			'settings' => array(
				'label'  => __( 'Settings', 'consent-pilot' ),
				'url'    => $this->settings_url,
				'target' => '',
			),
			'logs'     => array(
				'label'  => __( 'Logs', 'consent-pilot' ),
				'url'    => add_query_arg( 'tab', 'logs', $this->settings_url ),
				'target' => '',
			),
			'faq'      => array(
				'label'  => __( 'FAQ', 'consent-pilot' ),
				'url'    => add_query_arg( 'tab', 'faq', $this->settings_url ),
				'target' => '',
			),
			'support'  => array(
				/* translators: Heart icon before "Support me" label. */
				'label'  => '&#10084; ' . esc_html__( 'Support me', 'consent-pilot' ),
				'url'    => CONSENTPILOT_DONATE,
				'target' => '_blank',
			),
		);

		$tab_active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$callback   = 'render_tab_' . $tab_active;
		?>
		<div class="consentpilot-header">
			<div class="consentpilot-header-title">
				<h1><?php esc_html_e( 'Consent Pilot', 'consent-pilot' ); ?></h1>
			</div>

			<nav class="consentpilot-tabs-wrapper" aria-label="<?php esc_attr_e( 'Secondary menu', 'consent-pilot' ); ?>">
				<?php foreach ( $tabs as $tab => $tab_attr ) : ?>
					<?php
					$is_active = ( $tab_active === $tab );
					$classes   = 'consentpilot-tab' . ( $is_active ? ' active' : '' );
					$aria      = $is_active ? ' aria-current="true"' : '';
					$target    = $tab_attr['target'] ? ' target="' . esc_attr( $tab_attr['target'] ) . '" rel="noopener"' : '';
					?>
					<a
						class="<?php echo esc_attr( $classes ); ?>"
						href="<?php echo esc_url( $tab_attr['url'] ); ?>"
						<?php echo $aria; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( $tab_attr['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
		if ( method_exists( $this, $callback ) ) {
			$this->{$callback}();
		}
	}

	/**
	 * Settings tab.
	 *
	 * @return void
	 */
	private function render_tab_settings() {
		echo '<div class="consentpilot-body">';
		if ( empty( Settings::$options['gtm'] ) ) : ?>
			<div class="notice notice-warning">
				<p>&#9888;&#65039; <?php esc_html_e( 'Google Tag Manager container ID is not set. Tracking codes will not load on your site until you save a valid GTM container ID.', 'consent-pilot' ); ?></p>
			</div>
		<?php
		endif;
		echo '<form action="options.php" method="post">';
		settings_fields( CONSENTPILOT_PREFIX );
		do_settings_sections( CONSENTPILOT_SLUG );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Logs tab.
	 *
	 * @return void
	 */
	private function render_tab_logs() {
		$table = new ConsentController();
		$table->prepare_items();

		// Build current view state (these values get echoed back in hidden fields).
		$page   = CONSENTPILOT_SLUG;
		$status = $table->query_args['status'];

		// Views output contains safe anchor HTML from WP_List_Table.
		$views = $table->get_views();
		$views = implode( ' | ', $views );
		?>
		<div class="wrap">
			<ul class="subsubsub">
				<?php echo wp_kses_post( $views ); ?>
			</ul>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
				<input type="hidden" name="tab" value="logs">
				<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
				<?php $table->search_box( __( 'Search Sessions', 'consent-pilot' ), 'consent_session' ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle CSV export for consents.
	 *
	 * Note: Relies on WP's admin-post.php. Consider adding nonce/cap checks where this is triggered.
	 *
	 * @return void
	 */
	public function export_consent() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export data.', 'consent-pilot' ) );
		}

		$exporter = new ConsentController();
		$exporter->export_csv();
		exit;
	}

	/**
	 * FAQ tab.
	 *
	 * @return void
	 */
	private function render_tab_faq() {
		?>
		<div class="consentpilot-body">
			<div class="consentpilot-accordion">
				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'What does this plugin do?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'This plugin ensures that Google Tag Manager (GTM) is loaded only after the visitor has provided explicit consent. It helps you stay compliant with privacy regulations (e.g., GDPR, ePrivacy Directive) while still using GTM for analytics, marketing, and tracking.', 'consent-pilot' ); ?></p>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'Why do I need to load GTM only after consent?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'Under GDPR and other privacy laws, you are required to obtain valid user consent before setting cookies or running scripts for analytics, advertising, or remarketing. This plugin makes sure GTM and all tags it manages are not triggered until consent is granted.', 'consent-pilot' ); ?></p>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'Do I need to change my GTM setup?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'Yes, a few adjustments are needed:', 'consent-pilot' ); ?></p>
						<ol>
							<li>
								<?php esc_html_e( 'Enable Consent Mode inside Google Tag Manager.', 'consent-pilot' ); ?>
								<ol>
									<li><?php esc_html_e( 'Go to Google Tag Manager → Admin → Container Settings.', 'consent-pilot' ); ?></li>
									<li><?php esc_html_e( 'Enable Consent Overview.', 'consent-pilot' ); ?></li>
								</ol>
							</li>
							<li><?php esc_html_e( 'Assign the required consent types (e.g., ad_storage, analytics_storage) for each tag in your GTM container.', 'consent-pilot' ); ?></li>
							<li><?php esc_html_e( 'Add all tracking, advertising, and third-party scripts to GTM—not directly to WordPress.', 'consent-pilot' ); ?></li>
						</ol>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'What happens if the user declines consent?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'If the user does not provide consent, GTM will not load. This means no tracking or marketing tags will run, ensuring compliance with privacy regulations.', 'consent-pilot' ); ?></p>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'Does this plugin support Google Consent Mode?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'Yes. GTM will run in Consent Mode, which adjusts how Google tags behave based on the user’s consent choices. For example, if a user declines ad storage, GTM will respect that choice and won’t set advertising cookies.', 'consent-pilot' ); ?></p>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'Do I need to add any scripts directly in WordPress after installing this plugin?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'No. To maintain compliance, all scripts that require consent should be added inside GTM. The plugin only controls when GTM itself loads.', 'consent-pilot' ); ?></p>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'What kind of consents can I manage with this plugin?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'You can manage all consent types supported by Google Consent Mode, such as:', 'consent-pilot' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'ad_storage (ads personalization and remarketing)', 'consent-pilot' ); ?></li>
							<li><?php esc_html_e( 'analytics_storage (Google Analytics cookies)', 'consent-pilot' ); ?></li>
							<li><?php esc_html_e( 'functionality_storage (site preferences)', 'consent-pilot' ); ?></li>
							<li><?php esc_html_e( 'personalization_storage (content personalization)', 'consent-pilot' ); ?></li>
							<li><?php esc_html_e( 'security_storage (fraud prevention and security)', 'consent-pilot' ); ?></li>
						</ul>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'Does this plugin block GTM completely until consent is given?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'Yes. GTM will not load or execute any tags until the user provides valid consent.', 'consent-pilot' ); ?></p>
					</div>
				</details>

				<details>
					<summary>
						<span class="dashicons dashicons-arrow-down-alt2 consentpilot-chevron" aria-hidden="true"></span>
						<h4><?php esc_html_e( 'Will this affect site performance?', 'consent-pilot' ); ?></h4>
					</summary>
					<div>
						<p><?php esc_html_e( 'The plugin is optimized for performance. GTM is only loaded once consent is granted, which may even reduce initial load until tracking is enabled.', 'consent-pilot' ); ?></p>
					</div>
				</details>
			</div>
		</div>
		<?php
	}
}
