<?php
/**
 * Contact Form 7 integration.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Controller;

use BW\Appa\Modules\Forms\Repository\FormRepository;
use BW\Appa\Modules\Forms\Repository\RateLimitRepository;
use BW\Appa\Modules\Forms\Repository\SettingsRepository;
use BW\Appa\Modules\Forms\Service\FormToken;
use BW\Appa\Modules\Forms\Service\MailSender;
use BW\Appa\Modules\Forms\Service\SpamGuard;
use WPCF7_ContactForm;
use WPCF7_Submission;

/**
 * Adds honeypot, fill-time token and rate limit to all CF7 forms and routes the APPA forms to the configured recipient.
 * Cloudflare Turnstile comes from CF7's own integration (Contact → Integration).
 */
final class Cf7Controller {

	private const FIELD_TOKEN    = '_bw_appa_token';
	private const FIELD_HONEYPOT = 'bw_appa_website';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly FormRepository $forms,
		private readonly SettingsRepository $settings,
		private readonly RateLimitRepository $rate_limit,
		private readonly FormToken $token,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this->forms, 'ensure_all' ) );
		add_action( 'admin_notices', array( $this, 'missing_cf7_notice' ) );
		add_filter( 'wpcf7_form_hidden_fields', array( $this, 'hidden_fields' ) );
		add_filter( 'wpcf7_form_elements', array( $this, 'add_honeypot' ) );
		add_filter( 'wpcf7_spam', array( $this, 'check_spam' ), 10, 2 );
		add_filter( 'wpcf7_mail_components', array( $this, 'route_mail' ), 10, 3 );
		add_filter( 'wpcf7_form_tag', array( $this, 'prefill_subject' ) );
	}

	/**
	 * The subject can be prefilled from the address (e.g. /kontakt/?betreff=…), also in forms created earlier.
	 *
	 * @param array<string, mixed>|object $tag Form tag.
	 * @return array<string, mixed>|object
	 */
	public function prefill_subject( $tag ) {
		$name    = is_object( $tag ) ? ( $tag->name ?? '' ) : ( $tag['name'] ?? '' );
		$options = is_object( $tag ) ? (array) ( $tag->options ?? array() ) : (array) ( $tag['options'] ?? array() );
		if ( 'betreff' !== $name || in_array( 'default:get', $options, true ) ) {
			return $tag;
		}
		$options[] = 'default:get';
		if ( is_object( $tag ) ) {
			$tag->options = $options;
		} else {
			$tag['options'] = $options;
		}
		return $tag;
	}

	/**
	 * Hint in the admin when Contact Form 7 is not active.
	 */
	public function missing_cf7_notice(): void {
		if ( class_exists( WPCF7_ContactForm::class ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'APPA Funktionen: Für die Formulare wird das Plugin „Contact Form 7“ benötigt.', 'bw-appa' )
		);
	}

	/**
	 * Adds the signed render time.
	 *
	 * @param array<string, string> $fields Hidden fields.
	 * @return array<string, string>
	 */
	public function hidden_fields( array $fields ): array {
		$fields[ self::FIELD_TOKEN ] = $this->token->create( time() );
		return $fields;
	}

	/**
	 * Adds an invisible field that only bots fill in.
	 */
	public function add_honeypot( string $html ): string {
		return $html . sprintf(
			'<p class="bwa-form__hp" aria-hidden="true"><label>%1$s <input type="text" name="%2$s" value="" tabindex="-1" autocomplete="off"></label></p>',
			esc_html__( 'Bitte leer lassen', 'bw-appa' ),
			esc_attr( self::FIELD_HONEYPOT )
		);
	}

	/**
	 * Marks a submission as spam when a check fails; CF7 then shows its spam message and sends nothing.
	 */
	public function check_spam( bool $spam, WPCF7_Submission $submission ): bool {
		if ( $spam ) {
			return true;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- CF7 verifies the request; these are our own extra fields.
		$honeypot = isset( $_POST[ self::FIELD_HONEYPOT ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_HONEYPOT ] ) ) : '';
		$token    = isset( $_POST[ self::FIELD_TOKEN ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_TOKEN ] ) ) : '';
		// phpcs:enable

		$client = (string) $submission->get_meta( 'remote_ip' );
		$texts  = array_filter( array_map( static fn( mixed $v ): string => is_array( $v ) ? implode( ' ', $v ) : (string) $v, $submission->get_posted_data() ) );
		$config = $this->settings->get();
		$guard  = new SpamGuard( $config['min_seconds'], $config['max_links'], $config['max_per_hour'], $this->settings->blocked_words() );

		$reason = $guard->reason( $honeypot, $this->token->time( $token ), time(), $texts, $this->rate_limit->count( $client ) );
		$this->rate_limit->increment( $client );

		if ( null === $reason ) {
			return false;
		}
		$submission->add_spam_log(
			array(
				'agent'  => 'bw-appa',
				'reason' => $reason,
			)
		);
		return true;
	}

	/**
	 * Sends the APPA forms to the configured recipient and makes every CF7 sender name safe for mail().
	 *
	 * @param array<string, mixed> $components Mail components.
	 * @return array<string, mixed>
	 */
	public function route_mail( array $components, WPCF7_ContactForm $form, object $mail ): array {
		if ( isset( $components['sender'] ) && is_string( $components['sender'] ) ) {
			$components['sender'] = MailSender::clean( $components['sender'] );
		}
		if ( 'mail' !== ( method_exists( $mail, 'name' ) ? $mail->name() : '' ) ) {
			return $components;
		}
		$ours = array_map( 'intval', (array) get_option( FormRepository::OPTION, array() ) );
		if ( in_array( $form->id(), $ours, true ) ) {
			$components['recipient'] = $this->settings->get()['recipient'];
		}
		return $components;
	}
}
