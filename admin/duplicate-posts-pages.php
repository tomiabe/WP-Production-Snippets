<?php
/**
 * Duplicate Posts & Pages (Admin Row Action)
 *
 * Adds a "Duplicate" link to:
 * - All Posts
 * - All Pages
 *
 * Creates a draft copy including:
 * - Title
 * - Content
 * - Excerpt
 * - Taxonomies
 * - Custom fields (meta)
 *
 * Tested up to: WordPress 6.x
 * Scope: Admin only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add "Duplicate" link to row actions for posts and pages.
 */
function wpps_add_duplicate_link( $actions, $post ) {
	if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return $actions;
	}

	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}

	$url = wp_nonce_url(
		add_query_arg(
			array(
				'action'  => 'wpps_duplicate_post',
				'post_id' => $post->ID,
			),
			admin_url( 'admin.php' )
		),
		'wpps_duplicate_post_' . $post->ID
	);

	$actions['wpps_duplicate'] = '<a href="' . esc_url( $url ) . '">Duplicate</a>';

	return $actions;
}
add_filter( 'post_row_actions', 'wpps_add_duplicate_link', 10, 2 );
add_filter( 'page_row_actions', 'wpps_add_duplicate_link', 10, 2 );

/**
 * Handle duplication request.
 */
function wpps_handle_duplicate_post() {
	if (
		! is_admin() ||
		! isset( $_GET['action'] ) ||
		$_GET['action'] !== 'wpps_duplicate_post'
	) {
		return;
	}

	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	if ( ! $post_id ) {
		wp_die( 'Missing post ID.' );
	}

	check_admin_referer( 'wpps_duplicate_post_' . $post_id );

	$original = get_post( $post_id );
	if ( ! $original || ! in_array( $original->post_type, array( 'post', 'page' ), true ) ) {
		wp_die( 'Invalid post type.' );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'You are not allowed to duplicate this item.' );
	}

	$new_post_data = array(
		'post_type'      => $original->post_type,
		'post_status'    => 'draft',
		'post_title'     => $original->post_title . ' (Copy)',
		'post_content'   => $original->post_content,
		'post_excerpt'   => $original->post_excerpt,
		'post_author'    => get_current_user_id(),
		'post_parent'    => $original->post_parent,
		'menu_order'     => $original->menu_order,
		'comment_status' => $original->comment_status,
		'ping_status'    => $original->ping_status,
		'post_password'  => $original->post_password,
		'to_ping'        => $original->to_ping,
	);

	$new_post_id = wp_insert_post( $new_post_data, true );

	if ( is_wp_error( $new_post_id ) ) {
		wp_die( 'Failed to duplicate: ' . esc_html( $new_post_id->get_error_message() ) );
	}

	// Copy taxonomies.
	$taxonomies = get_object_taxonomies( $original->post_type );
	foreach ( $taxonomies as $taxonomy ) {
		$terms = wp_get_object_terms(
			$post_id,
			$taxonomy,
			array( 'fields' => 'ids' )
		);

		if ( ! is_wp_error( $terms ) ) {
			wp_set_object_terms( $new_post_id, $terms, $taxonomy, false );
		}
	}

	// Copy post meta (excluding some system keys).
	$meta = get_post_meta( $post_id );
	$skip = array(
		'_edit_lock',
		'_edit_last',
		'_wp_old_slug',
	);

	foreach ( $meta as $meta_key => $values ) {
		if ( in_array( $meta_key, $skip, true ) ) {
			continue;
		}

		foreach ( $values as $value ) {
			add_post_meta(
				$new_post_id,
				$meta_key,
				maybe_unserialize( $value )
			);
		}
	}

	// Redirect back to the relevant list table.
	wp_safe_redirect(
		admin_url( 'edit.php?post_type=' . $original->post_type )
	);
	exit;
}
add_action( 'admin_init', 'wpps_handle_duplicate_post' );
