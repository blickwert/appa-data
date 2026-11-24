<?php
/**
 * Membership of one user.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Model;

/**
 * Current membership state as shown in the member area (dates as Y-m-d or null).
 */
final class Membership {

	public const STATUS_ACTIVE  = 'active';
	public const STATUS_PENDING = 'pending';
	public const STATUS_ON_HOLD = 'on-hold';
	public const STATUS_EXPIRED = 'expired';
	public const STATUS_NONE    = 'none';

	/**
	 * Creates the value object.
	 */
	public function __construct(
		public readonly int $user_id,
		public readonly string $status,
		public readonly string $type,
		public readonly string $number,
		public readonly ?string $since,
		public readonly ?string $valid_until,
		public readonly ?string $next_renewal,
		public readonly bool $auto_renew,
		public readonly string $source,
	) {
	}

	/**
	 * No membership at all.
	 */
	public static function none( int $user_id, string $number = '', ?string $since = null ): self {
		return new self( $user_id, self::STATUS_NONE, '', $number, $since, null, null, false, 'none' );
	}

	/**
	 * Whether the member may use the protected content (documents, member events).
	 */
	public function grants_access(): bool {
		return self::STATUS_ACTIVE === $this->status;
	}
}
