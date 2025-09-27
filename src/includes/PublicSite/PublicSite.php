<?php
/**
 * Public-facing UI for ConsentPilot.
 *
 * @package ConsentPilot
 */

namespace Frogrammer\ConsentPilot\PublicSite;

use Frogrammer\ConsentPilot\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Class PublicSite
 *
 * Outputs the consent banner and modal on the public site.
 */
class PublicSite {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
        public function register_hooks() {
		// Add public-facing hooks as needed.
		if ( ! is_admin() ) {
			add_action( 'wp_footer', array( $this, 'consent_notice' ) );
		}
	}

	/**
	 * Echoes the consent banner and modal markup in the footer.
	 *
	 * @return void
	 */
        public function consent_notice() {
		$privacy_policy = get_privacy_policy_url();
		?>
		<!-- ===== Banner ===== -->
		<section
			id="consentpilot-banner"
			class="consentpilot"
			aria-label="<?php esc_attr_e( 'Cookie consent banner', 'consent-pilot' ); ?>"
			aria-describedby="consentpilot-banner-text"
		>
			<div class="container">
				<div class="banner">
					<div id="consentpilot-banner-text">
						<?php echo esc_html( Settings::$options['consent_notice'] ); ?>
					</div>

					<div class="actions" aria-label="<?php esc_attr_e( 'Cookie consent actions', 'consent-pilot' ); ?>">
						<button class="button" type="button" value="essential">
							<?php esc_html_e( 'Reject non-essential', 'consent-pilot' ); ?>
						</button>
						<button class="button" type="button" aria-haspopup="dialog" aria-controls="modal">
							<?php esc_html_e( 'Let me choose', 'consent-pilot' ); ?>
						</button>
						<button class="button button-primary" type="button" value="granted">
							<?php esc_html_e( 'Accept all', 'consent-pilot' ); ?>
						</button>
					</div>
				</div>
			</div>
		</section>

		<!-- ===== Modal ===== -->
		<div id="consentpilot-modal" class="consentpilot" aria-hidden="true">
			<div
				class="modal-card"
				role="dialog"
				aria-modal="true"
				aria-labelledby="consentpilot-modal-title"
				aria-describedby="consentpilot-modal-desc"
			>
				<div class="modal-header">
					<h2 id="consentpilot-modal-title"><?php esc_html_e( 'We value your privacy', 'consent-pilot' ); ?></h2>
				</div>

				<div class="modal-body" id="consentpilot-modal-desc">
					<p><?php echo esc_html( Settings::$options['consent_notice'] ); ?></p>
					<div class="modal-prefs" id="pref-panel">
						<div class="modal-row">
							<div>
								<strong><?php esc_html_e( 'Strictly necessary', 'consent-pilot' ); ?></strong>
								<span class="badge" aria-hidden="true"><?php esc_html_e( 'Always on', 'consent-pilot' ); ?></span>
								<small><?php esc_html_e( 'Required for core site functionality. Cannot be disabled.', 'consent-pilot' ); ?></small>
							</div>
							<label class="toggle" aria-label="<?php echo esc_attr__( 'Strictly necessary cookies', 'consent-pilot' ); ?>">
								<input type="checkbox" id="consentpilot-necessary" checked disabled />
							</label>
						</div>

						<div class="modal-row">
							<div>
								<strong><?php esc_html_e( 'Preferences', 'consent-pilot' ); ?></strong>
								<small><?php esc_html_e( 'Remember choices like language or region.', 'consent-pilot' ); ?></small>
							</div>
							<label class="toggle" aria-label="<?php echo esc_attr__( 'Enable preference cookies', 'consent-pilot' ); ?>">
								<input type="checkbox" id="consentpilot-preferences" />
							</label>
						</div>

						<div class="modal-row">
							<div>
								<strong><?php esc_html_e( 'Analytics', 'consent-pilot' ); ?></strong>
								<small><?php esc_html_e( 'Help us understand how our site is used.', 'consent-pilot' ); ?></small>
							</div>
							<label class="toggle" aria-label="<?php echo esc_attr__( 'Enable analytics cookies', 'consent-pilot' ); ?>">
								<input type="checkbox" id="consentpilot-analytics" />
							</label>
						</div>

						<div class="modal-row">
							<div>
								<strong><?php esc_html_e( 'Marketing', 'consent-pilot' ); ?></strong>
								<small><?php esc_html_e( 'Personalized content and advertising.', 'consent-pilot' ); ?></small>
							</div>
							<label class="toggle" aria-label="<?php echo esc_attr__( 'Enable marketing cookies', 'consent-pilot' ); ?>">
								<input type="checkbox" id="consentpilot-marketing" />
							</label>
						</div>
					</div>
				</div>

				<div class="actions">
					<button class="button" value="essential" type="button">
						<?php esc_html_e( 'Reject non-essential', 'consent-pilot' ); ?>
					</button>
					<button class="button" value="save" type="button">
						<?php esc_html_e( 'Save preferences', 'consent-pilot' ); ?>
					</button>
					<button class="button button-primary" value="granted" type="button">
						<?php esc_html_e( 'Accept all', 'consent-pilot' ); ?>
					</button>
				</div>

				<?php if ( ! empty( $privacy_policy ) ) : ?>
					<div class="modal-footer">
						<p class="small">
							<?php
							printf(
								// translators: 1: opening anchor tag to privacy policy, 2: closing anchor tag.
								esc_html__( 'Read our %1$sPrivacy Policy%2$s.', 'consent-pilot' ),
								'<a href="' . esc_url( $privacy_policy ) . '" target="_blank" rel="noopener">',
								'</a>'
							);
							?>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
