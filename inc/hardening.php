<?php
/**
 * Small privacy and hygiene hardening.
 *
 * - The public REST API no longer lists user accounts (names, slugs) to anonymous visitors.
 * - oEmbed discovery links are not printed (the oEmbed endpoint produced thousands of 4xx/5xx hits in the logs).
 * - Leftover advertising pages stay reachable but are kept out of search results and the sitemap.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'rest_endpoints', function ( $endpoints ) {
    if ( ! is_user_logged_in() ) {
        foreach ( array_keys( $endpoints ) as $route ) {
            if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
                unset( $endpoints[ $route ] );
            }
        }
    }
    return $endpoints;
} );

remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );

function holyprofweb_noindex_page_slugs() {
    return array( 'advertise', 'work-with-us' );
}

add_filter( 'wp_robots', function ( $robots ) {
    if ( is_page( holyprofweb_noindex_page_slugs() ) ) {
        unset( $robots['max-image-preview'] );
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
}, 32 );

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
    if ( 'page' === $post_type ) {
        $skip = get_posts( array( 'post_type' => 'page', 'post_name__in' => array_merge( holyprofweb_noindex_page_slugs(), array( 'how-we-review', 'submit' ) ), 'fields' => 'ids', 'posts_per_page' => 10, 'post_status' => 'publish' ) );
        $args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $skip );
    }
    return $args;
}, 20, 2 );

// The legacy "page shell" preloader (an inline script plus an overlay printed on every page) is removed.
add_action( 'init', function () {
    remove_action( 'wp_head', 'holyprofweb_render_page_shell_loader_script', 1 );
    remove_action( 'wp_body_open', 'holyprofweb_render_page_shell_loader', 5 );
    remove_filter( 'body_class', 'holyprofweb_page_shell_body_class' );
}, 0 );

// ---- Content hygiene: no links to removed pages, one H1 per page ------------------------------------------------

add_filter( 'the_content', function ( $content ) {
    if ( ! is_singular() || '' === $content ) {
        return $content;
    }
    // Headings in body content start at H2; the page title is the only H1.
    $content = preg_replace( '#<h1(\s[^>]*)?>#i', '<h2$1>', $content );
    $content = preg_replace( '#</h1>#i', '</h2>', $content );

    if ( false === stripos( $content, '<a ' ) ) {
        return $content;
    }
    $home_hosts = array( wp_parse_url( home_url(), PHP_URL_HOST ), 'holyprofweb.com', 'www.holyprofweb.com' );
    return preg_replace_callback( "#<a\s[^>]*href=[\"']([^\"']+)[\"'][^>]*>(.*?)</a>#is", function ( $m ) use ( $home_hosts ) {
        $host = wp_parse_url( $m[1], PHP_URL_HOST );
        if ( $host && ! in_array( $host, $home_hosts, true ) ) {
            return $m[0];
        }
        $path = wp_parse_url( $m[1], PHP_URL_PATH );
        if ( $path && ( 0 === strpos( $path, '/category/' ) || ( function_exists( 'holyprofweb_is_gone_path' ) && holyprofweb_is_gone_path( $path ) ) ) ) { // legacy category archives are retired too
            return $m[2]; // keep the words, drop the dead link
        }
        return $m[0];
    }, $content );
}, 25 );

// "All stories" is a date-ordered duplicate of the hubs: followed but not indexed.
add_filter( 'wp_robots', function ( $robots ) {
    if ( get_query_var( 'hpw_blog_archive' ) ) {
        unset( $robots['max-image-preview'] );
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
}, 33 );
