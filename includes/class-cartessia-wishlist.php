<?php
/**
 * Wishlist storage.
 *
 * Logged-in customers get a server-side wishlist (user meta) that follows them
 * across devices; guests keep theirs in the browser (localStorage), handled
 * entirely client-side. This class provides the two AJAX endpoints the view
 * module uses for the logged-in case.
 *
 * The wishlist is the user's own list, scoped to the current user, so it holds
 * no other user's data — but the endpoints still verify a nonce and a logged-in
 * user, and sanitise every ID to an integer.
 *
 * @package Cartessia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the wishlist AJAX endpoints.
 */
final class Cartessia_Wishlist {

	/**
	 * User-meta key holding the logged-in wishlist (array of product IDs).
	 */
	const META_KEY = 'cartessia_wishlist';

	/**
	 * Singleton instance.
	 *
	 * @var Cartessia_Wishlist|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Cartessia_Wishlist
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
		add_action( 'wp_ajax_cartessia_wishlist_get', array( $this, 'handle_get' ) );
		add_action( 'wp_ajax_cartessia_wishlist_set', array( $this, 'handle_set' ) );
	}

	/**
	 * AJAX: return the current user's wishlist product IDs.
	 *
	 * @return void
	 */
	public function handle_get() {
		check_ajax_referer( 'cartessia_wishlist', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'cartessia' ) ), 403 );
		}

		wp_send_json_success( array( 'ids' => self::get_ids( get_current_user_id() ) ) );
	}

	/**
	 * AJAX: store the current user's wishlist product IDs.
	 *
	 * @return void
	 */
	public function handle_set() {
		check_ajax_referer( 'cartessia_wishlist', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not logged in.', 'cartessia' ) ), 403 );
		}

		// Sanitise inline with the access: each ID is cast to a positive int.
		$ids = ( isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) )
			? array_map( 'absint', wp_unslash( $_POST['ids'] ) )
			: array();
		$ids = array_values( array_unique( array_filter( $ids ) ) );

		update_user_meta( get_current_user_id(), self::META_KEY, $ids );

		wp_send_json_success( array( 'ids' => $ids ) );
	}

	/**
	 * Read a user's wishlist IDs.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public static function get_ids( $user_id ) {
		$ids = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_array( $ids ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}
}
