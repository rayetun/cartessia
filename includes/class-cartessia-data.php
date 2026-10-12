<?php
/**
 * WooCommerce data access — HPOS-safe by construction.
 *
 * Every read Cartessia needs (product attributes, swatch term meta, variation
 * availability, stock) goes through WooCommerce CRUD here. No block or render
 * file touches wp_posts / wp_postmeta / order tables directly, which is exactly
 * what keeps Cartessia compatible with High-Performance Order Storage.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static WooCommerce data helpers.
 */
final class Cartessia_Data {

	/**
	 * Term-meta key holding a swatch colour (hex) for an attribute term.
	 */
	const META_COLOR = 'cartessia_swatch_color';

	/**
	 * Term-meta key holding a swatch image attachment ID for an attribute term.
	 */
	const META_IMAGE = 'cartessia_swatch_image';

	/**
	 * Build accessible swatch data for a variable product.
	 *
	 * Returns one entry per variation attribute, each with its label and the
	 * list of selectable terms. Every term carries a human-readable text label
	 * (never colour alone) so the front end can render a visible text
	 * alternative beside each colour — a core WCAG requirement for the block.
	 *
	 * @param WC_Product $product Product to inspect.
	 * @return array<int,array<string,mixed>> Attribute → terms structure, or [].
	 */
	public static function get_swatch_attributes( $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return array();
		}

		$attributes = $product->get_variation_attributes();
		$out        = array();

		foreach ( $attributes as $attribute_name => $options ) {
			$taxonomy   = wc_variation_attribute_name( $attribute_name ); // e.g. "attribute_pa_color".
			$raw_name   = str_replace( 'attribute_', '', $taxonomy );
			$is_tax     = taxonomy_exists( $raw_name );
			$label      = wc_attribute_label( $raw_name, $product );
			$term_items = array();

			foreach ( (array) $options as $option ) {
				if ( '' === $option ) {
					continue;
				}

				$item = array(
					'value' => $option,
					'label' => $option,
					'color' => '',
					'image' => '',
				);

				if ( $is_tax ) {
					$term = get_term_by( 'slug', $option, $raw_name );
					if ( $term instanceof WP_Term ) {
						$item['label'] = $term->name;
						$color         = get_term_meta( $term->term_id, self::META_COLOR, true );
						$image_id      = absint( get_term_meta( $term->term_id, self::META_IMAGE, true ) );

						if ( is_string( $color ) && '' !== $color ) {
							$sanitized      = sanitize_hex_color( $color );
							$item['color']  = $sanitized ? $sanitized : '';
						}
						if ( $image_id > 0 ) {
							$src = wp_get_attachment_image_url( $image_id, 'thumbnail' );
							if ( $src ) {
								$item['image'] = esc_url_raw( $src );
							}
						}
					}
				}

				$term_items[] = $item;
			}

			$out[] = array(
				'name'  => $taxonomy,
				'label' => $label,
				'terms' => $term_items,
			);
		}

		return $out;
	}

	/**
	 * Compact map of a variable product's variations for client-side matching.
	 *
	 * Each entry is { id, attributes } where attributes uses WooCommerce's
	 * "attribute_pa_color" => "slug" shape (an empty slug means "any"). Quick
	 * View uses this to resolve the variation a shopper selected before adding
	 * it to the cart via the Store API.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_variations_map( $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return array();
		}

		$map = array();
		foreach ( $product->get_available_variations() as $variation ) {
			if ( empty( $variation['variation_id'] ) ) {
				continue;
			}
			$map[] = array(
				'id'         => (int) $variation['variation_id'],
				'attributes' => isset( $variation['attributes'] ) ? (array) $variation['attributes'] : array(),
			);
		}

		return $map;
	}

	/**
	 * Resolve a product from an ID with a type guard.
	 *
	 * @param int $product_id Product ID.
	 * @return WC_Product|null
	 */
	public static function get_product( $product_id ) {
		$product = wc_get_product( absint( $product_id ) );
		return $product instanceof WC_Product ? $product : null;
	}

	/**
	 * Resolve which product a block should target.
	 *
	 * Order: explicit productId attribute, block postId context (product loops),
	 * the global product, then the queried product on a single product page.
	 *
	 * @param array         $attributes Block attributes.
	 * @param WP_Block|null $block      Block instance.
	 * @return WC_Product|null
	 */
	public static function resolve_product( $attributes, $block = null ) {
		if ( ! empty( $attributes['productId'] ) ) {
			$product = self::get_product( (int) $attributes['productId'] );
			if ( $product ) {
				return $product;
			}
		}

		if ( $block && isset( $block->context['postId'] ) ) {
			$product = self::get_product( (int) $block->context['postId'] );
			if ( $product ) {
				return $product;
			}
		}

		global $product;
		if ( $product instanceof WC_Product ) {
			return $product;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			return self::get_product( get_queried_object_id() );
		}

		return null;
	}
}
