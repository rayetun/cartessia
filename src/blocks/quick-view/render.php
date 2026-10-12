<?php
/**
 * Quick View — block render.
 *
 * A11y contract (WCAG 2.2 AA):
 * - The preview opens in a native <dialog> via showModal(): the browser traps
 *   focus inside it, Escape closes it, and focus returns to the trigger button
 *   automatically. We do not re-implement a focus trap — the platform's is
 *   correct and better tested.
 * - The dialog is labelled by the product title (aria-labelledby), and a polite
 *   live region announces "Product loaded" on open and the add-to-cart result.
 * - Variation swatches inside the dialog use the same accessible radio-group as
 *   the Swatches block (shared interactivity), so keyboard and screen-reader
 *   behaviour is identical in the modal.
 * - The close button has a real text label (screen-reader-text), not just an ×.
 *
 * The dialog content is server-rendered, so it is present for assistive tech
 * immediately and needs no round-trip to populate.
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

if ( ! $cartessia_product instanceof WC_Product || ! $cartessia_product->is_purchasable() ) {
	return '';
}

$cartessia_preset      = isset( $attributes['preset'] ) ? sanitize_key( $attributes['preset'] ) : 'centered';
$cartessia_button_text = ! empty( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Quick view', 'cartessia' );
$cartessia_is_variable = $cartessia_product->is_type( 'variable' );
$cartessia_variations  = $cartessia_is_variable ? Cartessia_Data::get_variations_map( $cartessia_product ) : array();

// Display options.
$cartessia_allowed_sizes = array( 'thumbnail', 'medium', 'large', 'woocommerce_thumbnail', 'woocommerce_single' );
$cartessia_image_size    = isset( $attributes['imageSize'] ) ? sanitize_key( $attributes['imageSize'] ) : 'woocommerce_single';
if ( ! in_array( $cartessia_image_size, $cartessia_allowed_sizes, true ) ) {
	$cartessia_image_size = 'woocommerce_single';
}
$cartessia_show_price = ! isset( $attributes['showPrice'] ) || (bool) $attributes['showPrice'];
$cartessia_show_desc  = ! isset( $attributes['showDescription'] ) || (bool) $attributes['showDescription'];
$cartessia_show_view  = ! isset( $attributes['showViewLink'] ) || (bool) $attributes['showViewLink'];
$cartessia_accent     = isset( $attributes['accentColor'] ) ? sanitize_hex_color( $attributes['accentColor'] ) : '';
$cartessia_close      = isset( $attributes['closeColor'] ) ? sanitize_hex_color( $attributes['closeColor'] ) : '';
$cartessia_short_desc = $cartessia_product->get_short_description();

$cartessia_uid      = wp_unique_id( 'cartessia-qv-' );
$cartessia_title_id = $cartessia_uid . '-title';

$cartessia_wrapper_args = array(
	'class'               => 'cartessia-qv is-preset-' . $cartessia_preset,
	'data-wp-interactive' => 'cartessia',
);
$cartessia_style = '';
if ( '' !== $cartessia_accent ) {
	$cartessia_style .= '--cartessia-qv-accent:' . $cartessia_accent . ';';
}
if ( '' !== $cartessia_close ) {
	$cartessia_style .= '--cartessia-qv-close:' . $cartessia_close . ';';
}
if ( '' !== $cartessia_style ) {
	$cartessia_wrapper_args['style'] = $cartessia_style;
}
$cartessia_wrapper = get_block_wrapper_attributes( $cartessia_wrapper_args );
?>
<div <?php echo wp_kses_data( $cartessia_wrapper ); ?>>
	<button
		type="button"
		class="cartessia-qv__trigger"
		aria-haspopup="dialog"
		data-wp-on--click="actions.openQuickView"
	>
		<?php echo esc_html( $cartessia_button_text ); ?>
	</button>

	<dialog
		class="cartessia-qv__dialog"
		aria-labelledby="<?php echo esc_attr( $cartessia_title_id ); ?>"
		data-cartessia-product="<?php echo esc_attr( (string) $cartessia_product->get_id() ); ?>"
		data-cartessia-type="<?php echo esc_attr( $cartessia_product->get_type() ); ?>"
		data-cartessia-variations="<?php echo esc_attr( (string) wp_json_encode( $cartessia_variations ) ); ?>"
		data-wp-on--close="actions.onQuickViewClose"
	>
		<div class="cartessia-qv__panel">
			<button
				type="button"
				class="cartessia-qv__close"
				data-wp-on--click="actions.closeQuickView"
			>
				<span class="screen-reader-text"><?php esc_html_e( 'Close', 'cartessia' ); ?></span>
				<span aria-hidden="true">&times;</span>
			</button>

			<div class="cartessia-qv__body">
				<div class="cartessia-qv__media">
					<?php echo wp_kses_post( $cartessia_product->get_image( $cartessia_image_size ) ); ?>
				</div>
				<div class="cartessia-qv__summary">
					<h2 id="<?php echo esc_attr( $cartessia_title_id ); ?>" class="cartessia-qv__title">
						<?php echo esc_html( $cartessia_product->get_name() ); ?>
					</h2>
					<?php if ( $cartessia_show_price ) : ?>
						<div class="cartessia-qv__price">
							<?php echo wp_kses_post( $cartessia_product->get_price_html() ); ?>
						</div>
					<?php endif; ?>
					<?php if ( $cartessia_show_desc && '' !== trim( (string) $cartessia_short_desc ) ) : ?>
						<div class="cartessia-qv__desc">
							<?php echo wp_kses_post( wpautop( $cartessia_short_desc ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $cartessia_is_variable ) : ?>
						<?php Cartessia_Swatches::render_markup( $cartessia_product ); ?>
					<?php endif; ?>

					<div class="cartessia-qv__actions">
						<button
							type="button"
							class="cartessia-qv__add"
							data-wp-on--click="actions.quickViewAddToCart"
						>
							<?php esc_html_e( 'Add to cart', 'cartessia' ); ?>
						</button>
						<?php if ( $cartessia_show_view ) : ?>
							<a class="cartessia-qv__view" href="<?php echo esc_url( get_permalink( $cartessia_product->get_id() ) ); ?>">
								<?php esc_html_e( 'View full details', 'cartessia' ); ?>
							</a>
						<?php endif; ?>
					</div>

					<div class="screen-reader-text" role="status" aria-live="polite" data-wp-text="state.qvAnnouncement"></div>
				</div>
			</div>
		</div>
	</dialog>
</div>
<?php
