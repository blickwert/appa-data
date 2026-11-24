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
	) {
	}

	/**
	 * Whether the event has a valid date.
	 */
	public function has_date(): bool {
		return '' !== $this->date;
	}
}
