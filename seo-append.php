<?php
// =========================================
// SEO — Meta Description, Open Graph, JSON-LD
// =========================================

function holyprofweb_detect_active_seo_provider() {
    if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) || defined( 'RANK_MATH_FILE' ) ) {
        return array(
            'key'            => 'rank-math',
            'label'          => 'Rank Math',
            'native_enabled' => false,
        );
    }

    if ( defined( 'WPSEO_VERSION' ) || defined( 'WPSEO_FILE' ) ) {
        return array(
            'key'            => 'yoast',
            'label'          => 'Yoast SEO',
            'native_enabled' => false,
        );
    }

    if ( defined( 'AIOSEO_VERSION' ) || class_exists( '\AIOSEO\Plugin\Common\Main' ) ) {
        return array(
            'key'            => 'aioseo',
            'label'          => 'All in One SEO',
            'native_enabled' => false,
        );
    }

    return array(
        'key'            => 'hpw',
        'label'          => 'HPW Native SEO',
        'native_enabled' => true,
    );
}

function holyprofweb_get_seo_publisher_logo_url() {
    $custom_logo_id = (int) get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id ) {
        $logo = wp_get_attachment_image_url( $custom_logo_id, 'full' );
        if ( $logo ) {
            return esc_url_raw( $logo );
        }
    }

    $logo_path = function_exists( 'holyprofweb_get_raster_logo_path' ) ? holyprofweb_get_raster_logo_path() : '';
    if ( ! $logo_path ) {
        return '';
    }

    $relative = wp_normalize_path( str_replace( wp_normalize_path( get_template_directory() ), '', wp_normalize_path( $logo_path ) ) );
    if ( '' === $relative ) {
        return '';
    }

    return esc_url_raw( trailingslashit( get_template_directory_uri() ) . ltrim( $relative, '/' ) );
}

function holyprofweb_get_seo_image_dimensions( $image_url ) {
    $image_url = (string) $image_url;
    if ( '' === $image_url ) {
        return array( 0, 0 );
    }

    if ( 0 === strpos( $image_url, 'data:image/svg+xml' ) ) {
        return array( 1200, 675 );
    }

    if ( preg_match( '/holyprofweb\.jpg(?:$|\?)/i', $image_url ) ) {
        return array( 1600, 900 );
    }

    return array( 0, 0 );
}

function holyprofweb_get_current_seo_url() {
    if ( is_singular() ) {
        return get_permalink();
    }

    if ( is_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            return get_category_link( $term->term_id );
        }
    }

    if ( is_search() ) {
        return get_search_link( get_search_query() );
    }

    if ( get_query_var( 'hpw_blog_archive' ) ) {
        return holyprofweb_get_blog_url();
    }

    if ( get_query_var( 'hpw_reports_archive' ) ) {
        return holyprofweb_get_reports_url();
    }

    return home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
}

function holyprofweb_normalize_hreflang_code( $code ) {
    $code = trim( (string) $code );
    if ( '' === $code ) {
        return '';
    }

    $code = str_replace( '_', '-', $code );
    $parts = explode( '-', $code );
    $parts[0] = strtolower( $parts[0] );
    if ( ! empty( $parts[1] ) ) {
        $parts[1] = strtoupper( $parts[1] );
    }

    return implode( '-', $parts );
}

function holyprofweb_get_hreflang_urls() {
    $entries = array();

    if ( function_exists( 'pll_the_languages' ) ) {
        $languages = pll_the_languages(
            array(
                'raw'           => 1,
                'hide_if_empty' => 0,
            )
        );

        if ( is_array( $languages ) ) {
            foreach ( $languages as $language ) {
                if ( empty( $language['url'] ) ) {
                    continue;
                }

                $hreflang = ! empty( $language['locale'] ) ? $language['locale'] : ( $language['slug'] ?? '' );
                $entries[] = array(
                    'hreflang' => holyprofweb_normalize_hreflang_code( $hreflang ),
                    'url'      => $language['url'],
                    'current'  => ! empty( $language['current_lang'] ),
                );
            }
        }
    } elseif ( has_filter( 'wpml_active_languages' ) ) {
        $languages = apply_filters(
            'wpml_active_languages',
            null,
            array(
                'skip_missing' => 0,
            )
        );

        if ( is_array( $languages ) ) {
            foreach ( $languages as $language ) {
                if ( empty( $language['url'] ) ) {
                    continue;
                }

                $hreflang = $language['default_locale'] ?? ( $language['language_code'] ?? '' );
                $entries[] = array(
                    'hreflang' => holyprofweb_normalize_hreflang_code( $hreflang ),
                    'url'      => $language['url'],
                    'current'  => ! empty( $language['active'] ),
                );
            }
        }
    }

    if ( empty( $entries ) ) {
        $current_url  = holyprofweb_get_current_seo_url();
        $default_lang = holyprofweb_normalize_hreflang_code( get_option( 'hpw_default_language', 'en_US' ) );
        if ( ! $default_lang ) {
            $default_lang = 'en-US';
        }

        $entries[] = array(
            'hreflang' => $default_lang,
            'url'      => $current_url,
            'current'  => true,
        );
    }

    $deduped = array();
    foreach ( $entries as $entry ) {
        if ( empty( $entry['hreflang'] ) || empty( $entry['url'] ) ) {
            continue;
        }
        $deduped[ $entry['hreflang'] ] = $entry;
    }

    if ( ! empty( $deduped ) ) {
        $first = reset( $deduped );
        $deduped['x-default'] = array(
            'hreflang' => 'x-default',
            'url'      => $first['url'],
            'current'  => false,
        );
    }

    return $deduped;
}

function holyprofweb_get_archive_share_image( $context = null ) {
    if ( is_category() ) {
        $term = $context instanceof WP_Term ? $context : get_queried_object();
        if ( $term instanceof WP_Term ) {
            $posts = get_posts(
                array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'category__in'   => array( (int) $term->term_id ),
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                )
            );

            if ( ! empty( $posts ) ) {
                $image = holyprofweb_get_post_image_url( (int) $posts[0], 'full' );
                return 0 === strpos( (string) $image, 'data:image/' ) ? holyprofweb_placeholder_url() : $image;
            }
        }
    }

    return holyprofweb_placeholder_url();
}

function holyprofweb_get_archive_schema_items() {
    $items = array();
    $query = $GLOBALS['wp_query'] ?? null;

    if ( ! $query instanceof WP_Query || empty( $query->posts ) || ! is_array( $query->posts ) ) {
        return $items;
    }

    $position = 1;
    foreach ( array_slice( $query->posts, 0, 12 ) as $entry ) {
        if ( ! $entry instanceof WP_Post ) {
            continue;
        }

        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'url'      => get_permalink( $entry ),
            'name'     => get_the_title( $entry ),
        );
        $position++;
    }

    return $items;
}

function holyprofweb_get_home_schema_items( $limit = 8 ) {
    $items = array();
    $query = new WP_Query(
        array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => max( 1, (int) $limit ),
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        )
    );

    if ( $query->have_posts() ) {
        $position = 1;
        foreach ( $query->posts as $entry ) {
            if ( ! $entry instanceof WP_Post ) {
                continue;
            }

            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'url'      => get_permalink( $entry ),
                'name'     => get_the_title( $entry ),
            );
            $position++;
        }
    }

    wp_reset_postdata();
    return $items;
}

function holyprofweb_get_home_schema_navigation() {
    $links = array(
        array( 'name' => 'Home',      'url' => home_url( '/' ) ),
        array( 'name' => 'Reviews',   'url' => home_url( '/category/reviews/' ) ),
        array( 'name' => 'Companies', 'url' => home_url( '/category/companies/' ) ),
        array( 'name' => 'Salaries',  'url' => home_url( '/category/salaries/' ) ),
        array( 'name' => 'Reports',   'url' => home_url( '/reports/' ) ),
        array( 'name' => 'Submit',    'url' => home_url( '/submit/' ) ),
        array( 'name' => 'Contact',   'url' => home_url( '/contact/' ) ),
    );

    $items = array();
    foreach ( $links as $link ) {
        $items[] = array(
            '@type' => 'SiteNavigationElement',
            'name'  => $link['name'],
            'url'   => $link['url'],
        );
    }

    return $items;
}

function holyprofweb_seo_head() {
    if ( is_admin() ) return;
    $seo_provider = holyprofweb_detect_active_seo_provider();
    if ( empty( $seo_provider['native_enabled'] ) ) {
        return;
    }

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
        if ( 0 === strpos( (string) $og_img, 'data:image/' ) ) {
            $og_img = holyprofweb_placeholder_url();
        }
        $rating   = holyprofweb_get_post_rating( $post->ID );
        $r_count  = holyprofweb_get_review_count( $post->ID );
    } elseif ( is_category() && $post instanceof WP_Term ) {
        $raw_desc = $post->description ?: sprintf( '%s listings on %s', $post->name, $site_name );
        $og_title = sprintf( '%s — %s', $post->name, $site_name );
        $og_url   = get_category_link( $post->term_id );
        $og_img   = holyprofweb_get_archive_share_image( $post ); $rating = 0; $r_count = 0;
    } elseif ( is_search() ) {
        $raw_desc = sprintf( 'Search results for "%s" on %s', get_search_query(), $site_name );
        $og_title = sprintf( 'Search: %s — %s', get_search_query(), $site_name );
        $og_url   = get_search_link( get_search_query() );
        $og_img   = holyprofweb_placeholder_url(); $rating = 0; $r_count = 0;
    } else {
        $raw_desc = get_bloginfo( 'description' );
        $og_title = $site_name;
        $og_url   = $site_url;
        $og_img   = holyprofweb_placeholder_url(); $rating = 0; $r_count = 0;
    }

    $description = esc_attr( mb_substr( wp_strip_all_tags( $raw_desc ), 0, 160 ) );
    $og_type     = is_singular() ? 'article' : 'website';
    $hreflangs   = holyprofweb_get_hreflang_urls();
    $current_hreflang = '';
    foreach ( $hreflangs as $entry ) {
        if ( ! empty( $entry['current'] ) && 'x-default' !== $entry['hreflang'] ) {
            $current_hreflang = $entry['hreflang'];
            break;
        }
    }
    if ( ! $current_hreflang ) {
        $current_hreflang = holyprofweb_normalize_hreflang_code( get_option( 'hpw_default_language', 'en_US' ) );
    }

    echo "\n<!-- HPW SEO -->\n";
    echo '<meta name="description" content="' . $description . '" />' . "\n";
    echo '<meta property="og:type"        content="' . esc_attr( $og_type ) . '" />' . "\n";
    echo '<meta property="og:title"       content="' . esc_attr( $og_title ) . '" />' . "\n";
    echo '<meta property="og:description" content="' . $description . '" />' . "\n";
    echo '<meta property="og:url"         content="' . esc_url( $og_url ) . '" />' . "\n";
    echo '<meta property="og:site_name"   content="' . esc_attr( $site_name ) . '" />' . "\n";
    if ( $current_hreflang ) {
        echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', $current_hreflang ) ) . '" />' . "\n";
        foreach ( $hreflangs as $entry ) {
            if ( $entry['hreflang'] === $current_hreflang || 'x-default' === $entry['hreflang'] ) {
                continue;
            }
            echo '<meta property="og:locale:alternate" content="' . esc_attr( str_replace( '-', '_', $entry['hreflang'] ) ) . '" />' . "\n";
        }
    }
    list( $og_img_width, $og_img_height ) = holyprofweb_get_seo_image_dimensions( $og_img );
    if ( $og_img ) {
        echo '<meta property="og:image"   content="' . esc_url( $og_img ) . '" />' . "\n";
        echo '<meta property="og:image:secure_url" content="' . esc_url( set_url_scheme( $og_img, 'https' ) ) . '" />' . "\n";
        if ( $og_img_width > 0 && $og_img_height > 0 ) {
            echo '<meta property="og:image:width" content="' . esc_attr( $og_img_width ) . '" />' . "\n";
            echo '<meta property="og:image:height" content="' . esc_attr( $og_img_height ) . '" />' . "\n";
        }
        echo '<meta property="og:image:alt" content="' . esc_attr( $og_title ) . '" />' . "\n";
        echo '<meta name="twitter:image"  content="' . esc_url( $og_img ) . '" />' . "\n";
        echo '<meta name="twitter:image:alt" content="' . esc_attr( $og_title ) . '" />' . "\n";
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

        $publisher_logo = holyprofweb_get_seo_publisher_logo_url();

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
        if ( $publisher_logo ) {
            $schema['publisher']['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $publisher_logo,
            );
        }
        if ( $og_img ) $schema['image'] = $og_img;
        if ( $reading_time > 0 ) $schema['timeRequired'] = 'PT' . $reading_time . 'M';
        if ( $cat_name ) {
            $schema['articleSection'] = $cat_name;
            $schema['keywords'] = implode( ', ', wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ) );
        }

        if ( $is_review ) {
            $schema['@type'] = 'Review';
            $schema['name']  = get_the_title( $post );
            $schema['reviewBody'] = mb_substr( wp_strip_all_tags( $raw_desc ), 0, 300 );
            $schema['itemReviewed'] = array(
                '@type' => 'Thing',
                'name'  => get_the_title( $post ),
            );
            if ( $source_url ) {
                if ( function_exists( 'holyprofweb_clean_public_url_submission' ) ) {
                    $source_url = holyprofweb_clean_public_url_submission( $source_url );
                } else {
                    $source_url = esc_url_raw( $source_url );
                }
                if ( $source_url ) {
                    $schema['itemReviewed']['sameAs'] = $source_url;
                }
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
                array( '@type' => 'ListItem', 'position' => 3, 'name' => get_the_title( $post ), 'item' => get_permalink( $post ) ),
            ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    } elseif ( is_category() || is_search() || get_query_var( 'hpw_blog_archive' ) || get_query_var( 'hpw_reports_archive' ) ) {
        $item_list = holyprofweb_get_archive_schema_items();
        $archive_name = $og_title;
        $archive_description = mb_substr( wp_strip_all_tags( $raw_desc ), 0, 200 );
        $archive_schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => is_search() ? 'SearchResultsPage' : 'CollectionPage',
            'name'        => $archive_name,
            'description' => $archive_description,
            'url'         => $og_url,
            'isPartOf'    => array(
                '@type' => 'WebSite',
                'name'  => $site_name,
                'url'   => $site_url,
            ),
        );

        if ( $og_img ) {
            $archive_schema['image'] = $og_img;
        }

        if ( ! empty( $item_list ) ) {
            $archive_schema['mainEntity'] = array(
                '@type'           => 'ItemList',
                'itemListElement' => $item_list,
            );
        }

        if ( is_category() && $post instanceof WP_Term ) {
            $archive_schema['about'] = array(
                '@type' => 'DefinedTerm',
                'name'  => $post->name,
                'url'   => get_category_link( $post->term_id ),
            );
        }

        if ( is_search() ) {
            $search_query = get_search_query();
            $archive_schema['query'] = $search_query;
            $archive_schema['potentialAction'] = array(
                '@type'       => 'SearchAction',
                'target'      => home_url( '/?s={search_term_string}' ),
                'query-input' => 'required name=search_term_string',
            );
        }

        $breadcrumb_items = array(
            array(
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'Home',
                'item'     => $site_url,
            ),
        );

        if ( is_category() && $post instanceof WP_Term ) {
            $breadcrumb_items[] = array(
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $post->name,
                'item'     => get_category_link( $post->term_id ),
            );
        } elseif ( is_search() ) {
            $breadcrumb_items[] = array(
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => sprintf( 'Search: %s', get_search_query() ),
                'item'     => $og_url,
            );
        } elseif ( get_query_var( 'hpw_blog_archive' ) ) {
            $breadcrumb_items[] = array(
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => 'Blog',
                'item'     => $og_url,
            );
        } elseif ( get_query_var( 'hpw_reports_archive' ) ) {
            $breadcrumb_items[] = array(
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => 'Reports',
                'item'     => $og_url,
            );
        }

        $breadcrumb = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $breadcrumb_items,
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $archive_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    } elseif ( is_front_page() || is_home() ) {
        $publisher_logo = holyprofweb_get_seo_publisher_logo_url();
        $homepage_items = holyprofweb_get_home_schema_items( 8 );
        $navigation_items = holyprofweb_get_home_schema_navigation();
        $website_id = trailingslashit( $site_url ) . '#website';
        $organization_id = trailingslashit( $site_url ) . '#organization';
        $webpage_id = trailingslashit( $site_url ) . '#webpage';
        $website_schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => $website_id,
            'name'            => $site_name,
            'url'             => $site_url,
            'description'     => mb_substr( wp_strip_all_tags( $raw_desc ), 0, 200 ),
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => home_url( '/?s={search_term_string}' ),
                'query-input' => 'required name=search_term_string',
            ),
        );
        $organization_schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            '@id'      => $organization_id,
            'name'     => $site_name,
            'url'      => $site_url,
        );
        $webpage_schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            '@id'         => $webpage_id,
            'url'         => $site_url,
            'name'        => $og_title,
            'description' => mb_substr( wp_strip_all_tags( $raw_desc ), 0, 200 ),
            'isPartOf'    => array( '@id' => $website_id ),
            'about'       => array( '@id' => $organization_id ),
            'inLanguage'  => $current_hreflang ?: 'en-US',
        );

        if ( $publisher_logo ) {
            $organization_schema['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $publisher_logo,
            );
        }
        if ( $og_img ) {
            $webpage_schema['primaryImageOfPage'] = array(
                '@type' => 'ImageObject',
                'url'   => $og_img,
            );
        }
        if ( ! empty( $homepage_items ) ) {
            $webpage_schema['mainEntity'] = array(
                '@type'           => 'ItemList',
                'itemListElement' => $homepage_items,
            );
        }
        if ( ! empty( $navigation_items ) ) {
            $website_schema['hasPart'] = $navigation_items;
        }
        $breadcrumb = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array(
                array(
                    '@type'    => 'ListItem',
                    'position' => 1,
                    'name'     => 'Home',
                    'item'     => $site_url,
                ),
            ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $website_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $organization_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $webpage_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    echo '<link rel="canonical" href="' . esc_url( holyprofweb_get_current_seo_url() ) . '" />' . "\n";
    foreach ( $hreflangs as $entry ) {
        echo '<link rel="alternate" hreflang="' . esc_attr( $entry['hreflang'] ) . '" href="' . esc_url( $entry['url'] ) . '" />' . "\n";
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
    if ( function_exists( 'holyprofweb_clean_public_url_submission' ) ) {
        $site_url = holyprofweb_clean_public_url_submission( $site_url );
    } else {
        $site_url = esc_url_raw( $site_url );
    }
    if ( ! $site_url ) return;
    if ( ! current_user_can( 'edit_post', (int) $post_id ) ) return;

    $response = wp_remote_get( esc_url_raw( $site_url ), array(
        'timeout'    => 8,
        'user-agent' => 'Mozilla/5.0 (compatible; HolyprofWeb/1.0)',
        'sslverify'  => true,
        'redirection'=> 3,
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

    if ( function_exists( 'holyprofweb_clean_public_url_submission' ) ) {
        $img_url = holyprofweb_clean_public_url_submission( $img_url );
    } else {
        $img_url = esc_url_raw( $img_url );
    }
    if ( ! $img_url ) return;

    $tmp = download_url( $img_url, 10 );
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
    if ( function_exists( 'holyprofweb_can_send_throttled_notification' ) && ! holyprofweb_can_send_throttled_notification( 'submission_notice', 10, HOUR_IN_SECONDS ) ) {
        return;
    }

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
