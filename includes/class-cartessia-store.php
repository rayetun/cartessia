<?php
/**
 * Shared Interactivity API store.
 *
 * The whole point of building Cartessia on the Interactivity API is that its
 * blocks share one client-side store: selecting a swatch inside Quick View
 * updates the same product in the grid, a wishlist toggle reflects everywhere
 * at once, and a filter change re-queries without a reload. This class owns the
 * single store namespace ("cartessia") and seeds the config every block's view
 * module reads.
 *
 * Nothing here bakes per-user markup into the page, so it stays safe under
 * full-page caching — personalised state (wishlist membership, cart result)
 * hydrates client-side from this config, not from cached HTML.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and seeds the shared store namespace.
 */
final class Cartessia_Store {

	/**
	 * The single Interactivity API store namespace shared by every block.
	 */
	const STORE_NS = 'cartessia';

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia_Store|null
	 */
	private static $instance = null;

	/**
	 * Whether config has already been seeded this request.
	 *
	 * @var bool
	 */
	private $seeded = false;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia_Store
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'seed_config' ) );
	}

	/**
	 * Seed the shared Interactivity config once per front-end request.
	 *
	 * @return void
	 */
	public function seed_config() {
		if ( $this->seeded || ! function_exists( 'wp_interactivity_config' ) ) {
			return;
		}

		global $wp_locale;
		$cartessia_months         = array();
		$cartessia_weekdays_full  = array();
		$cartessia_weekdays_short = array();
		if ( $wp_locale instanceof WP_Locale ) {
			for ( $cartessia_m = 1; $cartessia_m <= 12; $cartessia_m++ ) {
				$cartessia_months[] = $wp_locale->get_month( $cartessia_m );
			}
			for ( $cartessia_d = 0; $cartessia_d < 7; $cartessia_d++ ) {
				$cartessia_full             = $wp_locale->get_weekday( $cartessia_d );
				$cartessia_weekdays_full[]  = $cartessia_full;
				$cartessia_weekdays_short[] = $wp_locale->get_weekday_abbrev( $cartessia_full );
			}
		}

		wp_interactivity_config(
			self::STORE_NS,
			array(
				// WooCommerce Store API — used for cache-safe add-to-cart and
				// cart-token handling from Quick View / Swatches.
				'storeApiRoot' => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
				'nonce'        => wp_create_nonce( 'wc_store_api' ),
				// admin-ajax endpoint + nonce for the Product Customizer add-to-cart.
				'ajaxUrl'      => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'customizerNonce' => wp_create_nonce( 'cartessia_customizer' ),
				'deliveryNonce'   => wp_create_nonce( 'cartessia_delivery' ),
				'wishlistNonce'   => wp_create_nonce( 'cartessia_wishlist' ),
				// Whether a user is logged in (server wishlist) vs. guest
				// (localStorage). Cached pages are served logged-out, so this is
				// cache-safe: logged-in users bypass the cache.
				'loggedIn'        => is_user_logged_in(),
				// Fallback image for wishlist cards whose product has no image.
				'placeholderImg'  => function_exists( 'wc_placeholder_img_src' ) ? esc_url_raw( wc_placeholder_img_src( 'woocommerce_thumbnail' ) ) : '',
				// Currency formatting so the customizer's live total matches WC.
				'currency'     => array(
					'symbol'      => html_entity_decode( get_woocommerce_currency_symbol() ),
					'decimals'    => wc_get_price_decimals(),
					'decimalSep'  => wc_get_price_decimal_separator(),
					'thousandSep' => wc_get_price_thousand_separator(),
					'position'    => get_option( 'woocommerce_currency_pos', 'left' ),
				),
				// Localised month/weekday names + start-of-week for the calendar.
				'dates'        => array(
					'months'        => $cartessia_months,
					'weekdaysShort' => $cartessia_weekdays_short,
					'weekdaysFull'  => $cartessia_weekdays_full,
					'firstDay'      => (int) get_option( 'start_of_week', 0 ),
				),
				// Screen-reader announcement strings, translated server-side so
				// the view module never hardcodes English.
				'i18n'         => array(
					'productLoaded'  => __( 'Product loaded.', 'cartessia' ),
					'addedToCart'    => __( 'Added to cart.', 'cartessia' ),
					'addToCartError' => __( 'Sorry, this could not be added to your cart.', 'cartessia' ),
					'selectOptions'  => __( 'Please choose an option first.', 'cartessia' ),
					/* translators: %s: formatted total price. */
					'totalIs'        => __( 'Total: %s', 'cartessia' ),
					'wishlistAdded'  => __( 'Added to your wishlist.', 'cartessia' ),
					'wishlistRemoved' => __( 'Removed from your wishlist.', 'cartessia' ),
					/* translators: %d: number of matching products. */
					'resultsCount'   => __( '%d products found.', 'cartessia' ),
					'chooseDate'     => __( 'Choose a delivery date', 'cartessia' ),
					'previousMonth'  => __( 'Previous month', 'cartessia' ),
					'nextMonth'      => __( 'Next month', 'cartessia' ),
					'unavailableDate' => __( 'This date is not available.', 'cartessia' ),
					/* translators: %s: formatted date. */
					'dateSelected'   => __( 'Selected delivery date: %s', 'cartessia' ),
					'chooseDateFirst' => __( 'Please choose a delivery date first.', 'cartessia' ),
					'remove'         => __( 'Remove', 'cartessia' ),
					/* translators: %s: product name. */
					'removeNamed'    => __( 'Remove %s from your wishlist', 'cartessia' ),
				),
			)
		);

		$this->seeded = true;
	}
}
