<?php
/**
 * Lightweight Global Cache Control (No Plugin)
 *
 * Features:
 * - Sends no-cache headers on frontend
 * - Cache-busts CSS/JS with version query string
 * - Adds "Purge Cache" button to admin bar
 * - Flushes object cache
 * - Optional rewrite flush
 *
 * WARNING:
 * This disables browser caching for HTML pages.
 * Not recommended for high-traffic production sites.
 *
 * Tested up to: WordPress 6.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1) Frontend no-cache headers (HTML responses).
 */
add_action( 'send_headers', function () {
	if ( is_admin() ) {
		return;
	}

	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
}, 1 );

/**
 * 2) Asset versioning (CSS/JS).
 */
function wpps_cache_bust_version() {
	$v = get_option( 'wpps_cache_bust_version' );

	if ( ! $v ) {
		$v = (string) time();
		update_option( 'wpps_cache_bust_version', $v );
	}

	return $v;
}

add_filter( 'style_loader_src', function ( $src ) {
	return add_query_arg( 'v', wpps_cache_bust_version(), $src );
}, 9999 );

add_filter( 'script_loader_src', function ( $src ) {
	return add_query_arg( 'v', wpps_cache_bust_version(), $src );
}, 9999 );

/**
 * 3) Admin bar "Purge Cache" button.
 */
add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$url = wp_nonce_url(
		add_query_arg( array( 'wpps_purge_cache' => '1' ), admin_url() ),
		'wpps_purge_cache_action'
	);

	$wp_admin_bar->add_node( array(
		'id'    => 'wpps-purge-cache',
		'title' => 'Purge Cache',
		'href'  => $url,
	) );
}, 100 );

/**
 * 4) Purge handler.
 */
add_action( 'admin_init', function () {
	if ( ! is_admin() || ! isset( $_GET['wpps_purge_cache'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'wpps_purge_cache_action' );

	// Bump asset version.
	update_option( 'wpps_cache_bust_version', (string) time() );

	// Flush WP object cache (if persistent cache exists).
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	// Optional: flush rewrite rules.
	flush_rewrite_rules( false );

	wp_safe_redirect(
		add_query_arg( 'wpps_cache_purged', '1', admin_url() )
	);
	exit;
});

/**
 * 5) Admin notice after purge.
 */
add_action( 'admin_notices', function () {
	if ( isset( $_GET['wpps_cache_purged'] ) ) {
		echo '<div class="notice notice-success is-dismissible">
			<p>Cache purged. CSS/JS version updated.</p>
		</div>';
	}
});
