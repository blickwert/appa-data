<?php
/**
 * Header: name and membership type of the member.
 *
 * @package BW\WooMembership
 *
 * @var array{name: string, type: string, initials: string} $data
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="bwm-user">
	<span class="bwm-user__avatar" aria-hidden="true"><?php echo esc_html( $data['initials'] ); ?></span>
	<span class="bwm-user__text"><strong><?php echo esc_html( $data['name'] ); ?></strong><br><?php echo esc_html( $data['type'] ); ?></span>
</div>
