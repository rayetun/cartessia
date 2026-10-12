<?php
/**
 * Delivery Date Picker — block render.
 *
 * A11y contract (WCAG 2.2 AA):
 * - No-JS baseline is a real, fully accessible native <input type="date"> with
 *   min/max. JavaScript enhances it into a role="grid" calendar (see view.js)
 *   with full keyboard support (arrows / Home / End / PageUp / PageDown), while
 *   the native input remains the value holder the form/AJAX reads.
 * - Every date button is a real control; unavailable dates are aria-disabled and
 *   announced as unavailable rather than silently doing nothing.
 * - Month navigation and the chosen date are announced through polite live
 *   regions.
 *
 * Date rules are server-authoritative: the min lead time, max range, and
 * disabled weekdays are signed with Cartessia_Delivery::sign_rules(), and the
 * add-to-cart handler recomputes the allowed window from the signed rules — a
 * tampered client cannot submit a disallowed date.
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

$cartessia_allowed_presets = array( 'popover', 'inline', 'compact' );
$cartessia_preset          = isset( $attributes['preset'] ) ? sanitize_key( $attributes['preset'] ) : 'popover';
if ( ! in_array( $cartessia_preset, $cartessia_allowed_presets, true ) ) {
	$cartessia_preset = 'popover';
}
$cartessia_label     = isset( $attributes['fieldLabel'] ) ? $attributes['fieldLabel'] : __( 'Delivery date', 'cartessia' );
$cartessia_min_lead  = isset( $attributes['minLeadDays'] ) ? max( 0, (int) $attributes['minLeadDays'] ) : 0;
$cartessia_max_days  = isset( $attributes['maxDays'] ) ? max( 1, (int) $attributes['maxDays'] ) : 365;
$cartessia_disabled  = ( isset( $attributes['disabledWeekdays'] ) && is_array( $attributes['disabledWeekdays'] ) )
	? array_values( array_unique( array_map( 'intval', $attributes['disabledWeekdays'] ) ) )
	: array();
$cartessia_btn_text  = ! empty( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Add to cart', 'cartessia' );

$cartessia_tz       = wp_timezone();
$cartessia_today    = new DateTimeImmutable( 'today', $cartessia_tz );
$cartessia_min_date = $cartessia_today->modify( '+' . $cartessia_min_lead . ' days' )->format( 'Y-m-d' );
$cartessia_max_date = $cartessia_today->modify( '+' . $cartessia_max_days . ' days' )->format( 'Y-m-d' );

$cartessia_rules = array(
	'minLeadDays'      => $cartessia_min_lead,
	'maxDays'          => $cartessia_max_days,
	'disabledWeekdays' => $cartessia_disabled,
	'fieldLabel'       => sanitize_text_field( $cartessia_label ),
);
$cartessia_rules_json = (string) wp_json_encode( $cartessia_rules );
$cartessia_signature  = Cartessia_Delivery::sign_rules( $cartessia_rules_json );

$cartessia_uid     = wp_unique_id( 'cartessia-dp-' );
$cartessia_date_id = $cartessia_uid . '-date';
$cartessia_cal_id  = $cartessia_uid . '-cal';

$cartessia_wrapper = get_block_wrapper_attributes(
	array(
		'class'               => 'cartessia-dp is-preset-' . $cartessia_preset,
		'data-wp-interactive' => 'cartessia',
	)
);
?>
<div
	<?php echo wp_kses_data( $cartessia_wrapper ); ?>
	data-wp-init="callbacks.initDatePicker"
	data-cartessia-preset="<?php echo esc_attr( $cartessia_preset ); ?>"
	data-cartessia-product="<?php echo esc_attr( (string) $cartessia_product->get_id() ); ?>"
	data-cartessia-rules="<?php echo esc_attr( $cartessia_rules_json ); ?>"
	data-cartessia-sig="<?php echo esc_attr( $cartessia_signature ); ?>"
	data-cartessia-min="<?php echo esc_attr( $cartessia_min_date ); ?>"
	data-cartessia-max="<?php echo esc_attr( $cartessia_max_date ); ?>"
	data-cartessia-disabled="<?php echo esc_attr( (string) wp_json_encode( $cartessia_disabled ) ); ?>"
>
	<div class="cartessia-dp__field">
		<label class="cartessia-dp__label" for="<?php echo esc_attr( $cartessia_date_id ); ?>">
			<?php echo esc_html( $cartessia_label ); ?>
		</label>
		<input
			type="date"
			class="cartessia-dp__native"
			id="<?php echo esc_attr( $cartessia_date_id ); ?>"
			min="<?php echo esc_attr( $cartessia_min_date ); ?>"
			max="<?php echo esc_attr( $cartessia_max_date ); ?>"
			data-wp-on--change="actions.onNativeDateChange"
		/>
	</div>

	<?php if ( 'popover' === $cartessia_preset ) : ?>
		<button
			type="button"
			class="cartessia-dp__toggle"
			aria-haspopup="true"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $cartessia_cal_id ); ?>"
			hidden
		>
			<span class="cartessia-dp__toggle-text"><?php esc_html_e( 'Choose a delivery date', 'cartessia' ); ?></span>
		</button>
	<?php endif; ?>

	<div class="cartessia-dp__calendar" id="<?php echo esc_attr( $cartessia_cal_id ); ?>" hidden></div>

	<p class="cartessia-dp__chosen" role="status" aria-live="polite"></p>

	<div class="cartessia-dp__actions">
		<button type="button" class="cartessia-dp__add" data-wp-on--click="actions.deliveryAddToCart">
			<?php echo esc_html( $cartessia_btn_text ); ?>
		</button>
	</div>

	<div class="cartessia-dp__announce screen-reader-text" role="status" aria-live="polite"></div>
</div>
<?php
