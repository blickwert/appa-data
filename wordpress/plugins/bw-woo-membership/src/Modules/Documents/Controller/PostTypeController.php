<?php
/**
 * Post type "Mitglieder-Dokumente".
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents\Controller;

use BW\WooMembership\Modules\Documents\Repository\DocumentRepository;
use BW\WooMembership\Modules\Documents\Service\DocumentFormatter;

/**
 * Registers the document post type and category, the box for file and visibility, and the admin list columns.
 */
final class PostTypeController {

	private const NONCE = 'bw_wm_doc_box';

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly DocumentRepository $documents ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes_' . DocumentRepository::POST_TYPE, array( $this, 'add_box' ) );
		add_action( 'save_post_' . DocumentRepository::POST_TYPE, array( $this, 'save_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'manage_' . DocumentRepository::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . DocumentRepository::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
	}

	/**
	 * Post type (not public: files are only served through the download check) and category.
	 */
	public function register_post_type(): void {
		register_post_type(
			DocumentRepository::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Mitglieder-Dokumente', 'bw-woo-membership' ),
					'singular_name' => __( 'Mitglieder-Dokument', 'bw-woo-membership' ),
					'add_new'       => __( 'Dokument hinzufügen', 'bw-woo-membership' ),
					'add_new_item'  => __( 'Dokument hinzufügen', 'bw-woo-membership' ),
					'edit_item'     => __( 'Dokument bearbeiten', 'bw-woo-membership' ),
					'all_items'     => __( 'Alle Dokumente', 'bw-woo-membership' ),
					'not_found'     => __( 'Keine Dokumente gefunden.', 'bw-woo-membership' ),
					'menu_name'     => __( 'Mitglieder-Dokumente', 'bw-woo-membership' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => true,
				'menu_position'   => 21,
				'menu_icon'       => 'dashicons-media-document',
				'supports'        => array( 'title', 'excerpt', 'revisions' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
		register_taxonomy(
			DocumentRepository::TAXONOMY,
			DocumentRepository::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Kategorien', 'bw-woo-membership' ),
					'singular_name' => __( 'Kategorie', 'bw-woo-membership' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
			)
		);
	}

	/**
	 * Box "Datei und Sichtbarkeit".
	 */
	public function add_box(): void {
		add_meta_box( 'bw-wm-doc', __( 'Datei und Sichtbarkeit', 'bw-woo-membership' ), array( $this, 'render_box' ), DocumentRepository::POST_TYPE, 'side', 'high' );
	}

	/**
	 * Renders the box.
	 */
	public function render_box( \WP_Post $post ): void {
		$file_id = $this->documents->file_id( $post->ID );
		$file    = $file_id ? (string) get_attached_file( $file_id ) : '';
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<p>
			<input type="hidden" id="bw-wm-doc-file" name="bw_wm_doc_file" value="<?php echo esc_attr( (string) $file_id ); ?>">
			<span id="bw-wm-doc-name"><?php echo esc_html( '' !== $file ? basename( $file ) . ' (' . DocumentFormatter::label( $file, (int) @filesize( $file ) ) . ')' : __( 'Noch keine Datei.', 'bw-woo-membership' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- missing file just shows no size. ?></span>
		</p>
		<p><button type="button" class="button" id="bw-wm-doc-pick"><?php esc_html_e( 'Datei wählen oder hochladen', 'bw-woo-membership' ); ?></button></p>
		<p class="description"><?php esc_html_e( 'Wird geschützt gespeichert; Mitglieder laden sie nur angemeldet herunter.', 'bw-woo-membership' ); ?></p>
		<p>
			<label for="bw-wm-doc-visibility"><strong><?php esc_html_e( 'Sichtbar für', 'bw-woo-membership' ); ?></strong></label><br>
			<select id="bw-wm-doc-visibility" name="bw_wm_doc_visibility">
				<?php foreach ( DocumentRepository::VISIBILITIES as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $this->documents->visibility( $post->ID ), $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p><label><input type="checkbox" name="bw_wm_doc_pinned" value="1"<?php checked( $this->documents->pinned( $post->ID ) ); ?>> <?php esc_html_e( 'Oben anheften', 'bw-woo-membership' ); ?></label></p>
		<p class="description"><?php echo esc_html( sprintf( /* translators: %d: number of downloads */ __( '%d Downloads', 'bw-woo-membership' ), $this->documents->downloads( $post->ID ) ) ); ?></p>
		<?php
	}

	/**
	 * Saves the box.
	 */
	public function save_box( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$file_id = isset( $_POST['bw_wm_doc_file'] ) ? absint( $_POST['bw_wm_doc_file'] ) : 0;
		if ( $file_id && 'attachment' !== get_post_type( $file_id ) ) {
			$file_id = 0;
		}
		$visibility = isset( $_POST['bw_wm_doc_visibility'] ) ? sanitize_key( wp_unslash( $_POST['bw_wm_doc_visibility'] ) ) : 'members';
		$this->documents->save( $post_id, $file_id, $visibility, ! empty( $_POST['bw_wm_doc_pinned'] ) );
	}

	/**
	 * Media picker on the document screen.
	 */
	public function enqueue( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || DocumentRepository::POST_TYPE !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'bw-wm-doc-admin', plugins_url( 'assets/document-admin.js', BW_WM_FILE ), array( 'jquery', 'media-editor' ), BW_WM_VERSION, true );
	}

	/**
	 * List columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$date = $columns['date'] ?? '';
		unset( $columns['date'] );
		$columns['bw_wm_file']       = __( 'Datei', 'bw-woo-membership' );
		$columns['bw_wm_visibility'] = __( 'Sichtbar für', 'bw-woo-membership' );
		$columns['bw_wm_downloads']  = __( 'Downloads', 'bw-woo-membership' );
		$columns['date']             = $date;
		return $columns;
	}

	/**
	 * Column values.
	 */
	public function column( string $column, int $post_id ): void {
		if ( 'bw_wm_file' === $column ) {
			$file = (string) get_attached_file( $this->documents->file_id( $post_id ) );
			echo esc_html( '' !== $file && $this->documents->file_id( $post_id ) ? basename( $file ) . ' · ' . DocumentFormatter::label( $file, (int) @filesize( $file ) ) : '–' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} elseif ( 'bw_wm_visibility' === $column ) {
			echo esc_html( DocumentRepository::VISIBILITIES[ $this->documents->visibility( $post_id ) ] );
		} elseif ( 'bw_wm_downloads' === $column ) {
			echo esc_html( (string) $this->documents->downloads( $post_id ) );
		}
	}
}
