<?php
/**
 * Time-Based Style Variation Switcher (Block Themes)
 *
 * Automatically switches theme.json style variations
 * based on the site’s local time.
 *
 * Requirements:
 * - WordPress 6.x+
 * - Block theme using /styles/*.json variations
 *
 * Example:
 * - Morning   → default theme.json
 * - Afternoon → styles/parchment.json
 * - Night     → styles/inverted.json
 *
 * Notes:
 * - Uses site timezone (Settings → General → Timezone)
 * - No JavaScript required
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_theme_json_data_theme', function ( $theme_json ) {
	$hour = (int) current_time( 'G' ); // 0–23 (site timezone)

	// ---- Time windows (change as you like) ----
	$morning_starts   = 7;   // 07:00
	$afternoon_starts = 13;  // 13:00
	$night_starts     = 19;  // 19:00

	// ---- Style variation slugs (adjust for your theme) ----
	$morning_style   = 'default';   // default theme.json
	$afternoon_style = 'parchment'; // /styles/parchment.json
	$night_style     = 'inverted';  // /styles/inverted.json

	$style_slug = '';

	// Night
	if ( $hour >= $night_starts || $hour < $morning_starts ) {
		$style_slug = $night_style;

	// Afternoon
	} elseif ( $hour >= $afternoon_starts ) {
		$style_slug = $afternoon_style;

	// Morning → keep default theme.json
	} else {
		return $theme_json;
	}

	$file = get_theme_file_path( "styles/{$style_slug}.json" );
	if ( ! file_exists( $file ) ) {
		return $theme_json;
	}

	$variation = wp_json_file_decode( $file, array( 'associative' => true ) );
	if ( is_array( $variation ) ) {
		// Remove metadata keys before merging.
		unset( $variation['title'], $variation['slug'] );
		$theme_json->update_with( $variation );
	}

	return $theme_json;
}, 20 );
