<?php
/**
 * Membership subscriptions (WooCommerce Subscriptions): type at renewal, clearer cart texts.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Modules\Membership\Service\MembershipPeriod;
use BW\WooMembership\Modules\Membership\Service\MembershipTypes;
use BW\WooMembership\Modules\Membership\Service\ProofRules;

/**
 * Before a renewal order is created, the subscription switches between the student and the regular membership
 * depending on the student proof for the new period (answer M4: without proof the next renewal is regular).
 */
final class SubscriptionController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MembershipOrderRepository $orders,
		private readonly ProofRepository $proofs,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		// Priority 0: before WooCommerce Subscriptions creates the renewal order (priority 1).
		add_action( 'woocommerce_scheduled_subscription_payment', array( $this, 'switch_type' ), 0, 1 );
		add_filter( 'gettext_woocommerce-subscriptions', array( $this, 'texts' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'renewal_line' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'script_texts' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'account_menu' ), 20 );
		add_filter( 'woocommerce_subscriptions_translation_file_rel_path', array( $this, 'translation_path' ) );
		add_filter( 'lang_dir_for_domain', array( $this, 'translation_dir' ), 10, 3 );
	}

	/**
	 * WooCommerce Subscriptions passes an absolute path to load_plugin_textdomain(), which expects one relative to
	 * the plugins folder, so a language file in its own "languages" folder is never found (and the just-in-time
	 * loading of WordPress only looks in wp-content/languages/plugins). Language packs installed there keep working
	 * and take precedence.
	 *
	 * @param string|false $path   Folder WordPress found for the domain.
	 * @param string       $domain Text domain.
	 * @param string       $locale Locale.
	 */
	public function translation_dir( mixed $path, mixed $domain, mixed $locale ): mixed {
		if ( 'woocommerce-subscriptions' !== $domain || ! class_exists( 'WC_Subscriptions' ) || empty( \WC_Subscriptions::$plugin_file ) ) {
			return $path;
		}
		$own = trailingslashit( dirname( \WC_Subscriptions::$plugin_file ) ) . 'languages/';
		// Only when no language pack is installed in wp-content/languages/plugins.
		return ( false === $path || ! is_readable( trailingslashit( (string) $path ) . "woocommerce-subscriptions-{$locale}.mo" ) ) && is_readable( $own . "woocommerce-subscriptions-{$locale}.mo" ) ? $own : $path;
	}

	/**
	 * Same for load_plugin_textdomain(), which the plugin calls with an absolute path.
	 *
	 * @param string $path Path the plugin would use.
	 */
	public function translation_path( mixed $path ): mixed {
		return class_exists( 'WC_Subscriptions' ) && ! empty( \WC_Subscriptions::$plugin_file ) ? dirname( plugin_basename( \WC_Subscriptions::$plugin_file ) ) . '/languages' : $path;
	}

	/**
	 * "Mein Konto": the subscription is the membership fee; there are no downloads.
	 *
	 * @param array<string, string> $items Menu items.
	 * @return array<string, string>
	 */
	public function account_menu( mixed $items ): mixed {
		if ( ! is_array( $items ) ) {
			return $items;
		}
		unset( $items['downloads'] );
		if ( isset( $items['subscriptions'] ) ) {
			$items['subscriptions'] = __( 'Mitgliedsbeitrag', 'bw-woo-membership' );
		}
		return $items;
	}

	/**
	 * Same wording in the block cart and checkout, which build the price string in the browser.
	 */
	public function script_texts(): void {
		if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) ) {
			return;
		}
		wp_add_inline_script(
			'wp-hooks',
			sprintf(
				'wp.hooks.addFilter( \'i18n.gettext_woocommerce-subscriptions\', \'bw-wm/texts\', function ( translation, text ) { return \'every\' === text ? %1$s : ( \'every %%s\' === text ? %2$s : translation ); } );',
				wp_json_encode( _x( 'pro', 'billing period: every', 'bw-woo-membership' ) ),
				/* translators: %s: billing period, e.g. "Jahr" */
				wp_json_encode( _x( 'pro %s', 'billing period: every …', 'bw-woo-membership' ) )
			)
		);
	}

	/**
	 * Clearer German than the language pack: "96,00 € pro Jahr" instead of "jede(n) Jahr".
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 */
	public function texts( mixed $translation, mixed $text ): mixed {
		/* translators: %s: billing period, e.g. "Jahr" */
		return 'every %s' === $text ? _x( 'pro %s', 'billing period: every …', 'bw-woo-membership' ) : $translation;
	}

	/**
	 * Cart line of a membership synced to a fixed day: "Verlängerung: jährlich am 1. Jänner" (the plugin shows an
	 * English ordinal, "on Januar 1st each year").
	 *
	 * @param array<int, array<string, mixed>> $data Item data.
	 * @param array<string, mixed>             $item Cart item.
	 * @return array<int, array<string, mixed>>
	 */
	public function renewal_line( mixed $data, mixed $item ): mixed {
		$product = $item['data'] ?? null;
		if ( ! is_array( $data ) || ! $product instanceof \WC_Product || '' === $this->orders->product_type( (int) ( $item['product_id'] ?? 0 ) ) ) {
			return $data;
		}
		$sync = $product->get_meta( '_subscription_payment_sync_date' );
		if ( ! is_array( $sync ) || 'year' !== $product->get_meta( '_subscription_period' ) ) {
			return $data;
		}
		$when = MembershipPeriod::day_month( (int) ( $sync['day'] ?? 0 ), (int) ( $sync['month'] ?? 0 ) );
		foreach ( $data as $key => $row ) {
			if ( '' !== $when && ! empty( $row['wcs_subscription_detail'] ) && in_array( $row['name'] ?? '', array( 'Renews', __( 'Renews', 'woocommerce-subscriptions' ) ), true ) ) { // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- the plugin's own label.
				$data[ $key ]['name']  = __( 'Verlängerung', 'bw-woo-membership' );
				/* translators: %s: day and month, e.g. "1. Jänner" */
				$data[ $key ]['value'] = sprintf( __( 'jährlich am %s', 'bw-woo-membership' ), $when );
			}
		}
		return $data;
	}

	/**
	 * Swaps the membership product of a subscription that is due for renewal, when the proof requires it.
	 *
	 * @param int $subscription_id Subscription ID.
	 */
	public function switch_type( mixed $subscription_id ): void {
		$subscription = function_exists( 'wcs_get_subscription' ) ? wcs_get_subscription( (int) $subscription_id ) : null;
		if ( ! $subscription instanceof \WC_Order || ! $subscription->has_status( 'active' ) ) {
			return;
		}
		$user_id = (int) $subscription->get_customer_id();
		$type    = $this->orders->order_type( $subscription );
		if ( $user_id <= 0 || '' === $type ) {
			return;
		}
		// The new period starts on the due date (1 January), even if the payment action runs a little earlier.
		$due    = substr( (string) $subscription->get_date( 'next_payment', 'site' ), 0, 10 );
		$day    = 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $due ) ? max( $due, current_time( 'Y-m-d' ) ) : current_time( 'Y-m-d' );
		$target = ProofRules::renewal_type( $type, $this->proofs->get( $user_id ), $day );
		$product_id = $target !== $type ? $this->orders->product_for_type( $target ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$price = (float) ( class_exists( 'WC_Subscriptions_Product' ) ? \WC_Subscriptions_Product::get_price( $product ) : $product->get_price() );
		foreach ( $subscription->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product || '' === $this->orders->product_type( (int) $item->get_product_id() ) ) {
				continue;
			}
			$quantity = max( 1, (int) $item->get_quantity() );
			$item->set_product( $product );
			$item->set_subtotal( $price * $quantity );
			$item->set_total( $price * $quantity );
			$item->save();
		}
		$subscription->calculate_totals();
		$subscription->add_order_note(
			sprintf(
				/* translators: 1: old membership type, 2: new membership type */
				__( 'Mitgliedschaft zur Verlängerung umgestellt: %1$s → %2$s (Studiennachweis).', 'bw-woo-membership' ),
				MembershipTypes::label( $type ),
				MembershipTypes::label( $target )
			)
		);
		$subscription->save();
	}
}
