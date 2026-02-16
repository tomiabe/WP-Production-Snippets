<?php
/**
 * Remove Category & Tag Base from URLs
 *
 * Removes:
 *   /category/
 *   /tag/
 *
 * Examples:
 *   /category/news/          → /news/
 *   /category/parent/child/  → /parent/child/
 *   /tag/design/             → /design/
 *
 * Features:
 * - Supports hierarchical categories
 * - Keeps posts and pages working
 * - Resolves base-less category & tag archives
 * - Handles /something/page/2 pagination
 * - 301-redirects old /category/... and /tag/... URLs
 * - Flushes rewrite rules once
 *
 * WARNING:
 * This may conflict with:
 * - Pages using the same slug as categories/tags
 * - Custom post types and their archives
 * - SEO plugins that modify rewrites
 *
 * Test carefully on staging before using in production.
 *
 * Tested up to: WordPress 6.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build full hierarchical category path (e.g. parent/child).
 *
 * @param WP_Term $term Category term.
 * @return string
 */
function wpps_cat_full_path( $term ) {
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	$slugs  = array( $term->slug );
	$parent = (int) $term->parent;

	while ( $parent > 0 ) {
		$p = get_term( $parent, 'category' );

		if ( ! $p || is_wp_error( $p ) ) {
			break;
		}

		array_unshift( $slugs, $p->slug );
		$parent = (int) $p->parent;
	}

	return implode( '/', $slugs );
}

/**
 * Find category term by full path (e.g. "parent/child").
 *
 * @param string $path Category path.
 * @return WP_Term|false
 */
function wpps_get_category_by_path( $path ) {
	$path = trim( $path, '/' );

	if ( $path === '' ) {
		return false;
	}

	$parts = explode( '/', $path );
	$last  = end( $parts );

	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'slug'       => $last,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return false;
	}

	foreach ( $terms as $term ) {
		if ( wpps_cat_full_path( $term ) === $path ) {
			return $term;
		}
	}

	return false;
}

/**
 * 1) Output base-less category links.
 */
add_filter(
	'category_link',
	function ( $link, $term_id ) {
		$term = get_term( $term_id, 'category' );

		if ( ! $term || is_wp_error( $term ) ) {
			return $link;
		}

		return home_url( user_trailingslashit( wpps_cat_full_path( $term ) ) );
	},
	10,
	2
);

/**
 * 1) Output base-less tag links.
 */
add_filter(
	'tag_link',
	function ( $link, $term_id ) {
		$term = get_term( $term_id, 'post_tag' );

		if ( ! $term || is_wp_error( $term ) ) {
			return $link;
		}

		return home_url( user_trailingslashit( $term->slug ) );
	},
	10,
	2
);

/**
 * 2) Resolve incoming /slug or /parent/child to category/tag archives.
 */
add_filter(
	'request',
	function ( $vars ) {
		if ( is_admin() ) {
			return $vars;
		}

		$uri_path  = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );

		// Strip home path prefix.
		if ( $home_path && strpos( $uri_path, $home_path ) === 0 ) {
			$uri_path = substr( $uri_path, strlen( $home_path ) );
		}

		$req = trim( $uri_path, '/' );

		if ( $req === '' ) {
			return $vars;
		}

		// Handle pagination suffix: /something/page/2
		$paged = 0;

		if ( preg_match( '#^(.*?)/page/([0-9]+)$#', $req, $m ) ) {
			$req   = trim( $m[1], '/' );
			$paged = (int) $m[2];
		}

		// Keep real pages/posts priority if they exist.
		$page_or_post = get_page_by_path( $req, OBJECT, array( 'page', 'post' ) );

		if ( $page_or_post ) {
			return $vars;
		}

		// Try category by full path first.
		$cat = wpps_get_category_by_path( $req );

		if ( $cat ) {
			$vars = array(
				'category_name' => wpps_cat_full_path( $cat ),
			);

			if ( $paged > 1 ) {
				$vars['paged'] = $paged;
			}

			return $vars;
		}

		// Try tag (single segment only).
		if ( strpos( $req, '/' ) === false ) {
			$tag = get_term_by( 'slug', $req, 'post_tag' );

			if ( $tag && ! is_wp_error( $tag ) ) {
				$vars = array(
					'tag' => $req,
				);

				if ( $paged > 1 ) {
					$vars['paged'] = $paged;
				}

				return $vars;
			}
		}

		return $vars;
	},
	20
);

/**
 * 3) Optional: redirect old /category/... and /tag/... URLs to base-less URLs.
 */
add_action(
	'template_redirect',
	function () {
		if ( is_category() ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) ) {
				$new = home_url( user_trailingslashit( wpps_cat_full_path( $term ) ) );

				if ( home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) ) !== $new ) {
					wp_redirect( $new, 301 );
					exit;
				}
			}
		}

		if ( is_tag() ) {
			$term = get_queried_object();

			if ( $term && ! is_wp_error( $term ) ) {
				$new = home_url( user_trailingslashit( $term->slug ) );

				if ( home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) ) !== $new ) {
					wp_redirect( $new, 301 );
					exit;
				}
			}
		}
	},
	1
);

/**
 * 4) Flush rewrite rules once.
 *
 * Note: If you remove this snippet, you may want to visit
 * Settings → Permalinks and save again to restore defaults.
 */
add_action(
	'init',
	function () {
		$key = 'wpps_tax_base_removed_flushed';

		if ( get_option( $key ) !== '1' ) {
			flush_rewrite_rules( false );
			update_option( $key, '1' );
		}
	},
	99
);
