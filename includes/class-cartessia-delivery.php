<?php
/**
 * Accessible Delivery Date Picker — cart/order integration.
 *
 * Adds a product to the cart with a chosen delivery date, then shows it in the
 * cart, saves it to the order line item (HPOS-safe line-item meta), and includes
 * it in admin order details and customer emails.
 *
 * The date rules (minimum lead time, maximum range, disabled weekdays) are
 * signed with wp_hash() when the block renders, and this handler recomputes the
 * allowed window server-side from the signed rules — so a tampered client cannot
 * submit a date outside the merchant's rules.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the delivery-date AJAX handler and cart/order hooks.
 */
final class Cartessia_Delivery {

	/**
	 * Cart-item data key.
	 */
	const CART_KEY = 'cartessia_delivery';

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia_Delivery|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia_Delivery
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wire AJAX and cart/order hooks.
	 */
	private function __construct() {
		add_action( 'wp_ajax_cartessia_add_delivery', array( $this, 'handle_add' ) );
		add_action( 'wp_ajax_nopriv_cartessia_add_delivery', array( $this, 'handle_add' ) );

		add_filter( 'woocommerce_get_item_data', array( $this, 'display_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_line_item' ), 10, 4 );
	}

	/**
	 * Sign a delivery rules JSON string.
	 *
	 * @param string $rules_json The exact JSON the block rendered.
	 * @return string Signature.
	 */
	public static function sign_rules( $rules_json ) {
		return wp_hash( 'cartessia_delivery|' . $rules_json );
	}

	/**
	 * Validate an ISO date against signed rules. Returns the DateTimeImmutable
	 * (site timezone, midnight) on success or null on failure.
	 *
	 * @param string $date  Y-m-d string.
	 * @param array  $rules Decoded rules.
	 * @return DateTimeImmutable|null
	 */
	public static function validate_date( $date, $rules ) {
		if ( ! is_string( $date ) || 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		$tz     = wp_timezone();
		$picked = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $tz );
		if ( ! $picked ) {
			return null;
		}

		$today    = new DateTimeImmutable( 'today', $tz );
		$min_lead = isset( $rules['minLeadDays'] ) ? max( 0, (int) $rules['minLeadDays'] ) : 0;
		$max_days = isset( $rules['maxDays'] ) ? max( 1, (int) $rules['maxDays'] ) : 365;
		$min      = $today->modify( '+' . $min_lead . ' days' );
		$max      = $today->modify( '+' . $max_days . ' days' );

		if ( $picked < $min || $picked > $max ) {
			return null;
		}

		$disabled = isset( $rules['disabledWeekdays'] ) && is_array( $rules['disabledWeekdays'] )
			? array_map( 'intval', $rules['disabledWeekdays'] )
			: array();
		if ( in_array( (int) $picked->format( 'w' ), $disabled, true ) ) {
			return null;
		}

		return $picked;
	}

	/**
	 * AJAX: add a product to the cart with a chosen delivery date.
	 *
	 * @return void
	 */
	public function handle_add() {
		check_ajax_referer( 'cartessia_delivery', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || $product->is_type( 'variable' ) ) {
			wp_send_json_error( array( 'message' => __( 'This product cannot be added here.', 'cartessia' ) ), 400 );
		}

		$rules_json = isset( $_POST['rules'] ) ? sanitize_text_field( wp_unslash( $_POST['rules'] ) ) : '';
		$signature  = isset( $_POST['sig'] ) ? sanitize_text_field( wp_unslash( $_POST['sig'] ) ) : '';
		if ( '' === $rules_json || ! hash_equals( self::sign_rules( $rules_json ), $signature ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid delivery configuration.', 'cartessia' ) ), 400 );
		}

		$rules = json_decode( $rules_json, true );
		if ( ! is_array( $rules ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid delivery configuration.', 'cartessia' ) ), 400 );
		}

		$date   = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$picked = self::validate_date( $date, $rules );

		if ( ! $picked ) {
			wp_send_json_error( array( 'message' => __( 'Please choose an available delivery date.', 'cartessia' ) ), 400 );
		}

		$label   = isset( $rules['fieldLabel'] ) ? sanitize_text_field( $rules['fieldLabel'] ) : __( 'Delivery date', 'cartessia' );
		$display = date_i18n( (string) get_option( 'date_format' ), $picked->getTimestamp() );

		$cart_item_data = array(
			self::CART_KEY => array(
				'date'    => $picked->format( 'Y-m-d' ),
				'label'   => $label,
				'display' => $display,
			),
		);

		$added = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

		if ( $added ) {
			wp_send_json_success( array( 'message' => __( 'Added to cart.', 'cartessia' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Sorry, this could not be added to your cart.', 'cartessia' ) ), 400 );
	}

	/**
	 * Show the delivery date in the cart and checkout.
	 *
	 * @param array $item_data Existing rows.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item[ self::CART_KEY ]['display'] ) ) {
			return $item_data;
		}
		$delivery    = $cart_item[ self::CART_KEY ];
		$item_data[] = array(
			'name'  => ! empty( $delivery['label'] ) ? $delivery['label'] : __( 'Delivery date', 'cartessia' ),
			'value' => $delivery['display'],
		);
		return $item_data;
	}

	/**
	 * Persist the delivery date onto the order line item.
	 *
	 * @param WC_Order_Item_Product $item          Order line item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order.
	 * @return void
	 */
	public function save_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values[ self::CART_KEY ]['display'] ) ) {
			return;
		}
		$delivery = $values[ self::CART_KEY ];
		$label    = ! empty( $delivery['label'] ) ? $delivery['label'] : __( 'Delivery date', 'cartessia' );
		$item->add_meta_data( $label, $delivery['display'] );
	}
}
