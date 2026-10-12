<?php
/**
 * Live Product Customizer — cart integration.
 *
 * Adds a personalised product (custom text + a priced option) to the cart with
 * its custom data and an adjusted price, then shows that data in the cart and
 * saves it to the order line item (HPOS-safe — line-item meta, not order tables).
 *
 * Prices are server-authoritative: the block signs its price configuration with
 * wp_hash() when it renders, and this handler recomputes the add-on price from
 * the signed config — so a tampered client cannot change what an add-on costs.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the customizer AJAX handler and cart/order hooks.
 */
final class Cartessia_Customizer {

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia_Customizer|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia_Customizer
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
		add_action( 'wp_ajax_cartessia_add_custom', array( $this, 'handle_add' ) );
		add_action( 'wp_ajax_nopriv_cartessia_add_custom', array( $this, 'handle_add' ) );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'adjust_price' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_line_item' ), 10, 4 );
	}

	/**
	 * Sign a customizer config JSON string.
	 *
	 * @param string $config_json The exact JSON the block rendered.
	 * @return string Signature.
	 */
	public static function sign_config( $config_json ) {
		return wp_hash( 'cartessia_customizer|' . $config_json );
	}

	/**
	 * AJAX: add a personalised product to the cart.
	 *
	 * @return void
	 */
	public function handle_add() {
		check_ajax_referer( 'cartessia_customizer', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || $product->is_type( 'variable' ) ) {
			wp_send_json_error( array( 'message' => __( 'This product cannot be personalised here.', 'cartessia' ) ), 400 );
		}

		// Verify the price configuration is the one we signed at render time. The
		// signed config is compact JSON of already-sanitized labels/numbers, so
		// sanitize_text_field() is lossless here and keeps the signature valid.
		$config_json = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '';
		$signature   = isset( $_POST['sig'] ) ? sanitize_text_field( wp_unslash( $_POST['sig'] ) ) : '';
		if ( ! is_string( $config_json ) || '' === $config_json || ! hash_equals( self::sign_config( $config_json ), $signature ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid customization.', 'cartessia' ) ), 400 );
		}

		$config = json_decode( $config_json, true );
		if ( ! is_array( $config ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid customization.', 'cartessia' ) ), 400 );
		}

		$text         = isset( $_POST['text'] ) ? sanitize_text_field( wp_unslash( $_POST['text'] ) ) : '';
		$max_length   = isset( $config['textMaxLength'] ) ? absint( $config['textMaxLength'] ) : 0;
		if ( $max_length > 0 ) {
			$text = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max_length ) : substr( $text, 0, $max_length );
		}
		$option_index = isset( $_POST['option'] ) ? intval( $_POST['option'] ) : -1;

		$addon  = 0.0;
		$custom = array();

		if ( '' !== $text ) {
			$addon           += isset( $config['textPrice'] ) ? (float) $config['textPrice'] : 0.0;
			$custom['text']   = $text;
			$custom['text_label'] = isset( $config['textLabel'] ) ? sanitize_text_field( $config['textLabel'] ) : __( 'Personalization', 'cartessia' );
		}

		if ( $option_index >= 0 && ! empty( $config['options'][ $option_index ] ) ) {
			$opt                     = $config['options'][ $option_index ];
			$addon                  += isset( $opt['price'] ) ? (float) $opt['price'] : 0.0;
			$custom['option_label']  = isset( $opt['label'] ) ? sanitize_text_field( $opt['label'] ) : '';
			$custom['options_label'] = isset( $config['optionsLabel'] ) ? sanitize_text_field( $config['optionsLabel'] ) : __( 'Options', 'cartessia' );
		}

		$cart_item_data = array();
		if ( ! empty( $custom ) ) {
			$custom['addon']                 = $addon;
			$custom['base']                  = (float) $product->get_price();
			$cart_item_data['cartessia_custom'] = $custom;
		}

		$added = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

		if ( $added ) {
			wp_send_json_success(
				array(
					'message' => __( 'Added to cart.', 'cartessia' ),
				)
			);
		}

		wp_send_json_error( array( 'message' => __( 'Sorry, this could not be added to your cart.', 'cartessia' ) ), 400 );
	}

	/**
	 * Apply the add-on price to personalised cart items.
	 *
	 * Idempotent across recalculations: the original base price is stored in the
	 * cart item data, so this always sets base + add-on rather than compounding.
	 *
	 * @param WC_Cart $cart Cart object.
	 * @return void
	 */
	public function adjust_price( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['cartessia_custom'] ) || empty( $cart_item['data'] ) ) {
				continue;
			}
			$custom = $cart_item['cartessia_custom'];
			$base   = isset( $custom['base'] ) ? (float) $custom['base'] : (float) $cart_item['data']->get_price();
			$addon  = isset( $custom['addon'] ) ? (float) $custom['addon'] : 0.0;
			$cart_item['data']->set_price( $base + $addon );
		}
	}

	/**
	 * Show the personalisation in the cart and checkout.
	 *
	 * @param array $item_data Existing item data rows.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['cartessia_custom'] ) ) {
			return $item_data;
		}
		$custom = $cart_item['cartessia_custom'];

		if ( ! empty( $custom['text'] ) ) {
			$item_data[] = array(
				'name'  => isset( $custom['text_label'] ) ? $custom['text_label'] : __( 'Personalization', 'cartessia' ),
				'value' => $custom['text'],
			);
		}
		if ( ! empty( $custom['option_label'] ) ) {
			$item_data[] = array(
				'name'  => isset( $custom['options_label'] ) ? $custom['options_label'] : __( 'Options', 'cartessia' ),
				'value' => $custom['option_label'],
			);
		}

		return $item_data;
	}

	/**
	 * Persist the personalisation onto the order line item.
	 *
	 * @param WC_Order_Item_Product $item          Order line item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order.
	 * @return void
	 */
	public function save_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['cartessia_custom'] ) ) {
			return;
		}
		$custom = $values['cartessia_custom'];

		if ( ! empty( $custom['text'] ) ) {
			$label = isset( $custom['text_label'] ) ? $custom['text_label'] : __( 'Personalization', 'cartessia' );
			$item->add_meta_data( $label, $custom['text'] );
		}
		if ( ! empty( $custom['option_label'] ) ) {
			$label = isset( $custom['options_label'] ) ? $custom['options_label'] : __( 'Options', 'cartessia' );
			$item->add_meta_data( $label, $custom['option_label'] );
		}
	}
}
