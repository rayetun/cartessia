<?php
/**
 * Wishlist Button — block render.
 *
 * A11y contract (WCAG 2.2 AA):
 * - A real <button> with aria-pressed reflecting whether the product is saved,
 *   so screen readers announce the toggle state on activation. The accessible
 *   name updates between "Add …" and "Remove …" via the shared store.
 * - The heart icon is decorative (aria-hidden); the button's name comes from a
 *   text label (visible for the text preset, visually hidden for icon presets).
 * - State is hydrated client-side, so a cached page never bakes in one user's
 *   wishlist; aria-pressed starts false and is corrected on load.
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

$cartessia_product = Cartessia_Data::resolve_product( $attributes, $block );

if ( ! $cartessia_product instanceof WC_Product ) {
	return '';
}

$cartessia_allowed_presets = array( 'heart', 'outline', 'text' );
$cartessia_preset          = isset( $attributes['preset'] ) ? sanitize_key( $attributes['preset'] ) : 'heart';
if ( ! in_array( $cartessia_preset, $cartessia_allowed_presets, true ) ) {
	$cartessia_preset = 'heart';
}

$cartessia_add_label    = ! empty( $attributes['addLabel'] ) ? $attributes['addLabel'] : __( 'Add to wishlist', 'cartessia' );
$cartessia_remove_label = ! empty( $attributes['removeLabel'] ) ? $attributes['removeLabel'] : __( 'Remove from wishlist', 'cartessia' );
$cartessia_is_text      = 'text' === $cartessia_preset;
$cartessia_label_class  = $cartessia_is_text ? 'cartessia-wl__text' : 'screen-reader-text';

$cartessia_context = array(
	'productId'   => $cartessia_product->get_id(),
	'addLabel'    => $cartessia_add_label,
	'removeLabel' => $cartessia_remove_label,
);

$cartessia_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'cartessia-wl is-preset-' . $cartessia_preset,
		'data-wp-interactive' => 'cartessia',
	)
);
?>
<button
	<?php echo wp_kses_data( $cartessia_wrapper ); ?>
	type="button"
	aria-pressed="false"
	data-wp-context="<?php echo esc_attr( (string) wp_json_encode( $cartessia_context ) ); ?>"
	data-wp-on--click="actions.toggleWishlist"
	data-wp-bind--aria-pressed="state.isInWishlist"
>
	<span class="cartessia-wl__icon" aria-hidden="true">
		<svg viewBox="0 0 24 24" width="22" height="22" focusable="false" aria-hidden="true"><path d="M12 20.7 4.1 12.9a4.6 4.6 0 0 1 0-6.5 4.6 4.6 0 0 1 6.5 0l1.4 1.4 1.4-1.4a4.6 4.6 0 0 1 6.5 0 4.6 4.6 0 0 1 0 6.5Z"/></svg>
	</span>
	<span class="<?php echo esc_attr( $cartessia_label_class ); ?>" data-wp-text="state.wishlistLabel"><?php echo esc_html( $cartessia_add_label ); ?></span>
</button>
<?php
