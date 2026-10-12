<?php
/**
 * Admin settings shell.
 *
 * Cartessia is configured mostly per-block in the editor, so the admin page is
 * intentionally light in v1.0.0: an overview screen plus the extension point
 * ("cartessia_settings_tabs") a future Pro add-on hooks its tab into. No form is
 * processed here yet, which keeps the surface free of nonce/capability risk.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Cartessia admin screen.
 */
final class Cartessia_Settings {

	const MENU_SLUG = 'cartessia';

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia_Settings|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia_Settings
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Register the Cartessia submenu under WooCommerce.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Cartessia', 'cartessia' ),
			__( 'Cartessia', 'cartessia' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the settings/overview page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		/**
		 * Filter the Cartessia settings tabs.
		 *
		 * Registration point for a future Pro add-on. Each tab is
		 * array( 'label' => string, 'callback' => callable ).
		 *
		 * @param array<string,array<string,mixed>> $tabs Registered tabs.
		 */
		$tabs = apply_filters( 'cartessia_settings_tabs', array() );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Cartessia — Accessible Product Blocks for WooCommerce', 'cartessia' ) . '</h1>';
		echo '<p>' . esc_html__( 'Add Cartessia blocks — Variation Swatches, Quick View, Live Product Customizer, Delivery Date Picker, and Wishlist — from the block inserter on any product or shop template. Every block is keyboard and screen-reader accessible out of the box.', 'cartessia' ) . '</p>';

		if ( ! empty( $tabs ) ) {
			foreach ( $tabs as $tab ) {
				if ( isset( $tab['callback'] ) && is_callable( $tab['callback'] ) ) {
					call_user_func( $tab['callback'] );
				}
			}
		}

		echo '</div>';
	}
}
