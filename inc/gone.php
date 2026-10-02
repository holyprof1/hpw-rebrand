<?php
/**
 * Removed content, retired archives, per-post noindex and legacy URL handling.
 *
 * - Removed posts (Phase 2 audit decisions) are trashed and their paths registered here. Requests for them
 *   answer 410 Gone with a clean "removed" page, never a redirect to unrelated content.
 * - Posts marked noindex (_hpw_noindex) stay reachable but carry a robots noindex and are left out of
 *   sitemaps, hubs, the homepage and related stories.
 * - Old categories that are not one of the four publication areas are retired: noindex while they still have
 *   posts, 410 once empty.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HPW_GONE_OPTION = 'hpw_gone_paths';

/** Normalise a URL or path to "/path/" form (no query string). */
function holyprofweb_gone_normalise( $url_or_path ) {
    $path = (string) wp_parse_url( (string) $url_or_path, PHP_URL_PATH );
    return '/' . trim( $path, '/' ) . '/';
}

/** Register paths as permanently gone. Accepts a list of URLs/paths. Returns how many were added. */
function holyprofweb_gone_register( array $urls ) {
    $list  = (array) get_option( HPW_GONE_OPTION, array() );
    $added = 0;
    foreach ( $urls as $u ) {
        $p = holyprofweb_gone_normalise( $u );
        if ( '/' !== $p && ! isset( $list[ $p ] ) ) {
            $list[ $p ] = time();
            $added++;
        }
    }
    update_option( HPW_GONE_OPTION, $list, false );
    return $added;
}

function holyprofweb_is_gone_path( $path ) {
    $list = (array) get_option( HPW_GONE_OPTION, array() );
    return isset( $list[ holyprofweb_gone_normalise( $path ) ] );
}

// ---- 410 responses ----------------------------------------------------------------------------

add_action( 'template_redirect', function () {
    if ( is_admin() ) {
        return;
    }
    $check = is_404() || is_category() || is_tag() || get_query_var( 'hpw_reports_archive' );
    if ( ! $check ) {
        return;
    }
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $p   = holyprofweb_gone_normalise( $uri );

    $gone = holyprofweb_is_gone_path( $p ) || holyprofweb_is_gone_path( preg_replace( '#page/\d+/$#', '', $p ) );
    // Retired category archives with nothing left to show.
    if ( ! $gone && is_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term && ! isset( holyprofweb_sections()[ $term->slug ] ) ) {
            $left = get_posts( array(
                'post_type' => 'post', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 1, 'no_found_rows' => true,
                'cat' => $term->term_id, 'meta_query' => holyprofweb_real_posts_meta_query(),
            ) );
            $gone = empty( $left );
        }
    }
    // Tag archives are not part of the publication (tags were auto-assigned in the old archive).
    if ( ! $gone && is_tag() ) {
        $gone = true;
    }
    if ( ! $gone ) {
        return;
    }
    global $wp_query;
    $wp_query->set_404();
    $wp_query->set( 'hpw_gone', 1 );
    status_header( 410 );
    nocache_headers();
    header( 'X-Robots-Tag: noindex' );
    header( 'Cache-Control: public, max-age=3600' ); // gone pages may be cached briefly; LiteSpeed honours this
    include get_template_directory() . '/404.php';
    exit;
}, 0 );

// ---- Per-post noindex ------------------------------------------------------------------------------

add_filter( 'wp_robots', function ( $robots ) {
    if ( is_singular( 'post' ) && get_post_meta( get_queried_object_id(), '_hpw_noindex', true ) ) {
        unset( $robots['max-image-preview'] );
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    // Legacy category archives are not editorial hubs.
    if ( is_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term && ! isset( holyprofweb_sections()[ $term->slug ] ) ) {
            unset( $robots['max-image-preview'] );
            $robots['noindex'] = true;
            $robots['follow']  = true;
        }
    }
    return $robots;
}, 30 );

// ---- Paginated front page: /page/N/ belongs to the blog archive ------------------------------------------

add_action( 'template_redirect', function () {
    if ( ! is_front_page() && ! is_home() ) {
        return;
    }
    $paged = (int) get_query_var( 'paged' );
    if ( $paged < 2 ) {
        return;
    }
    $max = (int) $GLOBALS['wp_query']->max_num_pages;
    if ( $max >= $paged ) {
        wp_safe_redirect( trailingslashit( holyprofweb_get_blog_url() ) . 'page/' . $paged . '/', 301 );
        exit;
    }
}, 1 );

// The "All stories" archive lists only real, indexable-quality posts.
add_action( 'pre_get_posts', function ( $q ) {
    if ( is_admin() || ! $q->is_main_query() ) {
        return;
    }
    if ( $q->get( 'hpw_blog_archive' ) || $q->is_home() || $q->is_search() ) {
        $q->set( 'meta_query', holyprofweb_real_posts_meta_query() );
    }
} );

// ---- Legacy sitemap URLs that search engines still request -----------------------------------------------
// Bingbot requested /news-sitemap.xml and /post-sitemap1.xml hundreds of times (404). Serve the current
// sitemap index there so those submissions stop erroring. (Remove the stale submissions in Bing Webmaster Tools.)

add_action( 'parse_request', function () {
    $path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
    $legacy = array( '/news-sitemap.xml', '/post-sitemap1.xml', '/post-sitemap.xml', '/sitemap_index.xml', '/page-sitemap.xml', '/category-sitemap.xml' );
    if ( ! in_array( $path, $legacy, true ) || ! function_exists( 'wp_sitemaps_get_server' ) ) {
        return;
    }
    $server = wp_sitemaps_get_server();
    if ( ! $server->sitemaps_enabled() ) {
        return;
    }
    status_header( 200 );
    $server->renderer->render_index( $server->index->get_sitemap_list() );
    exit;
}, 0 );

// ---- Legacy pages with a genuinely equivalent replacement ----------------------------------------------------

add_action( 'template_redirect', function () {
    if ( is_admin() ) {
        return;
    }
    $map = array(
        '/how-we-review/' => '/editorial-policy/',   // the old review methodology page: the editorial policy replaces it
        '/submit/'        => '/contact/',            // the old "Submit a Review" form: tips and corrections go through Contact
    );
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $p   = holyprofweb_gone_normalise( $uri );
    if ( isset( $map[ $p ] ) ) {
        wp_safe_redirect( home_url( $map[ $p ] ), 301 );
        exit;
    }
}, 0 );

// ---- Thin hubs: a section with fewer than 3 posts is not indexed or listed in the sitemap yet ---------------

function holyprofweb_section_post_count( $slug ) {
    static $cache = array();
    if ( isset( $cache[ $slug ] ) ) {
        return $cache[ $slug ];
    }
    $q = new WP_Query( holyprofweb_section_query_args( $slug, array( 'posts_per_page' => 3, 'fields' => 'ids', 'no_found_rows' => true ) ) );
    return $cache[ $slug ] = count( $q->posts );
}

add_filter( 'wp_robots', function ( $robots ) {
    if ( is_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term && isset( holyprofweb_sections()[ $term->slug ] ) && holyprofweb_section_post_count( $term->slug ) < 3 ) {
            unset( $robots['max-image-preview'] );
            $robots['noindex'] = true;
            $robots['follow']  = true;
        }
    }
    return $robots;
}, 31 );
