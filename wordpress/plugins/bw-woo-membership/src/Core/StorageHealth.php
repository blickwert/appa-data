<?php
/**
 * Site Health test for protected files.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Core;

use BW\WooMembership\Support\PrivatePath;

/**
 * Checks whether the folders with student proofs and member documents can be opened over HTTP (nginx ignores
 * .htaccess). A probe file with a random name is written, requested and deleted again. The result is cached for the
 * notice on the members page.
 */
final class StorageHealth {

	public const TEST      = 'bw_wm_private_files';
	public const TRANSIENT = 'bw_wm_storage_health';

	/**
	 * nginx rule that closes the old folders below uploads.
	 */
	public const NGINX_RULE = 'location ~* /wp-content/uploads/(bw-member-docs|bw-member-proofs)/ { deny all; return 404; }';

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'site_status_tests', array( $this, 'add_test' ) );
	}

	/**
	 * Adds the direct test to Site Health.
	 *
	 * @param array<string, array<string, mixed>> $tests Tests.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_test( array $tests ): array {
		$tests['direct'][ self::TEST ] = array(
			'label' => __( 'Studiennachweise und Mitglieder-Dokumente sind geschützt', 'bw-woo-membership' ),
			'test'  => array( $this, 'site_health_test' ),
		);
		return $tests;
	}

	/**
	 * Result in the format of Site Health.
	 *
	 * @return array<string, mixed>
	 */
	public function site_health_test(): array {
		$check  = $this->run();
		$result = array(
			'label'       => __( 'Studiennachweise und Mitglieder-Dokumente sind geschützt', 'bw-woo-membership' ),
			'status'      => $check['status'],
			'badge'       => array(
				'label' => __( 'Sicherheit', 'bw-woo-membership' ),
				'color' => 'critical' === $check['status'] ? 'red' : 'blue',
			),
			'description' => '<p>' . esc_html( $this->summary( $check ) ) . '</p>',
			'actions'     => '',
			'test'        => self::TEST,
		);
		if ( 'critical' === $check['status'] ) {
			$result['label']        = __( 'Studiennachweise oder Mitglieder-Dokumente sind direkt abrufbar', 'bw-woo-membership' );
			$result['description'] .= $this->fix_html( $check['reachable'] );
		} elseif ( 'recommended' === $check['status'] ) {
			$result['label'] = __( 'Schutz der Studiennachweise und Mitglieder-Dokumente konnte nicht geprüft werden', 'bw-woo-membership' );
		}
		return $result;
	}

	/**
	 * Cached result (runs the check when nothing is cached).
	 *
	 * @return array{status: string, reachable: string[], unknown: string[], checked: string[]}
	 */
	public function cached(): array {
		$cached = get_transient( self::TRANSIENT );
		return is_array( $cached ) && isset( $cached['status'], $cached['reachable'], $cached['unknown'], $cached['checked'] ) ? $cached : $this->run();
	}

	/**
	 * Admin notice for the members page when files are reachable.
	 */
	public function render_notice(): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( Setup::CAPABILITY ) ) {
			return;
		}
		$check = $this->cached();
		if ( 'critical' !== $check['status'] ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Studiennachweise oder Mitglieder-Dokumente sind ohne Anmeldung abrufbar.', 'bw-woo-membership' ) . '</strong> ' . esc_html( $this->summary( $check ) ) . '</p>' . $this->fix_html( $check['reachable'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fix_html() escapes.
	}

	/**
	 * Probes every existing folder and caches the result for 12 hours.
	 *
	 * @return array{status: string, reachable: string[], unknown: string[], checked: string[]}
	 */
	public function run(): array {
		$reachable = array();
		$unknown   = array();
		$checked   = array();
		foreach ( $this->folders() as $folder ) {
			$url = $this->public_url( $folder );
			if ( '' === $url ) {
				continue;
			}
			$checked[] = $url;
			$probe     = $this->probe( $folder, $url );
			if ( 'reachable' === $probe ) {
				$reachable[] = $url;
			} elseif ( 'unknown' === $probe ) {
				$unknown[] = $url;
			}
		}
		if ( '' !== PrivateStorage::configured() && '' === PrivateStorage::base() ) {
			// Configured but not writable: new files silently fall back to uploads.
			$unknown[] = PrivateStorage::configured();
		}
		$status = 'good';
		if ( array() !== $reachable ) {
			$status = 'critical';
		} elseif ( array() !== $unknown ) {
			$status = 'recommended';
		}
		$result = array( 'status' => $status, 'reachable' => $reachable, 'unknown' => $unknown, 'checked' => $checked );
		set_transient( self::TRANSIENT, $result, 12 * HOUR_IN_SECONDS );
		return $result;
	}

	/**
	 * Folders to check: the current ones and old folders below uploads that still hold files.
	 *
	 * @return string[]
	 */
	private function folders(): array {
		$current = array( PrivateStorage::dir( PrivatePath::PROOFS ) );
		$docs    = PrivatePath::folder( PrivatePath::DOCS, PrivateStorage::base(), PrivateStorage::uploads_basedir() );
		if ( is_dir( $docs ) ) {
			$current[] = $docs;
		}
		$folders = array();
		foreach ( array_unique( array_merge( $current, PrivateStorage::folders( PrivatePath::PROOFS ), PrivateStorage::folders( PrivatePath::DOCS ) ) ) as $folder ) {
			if ( in_array( $folder, $current, true ) || $this->holds_files( $folder ) ) {
				$folders[] = $folder;
			}
		}
		return $folders;
	}

	/**
	 * Whether a folder holds more than the deny rules and the empty index.
	 */
	private function holds_files( string $folder ): bool {
		foreach ( (array) scandir( $folder ) as $name ) {
			if ( ! in_array( $name, array( '.', '..', '.htaccess', 'index.html' ), true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Public address of a folder ('' when it lies outside uploads and outside the WordPress folder).
	 */
	private function public_url( string $folder ): string {
		$uploads  = wp_upload_dir( null, false );
		$relative = PrivatePath::relative( $folder, (string) $uploads['basedir'] );
		if ( null !== $relative ) {
			return trailingslashit( (string) $uploads['baseurl'] ) . $relative;
		}
		$relative = PrivatePath::relative( $folder, ABSPATH );
		return null === $relative ? '' : site_url( '/' . $relative );
	}

	/**
	 * Writes a probe file, requests it and deletes it: 'reachable', 'closed' or 'unknown' (not writable, no answer).
	 */
	private function probe( string $folder, string $url ): string {
		$token = wp_generate_password( 32, false );
		$name  = 'bw-probe-' . strtolower( wp_generate_password( 16, false ) ) . '.txt';
		$path  = $folder . '/' . $name;
		if ( false === file_put_contents( $path, $token ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- tiny probe file, deleted right after.
			return 'unknown';
		}
		$response = wp_remote_get(
			trailingslashit( $url ) . $name,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				/** This filter is documented in wp-includes/class-wp-http-streams.php */
				'sslverify'   => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
			)
		);
		wp_delete_file( $path );
		if ( is_wp_error( $response ) ) {
			return 'unknown';
		}
		return 200 === (int) wp_remote_retrieve_response_code( $response ) && str_contains( (string) wp_remote_retrieve_body( $response ), $token ) ? 'reachable' : 'closed';
	}

	/**
	 * One sentence about the result.
	 *
	 * @param array{status: string, reachable: string[], unknown: string[], checked: string[]} $check Result.
	 */
	private function summary( array $check ): string {
		if ( 'critical' === $check['status'] ) {
			return __( 'Eine Testdatei in den geschützten Ordnern war über ihre Adresse abrufbar. Der Server beachtet die .htaccess-Sperre nicht (z. B. nginx). Wer die Adresse einer Datei kennt, kann Studiennachweise oder Dokumente ohne Anmeldung öffnen.', 'bw-woo-membership' );
		}
		if ( 'recommended' === $check['status'] ) {
			return __( 'Die Testdatei konnte nicht geschrieben oder nicht abgefragt werden, oder der Ordner aus BW_WM_PRIVATE_DIR ist nicht beschreibbar (dann landen neue Dateien unter uploads). Bitte Ordner und Sperre auf dem Server prüfen.', 'bw-woo-membership' );
		}
		if ( array() === $check['checked'] ) {
			return __( 'Die Dateien liegen außerhalb des Web-Roots und sind nur über die geprüften Downloads erreichbar.', 'bw-woo-membership' );
		}
		return __( 'Die geschützten Ordner liegen im Web-Root, eine Testdatei war aber nicht direkt abrufbar.', 'bw-woo-membership' );
	}

	/**
	 * Escaped instructions with the reachable folders and the nginx rule.
	 *
	 * @param string[] $reachable Reachable folder addresses.
	 */
	private function fix_html( array $reachable ): string {
		$items = '';
		foreach ( $reachable as $url ) {
			$items .= '<li><code>' . esc_html( $url ) . '/</code></li>';
		}
		return '<ul>' . $items . '</ul>'
			. '<p>' . esc_html__( 'Abhilfe, eine der beiden Möglichkeiten:', 'bw-woo-membership' ) . '</p>'
			. '<ol><li>' . esc_html__( 'Regel in der nginx-Konfiguration der Website ergänzen (im server-Block, vor der Regel für PHP) und nginx neu laden:', 'bw-woo-membership' )
			. '<pre><code>' . esc_html( self::NGINX_RULE ) . '</code></pre></li>'
			. '<li>' . esc_html__( 'Oder einen Ordner außerhalb des Web-Roots in der wp-config.php eintragen (define( \'BW_WM_PRIVATE_DIR\', \'/pfad/ausserhalb/private\' );) und vorhandene Dateien laut README des Plugins verschieben.', 'bw-woo-membership' ) . '</li></ol>';
	}
}
