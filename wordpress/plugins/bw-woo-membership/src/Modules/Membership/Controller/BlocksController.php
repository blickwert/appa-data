<?php
/**
 * Member area blocks.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Core\View;
use BW\WooMembership\Modules\Membership\Service\DashboardModel;

/**
 * Server-side blocks of the member area: user, overview, events and profile.
 */
final class BlocksController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly DashboardModel $model ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Registers the shared editor script, the stylesheet and the blocks.
	 */
	public function register_blocks(): void {
		$asset = require BW_WM_DIR . '/blocks/editor.asset.php';
		wp_register_script( 'bw-wm-blocks', plugins_url( 'blocks/editor.js', BW_WM_FILE ), $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'bw-wm-blocks', 'bw-woo-membership', BW_WM_DIR . '/languages' );
		wp_register_style( 'bw-wm-dashboard', plugins_url( 'assets/dashboard.css', BW_WM_FILE ), array(), BW_WM_VERSION );

		$callbacks = array(
			'user'     => array( $this, 'render_user' ),
			'overview' => array( $this, 'render_overview' ),
			'events'   => array( $this, 'render_events' ),
			'profile'  => array( $this, 'render_profile' ),
		);
		foreach ( $callbacks as $name => $callback ) {
			register_block_type( BW_WM_DIR . '/blocks/' . $name, array( 'render_callback' => $callback ) );
		}
	}

	/**
	 * Name and membership type (header).
	 */
	public function render_user(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		return View::render( 'dashboard/user', $this->model->user( get_current_user_id() ) );
	}

	/**
	 * Welcome, status and membership cards.
	 */
	public function render_overview(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		return View::render( 'dashboard/overview', $this->model->overview( get_current_user_id() ) );
	}

	/**
	 * Upcoming events.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render_events( array $attributes ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$limit = max( 1, min( 12, (int) ( $attributes['limit'] ?? 3 ) ) );
		return View::render(
			'dashboard/events',
			array(
				'events'  => $this->model->events( get_current_user_id(), $limit ),
				'all_url' => (string) get_post_type_archive_link( 'appa_event' ),
			)
		);
	}

	/**
	 * Profile panel with directory switch.
	 */
	public function render_profile(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		return View::render( 'dashboard/profile', $this->model->profile( get_current_user_id() ) );
	}
}
