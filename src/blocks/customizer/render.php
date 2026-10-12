<?php
/**
 * Live Product Customizer — block render.
 *
 * A11y contract (WCAG 2.2 AA):
 * - Every input has a real associated <label>; the priced-options group is a
 *   WAI-ARIA radio group (roving tabindex, arrow keys, Space/Enter).
 * - The running total is shown visually AND announced through a polite live
 *   region every time it changes — dynamic price changes that are only shown
 *   visually are a common screen-reader gap this block exists to close.
 * - The initial total is rendered server-side, so it is correct before the view
 *   module hydrates.
 *
 * Prices are server-authoritative: the price config is signed with
 * Cartessia_Customizer::sign_config(), and the add-to-cart handler recomputes the
 * add-on from the signed config, so a tampered client cannot change costs.
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

if ( ! $cartessia_product instanceof WC_Product || ! $cartessia_product->is_purchasable() || $cartessia_product->is_type( 'variable' ) ) {
	return '';
}

$cartessia_text_enabled    = ! isset( $attributes['textEnabled'] ) || (bool) $attributes['textEnabled'];
$cartessia_text_label      = isset( $attributes['textLabel'] ) ? $attributes['textLabel'] : __( 'Personalization', 'cartessia' );
$cartessia_text_ph         = isset( $attributes['textPlaceholder'] ) ? $attributes['textPlaceholder'] : '';
$cartessia_text_price      = isset( $attributes['textPrice'] ) ? (float) $attributes['textPrice'] : 0.0;
$cartessia_text_max        = isset( $attributes['textMaxLength'] ) ? absint( $attributes['textMaxLength'] ) : 0;
$cartessia_options_enabled = ! empty( $attributes['optionsEnabled'] );
$cartessia_options_label   = isset( $attributes['optionsLabel'] ) ? $attributes['optionsLabel'] : __( 'Options', 'cartessia' );
$cartessia_options         = ( $cartessia_options_enabled && ! empty( $attributes['options'] ) && is_array( $attributes['options'] ) ) ? $attributes['options'] : array();
$cartessia_button_text     = ! empty( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Add to cart', 'cartessia' );

// Normalise options to { label, price } and build the signed price config.
$cartessia_clean_options = array();
foreach ( $cartessia_options as $cartessia_opt ) {
	if ( ! is_array( $cartessia_opt ) || empty( $cartessia_opt['label'] ) ) {
		continue;
	}
	$cartessia_clean_options[] = array(
		'label' => sanitize_text_field( $cartessia_opt['label'] ),
		'price' => isset( $cartessia_opt['price'] ) ? (float) $cartessia_opt['price'] : 0.0,
	);
}

$cartessia_config = array(
	'textLabel'     => sanitize_text_field( $cartessia_text_label ),
	'textPrice'     => $cartessia_text_price,
	'textMaxLength' => $cartessia_text_max,
	'optionsLabel'  => sanitize_text_field( $cartessia_options_label ),
	'options'       => $cartessia_clean_options,
);
$cartessia_config_json = (string) wp_json_encode( $cartessia_config );
$cartessia_signature   = Cartessia_Customizer::sign_config( $cartessia_config_json );

$cartessia_base   = (float) $cartessia_product->get_price();
$cartessia_uid    = wp_unique_id( 'cartessia-cz-' );
$cartessia_text_id = $cartessia_uid . '-text';
$cartessia_opts_id = $cartessia_uid . '-opts';

$cartessia_context = array(
	'base'      => $cartessia_base,
	'textPrice' => $cartessia_text_price,
	'textActive' => false,
	'optionPrice' => 0.0,
	'optionIndex' => -1,
	'totalText' => wp_strip_all_tags( wc_price( $cartessia_base ) ),
	'announce'  => '',
);

$cartessia_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'cartessia-cz',
		'data-wp-interactive' => 'cartessia',
	)
);
?>
<div
	<?php echo wp_kses_data( $cartessia_wrapper ); ?>
	data-wp-context="<?php echo esc_attr( (string) wp_json_encode( $cartessia_context ) ); ?>"
	data-cartessia-product="<?php echo esc_attr( (string) $cartessia_product->get_id() ); ?>"
	data-cartessia-config="<?php echo esc_attr( $cartessia_config_json ); ?>"
	data-cartessia-sig="<?php echo esc_attr( $cartessia_signature ); ?>"
>
	<?php if ( $cartessia_text_enabled ) : ?>
		<div class="cartessia-cz__field">
			<label class="cartessia-cz__label" for="<?php echo esc_attr( $cartessia_text_id ); ?>">
				<?php echo esc_html( $cartessia_text_label ); ?>
				<?php if ( $cartessia_text_price > 0 ) : ?>
					<span class="cartessia-cz__price-note">
						<?php
						/* translators: %s: formatted add-on price. */
						printf( esc_html__( '(+%s)', 'cartessia' ), wp_kses_post( wc_price( $cartessia_text_price ) ) );
						?>
					</span>
				<?php endif; ?>
			</label>
			<input
				type="text"
				class="cartessia-cz__text"
				id="<?php echo esc_attr( $cartessia_text_id ); ?>"
				<?php echo $cartessia_text_max > 0 ? 'maxlength="' . esc_attr( (string) $cartessia_text_max ) . '"' : ''; ?>
				placeholder="<?php echo esc_attr( $cartessia_text_ph ); ?>"
				data-wp-on--input="actions.onCustomizerText"
			/>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $cartessia_clean_options ) ) : ?>
		<fieldset class="cartessia-cz__options">
			<legend class="cartessia-cz__label" id="<?php echo esc_attr( $cartessia_opts_id ); ?>">
				<?php echo esc_html( $cartessia_options_label ); ?>
			</legend>
			<div class="cartessia-cz__options-list" role="radiogroup" aria-labelledby="<?php echo esc_attr( $cartessia_opts_id ); ?>">
				<button
					type="button"
					class="cartessia-cz__option"
					role="radio"
					aria-checked="true"
					tabindex="0"
					data-cartessia-index="-1"
					data-cartessia-price="0"
					data-wp-on--click="actions.selectCustomizerOption"
					data-wp-on--keydown="actions.customizerOptionKeydown"
				>
					<?php esc_html_e( 'None', 'cartessia' ); ?>
				</button>
				<?php foreach ( $cartessia_clean_options as $cartessia_i => $cartessia_opt ) : ?>
					<button
						type="button"
						class="cartessia-cz__option"
						role="radio"
						aria-checked="false"
						tabindex="-1"
						data-cartessia-index="<?php echo esc_attr( (string) $cartessia_i ); ?>"
						data-cartessia-price="<?php echo esc_attr( (string) $cartessia_opt['price'] ); ?>"
						data-wp-on--click="actions.selectCustomizerOption"
						data-wp-on--keydown="actions.customizerOptionKeydown"
					>
						<?php echo esc_html( $cartessia_opt['label'] ); ?>
						<?php if ( $cartessia_opt['price'] > 0 ) : ?>
							<span class="cartessia-cz__price-note">
								<?php
								/* translators: %s: formatted add-on price. */
								printf( esc_html__( '(+%s)', 'cartessia' ), wp_kses_post( wc_price( $cartessia_opt['price'] ) ) );
								?>
							</span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		</fieldset>
	<?php endif; ?>

	<div class="cartessia-cz__total">
		<span class="cartessia-cz__total-label"><?php esc_html_e( 'Total:', 'cartessia' ); ?></span>
		<span class="cartessia-cz__total-value" data-wp-text="context.totalText"><?php echo wp_kses_post( wc_price( $cartessia_base ) ); ?></span>
	</div>

	<div class="cartessia-cz__actions">
		<button
			type="button"
			class="cartessia-cz__add"
			data-wp-on--click="actions.customizerAddToCart"
		>
			<?php echo esc_html( $cartessia_button_text ); ?>
		</button>
	</div>

	<div class="screen-reader-text" role="status" aria-live="polite" data-wp-text="context.announce"></div>
</div>
<?php
