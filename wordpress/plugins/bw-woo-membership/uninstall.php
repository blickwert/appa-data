<?php
/**
 * Uninstall: deliberately removes nothing. Member numbers, start dates, profiles and documents are records of the
 * association and must survive a reinstall (member numbers stay for life, meeting 2 October 2026).
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
