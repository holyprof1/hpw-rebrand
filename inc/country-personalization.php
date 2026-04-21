<?php
/**
 * Country-aware personalization helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function holyprofweb_get_personalization_cookie_name() {
    return 'hpw_country';
}

function holyprofweb_normalize_country_code( $country_code ) {
    $country_code = strtoupper( preg_replace( '/[^A-Z]/', '', (string) $country_code ) );
    return 2 === strlen( $country_code ) ? $country_code : '';
}

function holyprofweb_get_country_term_slug( $country_code ) {
    $country_code = holyprofweb_normalize_country_code( $country_code );
    return $country_code ? strtolower( $country_code ) : '';
}

function holyprofweb_get_country_taxonomy_name() {
    return 'target_country';
}

function holyprofweb_get_supported_country_codes() {
    $defaults = array(
        'AU', 'CA', 'CN', 'DE', 'ES', 'FR', 'GB', 'GH', 'IE', 'IN', 'KE', 'NG',
        'NL', 'PT', 'SA', 'SG', 'US', 'ZA',
    );

    return array_values(
        array_unique(
            array_filter(
                array_map( 'holyprofweb_normalize_country_code', apply_filters( 'holyprofweb_supported_country_codes', $defaults ) )
            )
        )
    );
}

function holyprofweb_get_country_seed_codes() {
    $codes = array_merge(
        holyprofweb_get_supported_country_codes(),
        array_keys( (array) holyprofweb_get_country_content_map() )
    );

    return array_values(
        array_unique(
            array_filter(
                array_map( 'holyprofweb_normalize_country_code', $codes )
            )
        )
    );
}

function holyprofweb_register_target_country_taxonomy() {
    register_taxonomy(
        holyprofweb_get_country_taxonomy_name(),
        array( 'post' ),
        array(
            'labels'            => array(
                'name'          => __( 'Target Countries', 'holyprofweb' ),
                'singular_name' => __( 'Target Country', 'holyprofweb' ),
                'search_items'  => __( 'Search Target Countries', 'holyprofweb' ),
                'all_items'     => __( 'All Target Countries', 'holyprofweb' ),
                'edit_item'     => __( 'Edit Target Country', 'holyprofweb' ),
                'update_item'   => __( 'Update Target Country', 'holyprofweb' ),
                'add_new_item'  => __( 'Add New Target Country', 'holyprofweb' ),
                'new_item_name' => __( 'New Target Country', 'holyprofweb' ),
                'menu_name'     => __( 'Target Countries', 'holyprofweb' ),
            ),
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'hierarchical'      => true,
            'rewrite'           => false,
            'query_var'         => false,
            'meta_box_cb'       => 'post_categories_meta_box',
            'capabilities'      => array(
                'manage_terms' => 'edit_posts',
                'edit_terms'   => 'edit_posts',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'edit_posts',
            ),
        )
    );
}
add_action( 'init', 'holyprofweb_register_target_country_taxonomy', 20 );

function holyprofweb_seed_target_country_terms() {
    $taxonomy = holyprofweb_get_country_taxonomy_name();
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return;
    }

    foreach ( holyprofweb_get_country_seed_codes() as $country_code ) {
        $slug = holyprofweb_get_country_term_slug( $country_code );
        if ( ! $slug || term_exists( $slug, $taxonomy ) ) {
            continue;
        }

        wp_insert_term(
            holyprofweb_get_country_name_from_code( $country_code ),
            $taxonomy,
            array(
                'slug' => $slug,
            )
        );
    }

    if ( ! term_exists( 'global', $taxonomy ) ) {
        wp_insert_term(
            __( 'Global', 'holyprofweb' ),
            $taxonomy,
            array(
                'slug' => 'global',
            )
        );
    }
}
add_action( 'init', 'holyprofweb_seed_target_country_terms', 30 );

function holyprofweb_get_personalization_cookie_country_code() {
    if ( empty( $_COOKIE[ holyprofweb_get_personalization_cookie_name() ] ) ) {
        return '';
    }

    return holyprofweb_normalize_country_code( wp_unslash( $_COOKIE[ holyprofweb_get_personalization_cookie_name() ] ) );
}

function holyprofweb_get_trusted_geo_proxy_cidrs() {
    $cidrs = apply_filters( 'holyprofweb_trusted_geo_proxy_cidrs', array() );
    return is_array( $cidrs ) ? array_values( array_filter( array_map( 'trim', $cidrs ) ) ) : array();
}

function holyprofweb_ip_matches_cidr( $ip_address, $cidr ) {
    $ip_address = trim( (string) $ip_address );
    $cidr       = trim( (string) $cidr );

    if ( '' === $ip_address || '' === $cidr ) {
        return false;
    }

    if ( false === strpos( $cidr, '/' ) ) {
        return $ip_address === $cidr;
    }

    list( $subnet, $mask ) = explode( '/', $cidr, 2 );
    $subnet = trim( $subnet );
    $mask   = (int) $mask;

    if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
        $ip_long     = ip2long( $ip_address );
        $subnet_long = ip2long( $subnet );
        $mask_long   = -1 << ( 32 - $mask );
        $subnet_long &= $mask_long;
        return ( $ip_long & $mask_long ) === $subnet_long;
    }

    if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) || ! filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
        return false;
    }

    $bytes_ip     = inet_pton( $ip_address );
    $bytes_subnet = inet_pton( $subnet );
    if ( false === $bytes_ip || false === $bytes_subnet ) {
        return false;
    }

    $bytes = (int) floor( $mask / 8 );
    $bits  = $mask % 8;

    if ( 0 !== $bytes && substr( $bytes_ip, 0, $bytes ) !== substr( $bytes_subnet, 0, $bytes ) ) {
        return false;
    }

    if ( 0 === $bits ) {
        return true;
    }

    $mask_byte = ~( 255 >> $bits ) & 255;
    return ( ord( $bytes_ip[ $bytes ] ) & $mask_byte ) === ( ord( $bytes_subnet[ $bytes ] ) & $mask_byte );
}

function holyprofweb_request_is_from_trusted_geo_proxy() {
    if ( holyprofweb_is_local_environment() ) {
        return true;
    }

    $remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    if ( '' === $remote_addr || ! filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
        return false;
    }

    foreach ( holyprofweb_get_trusted_geo_proxy_cidrs() as $cidr ) {
        if ( holyprofweb_ip_matches_cidr( $remote_addr, $cidr ) ) {
            return true;
        }
    }

    return false;
}

function holyprofweb_get_trusted_geo_header_country_code() {
    if ( ! holyprofweb_request_is_from_trusted_geo_proxy() ) {
        return '';
    }

    $geo_headers = array(
        'HTTP_CF_IPCOUNTRY',
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
        'HTTP_X_VERCEL_IP_COUNTRY',
        'HTTP_FASTLY_COUNTRY_CODE',
        'HTTP_X_APPENGINE_COUNTRY',
        'HTTP_X_COUNTRY_CODE',
        'HTTP_X_GEO_COUNTRY',
        'HTTP_X_COUNTRY',
    );

    foreach ( $geo_headers as $header_key ) {
        if ( empty( $_SERVER[ $header_key ] ) ) {
            continue;
        }

        $candidate = holyprofweb_normalize_country_code( wp_unslash( $_SERVER[ $header_key ] ) );
        if ( $candidate ) {
            return $candidate;
        }
    }

    if ( ! empty( $_SERVER['HTTP_X_AKAMAI_EDGESCAPE'] ) ) {
        $raw = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_AKAMAI_EDGESCAPE'] ) ) );
        if ( preg_match( '/country_code=([A-Z]{2})/', $raw, $matches ) ) {
            return holyprofweb_normalize_country_code( $matches[1] );
        }
    }

    return '';
}

function holyprofweb_get_trusted_client_ip() {
    $remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

    if ( ! holyprofweb_request_is_from_trusted_geo_proxy() ) {
        return filter_var( $remote_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ? $remote_addr : '';
    }

    $headers = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR',
    );

    foreach ( $headers as $header ) {
        if ( empty( $_SERVER[ $header ] ) ) {
            continue;
        }

        $raw        = (string) wp_unslash( $_SERVER[ $header ] );
        $candidates = 'HTTP_X_FORWARDED_FOR' === $header ? explode( ',', $raw ) : array( $raw );

        foreach ( $candidates as $candidate ) {
            $candidate = trim( $candidate );
            if ( filter_var( $candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                return $candidate;
            }
        }
    }

    return '';
}

function holyprofweb_should_personalize_for_request() {
    if ( is_admin() || is_feed() || is_preview() || wp_doing_cron() ) {
        return false;
    }

    if ( function_exists( 'holyprofweb_is_search_request_from_bot' ) && holyprofweb_is_search_request_from_bot() ) {
        return false;
    }

    return true;
}

function holyprofweb_get_current_country_code() {
    $locale = holyprofweb_detect_visitor_locale();
    return ! empty( $locale['region'] ) ? holyprofweb_normalize_country_code( $locale['region'] ) : '';
}

function holyprofweb_set_country_cookie() {
    if ( headers_sent() || ! holyprofweb_should_personalize_for_request() ) {
        return;
    }

    $country_code = holyprofweb_get_current_country_code();
    if ( ! $country_code || $country_code === holyprofweb_get_personalization_cookie_country_code() ) {
        return;
    }

    setcookie(
        holyprofweb_get_personalization_cookie_name(),
        $country_code,
        time() + ( 6 * HOUR_IN_SECONDS ),
        COOKIEPATH ? COOKIEPATH : '/',
        COOKIE_DOMAIN,
        is_ssl(),
        true
    );
    $_COOKIE[ holyprofweb_get_personalization_cookie_name() ] = $country_code;
}
add_action( 'template_redirect', 'holyprofweb_set_country_cookie', 0 );

function holyprofweb_get_country_label_from_term_slug( $slug ) {
    $slug = sanitize_title( (string) $slug );
    if ( 'global' === $slug ) {
        return __( 'Global', 'holyprofweb' );
    }

    return holyprofweb_get_country_name_from_code( strtoupper( $slug ) );
}

function holyprofweb_extract_country_codes_from_text( $text ) {
    $text = trim( (string) $text );
    if ( '' === $text ) {
        return array();
    }

    $segments = preg_split( '/[,\/|]+/', $text );
    $codes    = array();

    foreach ( (array) $segments as $segment ) {
        $segment = trim( sanitize_text_field( $segment ) );
        if ( '' === $segment ) {
            continue;
        }

        $code = holyprofweb_normalize_country_code( $segment );
        if ( $code ) {
            $codes[] = $code;
            continue;
        }

        foreach ( holyprofweb_get_country_seed_codes() as $seed_code ) {
            $country_name = holyprofweb_get_country_name_from_code( $seed_code );
            if ( 0 === strcasecmp( $segment, $country_name ) ) {
                $codes[] = $seed_code;
                break;
            }

            if ( 'US' === $seed_code && in_array( strtoupper( $segment ), array( 'USA', 'UNITED STATES', 'AMERICA' ), true ) ) {
                $codes[] = 'US';
                break;
            }

            if ( 'GB' === $seed_code && in_array( strtoupper( $segment ), array( 'UK', 'UNITED KINGDOM', 'BRITAIN', 'ENGLAND' ), true ) ) {
                $codes[] = 'GB';
                break;
            }
        }
    }

    return array_values( array_unique( array_filter( array_map( 'holyprofweb_normalize_country_code', $codes ) ) ) );
}

function holyprofweb_assign_target_country_terms_to_post( $post_id, $country_codes ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 ) {
        return;
    }

    $taxonomy = holyprofweb_get_country_taxonomy_name();
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return;
    }

    $slugs = array();
    foreach ( (array) $country_codes as $country_code ) {
        $country_code = holyprofweb_normalize_country_code( $country_code );
        if ( ! $country_code ) {
            continue;
        }

        $slug = holyprofweb_get_country_term_slug( $country_code );
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term(
                holyprofweb_get_country_name_from_code( $country_code ),
                $taxonomy,
                array(
                    'slug' => $slug,
                )
            );
        }
        $slugs[] = $slug;
    }

    if ( empty( $slugs ) ) {
        wp_set_post_terms( $post_id, array(), $taxonomy, false );
        return;
    }

    wp_set_post_terms( $post_id, array_values( array_unique( $slugs ) ), $taxonomy, false );
}

function holyprofweb_sync_post_country_terms( $post_id, $post = null ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    $post = $post instanceof WP_Post ? $post : get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
        return;
    }

    $terms      = wp_get_post_terms( $post_id, holyprofweb_get_country_taxonomy_name(), array( 'fields' => 'slugs' ) );
    $term_slugs = is_wp_error( $terms ) ? array() : array_values( array_filter( array_map( 'sanitize_title', (array) $terms ) ) );
    $legacy     = trim( (string) get_post_meta( $post_id, '_hpw_country_focus', true ) );

    if ( empty( $term_slugs ) && '' !== $legacy ) {
        holyprofweb_assign_target_country_terms_to_post( $post_id, holyprofweb_extract_country_codes_from_text( $legacy ) );
        $term_slugs = wp_get_post_terms( $post_id, holyprofweb_get_country_taxonomy_name(), array( 'fields' => 'slugs' ) );
        $term_slugs = is_wp_error( $term_slugs ) ? array() : array_values( array_filter( array_map( 'sanitize_title', (array) $term_slugs ) ) );
    }

    if ( ! empty( $term_slugs ) ) {
        $labels = array();
        foreach ( $term_slugs as $slug ) {
            $labels[] = holyprofweb_get_country_label_from_term_slug( $slug );
        }
        update_post_meta( $post_id, '_hpw_country_focus', implode( ', ', array_values( array_unique( array_filter( $labels ) ) ) ) );
    }
}
add_action( 'save_post_post', 'holyprofweb_sync_post_country_terms', 25, 2 );

function holyprofweb_get_post_target_country_codes( $post_id ) {
    $terms = wp_get_post_terms( (int) $post_id, holyprofweb_get_country_taxonomy_name(), array( 'fields' => 'slugs' ) );
    $codes = array();

    if ( ! is_wp_error( $terms ) ) {
        foreach ( (array) $terms as $slug ) {
            if ( 'global' === $slug ) {
                continue;
            }
            $codes[] = holyprofweb_normalize_country_code( strtoupper( $slug ) );
        }
    }

    if ( empty( $codes ) ) {
        $codes = holyprofweb_extract_country_codes_from_text( (string) get_post_meta( (int) $post_id, '_hpw_country_focus', true ) );
    }

    return array_values( array_unique( array_filter( $codes ) ) );
}

function holyprofweb_request_registry( $action, $scope = 'default', $post_ids = array() ) {
    static $registry = array();

    $scope = sanitize_key( (string) $scope );
    if ( '' === $scope ) {
        $scope = 'default';
    }

    if ( ! isset( $registry[ $scope ] ) ) {
        $registry[ $scope ] = array();
    }

    if ( 'get' === $action ) {
        return $registry[ $scope ];
    }

    if ( 'reserve' === $action ) {
        $registry[ $scope ] = array_values(
            array_unique(
                array_merge(
                    $registry[ $scope ],
                    array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) )
                )
            )
        );
        return $registry[ $scope ];
    }

    if ( 'reset' === $action ) {
        $registry[ $scope ] = array();
    }

    return $registry[ $scope ];
}

function holyprofweb_get_reserved_post_ids( $scope = 'default' ) {
    return holyprofweb_request_registry( 'get', $scope );
}

function holyprofweb_reserve_post_ids( $post_ids, $scope = 'default' ) {
    return holyprofweb_request_registry( 'reserve', $scope, $post_ids );
}

function holyprofweb_merge_meta_query( $base_args, $clauses ) {
    if ( empty( $clauses ) ) {
        return $base_args;
    }

    $merged = array( 'relation' => 'AND' );
    if ( ! empty( $base_args['meta_query'] ) && is_array( $base_args['meta_query'] ) ) {
        $merged[] = $base_args['meta_query'];
    }
    foreach ( $clauses as $clause ) {
        $merged[] = $clause;
    }

    $base_args['meta_query'] = $merged;
    return $base_args;
}

function holyprofweb_exclude_placeholder_meta_clause() {
    return array(
        'relation' => 'OR',
        array(
            'key'     => '_hpw_placeholder_post',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => '_hpw_placeholder_post',
            'value'   => '1',
            'compare' => '!=',
        ),
    );
}

function holyprofweb_merge_tax_query( $base_args, $clauses ) {
    if ( empty( $clauses ) ) {
        return $base_args;
    }

    $merged = array( 'relation' => 'AND' );
    if ( ! empty( $base_args['tax_query'] ) && is_array( $base_args['tax_query'] ) ) {
        $merged[] = $base_args['tax_query'];
    }
    foreach ( $clauses as $clause ) {
        $merged[] = $clause;
    }

    $base_args['tax_query'] = $merged;
    return $base_args;
}

function holyprofweb_get_country_priority_values_for_code( $country_code ) {
    $country_code = holyprofweb_normalize_country_code( $country_code );
    if ( ! $country_code ) {
        return array();
    }

    $values = array(
        $country_code,
        holyprofweb_get_country_name_from_code( $country_code ),
    );

    if ( 'US' === $country_code ) {
        $values = array_merge( $values, array( 'USA', 'United States', 'America' ) );
    } elseif ( 'GB' === $country_code ) {
        $values = array_merge( $values, array( 'UK', 'United Kingdom', 'Britain', 'England' ) );
    } elseif ( 'NG' === $country_code ) {
        $values[] = 'Nigeria';
    }

    return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $values ) ) ) );
}

function holyprofweb_get_generic_country_focus_values() {
    return array( 'Global', 'General', 'Worldwide', 'International', 'All Countries' );
}

function holyprofweb_get_personalized_post_ids( $base_args = array(), $limit = 6, $options = array() ) {
    $limit   = max( 1, (int) $limit );
    $options = wp_parse_args(
        $options,
        array(
            'scope'               => 'default',
            'reserve'             => true,
            'dedupe'              => true,
            'country_code'        => '',
            'module'              => 'generic',
            'allow_meta_fallback' => true,
            'exclude_placeholders'=> true,
        )
    );

    $base_args = wp_parse_args(
        $base_args,
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit,
            'no_found_rows'       => true,
            'fields'              => 'ids',
            'ignore_sticky_posts' => true,
        )
    );
    $base_args['posts_per_page'] = $limit;
    $base_args['fields']         = 'ids';

    if ( ! empty( $options['exclude_placeholders'] ) ) {
        $base_args = holyprofweb_merge_meta_query(
            $base_args,
            array(
                holyprofweb_exclude_placeholder_meta_clause(),
            )
        );
    }

    $excluded_ids = array_values( array_filter( array_map( 'intval', (array) ( $base_args['post__not_in'] ?? array() ) ) ) );
    if ( ! empty( $options['dedupe'] ) ) {
        $excluded_ids = array_values( array_unique( array_merge( $excluded_ids, holyprofweb_get_reserved_post_ids( $options['scope'] ) ) ) );
    }
    if ( ! empty( $excluded_ids ) ) {
        $base_args['post__not_in'] = $excluded_ids;
    }

    if ( ! holyprofweb_should_personalize_for_request() ) {
        $fallback_ids = get_posts( $base_args );
        if ( ! empty( $options['reserve'] ) ) {
            holyprofweb_reserve_post_ids( $fallback_ids, $options['scope'] );
        }
        return array_values( array_unique( array_map( 'intval', $fallback_ids ) ) );
    }

    $country_code = holyprofweb_normalize_country_code( $options['country_code'] );
    if ( ! $country_code ) {
        $country_code = holyprofweb_get_current_country_code();
    }

    $ordered_ids   = array();
    $country_quota = $country_code ? max( 1, min( $limit, (int) ceil( $limit * 0.6 ) ) ) : 0;
    $global_quota  = max( 1, min( $limit, (int) ceil( $limit * 0.25 ) ) );

    if ( $country_code && $country_quota > 0 ) {
        $country_args = $base_args;
        $country_args['posts_per_page'] = $country_quota;
        $country_args = holyprofweb_merge_tax_query(
            $country_args,
            array(
                array(
                    'taxonomy' => holyprofweb_get_country_taxonomy_name(),
                    'field'    => 'slug',
                    'terms'    => array( holyprofweb_get_country_term_slug( $country_code ) ),
                ),
            )
        );
        $ordered_ids = array_merge( $ordered_ids, get_posts( $country_args ) );

        if ( count( $ordered_ids ) < $country_quota && ! empty( $options['allow_meta_fallback'] ) ) {
            foreach ( holyprofweb_get_country_priority_values_for_code( $country_code ) as $priority_value ) {
                if ( count( $ordered_ids ) >= $country_quota ) {
                    break;
                }

                $legacy_args = $base_args;
                $legacy_args['posts_per_page'] = $country_quota - count( $ordered_ids );
                $legacy_args['post__not_in']   = array_values( array_unique( array_merge( $excluded_ids, $ordered_ids ) ) );
                $legacy_args = holyprofweb_merge_meta_query(
                    $legacy_args,
                    array(
                        array(
                            'key'     => '_hpw_country_focus',
                            'value'   => $priority_value,
                            'compare' => 'LIKE',
                        ),
                    )
                );
                $ordered_ids = array_merge( $ordered_ids, get_posts( $legacy_args ) );
            }
        }
    }

    if ( count( $ordered_ids ) < $limit ) {
        $global_args = $base_args;
        $global_args['posts_per_page'] = min( $global_quota, $limit - count( $ordered_ids ) );
        $global_args['post__not_in']   = array_values( array_unique( array_merge( $excluded_ids, $ordered_ids ) ) );
        $global_args = holyprofweb_merge_tax_query(
            $global_args,
            array(
                array(
                    'relation' => 'OR',
                    array(
                        'taxonomy' => holyprofweb_get_country_taxonomy_name(),
                        'field'    => 'slug',
                        'terms'    => array( 'global' ),
                    ),
                    array(
                        'taxonomy' => holyprofweb_get_country_taxonomy_name(),
                        'operator' => 'NOT EXISTS',
                    ),
                ),
            )
        );
        $ordered_ids = array_merge( $ordered_ids, get_posts( $global_args ) );

        if ( count( $ordered_ids ) < $limit ) {
            $legacy_global_args = $base_args;
            $legacy_global_args['posts_per_page'] = $limit - count( $ordered_ids );
            $legacy_global_args['post__not_in']   = array_values( array_unique( array_merge( $excluded_ids, $ordered_ids ) ) );
            $meta_clauses = array(
                'relation' => 'OR',
                array(
                    'key'     => '_hpw_country_focus',
                    'compare' => 'NOT EXISTS',
                ),
            );
            foreach ( holyprofweb_get_generic_country_focus_values() as $generic_value ) {
                $meta_clauses[] = array(
                    'key'     => '_hpw_country_focus',
                    'value'   => $generic_value,
                    'compare' => 'LIKE',
                );
            }
            $legacy_global_args['meta_query'] = $meta_clauses;
            $ordered_ids = array_merge( $ordered_ids, get_posts( $legacy_global_args ) );
        }
    }

    if ( count( $ordered_ids ) < $limit ) {
        $fallback_args = $base_args;
        $fallback_args['posts_per_page'] = $limit - count( $ordered_ids );
        $fallback_args['post__not_in']   = array_values( array_unique( array_merge( $excluded_ids, $ordered_ids ) ) );
        $ordered_ids = array_merge( $ordered_ids, get_posts( $fallback_args ) );
    }

    $ordered_ids = array_values( array_unique( array_map( 'intval', $ordered_ids ) ) );
    if ( ! empty( $options['reserve'] ) ) {
        holyprofweb_reserve_post_ids( $ordered_ids, $options['scope'] );
    }

    $payload = array(
        'country_code' => $country_code,
        'module'       => sanitize_key( (string) $options['module'] ),
        'scope'        => sanitize_key( (string) $options['scope'] ),
        'post_ids'     => $ordered_ids,
    );
    do_action( 'holyprofweb_personalized_posts_resolved', $payload );

    return $ordered_ids;
}

function holyprofweb_get_personalized_query( $base_args = array(), $limit = 6, $options = array() ) {
    $post_ids = holyprofweb_get_personalized_post_ids( $base_args, $limit, $options );
    if ( empty( $post_ids ) ) {
        $fallback_args = wp_parse_args(
            $base_args,
            array(
                'posts_per_page'      => $limit,
                'post_status'         => 'publish',
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
            )
        );
        return new WP_Query( $fallback_args );
    }

    $query_args = array(
        'post_type'           => $base_args['post_type'] ?? 'post',
        'post_status'         => $base_args['post_status'] ?? 'publish',
        'post__in'            => $post_ids,
        'orderby'             => 'post__in',
        'posts_per_page'      => min( $limit, count( $post_ids ) ),
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    );

    return new WP_Query( $query_args );
}

function holyprofweb_get_next_best_read_query( $post_id, $limit = 1 ) {
    $post_id = (int) $post_id;
    $tags    = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
    $cats    = wp_get_post_categories( $post_id );

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => max( 1, (int) $limit ),
        'post__not_in'   => array( $post_id ),
        'no_found_rows'  => true,
    );

    if ( ! empty( $tags ) ) {
        $args['tag__in'] = array_map( 'intval', $tags );
    } elseif ( ! empty( $cats ) ) {
        $args['category__in'] = array_map( 'intval', $cats );
    }

    return holyprofweb_get_personalized_query(
        $args,
        $limit,
        array(
            'scope'  => 'single-' . $post_id,
            'module' => 'next_best_read',
        )
    );
}

function holyprofweb_get_recommendation_link_attrs( $module, $post_id, $position = 0 ) {
    $module   = sanitize_key( (string) $module );
    $post_id  = (int) $post_id;
    $position = max( 0, (int) $position );

    return sprintf(
        ' data-hpw-rec-module="%1$s" data-hpw-rec-post="%2$d" data-hpw-rec-pos="%3$d"',
        esc_attr( $module ),
        $post_id,
        $position
    );
}

function holyprofweb_track_personalized_click_ajax() {
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'holyprofweb_personalization' ) ) {
        wp_send_json_error( 'Invalid nonce', 403 );
    }

    if ( function_exists( 'holyprofweb_enforce_rate_limit' ) ) {
        $limited = holyprofweb_enforce_rate_limit( 'personalized_click', 120, HOUR_IN_SECONDS, __( 'Too many click events from this browser.', 'holyprofweb' ) );
        if ( is_wp_error( $limited ) ) {
            wp_send_json_error( $limited->get_error_message(), 429 );
        }
    }

    $module       = isset( $_POST['module'] ) ? sanitize_key( wp_unslash( $_POST['module'] ) ) : '';
    $post_id      = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
    $position     = isset( $_POST['position'] ) ? max( 0, (int) wp_unslash( $_POST['position'] ) ) : 0;
    $country_code = holyprofweb_get_current_country_code();

    if ( ! $module || $post_id <= 0 || 'publish' !== get_post_status( $post_id ) ) {
        wp_send_json_error( 'Invalid payload', 400 );
    }

    $log      = get_option( 'holyprofweb_personalization_clicks', array() );
    $date_key = gmdate( 'Y-m-d' );
    if ( empty( $log[ $date_key ] ) || ! is_array( $log[ $date_key ] ) ) {
        $log[ $date_key ] = array();
    }

    $bucket_key = $module . '|' . ( $country_code ? $country_code : 'XX' ) . '|' . $post_id;
    if ( empty( $log[ $date_key ][ $bucket_key ] ) || ! is_array( $log[ $date_key ][ $bucket_key ] ) ) {
        $log[ $date_key ][ $bucket_key ] = array(
            'module'       => $module,
            'country_code' => $country_code ? $country_code : 'XX',
            'post_id'      => $post_id,
            'clicks'       => 0,
            'position'     => $position,
        );
    }

    $log[ $date_key ][ $bucket_key ]['clicks']++;
    $log = array_slice( $log, -14, 14, true );
    update_option( 'holyprofweb_personalization_clicks', $log, false );

    do_action(
        'holyprofweb_personalization_click_tracked',
        array(
            'module'       => $module,
            'post_id'      => $post_id,
            'country_code' => $country_code ? $country_code : 'XX',
            'position'     => $position,
        )
    );

    wp_send_json_success( array( 'tracked' => true ) );
}
add_action( 'wp_ajax_holyprofweb_track_personalized_click', 'holyprofweb_track_personalized_click_ajax' );
add_action( 'wp_ajax_nopriv_holyprofweb_track_personalized_click', 'holyprofweb_track_personalized_click_ajax' );

function holyprofweb_extend_personalization_script_config() {
    $locale = holyprofweb_detect_visitor_locale();
    $config = array(
        'personalization_nonce' => wp_create_nonce( 'holyprofweb_personalization' ),
        'detected_country'      => holyprofweb_normalize_country_code( $locale['region'] ?? '' ),
    );
    $json = wp_json_encode( $config );

    if ( wp_script_is( 'holyprofweb-main', 'enqueued' ) ) {
        wp_add_inline_script(
            'holyprofweb-main',
            'window.holyprofwebSearch = Object.assign({}, window.holyprofwebSearch || {}, ' . $json . ');',
            'before'
        );
    }

    if ( wp_script_is( 'holyprofweb-live-search', 'enqueued' ) ) {
        wp_add_inline_script(
            'holyprofweb-live-search',
            'window.holyprofwebSearch = Object.assign({}, window.holyprofwebSearch || {}, ' . $json . ');',
            'before'
        );
    }
}
add_action( 'wp_enqueue_scripts', 'holyprofweb_extend_personalization_script_config', 20 );
