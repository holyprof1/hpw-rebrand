<?php
/**
 * Editorial sections: the four hubs that make up the publication.
 *
 * Existing posts are never re-categorised. Each section also "adopts" a small set of legacy
 * categories so the hubs are populated from day one. The later content audit decides what stays.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HPW_SECTIONS_VERSION = '3';

function holyprofweb_sections() {
    return array(
        'apps-websites' => array(
            'label'  => 'Apps & Websites',
            'title'  => 'New Apps & Websites',
            'intro'  => 'New apps, websites, AI tools and online services: what they are, who made them, what they cost and where they work.',
            // Left out on purpose: web-development-seo and online-business (service/marketing posts), loan-apps (old review farm).
            'legacy' => array( 'apps', 'app', 'websites', 'website-reviews', 'crypto', 'earning-platforms', 'fintech', 'tech', 'startups', 'game' ),
        ),
        'products-tech' => array(
            'label'  => 'Products & Tech',
            'title'  => 'Products & Tech',
            'intro'  => 'New gadgets, phones, accessories and technology products: what changed, what it costs and who it is for.',
            'legacy' => array( 'product-reviews', 'shopping' ),
        ),
        'people' => array(
            'label'  => 'People',
            'title'  => 'People to Know',
            'intro'  => 'Founders, creators, developers and the people behind new products or suddenly in the news, with only verified background.',
            'legacy' => array( 'founders', 'founder', 'influencers', 'celeb' ),
        ),
        'internet-trends' => array(
            'label'  => 'Internet Trends',
            'title'  => 'Internet Trends',
            'intro'  => 'What is suddenly trending online, platform changes and the questions people are starting to ask.',
            'legacy' => array( 'whats-on-the-web', 'blog' ),
        ),
    );
}

/** Term IDs (new section term first, then adopted legacy terms) for a section. */
function holyprofweb_section_term_ids( $slug ) {
    static $cache = array();
    if ( isset( $cache[ $slug ] ) ) {
        return $cache[ $slug ];
    }
    $sections = holyprofweb_sections();
    $ids      = array();
    if ( ! isset( $sections[ $slug ] ) ) {
        return $cache[ $slug ] = $ids;
    }
    $own = get_term_by( 'slug', $slug, 'category' );
    if ( $own ) {
        $ids[] = (int) $own->term_id;
    }
    foreach ( $sections[ $slug ]['legacy'] as $legacy ) {
        $t = get_term_by( 'slug', $legacy, 'category' );
        if ( $t ) {
            $ids[] = (int) $t->term_id;
        }
    }
    return $cache[ $slug ] = array_values( array_unique( $ids ) );
}

/** Meta query that leaves out the theme's seeded demo/placeholder posts. */
function holyprofweb_real_posts_meta_query() {
    return array(
        'relation' => 'AND',
        array( 'key' => '_hpw_placeholder_post', 'compare' => 'NOT EXISTS' ),
        array( 'key' => '_hpw_seed_post', 'compare' => 'NOT EXISTS' ),
        array( 'key' => '_hpw_noindex', 'compare' => 'NOT EXISTS' ),
    );
}

/** Query args for posts belonging to a section (placeholder posts excluded). */
function holyprofweb_section_query_args( $slug, array $args = array() ) {
    $ids = holyprofweb_section_term_ids( $slug );
    return array_merge(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'category__in'        => $ids ? $ids : array( 0 ),
            'meta_query'          => holyprofweb_real_posts_meta_query(),
        ),
        $args
    );
}

/** The section slug a post belongs to, or ''. */
function holyprofweb_post_section( $post_id ) {
    $term_ids = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
    if ( ! $term_ids ) {
        return '';
    }
    foreach ( array_keys( holyprofweb_sections() ) as $slug ) {
        if ( array_intersect( $term_ids, holyprofweb_section_term_ids( $slug ) ) ) {
            return $slug;
        }
    }
    return '';
}

function holyprofweb_section_url( $slug ) {
    return home_url( '/' . $slug . '/' );
}

/** Label for a post's kicker: its section, else its first category. */
function holyprofweb_post_kicker( $post_id ) {
    $sections = holyprofweb_sections();
    $slug     = holyprofweb_post_section( $post_id );
    if ( $slug ) {
        return array( 'label' => $sections[ $slug ]['label'], 'url' => holyprofweb_section_url( $slug ) );
    }
    $cats = get_the_category( $post_id );
    if ( $cats ) {
        // Legacy categories are retired archives: show the label as text, never as a link.
        return array( 'label' => $cats[0]->name, 'url' => '' );
    }
    return array( 'label' => '', 'url' => '' );
}

// ---- Setup: create the four terms, update the tagline, flush rewrites once --------------------

function holyprofweb_sections_install() {
    if ( get_option( 'hpw_sections_version' ) === HPW_SECTIONS_VERSION ) {
        return;
    }
    foreach ( holyprofweb_sections() as $slug => $s ) {
        if ( ! get_term_by( 'slug', $slug, 'category' ) ) {
            wp_insert_term( $s['label'], 'category', array( 'slug' => $slug, 'description' => $s['intro'] ) );
        }
    }
    $old_tagline = array( 'Trusted reviews. Verified insights. Real answers.', '' );
    if ( in_array( (string) get_option( 'blogdescription' ), $old_tagline, true ) ) {
        update_option( 'blogdescription', 'Discover what’s new online.' );
    }
    update_option( 'hpw_sections_version', HPW_SECTIONS_VERSION );
    flush_rewrite_rules( false );
}
add_action( 'init', 'holyprofweb_sections_install', 99 );

// ---- URLs: /apps-websites/ instead of /category/apps-websites/ --------------------------------

add_action( 'init', function () {
    $slugs = implode( '|', array_keys( holyprofweb_sections() ) );
    add_rewrite_rule( '^(' . $slugs . ')/?$', 'index.php?category_name=$matches[1]', 'top' );
    add_rewrite_rule( '^(' . $slugs . ')/page/([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]', 'top' );
} );

add_filter( 'term_link', function ( $link, $term, $taxonomy ) {
    if ( 'category' === $taxonomy && 0 === (int) $term->parent && isset( holyprofweb_sections()[ $term->slug ] ) ) {
        return holyprofweb_section_url( $term->slug );
    }
    return $link;
}, 10, 3 );

// Send the old /category/<section>/ form to the short URL so there is only one address per hub.
add_action( 'template_redirect', function () {
    if ( ! is_category() ) {
        return;
    }
    $term = get_queried_object();
    if ( ! $term || ! isset( holyprofweb_sections()[ $term->slug ] ) ) {
        return;
    }
    $path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
    if ( false !== strpos( (string) $path, '/category/' . $term->slug ) ) {
        $paged = (int) get_query_var( 'paged' );
        $dest  = holyprofweb_section_url( $term->slug ) . ( $paged > 1 ? 'page/' . $paged . '/' : '' );
        wp_safe_redirect( $dest, 301 );
        exit;
    }
} );

// ---- Hub query: section term + adopted legacy terms ------------------------------------------

add_action( 'pre_get_posts', function ( $q ) {
    if ( is_admin() || ! $q->is_main_query() || ! $q->is_category() ) {
        return;
    }
    $slug = $q->get( 'category_name' );
    if ( ! $slug || ! isset( holyprofweb_sections()[ $slug ] ) ) {
        return;
    }
    $ids = holyprofweb_section_term_ids( $slug );
    if ( ! $ids ) {
        return;
    }
    // WP resolves the queried term from the lowest term ID, so the section term is pinned again on 'wp' below.
    $q->set( 'hpw_section', $slug );
    $q->set( 'category_name', '' );
    $q->set( 'cat', implode( ',', $ids ) );
    $q->set( 'posts_per_page', 12 );
    $q->set( 'meta_query', holyprofweb_real_posts_meta_query() );
} );

add_filter( 'template_include', function ( $template ) {
    if ( is_category() ) {
        $term = get_queried_object();
        if ( $term && isset( holyprofweb_sections()[ $term->slug ] ) ) {
            $hub = get_template_directory() . '/templates/section-hub.php';
            if ( file_exists( $hub ) ) {
                return $hub;
            }
        }
    }
    return $template;
}, 20 );

add_action( 'wp', function () {
    global $wp_query;
    $slug = $wp_query->get( 'hpw_section' );
    if ( ! $slug ) {
        return;
    }
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( $term ) {
        $wp_query->queried_object    = $term;
        $wp_query->queried_object_id = (int) $term->term_id;
    }
} );
