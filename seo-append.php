<?php
// =========================================
// SEO — Meta Description, Open Graph, JSON-LD
// =========================================

function holyprofweb_seo_head() {
    if ( is_admin() ) return;

    $post      = get_queried_object();
    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url( '/' );

    if ( is_singular() && $post instanceof WP_Post ) {
        $raw_desc = $post->post_excerpt
            ? wp_strip_all_tags( $post->post_excerpt )
            : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        $og_title = get_the_title( $post );
        $og_url   = get_permalink( $post );
        $og_img   = holyprofweb_get_post_image_url( $post->ID, 'full' );
        $rating   = holyprofweb_get_post_rating( $post->ID );
        $r_count  = holyprofweb_get_review_count( $post->ID );
    } elseif ( is_category() && $post instanceof WP_Term ) {
        $raw_desc = $post->description ?: sprintf( '%s listings on %s', $post->name, $site_name );
        $og_title = sprintf( '%s — %s', $post->name, $site_name );
        $og_url   = get_category_link( $post->term_id );
        $og_img   = ''; $rating = 0; $r_count = 0;
    } elseif ( is_search() ) {
        $raw_desc = sprintf( 'Search results for "%s" on %s', get_search_query(), $site_name );
        $og_title = sprintf( 'Search: %s — %s', get_search_query(), $site_name );
        $og_url   = get_search_link( get_search_query() );
        $og_img   = ''; $rating = 0; $r_count = 0;
    } else {
        $raw_desc = get_bloginfo( 'description' );
        $og_title = $site_name;
        $og_url   = $site_url;
        $og_img   = ''; $rating = 0; $r_count = 0;
    }

    $description = esc_attr( mb_substr( wp_strip_all_tags( $raw_desc ), 0, 160 ) );
    $og_type     = is_singular() ? 'article' : 'website';

    echo "\n<!-- HPW SEO -->\n";
    echo '<meta name="description" content="' . $description . '" />' . "\n";
    echo '<meta property="og:type"        content="' . esc_attr( $og_type ) . '" />' . "\n";
    echo '<meta property="og:title"       content="' . esc_attr( $og_title ) . '" />' . "\n";
    echo '<meta property="og:description" content="' . $description . '" />' . "\n";
    echo '<meta property="og:url"         content="' . esc_url( $og_url ) . '" />' . "\n";
    echo '<meta property="og:site_name"   content="' . esc_attr( $site_name ) . '" />' . "\n";
    if ( $og_img ) {
        echo '<meta property="og:image"   content="' . esc_url( $og_img ) . '" />' . "\n";
        echo '<meta name="twitter:image"  content="' . esc_url( $og_img ) . '" />' . "\n";
    }
    echo '<meta name="twitter:card"        content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title"       content="' . esc_attr( $og_title ) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . $description . '" />' . "\n";

    // JSON-LD
    if ( is_singular() && isset( $post ) && $post instanceof WP_Post ) {
        $cats     = get_the_category( $post->ID );
        $cat_name = ! empty( $cats ) ? $cats[0]->name : '';

        // Detect schema type from cached meta or category slugs
        $cached_type = get_post_meta( $post->ID, '_hpw_schema_type', true );
        $cat_slugs   = wp_list_pluck( get_the_category( $post->ID ), 'slug' );

        if ( $cached_type ) {
            $schema_type = $cached_type;
        } elseif ( array_intersect( array( 'biography', 'founders', 'influencers' ), $cat_slugs ) ) {
            $schema_type = 'Person';
        } elseif ( array_intersect( array( 'companies', 'fintech', 'banks', 'startups' ), $cat_slugs ) ) {
            $schema_type = 'Organization';
        } elseif ( array_intersect( array( 'reports', 'scam-reports', 'user-complaints' ), $cat_slugs ) ) {
            $schema_type = 'NewsArticle';
        } elseif ( array_intersect( array( 'salaries', 'nigeria', 'remote', 'tech-roles' ), $cat_slugs ) ) {
            $schema_type = 'Occupation';
        } elseif ( array_intersect( array( 'reviews', 'loan-apps', 'crypto', 'betting', 'earning-platforms' ), $cat_slugs ) ) {
            $schema_type = 'ItemPage';
        } else {
            $schema_type = 'Article';
        }

        $reading_time = (int) get_post_meta( $post->ID, '_hpw_reading_time', true );
        $source_url   = function_exists( 'holyprofweb_get_post_source_url' ) ? holyprofweb_get_post_source_url( $post->ID, $post ) : '';
        $verdict      = function_exists( 'holyprofweb_get_review_verdict' ) ? holyprofweb_get_review_verdict( $post->ID ) : array( 'label' => '' );
        $is_review    = (bool) array_intersect( array( 'reviews', 'scam-legit', 'app-reviews', 'website-reviews', 'loan-finance', 'shopping', 'scholarship', 'tech', 'blog-opinion', 'loan-apps', 'crypto', 'betting', 'earning-platforms' ), $cat_slugs );

        $schema = array(
            '@context'      => 'https://schema.org',
            '@type'         => $schema_type,
            'headline'      => get_the_title( $post ),
            'description'   => mb_substr( wp_strip_all_tags( $raw_desc ), 0, 200 ),
            'url'           => get_permalink( $post ),
            'datePublished' => get_the_date( 'c', $post ),
            'dateModified'  => get_the_modified_date( 'c', $post ),
            'author'        => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $post->post_author ) ),
            'publisher'     => array( '@type' => 'Organization', 'name' => $site_name, 'url' => $site_url ),
        );
        if ( $og_img ) $schema['image'] = $og_img;
        if ( $reading_time > 0 ) $schema['timeRequired'] = 'PT' . $reading_time . 'M';

        if ( $is_review ) {
            $schema['@type'] = 'Review';
            $schema['name']  = get_the_title( $post );
            $schema['reviewBody'] = mb_substr( wp_strip_all_tags( $raw_desc ), 0, 300 );
            $schema['itemReviewed'] = array(
                '@type' => 'Thing',
                'name'  => get_the_title( $post ),
            );
            if ( $source_url ) {
                $schema['itemReviewed']['sameAs'] = esc_url_raw( $source_url );
            }
            if ( ! empty( $verdict['label'] ) ) {
                $schema['reviewAspect'] = $verdict['label'];
            }
        }

        if ( $rating > 0 && $r_count > 0 ) {
            $aggregate = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $rating,
                'reviewCount' => (string) $r_count,
                'bestRating'  => '5',
                'worstRating' => '1',
            );
            if ( 'Review' === $schema['@type'] ) {
                $schema['itemReviewed']['aggregateRating'] = $aggregate;
                $schema['reviewRating'] = array(
                    '@type'       => 'Rating',
                    'ratingValue' => (string) $rating,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                );
            } else {
                if ( in_array( $schema_type, array( 'ItemPage', 'Article', 'NewsArticle' ), true ) ) {
                    $schema['@type'] = 'ItemPage';
                }
                $schema['aggregateRating'] = $aggregate;
            }
        }

        $breadcrumb = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array(
                array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $site_url ),
                array( '@type' => 'ListItem', 'position' => 2, 'name' => ( $cat_name ?: 'Articles' ), 'item' => ( $cat_name && ! empty( $cats ) ? get_category_link( $cats[0]->term_id ) : $site_url ) ),
                array( '@type' => 'ListItem', 'position' => 3, 'name' => get_the_title( $post ) ),
            ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    if ( is_singular() ) {
        echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '" />' . "\n";
    }
    echo "<!-- /HPW SEO -->\n";
}
add_action( 'wp_head', 'holyprofweb_seo_head', 2 );


// =========================================
// AUTO-FETCH OG IMAGE FROM SUBMITTED URL
// =========================================

function holyprofweb_fetch_og_image( $post_id, $site_url ) {
    if ( ! $site_url || ! filter_var( $site_url, FILTER_VALIDATE_URL ) ) return;
    if ( get_post_thumbnail_id( $post_id ) ) return;

    $response = wp_remote_get( esc_url_raw( $site_url ), array(
        'timeout'    => 8,
        'user-agent' => 'Mozilla/5.0 (compatible; HolyprofWeb/1.0)',
        'sslverify'  => false,
    ) );
    if ( is_wp_error( $response ) ) return;

    $html    = wp_remote_retrieve_body( $response );
    $img_url = '';

    if ( preg_match( '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m ) ) {
        $img_url = $m[1];
    } elseif ( preg_match( '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\'][^>]*>/i', $html, $m ) ) {
        $img_url = $m[1];
    }

    if ( ! $img_url ) {
        $parsed = wp_parse_url( $site_url );
        if ( isset( $parsed['host'] ) ) {
            $img_url = 'https://www.google.com/s2/favicons?domain=' . rawurlencode( $parsed['host'] ) . '&sz=128';
        }
    }
    if ( ! $img_url ) return;

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url( esc_url_raw( $img_url ), 10 );
    if ( is_wp_error( $tmp ) ) return;

    $file = array(
        'name'     => 'hpw-submit-' . $post_id . '.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => $tmp,
        'error'    => 0,
        'size'     => filesize( $tmp ),
    );
    $att_id = media_handle_sideload( $file, $post_id );
    @unlink( $tmp );
    if ( ! is_wp_error( $att_id ) ) {
        set_post_thumbnail( $post_id, $att_id );
    }
}


// =========================================
// ADMIN NOTIFICATION — NEW SUBMISSION
// =========================================

function holyprofweb_notify_submission( $post_id, $name, $site_url, $category, $message ) {
    $to      = get_option( 'hpw_email_notify_address', get_option( 'admin_email' ) );
    $from    = get_option( 'hpw_email_from_name', get_bloginfo( 'name' ) );
    $subject = '[HPW] New submission: "' . $name . '"';
    $lines   = array(
        'A new site/company has been submitted to HolyprofWeb.',
        '',
        'Name:     ' . $name,
        'URL:      ' . ( $site_url ?: '(none)' ),
        'Category: ' . $category,
        '',
        'Message:',
        $message,
        '',
        'Review: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
    );
    wp_mail( $to, $subject, implode( "\n", $lines ), array( 'From: ' . $from . ' <' . get_option( 'admin_email' ) . '>' ) );
}


// =========================================
// DOCUMENT TITLE — APPEND RATING
// =========================================

add_filter( 'document_title_parts', function( $parts ) {
    if ( is_singular() ) {
        $rating  = holyprofweb_get_post_rating( get_queried_object_id() );
        $r_count = holyprofweb_get_review_count( get_queried_object_id() );
        if ( $rating > 0 ) {
            $parts['title'] .= ' — Rated ' . number_format( $rating, 1 ) . '/5 (' . $r_count . ' reviews)';
        }
    }
    return $parts;
} );
