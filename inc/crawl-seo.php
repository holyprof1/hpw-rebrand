<?php
/**
 * Crawl & index hygiene: robots.txt, sitemaps, llms.txt, junk-URL noindex.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---- robots.txt --------------------------------------------------------------------------------

/**
 * Crawlers with their own group. A named group replaces the "*" group for that bot, so each one
 * repeats the same disallows. Names checked against OpenAI's published bot list (OAI-SearchBot =
 * ChatGPT search, GPTBot = model training; both deliberately allowed).
 */
function holyprofweb_allowed_crawlers() {
    return array(
        'OAI-SearchBot',   // ChatGPT search
        'GPTBot',          // OpenAI training; deliberate decision: allowed
        'ChatGPT-User',    // user-initiated ChatGPT fetches
        'ClaudeBot',
        'Claude-SearchBot',
        'PerplexityBot',
        'Google-Extended',
        'CCBot',           // already allowed before this rebuild; unchanged
    );
}

function holyprofweb_virtual_robots_txt( $output, $public ) {
    if ( get_option( 'hpw_discourage_indexing', 0 ) || ! $public ) {
        return "User-agent: *\nDisallow: /\n";
    }

    $rules = array(
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: /?s=',
        'Disallow: /search/',
        'Disallow: /*?replytocom=',
        'Disallow: /wp-login.php',
    );

    $out   = array( 'User-agent: *' );
    $out   = array_merge( $out, $rules, array( '' ) );
    foreach ( holyprofweb_allowed_crawlers() as $bot ) {
        $out[] = 'User-agent: ' . $bot;
        $out   = array_merge( $out, $rules, array( '' ) );
    }
    $out[] = 'Sitemap: ' . home_url( '/sitemap-index.xml' );
    $out[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );

    return implode( "\n", $out ) . "\n";
}
add_filter( 'robots_txt', 'holyprofweb_virtual_robots_txt', 10, 2 );

// ---- Sitemaps ----------------------------------------------------------------------------------
// The core index at /wp-sitemap.xml returned 404 on production while its child sitemaps worked,
// so a second index is served at /sitemap-index.xml, built from the same providers.

add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
    return in_array( $name, array( 'users' ), true ) ? false : $provider;
}, 10, 2 );

add_filter( 'wp_sitemaps_taxonomies', function ( $taxonomies ) {
    unset( $taxonomies['post_tag'] );
    return $taxonomies;
} );

add_filter( 'wp_sitemaps_taxonomies_query_args', function ( $args, $taxonomy ) {
    if ( 'category' === $taxonomy ) {
        $ids = array();
        foreach ( array_keys( holyprofweb_sections() ) as $slug ) {
            $t = get_term_by( 'slug', $slug, 'category' );
            if ( $t ) {
                $ids[] = (int) $t->term_id;
            }
        }
        $args['include']    = $ids ? $ids : array( 0 );
        $args['hide_empty'] = false;
    }
    return $args;
}, 10, 2 );

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
    if ( 'post' === $post_type ) {
        $args['meta_query'] = array( array( 'key' => '_hpw_placeholder_post', 'compare' => 'NOT EXISTS' ) );
    }
    if ( 'page' === $post_type ) {
        $skip = get_posts( array(
            'post_type' => 'page', 'name__in' => array( 'submit' ), 'fields' => 'ids', 'posts_per_page' => 5,
        ) );
        $args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $skip );
    }
    return $args;
}, 10, 2 );

add_action( 'init', function () {
    add_rewrite_rule( '^sitemap-index\.xml$', 'index.php?hpw_sitemap_index=1', 'top' );
    add_rewrite_rule( '^llms\.txt$', 'index.php?hpw_llms=1', 'top' );
} );
add_filter( 'query_vars', function ( $v ) {
    $v[] = 'hpw_sitemap_index';
    $v[] = 'hpw_llms';
    return $v;
} );

add_action( 'template_redirect', function () {
    if ( get_query_var( 'hpw_sitemap_index' ) ) {
        $server = function_exists( 'wp_sitemaps_get_server' ) ? wp_sitemaps_get_server() : null;
        if ( ! $server || ! $server->sitemaps_enabled() ) {
            status_header( 404 );
            exit;
        }
        $entries = array();
        foreach ( $server->registry->get_sitemaps() as $sitemap ) {
            $pages = max( 1, (int) $sitemap->get_max_num_pages() );
            if ( ! (int) $sitemap->get_max_num_pages() ) {
                continue;
            }
            for ( $i = 1; $i <= $pages; $i++ ) {
                $entries[] = $sitemap->get_sitemap_url( $sitemap->name, $i );
            }
        }
        status_header( 200 );
        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( $entries as $url ) {
            echo '<sitemap><loc>' . esc_url( $url ) . '</loc></sitemap>' . "\n";
        }
        echo '</sitemapindex>';
        exit;
    }

    if ( get_query_var( 'hpw_llms' ) ) {
        status_header( 200 );
        header( 'Content-Type: text/plain; charset=UTF-8' );
        echo holyprofweb_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput
        exit;
    }
}, 1 );

// ---- llms.txt (a discovery aid only; crawling, sitemaps and markup remain the real signals) -----

function holyprofweb_llms_txt() {
    $name = get_bloginfo( 'name' );
    $out  = "# {$name}\n\n";
    $out .= "> A discovery publication for new apps, websites, products, people and internet trends. We find things early, research them and explain them clearly. Articles say what was tested first-hand and what was only researched, and mark anything we could not verify.\n\n";
    $out .= "## Sections\n\n";
    foreach ( holyprofweb_sections() as $slug => $s ) {
        $out .= '- [' . $s['label'] . '](' . holyprofweb_section_url( $slug ) . '): ' . $s['intro'] . "\n";
    }
    $out .= "\n## Trust and policies\n\n";
    foreach ( array( 'about' => 'About', 'editorial-policy' => 'Editorial policy', 'corrections-updates-policy' => 'Corrections policy', 'contact' => 'Contact' ) as $slug => $label ) {
        if ( get_page_by_path( $slug ) ) {
            $out .= '- [' . $label . '](' . home_url( '/' . $slug . '/' ) . ")\n";
        }
    }
    $out .= "\n## Machine-readable\n\n- [Sitemap index](" . home_url( '/sitemap-index.xml' ) . ")\n- [RSS feed](" . home_url( '/feed/' ) . ")\n";
    return $out;
}

// ---- Junk URLs: noindex / redirect -------------------------------------------------------------

add_filter( 'wp_robots', function ( $robots ) {
    if ( is_tag() || is_date() || is_attachment() || ( is_paged() && is_search() ) ) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    // Large previews are required for Google Discover; make sure nothing narrows them.
    if ( empty( $robots['noindex'] ) ) {
        $robots['max-image-preview'] = 'large';
    }
    return $robots;
}, 15 );

// Attachment pages add nothing: send them to their post, or to the file itself.
add_action( 'template_redirect', function () {
    if ( ! is_attachment() ) {
        return;
    }
    $att    = get_queried_object();
    $parent = $att && $att->post_parent ? get_permalink( $att->post_parent ) : '';
    wp_safe_redirect( $parent ? $parent : wp_get_attachment_url( $att->ID ), 301 );
    exit;
}, 5 );
