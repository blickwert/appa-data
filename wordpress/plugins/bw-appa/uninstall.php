<?php
/**
 * Removes the plugin's options. Events and CF7 forms stay (they are content).
 *
 * @package BW\Appa
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'bw_appa_forms_settings' );
delete_option( 'bw_appa_forms_ids' );
