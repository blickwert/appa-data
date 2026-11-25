<?php
/**
 * Renders bw/icon.
 *
 * @package BW\AppaTheme
 *
 * @var array<string, string> $attributes Block attributes.
 */

declare(strict_types=1);

use BW\AppaTheme\Icons;

$bw_svg = Icons::svg( $attributes['icon'] ?? '' );
if ( '' === $bw_svg ) {
	return;
}

$bw_size = in_array( $attributes['size'] ?? 'm', array( 's', 'm', 'l' ), true ) ? $attributes['size'] : 'm';
// The attribute keeps the palette slug (saved in the content); the class names the role (html-css-richtlinien).
$bw_tones = array(
	'red'   => 'primary',
	'navy'  => 'text',
	'green' => 'success',
	'gold'  => 'accent',
	'blue'  => 'info',
);
$bw_tone  = $bw_tones[ $attributes['tone'] ?? 'red' ] ?? 'primary';
$bw_shape = in_array( $attributes['shape'] ?? 'plain', array( 'circle', 'square', 'tile' ), true ) ? ' bw-icon--' . $attributes['shape'] : '';

printf(
	'<span %1$s>%2$s</span>',
	get_block_wrapper_attributes( array( 'class' => "bw-icon bw-icon--{$bw_size} bw-icon--{$bw_tone}{$bw_shape}" ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core.
	$bw_svg // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG from the theme.
);
