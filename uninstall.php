<?php
/**
 * Cartessia uninstall cleanup.
 *
 * Runs on plugin deletion (not deactivation). Removes only Cartessia's own
 * options, transients, and user meta — it never touches WooCommerce product,
 * order, or customer data.
 *
 * @package Cartessia
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove Cartessia options.
$cartessia_options = array(
	'cartessia_settings',
	'cartessia_db_version',
);
foreach ( $cartessia_options as $cartessia_option ) {
	delete_option( $cartessia_option );
}

// Remove logged-in wishlist user meta (guest wishlists live in the browser and
// need no server-side cleanup).
delete_metadata( 'user', 0, 'cartessia_wishlist', '', true );

// Note: v1.0.0 stores no transients, so there is nothing further to clear.
// When Live Activity (v1.1.0) introduces cached aggregates, its transients are
// removed here with delete_transient() by their known keys.
