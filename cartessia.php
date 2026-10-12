<?php
/**
 * Plugin Name:       Cartessia — Accessible Product Blocks for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/cartessia/
 * Description:       Accessible-by-default WooCommerce blocks — variation swatches, quick view, live product customizer, delivery date picker, and wishlist — built on the Interactivity API to WCAG 2.2 AA.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Rayetun
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cartessia
 * Domain Path:       /languages
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CARTESSIA_VERSION', '1.0.0' );
define( 'CARTESSIA_FILE', __FILE__ );
define( 'CARTESSIA_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARTESSIA_URL', plugin_dir_url( __FILE__ ) );

/*
 * Declare High-Performance Order Storage (HPOS) compatibility.
 *
 * Cartessia never queries wp_posts / wp_postmeta for order data — it reads
 * everything through WooCommerce CRUD (see Cartessia_Data) — so it is HPOS-safe
 * by construction. This declaration must run on before_woocommerce_init, before
 * WooCommerce decides whether to warn about incompatible plugins.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				CARTESSIA_FILE,
				true
			);
		}
	}
);

require_once CARTESSIA_DIR . 'includes/class-cartessia.php';

/*
 * Uninstall is handled by uninstall.php (runs on delete, not deactivation).
 * There is no activation table to create in v1.0.0 — the wishlist stores guest
 * data client-side and logged-in data in user meta, so no custom table exists
 * to install here.
 */

Cartessia::get_instance();
