<?php
/**
 * Cartessia bootstrap.
 *
 * Loads dependencies, verifies WooCommerce is active (degrading gracefully —
 * never fatally — when it is not), registers the blocks, and boots the shared
 * Interactivity API store.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
final class Cartessia {

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia
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
		// Translations for WordPress.org-hosted plugins load automatically on
		// WordPress 6.5+, so no load_plugin_textdomain() call is needed.
		add_action( 'init', array( $this, 'boot' ) );
	}

	/**
	 * Boot the plugin once WordPress is initialised.
	 *
	 * If WooCommerce is not active, Cartessia registers nothing and shows an
	 * admin notice instead of throwing a fatal error. Every feature is gated
	 * behind this check, so a store owner who deactivates WooCommerce simply
	 * loses Cartessia's blocks — cleanly.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'render_missing_woocommerce_notice' ) );
			return;
		}

		$this->includes();

		Cartessia_Store::get_instance();
		Cartessia_Swatches::get_instance();
		Cartessia_Customizer::get_instance();
		Cartessia_Delivery::get_instance();
		Cartessia_Wishlist::get_instance();
		Cartessia_Settings::get_instance();

		if ( is_admin() ) {
			Cartessia_Swatch_Admin::get_instance();
		}

		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
		add_action( 'init', array( $this, 'register_blocks' ), 20 );
	}

	/**
	 * Add the "Cartessia" block category so the blocks group together.
	 *
	 * @param array<int,array<string,mixed>> $categories Existing categories.
	 * @return array<int,array<string,mixed>>
	 */
	public function register_block_category( $categories ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'cartessia',
					'title' => __( 'Cartessia', 'cartessia' ),
					'icon'  => null,
				),
			)
		);
	}

	/**
	 * Whether WooCommerce is available.
	 *
	 * Checks for the core WooCommerce class rather than the plugin file so it
	 * also holds true for network-activated or must-use installs.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Include the plugin's PHP classes.
	 *
	 * @return void
	 */
	private function includes() {
		require_once CARTESSIA_DIR . 'includes/class-cartessia-data.php';
		require_once CARTESSIA_DIR . 'includes/class-cartessia-store.php';
		require_once CARTESSIA_DIR . 'includes/class-cartessia-swatches.php';
		require_once CARTESSIA_DIR . 'includes/class-cartessia-customizer.php';
		require_once CARTESSIA_DIR . 'includes/class-cartessia-delivery.php';
		require_once CARTESSIA_DIR . 'includes/class-cartessia-wishlist.php';
		require_once CARTESSIA_DIR . 'includes/class-cartessia-settings.php';

		if ( is_admin() ) {
			require_once CARTESSIA_DIR . 'includes/class-cartessia-swatch-admin.php';
		}
	}

	/**
	 * Register every built block.
	 *
	 * Each block lives in build/blocks/<name> with its own block.json. We loop
	 * the directory rather than hard-coding names so adding a block in a later
	 * release needs no change here.
	 *
	 * @return void
	 */
	public function register_blocks() {
		$blocks_dir = CARTESSIA_DIR . 'build/blocks';

		if ( ! is_dir( $blocks_dir ) ) {
			return;
		}

		$entries = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

		if ( empty( $entries ) ) {
			return;
		}

		foreach ( $entries as $block_path ) {
			if ( file_exists( trailingslashit( $block_path ) . 'block.json' ) ) {
				register_block_type( $block_path );
			}
		}
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 *
	 * @return void
	 */
	public function render_missing_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Cartessia needs WooCommerce to be active. Its blocks stay hidden until WooCommerce is enabled.', 'cartessia' )
		);
	}
}
