<?php
/**
 * Profile panel: directory visibility and profile fields.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by DashboardModel::profile().
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$bw_wm_fields = $data['fields'];
?>
<section class="bwm-section bwm-profile" id="profil" aria-labelledby="bwm-profile">
	<?php if ( $data['saved'] ) : ?>
		<p class="bwm-saved" role="status"><?php esc_html_e( 'Gespeichert.', 'bw-woo-membership' ); ?></p>
	<?php endif; ?>
	<div class="bwm-profile__row">
		<div>
			<h2 id="bwm-profile"><?php esc_html_e( 'Mein Profil', 'bw-woo-membership' ); ?></h2>
			<p class="bwm-sub"><?php esc_html_e( 'Deine Angaben und Sichtbarkeit im öffentlichen Mitgliederverzeichnis.', 'bw-woo-membership' ); ?></p>
		</div>
	</div>

	<details class="bwm-profile__edit">
		<summary class="bwm-button"><?php esc_html_e( 'Profil bearbeiten', 'bw-woo-membership' ); ?></summary>
		<form class="bwm-form" method="post" action="<?php echo esc_url( $data['action'] ); ?>">
			<input type="hidden" name="action" value="bw_wm_profile">
			<?php wp_nonce_field( 'bw_wm_profile' ); ?>
			<p><label for="bwm-title"><?php esc_html_e( 'Titel / akademischer Grad', 'bw-woo-membership' ); ?></label><input id="bwm-title" type="text" name="bw_wm[title]" value="<?php echo esc_attr( $bw_wm_fields['title'] ); ?>" maxlength="80"></p>
			<p><label for="bwm-website"><?php esc_html_e( 'Website', 'bw-woo-membership' ); ?></label><input id="bwm-website" type="url" name="bw_wm[website]" value="<?php echo esc_attr( $bw_wm_fields['website'] ); ?>" placeholder="https://"></p>
			<p><label for="bwm-focus"><?php esc_html_e( 'Fachliche Schwerpunkte', 'bw-woo-membership' ); ?></label><input id="bwm-focus" type="text" name="bw_wm[focus]" value="<?php echo esc_attr( $bw_wm_fields['focus'] ); ?>" maxlength="200"></p>
			<fieldset>
				<legend><?php esc_html_e( 'Fachgruppen', 'bw-woo-membership' ); ?></legend>
				<?php foreach ( $data['groups'] as $bw_wm_key => $bw_wm_label ) : ?>
					<label class="bwm-check"><input type="checkbox" name="bw_wm[groups][]" value="<?php echo esc_attr( $bw_wm_key ); ?>"<?php checked( in_array( $bw_wm_key, $bw_wm_fields['groups'], true ) ); ?>> <?php echo esc_html( $bw_wm_label ); ?></label>
				<?php endforeach; ?>
			</fieldset>
			<p><label for="bwm-bio"><?php esc_html_e( 'Kurzprofil', 'bw-woo-membership' ); ?></label><textarea id="bwm-bio" name="bw_wm[bio]" rows="5" maxlength="1200"><?php echo esc_textarea( $bw_wm_fields['bio'] ); ?></textarea></p>
			<p><button class="bwm-button" type="submit"><?php esc_html_e( 'Profil speichern', 'bw-woo-membership' ); ?></button></p>
		</form>
	</details>

	<hr class="bwm-separator">

	<form class="bwm-profile__row bwm-directory" method="post" action="<?php echo esc_url( $data['action'] ); ?>">
		<input type="hidden" name="action" value="bw_wm_profile">
		<input type="hidden" name="bw_wm_part" value="directory">
		<?php wp_nonce_field( 'bw_wm_profile' ); ?>
		<div>
			<h3><label for="bwm-directory"><?php esc_html_e( 'Im öffentlichen Verzeichnis sichtbar', 'bw-woo-membership' ); ?></label></h3>
			<p class="bwm-sub"><?php esc_html_e( 'Mit deiner Zustimmung können Interessierte und Fachkolleg:innen dich finden. Du kannst sie jederzeit widerrufen.', 'bw-woo-membership' ); ?></p>
		</div>
		<p class="bwm-directory__controls">
			<input class="bwm-switch" id="bwm-directory" type="checkbox" name="bw_wm_directory" value="1"<?php checked( $data['visible'] ); ?><?php disabled( ! $data['active'] ); ?>>
			<button class="bwm-button bwm-button--outline" type="submit"<?php disabled( ! $data['active'] ); ?>><?php esc_html_e( 'Speichern', 'bw-woo-membership' ); ?></button>
		</p>
	</form>
</section>
