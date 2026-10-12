<?php
/**
 * Attribute-term swatch colour admin UI.
 *
 * Adds a colour picker to every WooCommerce product-attribute term (Add and
 * Edit screens under Products → Attributes → Configure terms). The chosen hex
 * is stored in term meta (Cartessia_Data::META_COLOR), which is exactly what the
 * swatch renderer reads — so a merchant sets colours without touching WP-CLI.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the swatch colour fields on product-attribute term screens.
 */
final class Cartessia_Swatch_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia_Swatch_Admin|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia_Swatch_Admin
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
		add_action( 'admin_init', array( $this, 'register_taxonomy_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Hook the colour field onto every product-attribute taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy_fields() {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return;
		}

		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );

			add_action( "{$taxonomy}_add_form_fields", array( $this, 'render_add_field' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'render_edit_field' ), 10, 2 );
			add_action( "created_{$taxonomy}", array( $this, 'save_field' ) );
			add_action( "edited_{$taxonomy}", array( $this, 'save_field' ) );
		}
	}

	/**
	 * Enqueue the WordPress colour picker on attribute-term screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}

		// Only on product-attribute taxonomies (pa_*).
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing, not a state change.
		if ( 0 !== strpos( $taxonomy, 'pa_' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script(
			'cartessia-swatch-admin',
			CARTESSIA_URL . 'admin/js/swatch-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			CARTESSIA_VERSION,
			true
		);
		wp_localize_script(
			'cartessia-swatch-admin',
			'cartessiaSwatchAdmin',
			array(
				'frameTitle'  => __( 'Select a swatch image', 'cartessia' ),
				'frameButton' => __( 'Use image', 'cartessia' ),
			)
		);
	}

	/**
	 * Colour field on the "Add term" screen.
	 *
	 * @return void
	 */
	public function render_add_field() {
		wp_nonce_field( 'cartessia_swatch_save', 'cartessia_swatch_nonce' );
		?>
		<div class="form-field cartessia-swatch-color-wrap">
			<label for="cartessia_swatch_color"><?php esc_html_e( 'Swatch colour', 'cartessia' ); ?></label>
			<input type="text" class="cartessia-color-field" name="cartessia_swatch_color" id="cartessia_swatch_color" value="" data-default-color="" />
			<p class="description"><?php esc_html_e( 'Optional. Pick a colour to show this term as a colour swatch in Cartessia. Leave empty for a text/button swatch.', 'cartessia' ); ?></p>
		</div>
		<div class="form-field cartessia-swatch-image-wrap">
			<label><?php esc_html_e( 'Swatch image', 'cartessia' ); ?></label>
			<?php self::render_image_control( 0 ); ?>
			<p class="description"><?php esc_html_e( 'Optional. Choose an image to show this term as an image swatch. An image takes precedence over the colour.', 'cartessia' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Output the media-picker control for the swatch image.
	 *
	 * @param int $image_id Current attachment ID (0 for none).
	 * @return void
	 */
	private static function render_image_control( $image_id ) {
		$image_id = absint( $image_id );
		$src      = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
		?>
		<div class="cartessia-swatch-image">
			<img
				class="cartessia-swatch-image__preview"
				src="<?php echo esc_url( $src ); ?>"
				alt=""
				style="max-width:60px;height:auto;display:<?php echo $src ? 'block' : 'none'; ?>;margin-bottom:6px;"
			/>
			<input type="hidden" class="cartessia-swatch-image__id" name="cartessia_swatch_image" value="<?php echo esc_attr( (string) $image_id ); ?>" />
			<button type="button" class="button cartessia-swatch-image__select"><?php esc_html_e( 'Select image', 'cartessia' ); ?></button>
			<button type="button" class="button-link cartessia-swatch-image__remove" style="display:<?php echo $image_id ? 'inline' : 'none'; ?>;"><?php esc_html_e( 'Remove image', 'cartessia' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Colour field on the "Edit term" screen.
	 *
	 * @param WP_Term $term     The term being edited.
	 * @param string  $taxonomy The taxonomy slug.
	 * @return void
	 */
	public function render_edit_field( $term, $taxonomy ) {
		$color = get_term_meta( $term->term_id, Cartessia_Data::META_COLOR, true );
		$color = is_string( $color ) ? $color : '';
		wp_nonce_field( 'cartessia_swatch_save', 'cartessia_swatch_nonce' );
		?>
		<tr class="form-field cartessia-swatch-color-wrap">
			<th scope="row"><label for="cartessia_swatch_color"><?php esc_html_e( 'Swatch colour', 'cartessia' ); ?></label></th>
			<td>
				<input type="text" class="cartessia-color-field" name="cartessia_swatch_color" id="cartessia_swatch_color" value="<?php echo esc_attr( $color ); ?>" data-default-color="<?php echo esc_attr( $color ); ?>" />
				<p class="description"><?php esc_html_e( 'Optional. Pick a colour to show this term as a colour swatch in Cartessia. Leave empty for a text/button swatch.', 'cartessia' ); ?></p>
			</td>
		</tr>
		<tr class="form-field cartessia-swatch-image-wrap">
			<th scope="row"><label><?php esc_html_e( 'Swatch image', 'cartessia' ); ?></label></th>
			<td>
				<?php self::render_image_control( (int) get_term_meta( $term->term_id, Cartessia_Data::META_IMAGE, true ) ); ?>
				<p class="description"><?php esc_html_e( 'Optional. Choose an image to show this term as an image swatch. An image takes precedence over the colour.', 'cartessia' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Persist the swatch colour for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_field( $term_id ) {
		// Real nonce verification (belt-and-braces alongside core's term nonce).
		if (
			! isset( $_POST['cartessia_swatch_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cartessia_swatch_nonce'] ) ), 'cartessia_swatch_save' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$raw   = isset( $_POST['cartessia_swatch_color'] ) ? sanitize_text_field( wp_unslash( $_POST['cartessia_swatch_color'] ) ) : '';
		$color = sanitize_hex_color( $raw );

		if ( $color ) {
			update_term_meta( $term_id, Cartessia_Data::META_COLOR, $color );
		} else {
			delete_term_meta( $term_id, Cartessia_Data::META_COLOR );
		}

		$image_id = isset( $_POST['cartessia_swatch_image'] ) ? absint( $_POST['cartessia_swatch_image'] ) : 0;

		if ( $image_id > 0 ) {
			update_term_meta( $term_id, Cartessia_Data::META_IMAGE, $image_id );
		} else {
			delete_term_meta( $term_id, Cartessia_Data::META_IMAGE );
		}
	}
}
