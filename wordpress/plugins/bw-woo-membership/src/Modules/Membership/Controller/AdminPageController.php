<?php
/**
 * Admin page "Mitglieder".
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Core\Setup;
use BW\WooMembership\Core\StorageHealth;
use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Modules\Membership\Service\MemberAdminService;
use BW\WooMembership\Modules\Membership\Service\JournalService;
use BW\WooMembership\Modules\Membership\Service\ProofService;
use BW\WooMembership\Support\Csv;

/**
 * Menu "Mitglieder" with the admin app, CSV export and access to pending proof files.
 */
final class AdminPageController {

	public const SLUG = 'bw-wm-members';

	/**
	 * Transient with the counts for the menu bubbles.
	 */
	private const COUNTS_CACHE = 'bw_wm_admin_counts';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MemberAdminService $admin,
		private readonly ProofService $proofs,
		private readonly JournalService $journal,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ), 9 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_bw_wm_export', array( $this, 'export' ) );
		add_action( 'admin_post_bw_wm_journal', array( $this, 'journal' ) );
		add_action( 'admin_post_bw_wm_proof_file', array( $this, 'proof_file' ) );
		add_action( 'admin_notices', array( $this, 'storage_notice' ) );
		add_filter( 'bw_wm_admin_menu_parent', static fn(): string => self::SLUG );
		// The menu counts change when a status or proof is written (upload, decision, sync) or a user is deleted.
		foreach ( array( 'added_user_meta', 'updated_user_meta', 'deleted_user_meta' ) as $hook ) {
			add_action( $hook, array( $this, 'forget_counts_on_meta' ), 10, 3 );
		}
		add_action( 'deleted_user', array( $this, 'forget_counts' ) );
	}

	/**
	 * Clears the cached menu counts when a membership status or a proof of a user changes.
	 *
	 * @param int|int[] $meta_id  Meta ID(s).
	 * @param int       $user_id  User ID.
	 * @param string    $meta_key Meta key.
	 */
	public function forget_counts_on_meta( $meta_id, $user_id, $meta_key ): void {
		if ( in_array( $meta_key, array( MemberRepository::META_STATUS, ProofRepository::META ), true ) ) {
			$this->forget_counts();
		}
	}

	/**
	 * Clears the cached menu counts.
	 */
	public function forget_counts(): void {
		delete_transient( self::COUNTS_CACHE );
	}

	/**
	 * Menu and submenus.
	 */
	public function menu(): void {
		$counts = get_transient( self::COUNTS_CACHE );
		if ( ! is_array( $counts ) ) {
			$counts = $this->admin->counts();
			set_transient( self::COUNTS_CACHE, $counts, 10 * MINUTE_IN_SECONDS );
		}
		$bubble = $counts['proofs'] > 0 ? ' <span class="awaiting-mod">' . (int) $counts['proofs'] . '</span>' : '';
		add_menu_page( __( 'Mitglieder', 'bw-woo-membership' ), __( 'Mitglieder', 'bw-woo-membership' ) . $bubble, Setup::CAPABILITY, self::SLUG, array( $this, 'render' ), 'dashicons-groups', 21 );
		add_submenu_page( self::SLUG, __( 'Alle Mitglieder', 'bw-woo-membership' ), __( 'Alle Mitglieder', 'bw-woo-membership' ), Setup::CAPABILITY, self::SLUG, array( $this, 'render' ) );
		add_submenu_page( self::SLUG, __( 'Nachweise prüfen', 'bw-woo-membership' ), __( 'Nachweise prüfen', 'bw-woo-membership' ) . $bubble, Setup::CAPABILITY, 'admin.php?page=' . self::SLUG . '&path=/proofs' );
	}

	/**
	 * App container.
	 */
	public function render(): void {
		if ( ! is_readable( self::asset_file() ) ) {
			printf(
				'<div class="wrap"><h1>%1$s</h1><div class="notice notice-error inline"><p>%2$s</p></div></div>',
				esc_html__( 'Mitglieder', 'bw-woo-membership' ),
				esc_html__( 'Die Verwaltungsoberfläche fehlt: Der Ordner „build“ des Plugins wurde nicht gefunden. Bitte das Plugin mit „npm install && npm run build“ bauen oder das fertige ZIP installieren.', 'bw-woo-membership' )
			);
			return;
		}
		echo '<div class="wrap"><div id="bw-wm-admin" class="bwo-app"><p>' . esc_html__( 'Lädt …', 'bw-woo-membership' ) . '</p></div></div>';
	}

	/**
	 * Asset file of the built admin app (missing when `npm run build` has not run).
	 */
	private static function asset_file(): string {
		return BW_WM_DIR . '/build/admin.asset.php';
	}

	/**
	 * App script and styles on the page only.
	 */
	public function enqueue( string $hook ): void {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		$asset_file = self::asset_file();
		if ( ! is_readable( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;
		wp_enqueue_script( 'bw-wm-admin', plugins_url( 'build/admin.js', BW_WM_FILE ), $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'bw-wm-admin', 'bw-woo-membership', BW_WM_DIR . '/languages' );
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'bw-wm-admin', plugins_url( 'assets/admin.css', BW_WM_FILE ), array( 'wp-components' ), BW_WM_VERSION );
		wp_add_inline_script(
			'bw-wm-admin',
			'window.bwWmAdmin = ' . wp_json_encode(
				array(
					'page'      => admin_url( 'admin.php?page=' . self::SLUG ),
					// add_query_arg, not wp_nonce_url: the latter escapes "&" for HTML output.
					'exportUrl' => add_query_arg( array( 'action' => 'bw_wm_export', '_wpnonce' => wp_create_nonce( 'bw_wm_export' ) ), admin_url( 'admin-post.php' ) ),
					'journalUrl' => add_query_arg( array( 'action' => 'bw_wm_journal', '_wpnonce' => wp_create_nonce( 'bw_wm_journal' ) ), admin_url( 'admin-post.php' ) ),
					'proofUrl'  => add_query_arg( array( 'action' => 'bw_wm_proof_file', '_wpnonce' => wp_create_nonce( 'bw_wm_proof_file' ) ), admin_url( 'admin-post.php' ) ),
					'docsUrl'   => admin_url( 'edit.php?post_type=bw_member_doc' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * CSV export of all members (UTF-8 with BOM for Excel, semicolon separated).
	 */
	public function export(): void {
		if ( ! current_user_can( Setup::CAPABILITY ) || ! check_admin_referer( 'bw_wm_export' ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="appa-mitglieder-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- streaming the export.
		foreach ( $this->admin->csv() as $row ) {
			fputcsv( $out, array_map( array( Csv::class, 'safe' ), $row ), ';' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Fee journal of any period for the bookkeeping (CSV, UTF-8 with BOM, semicolon separated).
	 */
	public function journal(): void {
		if ( ! current_user_can( Setup::CAPABILITY ) || ! check_admin_referer( 'bw_wm_journal' ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		$from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$export = $this->journal->export( $from, $to );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="appa-beitragsjournal-' . $export['label'] . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- streaming the export.
		foreach ( $export['rows'] as $row ) {
			// Amounts (also negative ones) stay numbers for the spreadsheet; only text is protected against formulas.
			$row = array_map( static fn( string $cell ): string => 1 === preg_match( '/^-?\d+(,\d{2})?$/', $cell ) ? $cell : Csv::safe( $cell ), $row );
			fputcsv( $out, $row, ';' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Warns on the members page when proofs or documents can be opened directly (cached Site Health check).
	 */
	public function storage_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_' . self::SLUG === $screen->id ) {
			( new StorageHealth() )->render_notice();
		}
	}

	/**
	 * Shows a pending proof file to the office.
	 */
	public function proof_file(): void {
		if ( ! current_user_can( Setup::CAPABILITY ) || ! check_admin_referer( 'bw_wm_proof_file' ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		$file = $this->proofs->file( isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0 );
		if ( '' === $file ) {
			wp_die( esc_html__( 'Kein Nachweis vorhanden (nach der Prüfung wird er gelöscht).', 'bw-woo-membership' ), '', array( 'response' => 404 ) );
		}
		$type = wp_check_filetype( $file );
		nocache_headers();
		header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
		header( 'Content-Disposition: inline; filename="studiennachweis.' . $type['ext'] . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- protected file.
		exit;
	}
}
