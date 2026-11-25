<?php
/**
 * Link to the cart with the number of items. A plain link instead of the WooCommerce mini cart: no drawer, no
 * JavaScript, nothing hidden but focusable (bookings go straight to the checkout anyway).
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

if ( ! function_exists( 'wc_get_cart_url' ) ) {
	return;
}
$bw_count = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$bw_label = 0 === $bw_count
	? __( 'Warenkorb (leer)', 'appa' )
	/* translators: %d: number of items */
	: sprintf( _n( 'Warenkorb, %d Artikel', 'Warenkorb, %d Artikel', $bw_count, 'appa' ), $bw_count );
?>
<a class="bw-cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php echo esc_attr( $bw_label ); ?>">
	<svg class="bw-cart-link__icon" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6.2"/><circle cx="9.5" cy="19" r="1.3" fill="currentColor"/><circle cx="17" cy="19" r="1.3" fill="currentColor"/></svg>
	<?php if ( $bw_count > 0 ) : ?>
		<span class="bw-cart-link__count" aria-hidden="true"><?php echo esc_html( (string) $bw_count ); ?></span>
	<?php endif; ?>
</a>
