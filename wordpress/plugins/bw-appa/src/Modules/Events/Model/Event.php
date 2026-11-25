<?php
/**
 * Event value object.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Model;

/**
 * The fields of an event, already sanitised.
 */
final class Event {

	/**
	 * Builds an event.
	 *
	 * @param string $date        Date as Y-m-d or ''.
	 * @param string $time        Start time as H:i or ''.
	 * @param string $zoom_link   Zoom URL or ''.
	 * @param string $zoom_access Access data (meeting ID, passcode) or ''.
	 */
	public function __construct(
		public readonly string $date,
		public readonly string $time,
		public readonly string $zoom_link,
		public readonly string $zoom_access,
		public readonly string $end_date = '',
		public readonly string $end_time = '',
		public readonly string $format = '',
		public readonly string $location = '',
		public readonly string $audience = 'open',
		public readonly bool $registration = false,
	) {
	}

	/**
	 * Whether the event has a valid date.
	 */
	public function has_date(): bool {
		return '' !== $this->date;
	}

	/**
	 * Whether the event takes place online (online or hybrid; without a format: when a Zoom link is set).
	 */
	public function is_online(): bool {
		return in_array( $this->format, array( 'online', 'hybrid' ), true ) || ( '' === $this->format && '' !== $this->zoom_link );
	}
}
