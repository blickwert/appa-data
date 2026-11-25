<?php
/**
 * Editor fields.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Controller;

use BW\Appa\Modules\Events\Repository\EventRepository;

/**
 * Adds the "Termin" panel (date, time, Zoom link, access data) to the block editor sidebar.
 */
final class EditorController {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads the panel script on the event edit screen only.
	 */
	public function enqueue(): void {
		$screen = get_current_screen();
		if ( ! $screen || EventRepository::POST_TYPE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script(
			'bw-appa-event-panel',
			plugins_url( 'assets/js/event-panel.js', BW_APPA_FILE ),
			array( 'wp-components', 'wp-core-data', 'wp-data', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-plugins' ),
			BW_APPA_VERSION,
			array( 'in_footer' => true )
		);
		wp_set_script_translations( 'bw-appa-event-panel', 'bw-appa', BW_APPA_DIR . '/languages' );
		wp_localize_script(
			'bw-appa-event-panel',
			'bwAppaEvent',
			array(
				'keys' => array(
					'date'   => EventRepository::META_DATE,
					'time'   => EventRepository::META_TIME,
					'link'   => EventRepository::META_LINK,
					'access'       => EventRepository::META_ACCESS,
					'audience'     => EventRepository::META_AUDIENCE,
					'priceMember'  => EventRepository::META_PRICE_MEMBER,
					'pricePublic'  => EventRepository::META_PRICE_PUBLIC,
					'capacity'     => EventRepository::META_CAPACITY,
					'registration' => EventRepository::META_REGISTRATION,
					'deadline'     => EventRepository::META_DEADLINE,
					'endDate'      => EventRepository::META_END_DATE,
					'endTime'      => EventRepository::META_END_TIME,
					'format'       => EventRepository::META_FORMAT,
					'location'     => EventRepository::META_LOCATION,
					'reminder'     => EventRepository::META_REMINDER,
					'cancel'       => EventRepository::META_CANCEL,
				),
				'audiences' => EventRepository::audiences(),
				/**
				 * Registration figures for the editor sidebar (answered by the plugin "APPA Mitgliedschaft"):
				 * enabled, registered, capacity, left, waitlist, url (participants page).
				 *
				 * @param array<string, mixed> $info    Default: none.
				 * @param int                  $post_id Event.
				 */
				'info'      => apply_filters( 'bw_appa_event_editor_info', array(), (int) get_the_ID() ),
				'formats'   => EventRepository::formats(),
			)
		);
	}
}
