<?php
/**
 * Variation Swatches — block render.
 *
 * Thin wrapper: resolve which product to show, then defer to the shared
 * renderer (Cartessia_Swatches::markup) that also powers automatic single-
 * product enhancement, so the block and the product page share one accessible
 * implementation. See that class for the full a11y contract.
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

if ( ! Cartessia_Swatches::has_swatches( $cartessia_product ) ) {
	return '';
}

$cartessia_wrapper = get_block_wrapper_attributes();
?>
<div <?php echo wp_kses_data( $cartessia_wrapper ); ?>>
	<?php
	Cartessia_Swatches::render_markup(
		$cartessia_product,
		array(
			'preset'     => isset( $attributes['preset'] ) ? $attributes['preset'] : 'rounded',
			'size'       => isset( $attributes['size'] ) ? $attributes['size'] : 'medium',
			'showLabels' => ! isset( $attributes['showLabels'] ) || (bool) $attributes['showLabels'],
		)
	);
	?>
</div>
<?php
