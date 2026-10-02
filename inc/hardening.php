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
