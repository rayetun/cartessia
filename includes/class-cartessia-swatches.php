<?php
/**
 * Variation Swatches — shared renderer + single-product integration.
 *
 * The same accessible markup is used two ways:
 *  1. The cartessia/swatches block (render.php calls self::markup()).
 *  2. Automatic enhancement of WooCommerce's own variation form on single
 *     variable-product pages (self::render_on_product(), hooked into the add-to-
 *     cart form). There the swatches drive the native <select> elements and the
 *     view module hides the now-redundant dropdown rows — with JavaScript off,
 *     the native dropdowns stay visible and fully usable.
 *
 * Keeping one markup function means the block and the product page can never
 * drift apart in accessibility behaviour.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders swatches and wires the single-product integration.
 */
final class Cartessia_Swatches {

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia_Swatches|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia_Swatches
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wire the single-product hook.
	 */
	private function __construct() {
		if ( self::auto_enhance_enabled() ) {
			add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_on_product' ) );
		}
	}

	/**
	 * Whether automatic single-product enhancement is enabled.
	 *
	 * On by default; filterable so a theme or a future setting can disable it.
	 *
	 * @return bool
	 */
	public static function auto_enhance_enabled() {
		/**
		 * Filter whether Cartessia auto-renders swatches on single product pages.
		 *
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'cartessia_auto_enhance_product_page', true );
	}

	/**
	 * Enqueue the swatch view module and styles (for the non-block hook path).
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'cartessia-swatches',
			CARTESSIA_URL . 'build/blocks/swatches/style-index.css',
			array(),
			CARTESSIA_VERSION
		);

		if ( function_exists( 'wp_enqueue_script_module' ) ) {
			wp_enqueue_script_module(
				'cartessia-swatches-view',
				CARTESSIA_URL . 'build/blocks/swatches/view.js',
				array( '@wordpress/interactivity' ),
				CARTESSIA_VERSION
			);
		}
	}

	/**
	 * Render swatches automatically on a single variable-product page.
	 *
	 * @return void
	 */
	public function render_on_product() {
		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		self::enqueue_assets();

		self::render_markup(
			$product,
			array(
				'preset'     => 'rounded',
				'size'       => 'medium',
				'showLabels' => true,
			)
		);
	}

	/**
	 * Whether a product has any swatch attributes to render.
	 *
	 * @param WC_Product $product The product.
	 * @return bool
	 */
	public static function has_swatches( $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return false;
		}
		return ! empty( Cartessia_Data::get_swatch_attributes( $product ) );
	}

	/**
	 * Echo the accessible swatches markup for a product.
	 *
	 * Every dynamic value is escaped inline at its point of output, so this is
	 * safe to call directly in a template. Outputs nothing when the product has
	 * no swatch attributes.
	 *
	 * @param WC_Product $product The variable product.
	 * @param array      $args    { preset, size, showLabels, extra_class }.
	 * @return void
	 */
	public static function render_markup( $product, $args = array() ) {
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$groups = Cartessia_Data::get_swatch_attributes( $product );
		if ( empty( $groups ) ) {
			return;
		}

		$preset      = isset( $args['preset'] ) ? sanitize_key( $args['preset'] ) : 'rounded';
		$size        = isset( $args['size'] ) ? sanitize_key( $args['size'] ) : 'medium';
		$show_labels = ! isset( $args['showLabels'] ) || (bool) $args['showLabels'];
		$extra_class = isset( $args['extra_class'] ) ? sanitize_html_class( $args['extra_class'] ) : '';
		$uid         = wp_unique_id( 'cartessia-swatches-' );

		$classes = 'cartessia-swatches is-preset-' . $preset . ' is-size-' . $size;
		if ( '' !== $extra_class ) {
			$classes .= ' ' . $extra_class;
		}
		?>
		<div
			class="<?php echo esc_attr( $classes ); ?>"
			data-wp-interactive="cartessia"
			data-wp-init="callbacks.initSwatches"
			data-cartessia-product="<?php echo esc_attr( (string) $product->get_id() ); ?>"
			data-wp-context="<?php echo esc_attr( wp_json_encode( array( 'productId' => $product->get_id() ) ) ); ?>"
		>
			<?php foreach ( $groups as $index => $group ) : ?>
				<?php $label_id = $uid . '-label-' . $index; ?>
				<div class="cartessia-swatch-group">
					<span class="cartessia-swatch-group__label" id="<?php echo esc_attr( $label_id ); ?>">
						<?php echo esc_html( $group['label'] ); ?>
					</span>
					<div
						class="cartessia-swatch-group__options"
						role="radiogroup"
						aria-labelledby="<?php echo esc_attr( $label_id ); ?>"
						data-cartessia-attr="<?php echo esc_attr( $group['name'] ); ?>"
					>
						<?php foreach ( $group['terms'] as $term_index => $term ) : ?>
							<?php
							$has_color = '' !== $term['color'];
							$has_image = '' !== $term['image'];
							$is_visual = $has_color || $has_image;
							$style     = '';
							if ( $has_color ) {
								$style = '--cartessia-swatch-color:' . $term['color'] . ';';
							} elseif ( $has_image ) {
								$style = "--cartessia-swatch-image:url('" . esc_url( $term['image'] ) . "');";
							}
							$tabindex = 0 === $term_index ? '0' : '-1';
							?>
							<button
								type="button"
								class="cartessia-swatch<?php echo $is_visual ? ' cartessia-swatch--visual' : ' cartessia-swatch--text'; ?>"
								role="radio"
								aria-checked="false"
								tabindex="<?php echo esc_attr( $tabindex ); ?>"
								data-cartessia-value="<?php echo esc_attr( $term['value'] ); ?>"
								data-wp-on--click="actions.selectSwatch"
								data-wp-on--keydown="actions.onKeydown"
								<?php echo '' !== $style ? 'style="' . esc_attr( $style ) . '"' : ''; ?>
							>
								<?php if ( $is_visual ) : ?>
									<span class="cartessia-swatch__chip" aria-hidden="true"></span>
								<?php endif; ?>
								<?php if ( $show_labels || ! $is_visual ) : ?>
									<span class="cartessia-swatch__label"><?php echo esc_html( $term['label'] ); ?></span>
								<?php else : ?>
									<span class="screen-reader-text"><?php echo esc_html( $term['label'] ); ?></span>
								<?php endif; ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="screen-reader-text" role="status" aria-live="polite" data-wp-text="state.announcement"></div>
		</div>
		<?php
	}
}
