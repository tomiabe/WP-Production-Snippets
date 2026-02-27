<?php
/**
 * Per-Post Visibility Toggles (Posts Only)
 *
 * Adds sidebar options to:
 * - Hide featured image
 * - Hide excerpt
 *
 * Applies only to the "post" post type.
 *
 * Tested up to: WordPress 6.x
 * Scope: Admin + frontend output filtering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ========= META BOX ========= */

/**
 * Register meta box on the post edit screen.
 */
function wpps_hide_elements_add_meta_box() {
	add_meta_box(
		'wpps_hide_elements_box',
		'Post Display Options',
		'wpps_hide_elements_meta_box_html',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'wpps_hide_elements_add_meta_box' );

/**
 * Meta box HTML.
 */
function wpps_hide_elements_meta_box_html( $post ) {
	wp_nonce_field( 'wpps_hide_elements_save', 'wpps_hide_elements_nonce' );

	$hide_featured = get_post_meta( $post->ID, '_wpps_hide_featured_image', true );
	$hide_excerpt  = get_post_meta( $post->ID, '_wpps_hide_excerpt', true );
	?>
	<p>
		<label>
			<input type="checkbox"
				   name="wpps_hide_featured_image"
				   value="1"
				<?php checked( $hide_featured, '1' ); ?> />
			Hide featured image on this post
		</label>
	</p>
	<p>
		<label>
			<input type="checkbox"
				   name="wpps_hide_excerpt"
				   value="1"
				<?php checked( $hide_excerpt, '1' ); ?> />
			Hide excerpt on this post
		</label>
	</p>
	<?php
}

/* ========= SAVE META ========= */

/**
 * Save meta box values.
 */
function wpps_hide_elements_save_meta( $post_id ) {
	if (
		! isset( $_POST['wpps_hide_elements_nonce'] ) ||
		! wp_verify_nonce( $_POST['wpps_hide_elements_nonce'], 'wpps_hide_elements_save' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$hide_featured = isset( $_POST['wpps_hide_featured_image'] ) ? '1' : '';
	$hide_excerpt  = isset( $_POST['wpps_hide_excerpt'] ) ? '1' : '';

	if ( $hide_featured ) {
		update_post_meta( $post_id, '_wpps_hide_featured_image', '1' );
	} else {
		delete_post_meta( $post_id, '_wpps_hide_featured_image' );
	}

	if ( $hide_excerpt ) {
		update_post_meta( $post_id, '_wpps_hide_excerpt', '1' );
	} else {
		delete_post_meta( $post_id, '_wpps_hide_excerpt' );
	}
}
add_action( 'save_post_post', 'wpps_hide_elements_save_meta' );

/* ========= FRONTEND FILTERS ========= */

/**
 * Hide featured image output on single post if enabled.
 */
function wpps_hide_featured_image_output( $html, $post_id ) {
	if ( is_admin() ) {
		return $html;
	}

	if ( is_singular( 'post' ) && (int) get_queried_object_id() === (int) $post_id ) {
		$hide = get_post_meta( $post_id, '_wpps_hide_featured_image', true );
		if ( '1' === $hide ) {
			return '';
		}
	}

	return $html;
}
add_filter( 'post_thumbnail_html', 'wpps_hide_featured_image_output', 10, 2 );

/**
 * Hide excerpt output on single post if enabled.
 */
function wpps_hide_excerpt_output( $excerpt, $post ) {
	if ( is_admin() ) {
		return $excerpt;
	}

	if ( ! $post instanceof WP_Post ) {
		return $excerpt;
	}

	if ( is_singular( 'post' ) && (int) get_queried_object_id() === (int) $post->ID ) {
		$hide = get_post_meta( $post->ID, '_wpps_hide_excerpt', true );
		if ( '1' === $hide ) {
			return '';
		}
	}

	return $excerpt;
}
add_filter( 'get_the_excerpt', 'wpps_hide_excerpt_output', 10, 2 );
