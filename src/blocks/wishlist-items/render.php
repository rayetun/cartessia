<?php
/**
 * Wishlist (display) — block render.
 *
 * The saved products are per-user, so they are rendered client-side from the
 * shared wishlist state (see view.js) rather than server-side — that keeps the
 * markup cache-safe. The server output is just the container, an empty-state
 * message (the accessible no-JS fallback), and a polite live region.
 *
 * @package Cartessia
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks markup (unused).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cartessia_columns = isset( $attributes['columns'] ) ? max( 1, min( 6, (int) $attributes['columns'] ) ) : 3;

$cartessia_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'cartessia-wl-items has-columns-' . $cartessia_columns,
		'data-wp-interactive' => 'cartessia',
	)
);
?>
<div
	<?php echo wp_kses_data( $cartessia_wrapper ); ?>
	data-wp-watch="callbacks.renderWishlistItems"
>
	<ul class="cartessia-wl-items__list"></ul>
	<p class="cartessia-wl-items__empty"><?php esc_html_e( 'Your wishlist is empty.', 'cartessia' ); ?></p>
	<div class="cartessia-wl-items__announce screen-reader-text" role="status" aria-live="polite"></div>
</div>
<?php
