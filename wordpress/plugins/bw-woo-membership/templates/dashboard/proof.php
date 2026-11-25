<?php
/**
 * Student proof: status and upload form.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by ProofController::render().
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<section class="bwm-section bwm-proof" id="nachweis" aria-labelledby="bwm-proof">
	<div class="bwm-section__head">
		<h2 id="bwm-proof"><?php esc_html_e( 'Studiennachweis', 'bw-woo-membership' ); ?></h2>
		<span class="bwm-pill bwm-pill--<?php echo esc_attr( $data['status'] ); ?>"><?php echo esc_html( $data['label'] ); ?></span>
	</div>
	<p><?php echo esc_html( $data['text'] ); ?></p>
	<?php if ( '' !== $data['message'] ) : ?>
		<p class="bwm-saved<?php echo $data['error'] ? ' bwm-saved--error' : ''; ?>" role="status"><?php echo esc_html( $data['message'] ); ?></p>
	<?php endif; ?>
	<?php if ( $data['can'] ) : ?>
		<form class="bwm-form bwm-proof__form" method="post" action="<?php echo esc_url( $data['action'] ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="bw_wm_proof">
			<?php wp_nonce_field( 'bw_wm_proof' ); ?>
			<p><label for="bwm-proof-file"><?php esc_html_e( 'Inskriptionsbestätigung (PDF, JPG oder PNG, höchstens 5 MB)', 'bw-woo-membership' ); ?></label><input id="bwm-proof-file" type="file" name="bw_wm_proof" accept=".pdf,.jpg,.jpeg,.png" required></p>
			<p><button class="bwm-button" type="submit"><?php esc_html_e( 'Nachweis hochladen', 'bw-woo-membership' ); ?></button></p>
			<p class="bwm-sub"><?php esc_html_e( 'Wir löschen die Datei, sobald wir sie geprüft haben.', 'bw-woo-membership' ); ?></p>
		</form>
	<?php endif; ?>
</section>
