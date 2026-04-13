<?php
/**
 * HolyprofWeb Theme Functions
 * Platform-level theme: reviews, companies, salaries, biography, reports.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =========================================
// THEME SETUP
// =========================================

function holyprofweb_setup() {

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    set_post_thumbnail_size( 800, 450, true );
    add_image_size( 'holyprofweb-card', 640, 480, true );
    add_image_size( 'holyprofweb-thumb', 360, 360, true );

    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list',
        'gallery', 'caption', 'style', 'script',
    ) );

    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    add_theme_support( 'post-formats', array( 'aside', 'quote', 'link' ) );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'align-wide' );

    register_nav_menus( array(
        'primary' => __( 'Primary Navigation', 'holyprofweb' ),
        'footer'  => __( 'Footer Navigation', 'holyprofweb' ),
    ) );
}
add_action( 'after_setup_theme', 'holyprofweb_setup' );


// =========================================
// ENQUEUE STYLES & SCRIPTS
// =========================================

function holyprofweb_enqueue_assets() {
    $style_path       = get_stylesheet_directory() . '/style.css';
    $main_script_path = get_template_directory() . '/assets/js/main.js';
    $search_script_path = get_template_directory() . '/assets/js/live-search.js';
    $style_version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );
    $main_version     = file_exists( $main_script_path ) ? (string) filemtime( $main_script_path ) : wp_get_theme()->get( 'Version' );
    $search_version   = file_exists( $search_script_path ) ? (string) filemtime( $search_script_path ) : wp_get_theme()->get( 'Version' );

    wp_enqueue_style(
        'holyprofweb-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );
    add_action( 'wp_head', function() {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }, 1 );

    wp_enqueue_style(
        'holyprofweb-style',
        get_stylesheet_uri(),
        array( 'holyprofweb-fonts' ),
        $style_version
    );

    wp_enqueue_script(
        'holyprofweb-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        $main_version,
        true
    );

    wp_enqueue_script(
        'holyprofweb-live-search',
        get_template_directory_uri() . '/assets/js/live-search.js',
        array(),
        $search_version,
        true
    );

    wp_localize_script(
        'holyprofweb-live-search',
        'holyprofwebSearch',
        array(
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'holyprofweb_live_search' ),
            'reaction_nonce' => wp_create_nonce( 'holyprofweb_reaction' ),
        )
    );

    // Also expose config to main.js via holyprofwebSearch (same global)
    wp_localize_script(
        'holyprofweb-main',
        'holyprofwebSearch',
        array(
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'holyprofweb_live_search' ),
            'reaction_nonce' => wp_create_nonce( 'holyprofweb_reaction' ),
            'review_nonce'   => wp_create_nonce( 'holyprofweb_submit_review' ),
            'salary_nonce'   => wp_create_nonce( 'holyprofweb_submit_salary' ),
            'copy_protection_enabled' => (bool) get_option( 'hpw_enable_copy_protection', 1 ),
        )
    );

    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'holyprofweb_enqueue_assets' );

function holyprofweb_get_language_catalog() {
    return array(
        'en_US' => 'English (US)',
        'en_GB' => 'English (UK)',
        'fr_FR' => 'French (Français)',
        'es_ES' => 'Spanish (Español)',
        'pt_BR' => 'Portuguese (Português)',
        'ar'    => 'Arabic (العربية) — RTL',
        'yo'    => 'Yoruba',
        'ig_NG' => 'Igbo',
        'ha'    => 'Hausa',
        'sw'    => 'Swahili',
        'zh_CN' => 'Chinese Simplified',
    );
}

function holyprofweb_sanitize_enabled_languages( $value ) {
    $catalog = holyprofweb_get_language_catalog();
    $items   = is_array( $value ) ? $value : array();
    $items   = array_values( array_unique( array_map( 'sanitize_text_field', $items ) ) );

    return array_values(
        array_filter(
            $items,
            static function( $code ) use ( $catalog ) {
                return isset( $catalog[ $code ] );
            }
        )
    );
}

function holyprofweb_get_enabled_languages() {
    $saved = get_option( 'hpw_enabled_languages', array() );
    $saved = is_array( $saved ) ? $saved : array();

    if ( empty( $saved ) ) {
        $saved = array( get_option( 'hpw_default_language', 'en_US' ) );
    }

    return holyprofweb_sanitize_enabled_languages( $saved );
}

function holyprofweb_get_language_switcher_items() {
    $catalog = holyprofweb_get_language_catalog();
    $items   = array();

    if ( function_exists( 'pll_the_languages' ) ) {
        $languages = pll_the_languages(
            array(
                'raw'           => 1,
                'hide_if_empty' => 0,
            )
        );

        if ( is_array( $languages ) ) {
            foreach ( $languages as $language ) {
                $items[] = array(
                    'label'   => $language['name'],
                    'url'     => ! empty( $language['url'] ) ? $language['url'] : '',
                    'current' => ! empty( $language['current_lang'] ),
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
                $items[] = array(
                    'label'   => $language['translated_name'] ?? ( $language['native_name'] ?? $language['language_code'] ),
                    'url'     => $language['url'] ?? '',
                    'current' => ! empty( $language['active'] ),
                );
            }
        }
    }

    if ( count( $items ) > 1 ) {
        return $items;
    }

    foreach ( holyprofweb_get_enabled_languages() as $code ) {
        if ( ! isset( $catalog[ $code ] ) ) {
            continue;
        }

        $items[] = array(
            'label'   => $catalog[ $code ],
            'url'     => '',
            'current' => $code === get_option( 'hpw_default_language', 'en_US' ),
        );
    }

    return $items;
}

function holyprofweb_render_theme_boot_script() {
    ?>
    <script>
    (function () {
        var storageKey = 'hpw-theme';
        var root = document.documentElement;
        var savedTheme = null;

        try {
            savedTheme = localStorage.getItem(storageKey);
        } catch (err) {
            savedTheme = null;
        }

        if (savedTheme !== 'default' && savedTheme !== 'dark' && savedTheme !== 'light') {
            savedTheme = 'default';
        }

        root.setAttribute('data-theme', savedTheme);
        root.style.colorScheme = savedTheme === 'dark' ? 'dark' : 'light';
    })();
    </script>
    <?php
}
add_action( 'wp_head', 'holyprofweb_render_theme_boot_script', 0 );


// =========================================
// SIDEBARS / WIDGET AREAS
// =========================================

function holyprofweb_register_sidebars() {

    register_sidebar( array(
        'name'          => __( 'Sidebar', 'holyprofweb' ),
        'id'            => 'sidebar-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Footer Widgets', 'holyprofweb' ),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'holyprofweb_register_sidebars' );


// =========================================
// EXCERPT
// =========================================

add_filter( 'excerpt_length', function() { return 22; }, 999 );
add_filter( 'excerpt_more',   function() { return '&hellip;'; } );


// =========================================
// BODY CLASSES
// =========================================

function holyprofweb_body_classes( $classes ) {
    if ( is_singular() )   $classes[] = 'singular';
    if ( is_front_page() ) $classes[] = 'front-page';
    return $classes;
}
add_filter( 'body_class', 'holyprofweb_body_classes' );


// =========================================
// CUSTOM SEARCH FORM (with inline icon)
// =========================================

function holyprofweb_search_form( $form ) {
    $uid = 'search-' . uniqid();
    $val = get_search_query();

    $form = sprintf(
        '<form role="search" method="get" class="search-form" action="%s">
            <label class="screen-reader-text" for="%s">%s</label>
            <div class="search-input-wrap">
                <svg class="search-icon-inline" width="15" height="15" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" class="search-field" id="%s" name="s"
                       placeholder="%s" value="%s" autocomplete="off" />
            </div>
            <button type="submit" class="search-submit">%s</button>
        </form>',
        esc_url( home_url( '/' ) ),
        esc_attr( $uid ),
        esc_html__( 'Search for:', 'holyprofweb' ),
        esc_attr( $uid ),
        esc_attr__( 'Search reviews, companies, salaries\u2026', 'holyprofweb' ),
        esc_attr( $val ),
        esc_html__( 'Search', 'holyprofweb' )
    );

    return $form;
}
add_filter( 'get_search_form', 'holyprofweb_search_form' );


// =========================================
// RELATED POSTS HELPER
// =========================================

function holyprofweb_get_related_posts( $post_id, $count = 3 ) {
    $categories = get_the_category( $post_id );
    if ( empty( $categories ) ) {
        return new WP_Query( array( 'post__in' => array( 0 ) ) );
    }

    return new WP_Query( array(
        'post__not_in'   => array( $post_id ),
        'posts_per_page' => $count,
        'category__in'   => wp_list_pluck( $categories, 'term_id' ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ) );
}

function holyprofweb_get_inline_also_read_posts( $post_id, $limit = 3 ) {
    $categories = get_the_category( $post_id );
    $category_ids = ! empty( $categories ) ? wp_list_pluck( $categories, 'term_id' ) : array();
    $country_context = holyprofweb_get_active_country_context( $post_id );
    $country_focus = ! empty( $country_context['focus'] ) ? $country_context['focus'] : '';

    $query_args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => max( 3, (int) $limit ),
        'post__not_in'   => array( $post_id ),
        'no_found_rows'  => true,
    );

    if ( ! empty( $category_ids ) ) {
        $query_args['category__in'] = $category_ids;
    }

    if ( $country_focus && 'General' !== $country_focus && 'Unknown' !== $country_focus ) {
        $query_args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => '_hpw_country_focus',
                'value'   => $country_focus,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => '_hpw_country_focus',
                'compare' => 'NOT EXISTS',
            ),
        );
    }

    $query = new WP_Query( $query_args );
    if ( ! empty( $query->posts ) ) {
        return array_slice( $query->posts, 0, $limit );
    }

    $fallback = holyprofweb_get_related_posts( $post_id, $limit );
    if ( ! empty( $fallback->posts ) ) {
        return array_slice( $fallback->posts, 0, $limit );
    }

    return array();
}

function holyprofweb_inject_inline_also_read( $content ) {
    if ( is_admin() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    global $post;
    if ( ! $post instanceof WP_Post ) {
        return $content;
    }

    $related_posts = holyprofweb_get_inline_also_read_posts( $post->ID, 3 );
    if ( empty( $related_posts ) ) {
        return $content;
    }

    $parts = explode( '</p>', $content );
    if ( count( $parts ) < 2 ) {
        return $content;
    }

    $insert_points = array( 2, 4, 6 );
    $paragraphs = 0;
    $new_parts = array();
    $inserted = 0;

    foreach ( $parts as $index => $part ) {
        $new_parts[] = $part;

        if ( '' !== trim( wp_strip_all_tags( $part ) ) ) {
            $paragraphs++;
        }

        if ( $inserted < count( $related_posts ) && in_array( $paragraphs, $insert_points, true ) && $index < count( $parts ) - 1 ) {
            $related_post = $related_posts[ $inserted ];
            $new_parts[] = '<div class="also-read-inline also-read-inline--body"><strong>' . esc_html__( 'Also read:', 'holyprofweb' ) . '</strong> <a href="' . esc_url( get_permalink( $related_post ) ) . '">' . esc_html( holyprofweb_get_decoded_post_title( $related_post->ID ) ) . '</a></div>';
            $inserted++;
        }
    }

    return implode( '</p>', $new_parts );
}
add_filter( 'the_content', 'holyprofweb_inject_inline_also_read', 19 );


// =========================================
// PAGINATION HELPER
// =========================================

function holyprofweb_pagination( $query = null ) {
    global $wp_query;
    if ( null === $query ) $query = $wp_query;
    if ( $query->max_num_pages <= 1 ) return;

    $links = paginate_links( array(
        'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
        'format'    => '?paged=%#%',
        'current'   => max( 1, get_query_var( 'paged' ) ),
        'total'     => $query->max_num_pages,
        'prev_text' => '&larr;',
        'next_text' => '&rarr;',
        'type'      => 'plain',
    ) );

    if ( $links ) {
        echo '<nav class="pagination" aria-label="' . esc_attr__( 'Posts pagination', 'holyprofweb' ) . '">';
        echo wp_kses_post( $links );
        echo '</nav>';
    }
}


// =========================================
// POST THUMBNAIL WITH FALLBACK HELPER
// =========================================

function holyprofweb_thumbnail( $size = 'holyprofweb-card', $link = true ) {
    $url = $link ? get_permalink() : null;

    if ( $link ) echo '<a href="' . esc_url( $url ) . '" class="post-card-thumb" tabindex="-1" aria-hidden="true">';
    else         echo '<div class="post-card-thumb">';

    if ( has_post_thumbnail() ) {
        the_post_thumbnail( $size, array( 'loading' => 'lazy' ) );
    } else {
        // Check for an SVG data URL stored in post meta (generated fallback that
        // isn't a real WP attachment, so has_post_thumbnail() is false for it).
        $gen_url = (string) get_post_meta( get_the_ID(), '_holyprofweb_gen_image_url', true );
        if ( $gen_url && 0 === strpos( $gen_url, 'data:image/svg+xml' ) ) {
            // Data URLs cannot be sanitized with esc_url_raw (it strips them).
            // The value is theme-generated SVG — safe to output directly in src.
            echo '<img src="' . $gen_url . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;" />';
        } else {
            $ph = get_template_directory_uri() . '/assets/images/placeholder.svg';
            echo '<img src="' . esc_url( $ph ) . '" alt="" loading="lazy" class="post-card-placeholder-img" />';
        }
    }

    echo $link ? '</a>' : '</div>';
}

function holyprofweb_get_image_size_dimensions( $size = 'holyprofweb-card' ) {
    $registered = function_exists( 'wp_get_registered_image_subsizes' ) ? wp_get_registered_image_subsizes() : array();

    if ( isset( $registered[ $size ] ) ) {
        return array(
            'width'  => max( 1, (int) $registered[ $size ]['width'] ),
            'height' => max( 1, (int) $registered[ $size ]['height'] ),
        );
    }

    switch ( $size ) {
        case 'holyprofweb-thumb':
            return array( 'width' => 360, 'height' => 360 );
        case 'full':
            return array( 'width' => 1200, 'height' => 630 );
        case 'holyprofweb-card':
        default:
            return array( 'width' => 640, 'height' => 480 );
    }
}


// =========================================
// PERFORMANCE — CLEAN UP HEAD
// =========================================

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );


// =========================================
// COMMENT FORM — RELABEL AS USER REVIEWS
// =========================================

function holyprofweb_reviews_comment_defaults( $defaults ) {
    if ( ! is_singular( 'post' ) ) return $defaults;

    $defaults['title_reply']        = __( 'User Reviews', 'holyprofweb' );
    $defaults['title_reply_to']     = __( 'Reply to %s', 'holyprofweb' );
    $defaults['label_submit']       = __( 'Post Review', 'holyprofweb' );
    $defaults['title_reply_before'] = '<h2 id="reply-title" class="reviews-section-title">';
    $defaults['title_reply_after']  = '</h2>';

    return $defaults;
}
add_filter( 'comment_form_defaults', 'holyprofweb_reviews_comment_defaults' );


// =========================================
// ADS SYSTEM — ADMIN SETTINGS PAGE
// =========================================

/**
 * Register Appearance → Holyprof Ads menu item.
 */
function holyprofweb_ads_admin_menu() {
    return;
}
add_action( 'admin_menu', 'holyprofweb_ads_admin_menu' );

/**
 * Render the Ads settings page — Adsterra units only.
 */
function holyprofweb_ads_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $saved = false;

    if (
        isset( $_POST['holyprofweb_ads_nonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['holyprofweb_ads_nonce'] ) ), 'holyprofweb_save_ads' )
    ) {
        update_option( 'holyprofweb_ads_enabled', empty( $_POST['hpw_ads_enabled'] ) ? '0' : '1' );

        // Ad unit codes — base64-encoded by JS to bypass server WAF.
        $ad_units = array(
            'social'         => 'hpw_ad_social',
            'native'         => 'hpw_ad_native',
            'banner_728x90'  => 'hpw_ad_728x90',
            'banner_468x60'  => 'hpw_ad_468x60',
            'banner_320x50'  => 'hpw_ad_320x50',
            'banner_160x300' => 'hpw_ad_160x300',
            'banner_160x600' => 'hpw_ad_160x600',
            'banner_300x250' => 'hpw_ad_300x250',
        );
        foreach ( $ad_units as $option_suffix => $field ) {
            update_option( 'holyprofweb_ad_enabled_' . $option_suffix, empty( $_POST[ $field . '_enabled' ] ) ? '0' : '1' );

            $b64_field = $field . '_b64';
            if ( ! empty( $_POST[ $b64_field ] ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $code = base64_decode( wp_unslash( $_POST[ $b64_field ] ) );
            } else {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $code = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
            }
            update_option( 'holyprofweb_ad_format_' . $option_suffix, $code );
        }

        // Density per format group.
        foreach ( array( 'social', 'native', 'leaderboard', 'rectangle', 'mobile' ) as $group ) {
            $val = isset( $_POST[ 'hpw_density_' . $group ] ) ? sanitize_key( wp_unslash( $_POST[ 'hpw_density_' . $group ] ) ) : 'basic';
            if ( 'rigid' === $val ) {
                $val = 'advanced';
            }
            update_option( 'holyprofweb_ad_density_' . $group, in_array( $val, array( 'basic', 'normal', 'advanced' ), true ) ? $val : 'basic' );
        }

        $saved = true;
    }

    // Helper: decode stored code for textarea display.
    $get_code = function( $key ) {
        return (string) get_option( 'holyprofweb_ad_format_' . $key, '' );
    };
    $is_unit_enabled = function( $key ) {
        return '0' !== (string) get_option( 'holyprofweb_ad_enabled_' . $key, '1' );
    };
    $density_label = function( $group ) {
        $d = holyprofweb_get_ad_density( $group );
        if ( 'rigid' === $d ) {
            $d = 'advanced';
        }
        return in_array( $d, array( 'basic', 'normal', 'advanced' ), true ) ? $d : 'basic';
    };
    ?>
    <div class="wrap">
        <h1>&#128250; <?php esc_html_e( 'HPW Settings — Ads', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'ads' ); ?>

        <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Ad codes saved.', 'holyprofweb' ); ?></p>
        </div>
        <?php endif; ?>

        <p style="color:#666;margin-bottom:24px;">
            <?php esc_html_e( 'Manage the 8 ad units here. Choose Basic, Normal, or Advanced placement per ad group, and switch any unit off without deleting its code.', 'holyprofweb' ); ?>
        </p>

        <form method="post" id="hpw-ads-form">
            <?php wp_nonce_field( 'holyprofweb_save_ads', 'holyprofweb_ads_nonce' ); ?>
            <style>
                #hpw-ads-form .form-table {
                    width: 100%;
                    max-width: none;
                }
                #hpw-ads-form .form-table th {
                    width: 180px;
                    padding-left: 0;
                }
                #hpw-ads-form .form-table td {
                    padding-right: 0;
                }
                #hpw-ads-form .hpw-ad-code {
                    min-height: 180px;
                    width: 100%;
                    font-size: 12px;
                    line-height: 1.5;
                }
                #hpw-ads-form .hpw-ad-switch {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 10px;
                    font-weight: 600;
                }
                #hpw-ads-form .hpw-placement-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0 0 24px;
                    background: #fff;
                }
                #hpw-ads-form .hpw-placement-table th,
                #hpw-ads-form .hpw-placement-table td {
                    border: 1px solid #dcdcde;
                    padding: 10px 12px;
                    vertical-align: top;
                }
                #hpw-ads-form .hpw-placement-table th {
                    background: #f6f7f7;
                    text-align: left;
                }
            </style>

            <table class="form-table" role="presentation" style="max-width:680px;margin-bottom:20px;">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Global Ads Switch', 'holyprofweb' ); ?></th>
                    <td>
                        <label class="hpw-ad-switch">
                            <input type="checkbox" name="hpw_ads_enabled" value="1" <?php checked( '1', get_option( 'holyprofweb_ads_enabled', '1' ) ); ?> />
                            <span><?php esc_html_e( 'Enable ads sitewide', 'holyprofweb' ); ?></span>
                        </label>
                        <p class="description"><?php esc_html_e( 'Turn this off to hide all ads without removing saved ad code.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
            </table>

            <table class="hpw-placement-table" role="presentation">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Ad Unit', 'holyprofweb' ); ?></th>
                        <th><?php esc_html_e( 'Basic', 'holyprofweb' ); ?></th>
                        <th><?php esc_html_e( 'Normal', 'holyprofweb' ); ?></th>
                        <th><?php esc_html_e( 'Advanced', 'holyprofweb' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><?php esc_html_e( 'Social Bar', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Floating social bar sitewide.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Floating social bar sitewide.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Floating social bar sitewide.', 'holyprofweb' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Native Banner', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'After 2nd paragraph in posts.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Posts plus archive inline blocks.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Posts, archives, and homepage inline blocks.', 'holyprofweb' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Banner 728×90', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Header leaderboard.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Header and footer leaderboard.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Header, footer, and inline feed/archive support.', 'holyprofweb' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Banner 468×60', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Desktop leaderboard fallback.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Desktop leaderboard fallback.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Desktop leaderboard fallback.', 'holyprofweb' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Banner 320×50', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Mobile sticky footer.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sticky footer plus homepage mobile slot.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sticky footer plus homepage and archive mobile slots.', 'holyprofweb' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Banner 160×300', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar rectangle fallback.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar and inline fallback.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar, inline, and extra dense fallback.', 'holyprofweb' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Banner 160×600', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar skyscraper fallback.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar skyscraper fallback.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar skyscraper fallback.', 'holyprofweb' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Banner 300×250', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar plus first in-content slot.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'Sidebar, second sidebar, first in-content, archive/home inline.', 'holyprofweb' ); ?></td><td><?php esc_html_e( 'All normal placements plus second in-content slot.', 'holyprofweb' ); ?></td></tr>
                </tbody>
            </table>

            <?php
            // ── Section helper ────────────────────────────────────────────
            $section = function( $title, $desc ) {
                echo '<h2 style="margin-top:32px;border-bottom:1px solid #ddd;padding-bottom:6px;">' . esc_html( $title ) . '</h2>';
                if ( $desc ) echo '<p style="color:#666;margin-bottom:12px;">' . esc_html( $desc ) . '</p>';
            };
            $unit_row = function( $label, $adsterra_id, $field, $code_key ) use ( $get_code, $is_unit_enabled ) {
                $val = $get_code( $code_key );
                ?>
                <tr>
                    <th scope="row">
                        <strong><?php echo esc_html( $label ); ?></strong><br>
                        <span style="font-size:11px;color:#888;font-weight:normal;">ID: <?php echo esc_html( $adsterra_id ); ?></span>
                    </th>
                    <td>
                        <label class="hpw-ad-switch">
                            <input type="checkbox" name="<?php echo esc_attr( $field . '_enabled' ); ?>" value="1" <?php checked( true, $is_unit_enabled( $code_key ) ); ?> />
                            <span><?php esc_html_e( 'Enable this ad unit', 'holyprofweb' ); ?></span>
                        </label>
                        <textarea name="<?php echo esc_attr( $field ); ?>" rows="8"
                                  class="large-text code hpw-ad-code"
                                  placeholder="Paste Adsterra script code here…"><?php echo esc_textarea( $val ); ?></textarea>
                    </td>
                </tr>
                <?php
            };
            $density_row = function( $group, $basic_desc, $normal_desc, $rigid_desc ) use ( $density_label ) {
                $cur = $density_label( $group );
                ?>
                <tr>
                    <th scope="row" style="padding-top:4px;">Placement</th>
                    <td>
                        <select name="hpw_density_<?php echo esc_attr( $group ); ?>" style="min-width:220px;">
                            <option value="basic" <?php selected( $cur, 'basic' ); ?>><?php echo esc_html( 'Basic — ' . $basic_desc ); ?></option>
                            <option value="normal" <?php selected( $cur, 'normal' ); ?>><?php echo esc_html( 'Normal — ' . $normal_desc ); ?></option>
                            <option value="advanced" <?php selected( $cur, 'advanced' ); ?>><?php echo esc_html( 'Advanced — ' . $rigid_desc ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding-top:0;"><hr style="margin:4px 0 16px;"></td></tr>
                <?php
            };
            ?>

            <?php $section( 'Social Bar', 'Sitewide floating bar — always shown when active.' ); ?>
            <table class="form-table" role="presentation">
                <?php $unit_row( 'Social Bar', '28927838', 'hpw_ad_social', 'social' ); ?>
                <?php $density_row( 'social', 'Floating social bar sitewide.', 'Floating social bar sitewide.', 'Floating social bar sitewide.' ); ?>
            </table>

            <?php $section( 'Native Banner', 'Blends with content — good for feeds and article bodies.' ); ?>
            <table class="form-table" role="presentation">
                <?php $unit_row( 'Native Banner', '28927839', 'hpw_ad_native', 'native' ); ?>
                <?php $density_row( 'native', 'Inside posts only (after 2nd paragraph).', 'Posts plus one archive/feed insertion.', 'Posts, feed/archive listings, and homepage inline.' ); ?>
            </table>

            <?php $section( 'Leaderboard — Desktop Wide', 'Banner 728×90 is primary; 468×60 is the fallback if 728 is empty.' ); ?>
            <table class="form-table" role="presentation">
                <?php $unit_row( 'Banner 728×90', '28927843', 'hpw_ad_728x90', 'banner_728x90' ); ?>
                <?php $unit_row( 'Banner 468×60', '28927841', 'hpw_ad_468x60', 'banner_468x60' ); ?>
                <?php $density_row( 'leaderboard', 'Header only.', 'Header plus footer.', 'Header, footer, and feed/archive inline.' ); ?>
            </table>

            <?php $section( 'Rectangle / Sidebar', '300×250 is primary; 160×300 and 160×600 are fallbacks.' ); ?>
            <table class="form-table" role="presentation">
                <?php $unit_row( 'Banner 300×250', '28927844', 'hpw_ad_300x250', 'banner_300x250' ); ?>
                <?php $unit_row( 'Banner 160×300', '28927840', 'hpw_ad_160x300', 'banner_160x300' ); ?>
                <?php $unit_row( 'Banner 160×600', '28927844', 'hpw_ad_160x600', 'banner_160x600' ); ?>
                <?php $density_row( 'rectangle', 'Sidebar plus 1 in-content slot.', 'Sidebar, sidebar-2, first in-content, and archive/home inline.', 'All normal placements plus second in-content slot.' ); ?>
            </table>

            <?php $section( 'Mobile Banner', 'Compact banner shown only on mobile devices.' ); ?>
            <table class="form-table" role="presentation">
                <?php $unit_row( 'Banner 320×50', '28927842', 'hpw_ad_320x50', 'banner_320x50' ); ?>
                <?php $density_row( 'mobile', 'Mobile sticky footer.', 'Mobile sticky plus homepage mobile inline.', 'Mobile sticky plus homepage and archive mobile inline.' ); ?>
            </table>

            <?php submit_button( __( 'Save Ad Codes', 'holyprofweb' ) ); ?>
        </form>
    </div>

    <script>
    (function(){
        var form = document.getElementById('hpw-ads-form');
        if ( ! form ) return;
        form.addEventListener('submit', function(){
            var areas = form.querySelectorAll('.hpw-ad-code');
            areas.forEach(function(ta){
                var orig = ta.name;
                if ( ! orig ) return;
                var val  = ta.value;
                if ( val.trim() === '' ) return;
                try {
                    var encoded = btoa(unescape(encodeURIComponent(val)));
                    var hidden  = document.createElement('input');
                    hidden.type  = 'hidden';
                    hidden.name  = orig + '_b64';
                    hidden.value = encoded;
                    form.appendChild(hidden);
                    ta.name = ''; // stop raw POST of this field
                } catch(e) {}
            });
        });
    })();
    </script>
    <?php
}

/**
 * Retrieve ad code for a slot.
 * Returns empty string if not set.
 */
function holyprofweb_get_ad_code( $slot ) {
    return (string) get_option( 'holyprofweb_ad_' . sanitize_key( $slot ), '' );
}

function holyprofweb_get_ad_unit_code( $unit ) {
    $unit = sanitize_key( $unit );
    if ( '' === $unit ) {
        return '';
    }

    if ( '1' !== (string) get_option( 'holyprofweb_ads_enabled', '1' ) ) {
        return '';
    }

    if ( '0' === (string) get_option( 'holyprofweb_ad_enabled_' . $unit, '1' ) ) {
        return '';
    }

    $code = (string) get_option( 'holyprofweb_ad_format_' . $unit, '' );
    if ( trim( $code ) ) {
        return $code;
    }

    $legacy_map = array(
        'leaderboard' => 'leaderboard',
        'rectangle'   => 'rectangle',
        'mobile'      => 'mobile',
        'social'      => 'social',
    );

    if ( isset( $legacy_map[ $unit ] ) ) {
        return (string) get_option( 'holyprofweb_ad_format_' . $legacy_map[ $unit ], '' );
    }

    return '';
}

function holyprofweb_get_first_available_ad_code( $units ) {
    foreach ( (array) $units as $unit ) {
        $code = holyprofweb_get_ad_unit_code( $unit );
        if ( trim( $code ) ) {
            return $code;
        }
    }

    return '';
}

function holyprofweb_get_ad_format_code( $format ) {
    $format = sanitize_key( $format );
    $lookup = array(
        'leaderboard' => array( 'banner_728x90', 'banner_468x60', 'leaderboard' ),
        'rectangle'   => array( 'banner_300x250', 'banner_160x300', 'banner_160x600', 'rectangle' ),
        'mobile'      => array( 'banner_320x50', 'mobile' ),
        'social'      => array( 'social' ),
        'native'      => array( 'native' ),
    );
    $code   = holyprofweb_get_first_available_ad_code( $lookup[ $format ] ?? array( $format ) );

    if ( trim( $code ) ) {
        return $code;
    }

    $fallbacks = array(
        'leaderboard' => 'header',
        'rectangle'   => 'sidebar',
        'incontent'   => 'incontent',
        'footer'      => 'footer',
        'mobile'      => '',
        'social'      => '',
    );

    $fallback_slot = isset( $fallbacks[ $format ] ) ? $fallbacks[ $format ] : '';
    return $fallback_slot ? holyprofweb_get_ad_code( $fallback_slot ) : '';
}

function holyprofweb_get_ad_density( $format ) {
    $density = sanitize_key( (string) get_option( 'holyprofweb_ad_density_' . sanitize_key( $format ), 'basic' ) );
    return 'rigid' === $density ? 'advanced' : $density;
}

function holyprofweb_ad_density_allows( $format, $placement ) {
    $density = holyprofweb_get_ad_density( $format );
    $map = array(
        'leaderboard' => array(
            'basic'  => array( 'header' ),
            'normal' => array( 'header', 'footer', 'archive_inline' ),
            'advanced' => array( 'header', 'front_inline', 'archive_inline', 'footer' ),
        ),
        'rectangle' => array(
            'basic'  => array( 'sidebar', 'incontent_1' ),
            'normal' => array( 'sidebar', 'sidebar_2', 'incontent_1', 'front_inline', 'archive_inline' ),
            'advanced' => array( 'sidebar', 'sidebar_2', 'incontent_1', 'incontent_2', 'front_inline', 'archive_inline' ),
        ),
        'mobile' => array(
            'basic'  => array( 'mobile_sticky' ),
            'normal' => array( 'mobile_sticky', 'front_mobile' ),
            'advanced' => array( 'mobile_sticky', 'front_mobile', 'archive_mobile' ),
        ),
        'social' => array(
            'basic'  => array( 'social_bar' ),
            'normal' => array( 'social_bar' ),
            'advanced' => array( 'social_bar' ),
        ),
        'native' => array(
            'basic'  => array( 'incontent_1' ),
            'normal' => array( 'incontent_1', 'incontent_2', 'archive_inline' ),
            'advanced' => array( 'incontent_1', 'incontent_2', 'archive_inline', 'front_inline' ),
        ),
        'footer' => array(
            'basic'  => array( 'footer' ),
            'normal' => array( 'footer' ),
            'advanced' => array( 'footer' ),
        ),
    );

    $allowed = $map[ $format ][ $density ] ?? array();
    return in_array( $placement, $allowed, true );
}

function holyprofweb_render_ad_format( $format, $placement, $extra_class = '' ) {
    if ( ! holyprofweb_ad_density_allows( $format, $placement ) ) {
        return;
    }

    $inline_like   = in_array( $placement, array( 'front_inline', 'archive_inline', 'incontent_1', 'incontent_2' ), true );
    $desktop_units = array();
    $mobile_units  = array();

    if ( 'leaderboard' === $format ) {
        $desktop_units = array( 'banner_728x90', 'banner_468x60', 'leaderboard' );
        $mobile_units  = array( 'banner_320x50', 'mobile' );
        if ( $inline_like ) {
            $desktop_units[] = 'native';
            $mobile_units[]  = 'native';
        }
    } elseif ( 'rectangle' === $format ) {
        $desktop_units = array( 'banner_300x250', 'banner_160x300', 'banner_160x600', 'rectangle' );
        $mobile_units  = array( 'banner_320x50', 'mobile' );
        if ( $inline_like ) {
            array_unshift( $desktop_units, 'native' );
            array_unshift( $mobile_units, 'native' );
        }
    } elseif ( 'mobile' === $format ) {
        $mobile_units = array( 'banner_320x50', 'mobile', 'native' );
    } elseif ( 'social' === $format ) {
        $desktop_units = array( 'social' );
        $mobile_units  = array( 'social' );
    } else {
        $desktop_units = array( $format );
    }

    $desktop_code = holyprofweb_get_first_available_ad_code( $desktop_units );
    $mobile_code  = holyprofweb_get_first_available_ad_code( $mobile_units );

    if ( ! trim( $desktop_code ) && trim( $mobile_code ) && ! in_array( $placement, array( 'mobile_sticky', 'front_mobile', 'archive_mobile' ), true ) ) {
        $desktop_code = $mobile_code;
    }

    if ( ! trim( $desktop_code ) && ! trim( $mobile_code ) ) {
        return;
    }

    $class = 'ad-container ad-format-' . esc_attr( $format ) . ' ad-placement-' . esc_attr( $placement );
    if ( $extra_class ) {
        $class .= ' ' . esc_attr( $extra_class );
    }

    echo '<div class="' . $class . '">';
    if ( trim( $desktop_code ) ) {
        echo '<div class="ad-variant ad-variant--desktop">';
        echo $desktop_code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
    if ( trim( $mobile_code ) ) {
        echo '<div class="ad-variant ad-variant--mobile">';
        echo $mobile_code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Render ad block with wrapper.
 * Code is admin-controlled and intentionally unescaped.
 */
function holyprofweb_render_ad( $slot, $extra_class = '' ) {
    $slot_map = array(
        'header'     => array( 'format' => 'leaderboard', 'placement' => 'header' ),
        'sidebar'    => array( 'format' => 'rectangle',   'placement' => 'sidebar' ),
        'sidebar_2'  => array( 'format' => 'rectangle',   'placement' => 'sidebar_2' ),
        'incontent'  => array( 'format' => 'rectangle',   'placement' => 'incontent_1' ),
        'incontent_2'=> array( 'format' => 'rectangle',   'placement' => 'incontent_2' ),
        'footer'     => array( 'format' => 'leaderboard', 'placement' => 'footer' ),
    );
    if ( isset( $slot_map[ $slot ] ) ) {
        holyprofweb_render_ad_format( $slot_map[ $slot ]['format'], $slot_map[ $slot ]['placement'], $extra_class );
        return;
    }

    $code = holyprofweb_get_ad_code( $slot );
    if ( empty( trim( $code ) ) ) {
        return;
    }

    $class = 'ad-container ad-' . esc_attr( $slot );
    if ( $extra_class ) {
        $class .= ' ' . esc_attr( $extra_class );
    }

    echo '<div class="' . $class . '">';
    echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — admin-managed ad code
    echo '</div>';
}

/**
 * Inject in-content ads after the 2nd and 4th paragraphs.
 */
function holyprofweb_inject_incontent_ad( $content ) {
    if ( ! is_single() || is_admin() ) {
        return $content;
    }

    ob_start();
    holyprofweb_render_ad_format( 'rectangle', 'incontent_1', 'ad-incontent-slot' );
    $code_1 = trim( (string) ob_get_clean() );
    ob_start();
    holyprofweb_render_ad_format( 'rectangle', 'incontent_2', 'ad-incontent-slot' );
    $code_2 = trim( (string) ob_get_clean() );
    $code_3 = '';
    if ( holyprofweb_ad_density_allows( 'rectangle', 'incontent_2' ) && 'advanced' === holyprofweb_get_ad_density( 'rectangle' ) ) {
        ob_start();
        holyprofweb_render_ad_format( 'rectangle', 'incontent_2', 'ad-incontent-slot ad-incontent-slot--extra' );
        $code_3 = trim( (string) ob_get_clean() );
    }

    $has_ad1 = '' !== $code_1;
    $has_ad2 = '' !== $code_2;
    $has_ad3 = '' !== $code_3;

    if ( ! $has_ad1 && ! $has_ad2 && ! $has_ad3 ) {
        return $content;
    }

    $parts = explode( '</p>', $content );
    $total = count( $parts );

    // Inject ad 1 after the 2nd paragraph (index 1, since 0-based)
    if ( $has_ad1 && $total > 2 ) {
        $ad_block_1 = '<div class="ad-container ad-incontent">' . $code_1 . '</div>';
        // Rebuild with ad1 inserted after part index 1
        $new_parts = array();
        foreach ( $parts as $i => $part ) {
            $new_parts[] = $part;
            if ( $i === 1 ) {
                $new_parts[] = $ad_block_1 . '<!-- ad1-injected -->';
            }
        }
        $parts = $new_parts;
    }

    // Inject ad 2 after the 4th paragraph (index 3 in original, but we need to
    // account for the injected ad block shifting indices)
    if ( $has_ad2 ) {
        // Rebuild parts from scratch on the current $parts array
        // Find the 4th </p> by counting only the original paragraph closes
        $rebuilt  = implode( '</p>', $parts );
        $segments = explode( '</p>', $rebuilt );
        $seg_total = count( $segments );

        if ( $seg_total > 4 ) {
            $ad_block_2 = '<div class="ad-container ad-incontent-2">' . $code_2 . '</div>';
            $new_segs   = array();
            $para_count = 0;
            foreach ( $segments as $j => $seg ) {
                $new_segs[] = $seg;
                // Only count segments that look like paragraph closings (non-empty content)
                if ( trim( $seg ) !== '' && strpos( $seg, '<!-- ad1-injected -->' ) === false ) {
                    $para_count++;
                }
                if ( $para_count === 4 && $j < $seg_total - 1 ) {
                    $new_segs[] = $ad_block_2 . '<!-- ad2-injected -->';
                    $para_count = PHP_INT_MAX; // prevent re-injection
                }
            }
            $parts = $new_segs;
        } else {
            $parts = $segments;
        }
    }

    if ( $has_ad3 ) {
        $rebuilt_3  = implode( '</p>', $parts );
        $segments_3 = explode( '</p>', $rebuilt_3 );
        $seg_total_3 = count( $segments_3 );

        if ( $seg_total_3 > 7 ) {
            $ad_block_3 = '<div class="ad-container ad-incontent-3">' . $code_3 . '</div>';
            $new_segs_3 = array();
            $para_count_3 = 0;
            foreach ( $segments_3 as $k => $seg_3 ) {
                $new_segs_3[] = $seg_3;
                if ( trim( $seg_3 ) !== '' && strpos( $seg_3, '<!-- ad1-injected -->' ) === false && strpos( $seg_3, '<!-- ad2-injected -->' ) === false ) {
                    $para_count_3++;
                }
                if ( $para_count_3 === 7 && $k < $seg_total_3 - 1 ) {
                    $new_segs_3[] = $ad_block_3 . '<!-- ad3-injected -->';
                    $para_count_3 = PHP_INT_MAX;
                }
            }
            $parts = $new_segs_3;
        }
    }

    $content = implode( '</p>', $parts );

    // Clean up internal markers
    $content = str_replace( '<!-- ad1-injected -->', '', $content );
    $content = str_replace( '<!-- ad2-injected -->', '', $content );
    $content = str_replace( '<!-- ad3-injected -->', '', $content );

    return $content;
}
add_filter( 'the_content', 'holyprofweb_inject_incontent_ad' );

/**
 * Output footer banner when the before_footer action fires.
 */
function holyprofweb_output_footer_banner() {
    holyprofweb_render_ad( 'footer', 'ad-footer-banner' );
    holyprofweb_render_ad_format( 'social', 'social_bar', 'ad-social-bar' );
    if ( wp_is_mobile() ) {
        holyprofweb_render_ad_format( 'mobile', 'mobile_sticky', 'ad-mobile-sticky' );
    }
}
add_action( 'holyprofweb_before_footer', 'holyprofweb_output_footer_banner' );


// =========================================
// SEARCH INTELLIGENCE
// =========================================

/**
 * Apply date filter to search queries.
 * Reads ?filter=today|week from the URL.
 */
function holyprofweb_search_date_filter( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $filter = isset( $_GET['filter'] ) ? sanitize_key( $_GET['filter'] ) : '';

    switch ( $filter ) {
        case 'today':
            $query->set( 'date_query', array( array(
                'after'     => '1 day ago',
                'inclusive' => true,
            ) ) );
            break;

        case 'week':
            $query->set( 'date_query', array( array(
                'after'     => '1 week ago',
                'inclusive' => true,
            ) ) );
            break;
    }
}
add_action( 'pre_get_posts', 'holyprofweb_search_date_filter' );

// ── Admin post list — extra filter dropdowns ──────────────────────────────────

add_action( 'restrict_manage_posts', function ( $post_type ) {
    if ( 'post' !== $post_type ) return;

    // Image type filter.
    $cur_type = isset( $_GET['hpw_admin_img_type'] ) ? sanitize_key( $_GET['hpw_admin_img_type'] ) : '';
    $types = array(
        ''          => 'Any image type',
        'none'      => 'No image',
        'featured'  => 'Has featured image',
        'remote'    => 'Remote / OG image',
        'gd'        => 'Generated — GD',
        'svg'       => 'Generated — SVG',
        'generated' => 'Generated (any)',
        'external'  => 'Manual image link',
    );
    echo '<select name="hpw_admin_img_type">';
    foreach ( $types as $val => $label ) {
        echo '<option value="' . esc_attr( $val ) . '"' . selected( $cur_type, $val, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select> ';

    // Country filter.
    global $wpdb;
    $countries = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_hpw_country_focus' AND meta_value != '' ORDER BY meta_value ASC LIMIT 80"
    );
    if ( $countries ) {
        $cur_country = isset( $_GET['hpw_admin_country'] ) ? sanitize_text_field( wp_unslash( $_GET['hpw_admin_country'] ) ) : '';
        echo '<select name="hpw_admin_country" style="margin-left:4px;">';
        echo '<option value="">Any country</option>';
        foreach ( $countries as $c ) {
            echo '<option value="' . esc_attr( $c ) . '"' . selected( $cur_country, $c, false ) . '>' . esc_html( $c ) . '</option>';
        }
        echo '</select> ';
    }
} );

add_action( 'pre_get_posts', function ( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    if ( 'post' !== $query->get( 'post_type' ) && get_current_screen() && 'edit-post' !== get_current_screen()->id ) return;
    if ( ! function_exists( 'get_current_screen' ) ) return;
    $screen = get_current_screen();
    if ( ! $screen || 'edit-post' !== $screen->id ) return;

    $meta_q = (array) ( $query->get( 'meta_query' ) ?: array() );

    // Image type filter.
    $img_type = isset( $_GET['hpw_admin_img_type'] ) ? sanitize_key( wp_unslash( $_GET['hpw_admin_img_type'] ) ) : '';
    switch ( $img_type ) {
        case 'none':
            $meta_q[] = array( 'key' => '_holyprofweb_gen_image_url',    'compare' => 'NOT EXISTS' );
            $meta_q[] = array( 'key' => '_holyprofweb_remote_image_url', 'compare' => 'NOT EXISTS' );
            $meta_q[] = array( 'key' => 'external_image',                'compare' => 'NOT EXISTS' );
            $meta_q[] = array( 'key' => '_thumbnail_id',                 'compare' => 'NOT EXISTS' );
            $meta_q['relation'] = 'AND';
            break;
        case 'featured':
            $meta_q[] = array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' );
            break;
        case 'remote':
            $meta_q[] = array( 'key' => '_holyprofweb_remote_image_url', 'compare' => 'EXISTS' );
            break;
        case 'generated':
            $meta_q[] = array( 'key' => '_holyprofweb_gen_image_url', 'compare' => 'EXISTS' );
            break;
        case 'external':
            $meta_q[] = array( 'key' => 'external_image', 'compare' => 'EXISTS' );
            break;
        // gd/svg: applied post-fetch — too complex for meta_query alone.
    }

    // Country filter.
    $country = isset( $_GET['hpw_admin_country'] ) ? sanitize_text_field( wp_unslash( $_GET['hpw_admin_country'] ) ) : '';
    if ( $country ) {
        $meta_q[] = array( 'key' => '_hpw_country_focus', 'value' => $country, 'compare' => '=' );
    }

    if ( count( $meta_q ) > ( isset( $meta_q['relation'] ) ? 1 : 0 ) ) {
        if ( ! isset( $meta_q['relation'] ) ) {
            $meta_q['relation'] = 'AND';
        }
        $query->set( 'meta_query', $meta_q ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    }
} );

/**
 * Track search queries in wp_options.
 * Fires after main query is run but before template loads.
 */
function holyprofweb_get_search_log_limit() {
    return max( 100, absint( get_option( 'hpw_search_log_limit', 250 ) ) );
}

function holyprofweb_get_search_alert_threshold() {
    return max( 2, absint( get_option( 'hpw_search_alert_threshold', 2 ) ) );
}

function holyprofweb_normalize_search_term( $term ) {
    $term = html_entity_decode( (string) $term, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
    $term = wp_strip_all_tags( $term );
    $term = preg_replace( '/\bencodeURIComponent\s*\([^)]*\)/i', ' ', $term );
    $term = preg_replace( '/\blabel\s*\/\s*/i', '', $term );
    $term = preg_replace( '/[`\'"\{\}\[\];]+/', ' ', $term );
    $term = preg_replace( '/\s+/', ' ', $term );
    $term = trim( sanitize_text_field( $term ) );

    if ( '' === $term ) {
        return '';
    }

    if ( false !== stripos( $term, 'encodeURIComponent' ) ) {
        return '';
    }

    if ( preg_match( '/^(item|term|query|label|undefined|null|function|return)$/i', $term ) ) {
        return '';
    }

    if ( strlen( $term ) < 4 ) {
        return '';
    }

    if ( ! preg_match( '/[a-z0-9]/i', $term ) ) {
        return '';
    }

    if ( holyprofweb_is_noise_search_term( $term ) ) {
        return '';
    }

    return $term;
}

function holyprofweb_is_noise_search_term( $term ) {
    $term = strtolower( trim( (string) $term ) );
    if ( '' === $term ) {
        return true;
    }

    // Ignore path-like or campaign-token searches that are usually bot/ad junk.
    if ( preg_match( '#^(?:/|https?://|www\.)#', $term ) ) {
        return true;
    }

    if ( preg_match( '/(?:^|[\s\/_-])(sid|pid|cid|utm|gclid|fbclid)(?:[\s\/_-]|$)/i', $term ) ) {
        return true;
    }

    if ( preg_match( '/(?:^|\/)vod-[a-z0-9-]+$/i', $term ) ) {
        return true;
    }

    if ( preg_match( '/(?:^|[\s\/_-])[a-z]{2,8}-\d{2,}(?:-[a-z0-9]{1,12}){2,}(?:[\s\/_-]|$)/i', $term ) ) {
        return true;
    }

    return false;
}

function holyprofweb_is_search_request_from_bot() {
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    if ( '' === $ua ) {
        return false;
    }

    return (bool) preg_match( '/bot|crawl|spider|slurp|crawler|preview|headless|python|curl|wget|scrapy|axios|httpclient|go-http-client|node-fetch|phantom/i', $ua );
}

function holyprofweb_get_search_log() {
    $log = get_option( 'holyprofweb_search_log', array() );
    if ( ! is_array( $log ) ) {
        return array();
    }

    $log_changed = false;

    foreach ( $log as $key => $entry ) {
        if ( ! is_array( $entry ) ) {
            unset( $log[ $key ] );
            $log_changed = true;
            continue;
        }

        $term = isset( $entry['term'] ) ? holyprofweb_normalize_search_term( $entry['term'] ) : '';
        if ( '' === $term ) {
            unset( $log[ $key ] );
            $log_changed = true;
            continue;
        }

        $log[ $key ] = wp_parse_args(
            $entry,
            array(
                'term'            => $term,
                'count'           => 0,
                'ts'              => 0,
                'first_ts'        => 0,
                'last_results'    => 0,
                'peak_results'    => 0,
                'last_country'    => '',
                'countries'       => array(),
                'last_referrer'   => '',
                'referrers'       => array(),
                'auto_draft_id'   => 0,
                'draft_created_at'=> 0,
            )
        );

        if ( $log[ $key ]['term'] !== $term ) {
            $log[ $key ]['term'] = $term;
            $log_changed = true;
        }
    }

    uasort(
        $log,
        static function( $a, $b ) {
            if ( (int) $a['count'] === (int) $b['count'] ) {
                return (int) $b['ts'] <=> (int) $a['ts'];
            }
            return (int) $b['count'] <=> (int) $a['count'];
        }
    );

    if ( $log_changed ) {
        update_option( 'holyprofweb_search_log', $log, false );
    }

    return $log;
}

function holyprofweb_get_search_alert_rows() {
    $threshold = holyprofweb_get_search_alert_threshold();
    $rows      = array();

    foreach ( holyprofweb_get_search_log() as $key => $entry ) {
        if ( (int) $entry['count'] >= $threshold ) {
            $rows[ $key ] = $entry;
        }
    }

    return $rows;
}

function holyprofweb_find_draft_by_title( $title ) {
    global $wpdb;

    $title = sanitize_text_field( $title );
    if ( '' === $title ) {
        return 0;
    }

    $post_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = 'post'
               AND post_status IN ('draft','publish','pending')
               AND post_title = %s
             ORDER BY FIELD(post_status,'publish','pending','draft'), ID DESC
             LIMIT 1",
            $title
        )
    );

    return $post_id ? (int) $post_id : 0;
}

function holyprofweb_track_search() {
    if ( ! is_search() || is_admin() ) {
        return;
    }

    if ( holyprofweb_is_search_request_from_bot() ) {
        return;
    }

    $term = holyprofweb_normalize_search_term( get_search_query() );
    if ( empty( trim( $term ) ) ) {
        return;
    }

    if ( ! empty( $_COOKIE['hpw_search_tracked'] ) ) {
        return;
    }

    $log = holyprofweb_get_search_log();
    $key = md5( strtolower( trim( $term ) ) );
    $locale = holyprofweb_detect_visitor_locale();
    $country = ! empty( $locale['country_name'] ) ? sanitize_text_field( $locale['country_name'] ) : '';
    $referrer_host = '';
    if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        $referrer_host = (string) wp_parse_url( wp_unslash( $_SERVER['HTTP_REFERER'] ), PHP_URL_HOST );
        $referrer_host = sanitize_text_field( preg_replace( '/^www\./i', '', $referrer_host ) );
    }
    $results_count = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;

    if ( isset( $log[ $key ] ) ) {
        $log[ $key ]['count']++;
        $log[ $key ]['ts'] = time();
    } else {
        $log[ $key ] = array(
            'term'             => $term,
            'count'            => 1,
            'ts'               => time(),
            'first_ts'         => time(),
            'last_results'     => 0,
            'peak_results'     => 0,
            'last_country'     => '',
            'countries'        => array(),
            'last_referrer'    => '',
            'referrers'        => array(),
            'auto_draft_id'    => 0,
            'draft_created_at' => 0,
        );
    }

    $log[ $key ]['last_results'] = $results_count;
    $log[ $key ]['peak_results'] = max( (int) $log[ $key ]['peak_results'], $results_count );

    if ( $country ) {
        $log[ $key ]['last_country'] = $country;
        if ( empty( $log[ $key ]['countries'][ $country ] ) ) {
            $log[ $key ]['countries'][ $country ] = 0;
        }
        $log[ $key ]['countries'][ $country ]++;
        arsort( $log[ $key ]['countries'] );
        $log[ $key ]['countries'] = array_slice( $log[ $key ]['countries'], 0, 8, true );
    }

    if ( $referrer_host ) {
        $log[ $key ]['last_referrer'] = $referrer_host;
        if ( empty( $log[ $key ]['referrers'][ $referrer_host ] ) ) {
            $log[ $key ]['referrers'][ $referrer_host ] = 0;
        }
        $log[ $key ]['referrers'][ $referrer_host ]++;
        arsort( $log[ $key ]['referrers'] );
        $log[ $key ]['referrers'] = array_slice( $log[ $key ]['referrers'], 0, 8, true );
    }

    if ( empty( $log[ $key ]['auto_draft_id'] ) && ! empty( $log[ $key ]['term'] ) ) {
        $log[ $key ]['auto_draft_id'] = holyprofweb_find_draft_by_title( $log[ $key ]['term'] );
    }

    // Keep the log to 200 entries, sorted by frequency
    uasort( $log, function( $a, $b ) { return $b['count'] - $a['count']; } );
    $log = array_slice( $log, 0, holyprofweb_get_search_log_limit(), true );

    update_option( 'holyprofweb_search_log', $log, false );

    if ( ! headers_sent() ) {
        setcookie( 'hpw_search_tracked', '1', time() + HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
    }
}
add_action( 'wp', 'holyprofweb_track_search' );

/**
 * Get top searched terms.
 * Used in the trending section.
 *
 * @param int $count Number of terms to return.
 * @return array
 */
function holyprofweb_get_trending_searches( $count = 5 ) {
    $log = holyprofweb_get_search_log();
    $items = array();

    foreach ( $log as $entry ) {
        $term = is_array( $entry ) && ! empty( $entry['term'] ) ? holyprofweb_normalize_search_term( $entry['term'] ) : '';
        if ( '' === $term ) {
            continue;
        }

        $items[] = array( 'term' => $term );

        if ( count( $items ) >= $count ) {
            break;
        }
    }

    return $items;
}

function holyprofweb_get_country_name_from_code( $country_code ) {
    $country_code = strtoupper( preg_replace( '/[^A-Z]/', '', (string) $country_code ) );
    if ( 2 !== strlen( $country_code ) ) {
        return '';
    }

    if ( class_exists( 'Locale' ) && method_exists( 'Locale', 'getDisplayRegion' ) ) {
        $name = \Locale::getDisplayRegion( '-' . $country_code, 'en' );
        if ( is_string( $name ) && '' !== trim( $name ) && $name !== $country_code ) {
            return $name;
        }
    }

    $fallback = array(
        'CA' => 'Canada',
        'CN' => 'China',
        'DE' => 'Germany',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'GH' => 'Ghana',
        'KE' => 'Kenya',
        'NG' => 'Nigeria',
        'US' => 'United States',
        'ZA' => 'South Africa',
    );

    return isset( $fallback[ $country_code ] ) ? $fallback[ $country_code ] : $country_code;
}

function holyprofweb_get_public_visitor_ip() {
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

        $raw = (string) wp_unslash( $_SERVER[ $header ] );
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

function holyprofweb_lookup_geo_from_ip( $ip_address ) {
    $ip_address = trim( (string) $ip_address );
    if ( ! $ip_address || ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
        return array();
    }

    $cache_key = 'hpw_geoip_' . md5( $ip_address );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $result = array();

    if ( function_exists( 'geoip_country_code_by_name' ) ) {
        $code = geoip_country_code_by_name( $ip_address );
        if ( $code ) {
            $result = array(
                'region'  => strtoupper( $code ),
                'country' => holyprofweb_get_country_name_from_code( $code ),
                'source'  => 'geoip_extension',
            );
        }
    }

    if ( empty( $result ) ) {
        $providers = array(
            'https://ipapi.co/%s/json/',
            'https://ipwho.is/%s',
        );

        foreach ( $providers as $provider ) {
            $response = wp_safe_remote_get(
                sprintf( $provider, rawurlencode( $ip_address ) ),
                array(
                    'timeout'    => 2.5,
                    'user-agent' => 'HolyprofWeb/1.0; geolocation fallback',
                    'sslverify'  => true,
                )
            );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! is_array( $body ) ) {
                continue;
            }

            $region = '';
            $country = '';

            if ( ! empty( $body['country_code'] ) ) {
                $region = strtoupper( sanitize_text_field( (string) $body['country_code'] ) );
            } elseif ( ! empty( $body['country'] ) && preg_match( '/^[A-Z]{2}$/i', (string) $body['country'] ) ) {
                $region = strtoupper( sanitize_text_field( (string) $body['country'] ) );
            }

            if ( ! empty( $body['country_name'] ) ) {
                $country = sanitize_text_field( (string) $body['country_name'] );
            } elseif ( ! empty( $body['country'] ) && ! preg_match( '/^[A-Z]{2}$/i', (string) $body['country'] ) ) {
                $country = sanitize_text_field( (string) $body['country'] );
            }

            if ( $region || $country ) {
                $result = array(
                    'region'  => $region,
                    'country' => $country ? $country : holyprofweb_get_country_name_from_code( $region ),
                    'source'  => 'ip_lookup',
                );
                break;
            }
        }
    }

    set_transient( $cache_key, $result, DAY_IN_SECONDS );
    return $result;
}

function holyprofweb_detect_visitor_locale() {
    $geo_headers = array(
        'HTTP_CF_IPCOUNTRY',
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
        'HTTP_X_VERCEL_IP_COUNTRY',
        'HTTP_FASTLY_COUNTRY_CODE',
        'HTTP_X_AKAMAI_EDGESCAPE',
        'HTTP_X_COUNTRY_CODE',
        'HTTP_X_GEO_COUNTRY',
        'HTTP_X_APPENGINE_COUNTRY',
        'HTTP_X_COUNTRY',
    );

    $region = '';
    $mode = get_option( 'hpw_country_mode', 'headers' );
    if ( 'headers' === $mode ) {
        foreach ( $geo_headers as $header_key ) {
            if ( empty( $_SERVER[ $header_key ] ) ) {
                continue;
            }

            $raw_value = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header_key ] ) ) );
            $candidate = $raw_value;

            if ( 'HTTP_X_AKAMAI_EDGESCAPE' === $header_key && preg_match( '/country_code=([A-Z]{2})/', $raw_value, $matches ) ) {
                $candidate = $matches[1];
            }

            if ( preg_match( '/^[A-Z]{2}$/', $candidate ) ) {
                $region = $candidate;
                break;
            }
        }
    }

    $header = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? strtolower( (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) : '';
    $language = 'en';
    $source   = 'unknown';
    if ( $header ) {
        $primary = trim( explode( ',', $header )[0] );
        $parts   = preg_split( '/[-_]/', $primary );
        $language = ! empty( $parts[0] ) ? strtolower( $parts[0] ) : 'en';

        if ( 'language' === $mode && ! $region && ! empty( $parts[1] ) ) {
            $region = strtoupper( $parts[1] );
        }
    }

    if ( $region ) {
        $source = 'headers' === $mode ? 'geo_header' : 'accept_language';
    } elseif ( 'language' === $mode && $header ) {
        $source = 'accept_language';
    }

    $country = $region ? holyprofweb_get_country_name_from_code( $region ) : '';

    if ( ! $region && 'manual' !== $mode ) {
        $ip_geo = holyprofweb_lookup_geo_from_ip( holyprofweb_get_public_visitor_ip() );
        if ( ! empty( $ip_geo['region'] ) ) {
            $region  = strtoupper( sanitize_text_field( $ip_geo['region'] ) );
            $country = ! empty( $ip_geo['country'] ) ? sanitize_text_field( $ip_geo['country'] ) : holyprofweb_get_country_name_from_code( $region );
            $source  = ! empty( $ip_geo['source'] ) ? $ip_geo['source'] : 'ip_lookup';
        }
    }

    if ( ! $country && $region ) {
        $country = holyprofweb_get_country_name_from_code( $region );
    }

    if ( ! $country ) {
        $country = 'Unknown';
    }

    return array(
        'language' => $language,
        'country'  => $country,
        'region'   => $region ?: 'XX',
        'source'   => $source,
    );
}

function holyprofweb_get_country_content_map() {
    $defaults = array(
        'US' => array(
            'label'      => 'United States',
            'language'   => 'en',
            'currency'   => 'USD',
            'focus'      => 'United States',
            'hook'       => 'Explain what this means for users in the United States, including pricing, regulations, and expectations.',
            'topics'     => 'US apps, salaries, websites, startups, and public figures',
            'continents' => array( 'North America' ),
        ),
        'FR' => array(
            'label'      => 'France',
            'language'   => 'fr',
            'currency'   => 'EUR',
            'focus'      => 'France',
            'hook'       => 'Add a France angle with consumer trust, fees, and what French readers should verify first.',
            'topics'     => 'French companies, websites, jobs, and policy-facing biographies',
            'continents' => array( 'Europe' ),
        ),
        'DE' => array(
            'label'      => 'Germany',
            'language'   => 'de',
            'currency'   => 'EUR',
            'focus'      => 'Germany',
            'hook'       => 'Add a Germany angle with reliability, market context, and what German readers care about.',
            'topics'     => 'German companies, salaries, platforms, and political biographies',
            'continents' => array( 'Europe' ),
        ),
        'NG' => array(
            'label'      => 'Nigeria',
            'language'   => 'en',
            'currency'   => 'NGN',
            'focus'      => 'Nigeria',
            'hook'       => 'Add a Nigeria angle with local payment realities, trust concerns, and what users should watch for.',
            'topics'     => 'Nigerian reviews, salaries, finance apps, and company profiles',
            'continents' => array( 'Africa' ),
        ),
    );

    return apply_filters( 'holyprofweb_country_content_map', $defaults );
}

function holyprofweb_get_active_country_context( $post_id = 0 ) {
    $locale      = holyprofweb_detect_visitor_locale();
    $country_map = holyprofweb_get_country_content_map();
    $region      = ! empty( $locale['region'] ) ? $locale['region'] : 'XX';

    $context = isset( $country_map[ $region ] ) ? $country_map[ $region ] : array(
        'label'      => $locale['country'],
        'language'   => $locale['language'],
        'currency'   => '',
        'focus'      => $locale['country'],
        'hook'       => sprintf( 'Add a %s angle with local market expectations, user trust signals, pricing context, and what readers in %s should verify first.', $locale['country'], $locale['country'] ),
        'topics'     => sprintf( '%s reviews, companies, salaries, biographies, and report-driven topics', $locale['country'] ),
        'continents' => array(),
    );

    if ( $post_id ) {
        $post_focus = trim( (string) get_post_meta( $post_id, '_hpw_country_focus', true ) );
        if ( $post_focus ) {
            $context['post_focus'] = $post_focus;
        }
    }

    $context['region']  = $region;
    $context['country'] = $locale['country'];
    $context['source']  = $locale['source'];

    return $context;
}

function holyprofweb_get_country_priority_values() {
    $locale = holyprofweb_detect_visitor_locale();
    $region = ! empty( $locale['region'] ) ? strtoupper( (string) $locale['region'] ) : '';
    $country = ! empty( $locale['country'] ) ? trim( (string) $locale['country'] ) : '';
    $values = array();

    if ( $country ) {
        $values[] = $country;
    }

    if ( 'US' === $region ) {
        $values = array_merge( $values, array( 'United States', 'USA', 'US', 'America' ) );
    } elseif ( 'NG' === $region ) {
        $values = array_merge( $values, array( 'Nigeria', 'NG' ) );
    } elseif ( 'GB' === $region ) {
        $values = array_merge( $values, array( 'United Kingdom', 'UK', 'Britain', 'England', 'GB' ) );
    } elseif ( $region ) {
        $values[] = $region;
    }

    $values = array_values(
        array_unique(
            array_filter(
                array_map( 'trim', $values )
            )
        )
    );

    return $values;
}

function holyprofweb_get_localized_post_ids( $base_args = array(), $limit = 6 ) {
    $limit = max( 1, (int) $limit );
    $base_args = wp_parse_args(
        $base_args,
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        )
    );
    $base_args['posts_per_page'] = $limit;
    $base_args['fields'] = 'ids';

    $priority_values = holyprofweb_get_country_priority_values();
    if ( empty( $priority_values ) ) {
        return get_posts( $base_args );
    }

    $ordered_ids = array();

    foreach ( $priority_values as $value ) {
        if ( count( $ordered_ids ) >= $limit ) {
            break;
        }

        $match_args = $base_args;
        $match_args['posts_per_page'] = $limit - count( $ordered_ids );
        $match_args['meta_query'] = array(
            array(
                'key'     => '_hpw_country_focus',
                'value'   => $value,
                'compare' => 'LIKE',
            ),
        );
        if ( ! empty( $ordered_ids ) ) {
            $match_args['post__not_in'] = $ordered_ids;
        }

        $ordered_ids = array_merge( $ordered_ids, get_posts( $match_args ) );
    }

    if ( count( $ordered_ids ) < $limit ) {
        $fallback_args = $base_args;
        $fallback_args['posts_per_page'] = $limit - count( $ordered_ids );
        if ( ! empty( $ordered_ids ) ) {
            $fallback_args['post__not_in'] = $ordered_ids;
        }
        $ordered_ids = array_merge( $ordered_ids, get_posts( $fallback_args ) );
    }

    return array_values( array_unique( array_map( 'intval', $ordered_ids ) ) );
}

function holyprofweb_get_geo_header_debug() {
    $headers = array();
    foreach ( array( 'HTTP_CF_IPCOUNTRY', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_GEO_COUNTRY', 'HTTP_X_APPENGINE_COUNTRY' ) as $key ) {
        if ( empty( $_SERVER[ $key ] ) ) {
            continue;
        }
        $headers[ $key ] = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
    }
    return $headers;
}

function holyprofweb_track_visit_context() {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return;
    }

    if ( holyprofweb_is_search_request_from_bot() ) {
        return;
    }

    if ( ! empty( $_COOKIE['hpw_visit_context_tracked'] ) ) {
        return;
    }

    $stats  = get_option( 'holyprofweb_visit_stats', array(
        'countries' => array(),
        'languages' => array(),
        'referrers' => array(),
        'pages'     => array(),
        'sources'   => array(),
    ) );
    $locale = holyprofweb_detect_visitor_locale();
    $page   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
    $ref    = isset( $_SERVER['HTTP_REFERER'] ) ? wp_parse_url( wp_unslash( $_SERVER['HTTP_REFERER'] ), PHP_URL_HOST ) : '';

    foreach ( array( 'countries' => $locale['country'], 'languages' => $locale['language'], 'pages' => $page, 'sources' => $locale['source'] ) as $bucket => $key ) {
        if ( ! $key ) {
            continue;
        }
        if ( ! isset( $stats[ $bucket ][ $key ] ) ) {
            $stats[ $bucket ][ $key ] = 0;
        }
        $stats[ $bucket ][ $key ]++;
        arsort( $stats[ $bucket ] );
        $stats[ $bucket ] = array_slice( $stats[ $bucket ], 0, 50, true );
    }

    if ( $ref ) {
        if ( ! isset( $stats['referrers'][ $ref ] ) ) {
            $stats['referrers'][ $ref ] = 0;
        }
        $stats['referrers'][ $ref ]++;
        arsort( $stats['referrers'] );
        $stats['referrers'] = array_slice( $stats['referrers'], 0, 30, true );
    }

    update_option( 'holyprofweb_visit_stats', $stats, false );

    if ( ! headers_sent() ) {
        setcookie( 'hpw_visit_context_tracked', '1', time() + HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
    }
}
add_action( 'wp', 'holyprofweb_track_visit_context' );

function holyprofweb_get_post_view_stats( $post_id = 0 ) {
    $stats = get_post_meta( $post_id, '_hpw_view_stats', true );
    return is_array( $stats ) ? $stats : array(
        'total'     => 0,
        'countries' => array(),
    );
}

function holyprofweb_track_post_views() {
    if ( is_admin() || wp_doing_ajax() || ! is_singular( 'post' ) ) {
        return;
    }

    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
        return;
    }

    $cookie_key = 'hpw_viewed_' . $post_id;
    if ( ! empty( $_COOKIE[ $cookie_key ] ) ) {
        return;
    }

    $stats   = holyprofweb_get_post_view_stats( $post_id );
    $locale  = holyprofweb_detect_visitor_locale();
    $country = ! empty( $locale['country'] ) ? $locale['country'] : 'Unknown';

    $stats['total']++;
    if ( ! isset( $stats['countries'][ $country ] ) ) {
        $stats['countries'][ $country ] = 0;
    }
    $stats['countries'][ $country ]++;
    arsort( $stats['countries'] );

    update_post_meta( $post_id, '_hpw_view_stats', $stats );

    if ( ! headers_sent() ) {
        setcookie( $cookie_key, '1', time() + ( 12 * HOUR_IN_SECONDS ), COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
    }
}
add_action( 'template_redirect', 'holyprofweb_track_post_views', 5 );

function holyprofweb_get_most_viewed_posts( $limit = 10, $country = '' ) {
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => min( 100, max( 1, $limit * 5 ) ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    $ranked = array();
    foreach ( $posts as $post_id ) {
        $stats = holyprofweb_get_post_view_stats( $post_id );
        $count = $country && ! empty( $stats['countries'][ $country ] )
            ? (int) $stats['countries'][ $country ]
            : (int) $stats['total'];

        if ( $count < 1 ) {
            continue;
        }

        $ranked[ $post_id ] = $count;
    }

    arsort( $ranked );
    return array_slice( $ranked, 0, $limit, true );
}


// =========================================
// THEME ACTIVATION — AUTO SETUP
// =========================================

function holyprofweb_on_activate() {
    holyprofweb_enforce_permalink_structure();
    holyprofweb_create_categories();
    holyprofweb_cleanup_uncategorized_category();
    holyprofweb_ensure_placeholder_posts();
    holyprofweb_create_menus();
    holyprofweb_cleanup_defaults();
    holyprofweb_create_submit_page();
    holyprofweb_create_static_pages();

    if ( ! get_option( 'holyprofweb_posts_created' ) ) {
        holyprofweb_create_sample_posts();
        update_option( 'holyprofweb_posts_created', true );
    }

    holyprofweb_schedule_content_audit();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'holyprofweb_on_activate' );
add_action( 'init', 'holyprofweb_schedule_content_audit', 40 );

add_filter( 'cron_schedules', function( $schedules ) {
    if ( empty( $schedules['every_five_minutes'] ) ) {
        $schedules['every_five_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 5 Minutes', 'holyprofweb' ),
        );
    }
    if ( empty( $schedules['every_fifteen_minutes'] ) ) {
        $schedules['every_fifteen_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 15 Minutes', 'holyprofweb' ),
        );
    }
    return $schedules;
} );

function holyprofweb_get_draft_audit_schedule() {
    $allowed = array( 'every_fifteen_minutes', 'hourly', 'twicedaily', 'daily' );
    $saved   = sanitize_key( (string) get_option( 'hpw_draft_audit_schedule', 'every_fifteen_minutes' ) );

    return in_array( $saved, $allowed, true ) ? $saved : 'every_fifteen_minutes';
}

function holyprofweb_schedule_content_audit() {
    $draft_schedule = holyprofweb_get_draft_audit_schedule();
    $draft_event    = function_exists( 'wp_get_scheduled_event' ) ? wp_get_scheduled_event( 'holyprofweb_draft_publish_audit' ) : false;

    if ( ! wp_next_scheduled( 'holyprofweb_daily_content_audit' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'holyprofweb_daily_content_audit' );
    }

    if ( $draft_event && ! empty( $draft_event->schedule ) && $draft_event->schedule !== $draft_schedule ) {
        wp_unschedule_event( $draft_event->timestamp, 'holyprofweb_draft_publish_audit' );
        $draft_event = false;
    }

    if ( ! $draft_event && ! wp_next_scheduled( 'holyprofweb_draft_publish_audit' ) ) {
        wp_schedule_event( time() + ( 15 * MINUTE_IN_SECONDS ), $draft_schedule, 'holyprofweb_draft_publish_audit' );
    }
}

function holyprofweb_mark_wp_cron_run() {
    if ( wp_doing_cron() ) {
        update_option( 'holyprofweb_wp_cron_last_run', time(), false );
    }
}
add_action( 'init', 'holyprofweb_mark_wp_cron_run', 1 );

function holyprofweb_unschedule_content_audit() {
    $timestamp = wp_next_scheduled( 'holyprofweb_daily_content_audit' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'holyprofweb_daily_content_audit' );
    }

    $draft_timestamp = wp_next_scheduled( 'holyprofweb_draft_publish_audit' );
    if ( $draft_timestamp ) {
        wp_unschedule_event( $draft_timestamp, 'holyprofweb_draft_publish_audit' );
    }
}

function holyprofweb_run_content_audit() {
    $days_limit     = max( 3, absint( get_option( 'hpw_refresh_days', 21 ) ) );
    $minimum_words  = max( 1000, absint( get_option( 'hpw_ai_minimum_words', 1000 ) ) );
    $queue_limit    = max( 5, absint( get_option( 'hpw_refresh_queue_limit', 25 ) ) );
    $stale_before   = gmdate( 'Y-m-d H:i:s', time() - ( $days_limit * DAY_IN_SECONDS ) );
    $candidate_ids  = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $queue_limit,
        'orderby'        => 'modified',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'date_query'     => array(
            array(
                'column' => 'post_modified_gmt',
                'before' => $stale_before,
            ),
        ),
        'no_found_rows'  => true,
    ) );
    $queue          = array();

    foreach ( $candidate_ids as $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            continue;
        }

        $plain = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $post->post_content ) ) );
        $words = str_word_count( wp_strip_all_tags( $plain ) );
        $needs = array();

        if ( $words < $minimum_words ) {
            $needs[] = 'content_depth';
        }
        if ( ! has_post_thumbnail( $post_id ) && ! get_post_meta( $post_id, 'external_image', true ) ) {
            $needs[] = 'image';
        }
        if ( holyprofweb_post_uses_generated_image_fallback( $post_id ) ) {
            $needs[] = 'image_upgrade';
            holyprofweb_upgrade_generated_featured_image( $post_id, $post );
        }
        if ( ! get_post_meta( $post_id, '_hpw_source_url', true ) ) {
            $needs[] = 'source';
        }

        if ( empty( $needs ) ) {
            continue;
        }

        $queue[ $post_id ] = array(
            'title'         => get_the_title( $post_id ),
            'modified_gmt'  => $post->post_modified_gmt,
            'word_count'    => $words,
            'needs'         => $needs,
            'country_focus' => get_post_meta( $post_id, '_hpw_country_focus', true ),
        );
    }

    update_option( 'holyprofweb_content_refresh_queue', $queue, false );
    update_option( 'holyprofweb_content_audit_last_run', time(), false );
}
add_action( 'holyprofweb_daily_content_audit', 'holyprofweb_run_content_audit' );

function holyprofweb_get_content_refresh_queue() {
    $queue = get_option( 'holyprofweb_content_refresh_queue', array() );
    return is_array( $queue ) ? $queue : array();
}

function holyprofweb_get_draft_publish_queue() {
    $queue = get_option( 'holyprofweb_draft_publish_queue', array() );
    return is_array( $queue ) ? $queue : array();
}

function holyprofweb_get_draft_force_publish_attempts() {
    return 4;
}

function holyprofweb_get_draft_force_publish_window() {
    return 10 * MINUTE_IN_SECONDS;
}

function holyprofweb_is_draft_recently_modified( $post, $minutes = 30 ) {
    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    $minutes = max( 1, (int) $minutes );
    $modified_gmt = $post->post_modified_gmt ? strtotime( $post->post_modified_gmt . ' GMT' ) : 0;
    if ( ! $modified_gmt ) {
        return false;
    }

    $now_gmt = current_time( 'timestamp', true );
    return ( $now_gmt - $modified_gmt ) < ( $minutes * MINUTE_IN_SECONDS );
}

function holyprofweb_should_force_publish_draft( $post, $queue_item = array() ) {
    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    // Never force-publish while a human is still editing recently.
    if ( holyprofweb_is_draft_recently_modified( $post, 30 ) ) {
        return false;
    }

    $attempts = isset( $queue_item['attempts'] ) ? (int) $queue_item['attempts'] : 0;
    if ( $attempts >= holyprofweb_get_draft_force_publish_attempts() ) {
        return true;
    }

    $first_seen = isset( $queue_item['first_seen'] ) ? absint( $queue_item['first_seen'] ) : 0;
    if ( ! $first_seen ) {
        $first_seen = strtotime( (string) $post->post_date_gmt . ' GMT' );
    }

    return $first_seen > 0 && ( time() - $first_seen ) >= holyprofweb_get_draft_force_publish_window();
}

function holyprofweb_publish_post_now( $post_id ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 ) {
        return;
    }

    remove_action( 'save_post_post', 'holyprofweb_maybe_publish_ready_draft_on_save', 40 );
    wp_update_post( array(
        'ID'          => $post_id,
        'post_status' => 'publish',
    ) );
    add_action( 'save_post_post', 'holyprofweb_maybe_publish_ready_draft_on_save', 40, 2 );
}

function holyprofweb_content_looks_repetitive( $content ) {
    $paragraphs = array_values( array_filter( array_map( 'trim', preg_split( '/\n\s*\n/', wp_strip_all_tags( (string) $content ) ) ) ) );
    if ( count( $paragraphs ) < 4 ) {
        return false;
    }

    $normalized = array_map( function( $paragraph ) {
        return strtolower( preg_replace( '/\s+/', ' ', $paragraph ) );
    }, $paragraphs );

    $unique_ratio = count( array_unique( $normalized ) ) / max( 1, count( $normalized ) );
    return $unique_ratio < 0.75;
}

function holyprofweb_assign_smart_categories( $post_id, $post = null ) {
    $post = $post ? $post : get_post( $post_id );
    if ( ! $post ) {
        return;
    }

    $title   = strtolower( holyprofweb_get_decoded_post_title( $post_id ) );
    $content = strtolower( wp_strip_all_tags( (string) $post->post_content ) );
    $source  = strtolower( (string) get_post_meta( $post_id, '_hpw_source_url', true ) );
    $haystack = trim( $title . ' ' . $content . ' ' . $source );

    $primary   = 'reviews';
    $secondary = array();
    $children  = array();

    $looks_like_report = preg_match( '/scam|legit|complaint|complaints|withdrawal|warning sign|red flag|fraud|is it safe|is it legit/', $title )
        || preg_match( '/scam|legit|complaint|complaints|withdrawal|warning sign|red flag|fraud/', $haystack );
    $looks_like_salary = preg_match( '/\bsalar(?:y|ies)\b|\bpay(?:\s+range|\s+scale|\s+band)?\b|\bearn(?:ings)?\b|\bmonthly pay\b|\bannual pay\b|\bcompensation package\b|\bsalary benchmark\b/', $title )
        || preg_match( '/\bsalar(?:y|ies)\b|\bpay(?:\s+range|\s+scale|\s+band)?\b|\bearn(?:ings)?\b|\bmonthly pay\b|\bannual pay\b|\bcompensation package\b|\bsalary benchmark\b/', $content );
    $looks_like_company = preg_match( '/company|bank|startup|fintech|telecom|profile|overview|llc|limited|inc|ltd/', $haystack );

    if ( $looks_like_report ) {
        $primary = 'reports';
        $children[] = 'scam-legit';
    } elseif ( preg_match( '/biography|net worth|founder|ceo|president|minister|senator|governor|personality|wiki/', $haystack ) ) {
        $primary = 'biography';
    } elseif ( $looks_like_salary ) {
        $primary = $looks_like_company ? 'companies' : 'salaries';
    } elseif ( $looks_like_company ) {
        $primary = 'companies';
    }

    if ( $looks_like_salary ) {
        $secondary[] = 'salaries';
    }
    if ( $looks_like_company ) {
        $secondary[] = 'companies';
    }

    if ( 'reviews' === $primary ) {
        if ( preg_match( '/scam|legit|safe|trusted|fraud|warning sign|red flag/', $haystack ) ) {
            $children[] = 'scam-legit';
        }
        if ( preg_match( '/loan|lender|credit|borrow|finance/', $haystack ) ) {
            $children[] = 'loan-finance';
        }
        if ( preg_match( '/app|android|ios|play store|mobile app|download/', $haystack ) ) {
            $children[] = 'app-reviews';
        }
        if ( preg_match( '/website|site|domain|platform|web app|browser/', $haystack ) ) {
            $children[] = 'website-reviews';
        }
        if ( preg_match( '/shop|shopping|store|order|delivery|marketplace/', $haystack ) ) {
            $children[] = 'shopping';
        }
        if ( preg_match( '/scholarship|school|admission|student/', $haystack ) ) {
            $children[] = 'scholarship';
        }
        if ( preg_match( '/tech|software|saas|hosting|developer/', $haystack ) ) {
            $children[] = 'tech';
        }
    }

    $terms = array();
    $primary_term = get_term_by( 'slug', $primary, 'category' );
    if ( $primary_term && ! is_wp_error( $primary_term ) ) {
        $terms[] = (int) $primary_term->term_id;
    }

    foreach ( array_unique( $secondary ) as $secondary_slug ) {
        if ( $secondary_slug === $primary ) {
            continue;
        }

        $secondary_term = get_term_by( 'slug', $secondary_slug, 'category' );
        if ( $secondary_term && ! is_wp_error( $secondary_term ) ) {
            $terms[] = (int) $secondary_term->term_id;
        }
    }

    $review_children = array(
        'scam-legit'     => 'Scam / Legit',
        'app-reviews'    => 'App Reviews',
        'website-reviews'=> 'Website Reviews',
        'loan-finance'   => 'Loan / Finance',
        'shopping'       => 'Shopping',
        'scholarship'    => 'Scholarship',
        'tech'           => 'Tech',
        'blog-opinion'   => 'Blog / Opinion',
    );

    foreach ( array_unique( $children ) as $child ) {
        $child_term = get_term_by( 'slug', $child, 'category' );
        if ( ! $child_term && 'reviews' === $primary && isset( $review_children[ $child ] ) && $primary_term ) {
            $created = wp_insert_term( $review_children[ $child ], 'category', array(
                'slug'   => $child,
                'parent' => (int) $primary_term->term_id,
            ) );
            if ( ! is_wp_error( $created ) && ! empty( $created['term_id'] ) ) {
                $child_term = get_term( (int) $created['term_id'], 'category' );
            }
        }
        if ( $child_term && ! is_wp_error( $child_term ) ) {
            $terms[] = (int) $child_term->term_id;
        }
    }

    if ( ! empty( $terms ) ) {
        wp_set_post_categories( $post_id, $terms, false );
    }
}

function holyprofweb_get_category_normalization_targets() {
    return array(
        'reviews' => array(
            'canonical_slug'    => 'reviews',
            'canonical_name'    => 'Reviews',
            'parent_slug'       => '',
            'aliases'           => array( 'review', 'reviews' ),
        ),
        'website-reviews' => array(
            'canonical_slug'    => 'website-reviews',
            'canonical_name'    => 'Website Reviews',
            'parent_slug'       => 'reviews',
            'aliases'           => array( 'website reviews', 'website-review', 'website-reviews', 'web reviews', 'site reviews', 'site-review', 'websites' ),
        ),
        'app-reviews' => array(
            'canonical_slug'    => 'app-reviews',
            'canonical_name'    => 'App Reviews',
            'parent_slug'       => 'reviews',
            'aliases'           => array( 'app reviews', 'app-review', 'app-reviews', 'apps', 'application reviews' ),
        ),
    );
}

function holyprofweb_get_or_create_category_term( $slug, $name = '', $parent_slug = '' ) {
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( $term && ! is_wp_error( $term ) ) {
        return $term;
    }

    $args = array( 'slug' => $slug );
    if ( $parent_slug ) {
        $parent = get_term_by( 'slug', $parent_slug, 'category' );
        if ( $parent && ! is_wp_error( $parent ) ) {
            $args['parent'] = (int) $parent->term_id;
        }
    }

    $created = wp_insert_term( $name ? $name : ucwords( str_replace( '-', ' ', $slug ) ), 'category', $args );
    if ( is_wp_error( $created ) || empty( $created['term_id'] ) ) {
        return null;
    }

    return get_term( (int) $created['term_id'], 'category' );
}

function holyprofweb_normalize_post_categories( $post_id ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 ) {
        return;
    }

    $terms = get_the_category( $post_id );
    if ( empty( $terms ) ) {
        return;
    }

    $map         = holyprofweb_get_category_normalization_targets();
    $keep_ids    = array();
    $matched     = array();
    $changed     = false;

    foreach ( $terms as $term ) {
        $slug = strtolower( (string) $term->slug );
        $name = strtolower( trim( preg_replace( '/\s+/', ' ', (string) $term->name ) ) );
        $canonical_slug = '';

        foreach ( $map as $target_slug => $config ) {
            if ( $slug === $target_slug || $name === strtolower( $config['canonical_name'] ) || in_array( $slug, $config['aliases'], true ) || in_array( $name, $config['aliases'], true ) ) {
                $canonical_slug = $target_slug;
                break;
            }
        }

        if ( $canonical_slug ) {
            $matched[ $canonical_slug ] = true;
            if ( $slug !== $canonical_slug ) {
                $changed = true;
            }
            continue;
        }

        $keep_ids[] = (int) $term->term_id;
    }

    if ( empty( $matched ) ) {
        return;
    }

    foreach ( array_keys( $matched ) as $target_slug ) {
        $config = $map[ $target_slug ];
        if ( ! empty( $config['parent_slug'] ) ) {
            $parent = holyprofweb_get_or_create_category_term( $config['parent_slug'], $map[ $config['parent_slug'] ]['canonical_name'] ?? $config['parent_slug'] );
            if ( $parent ) {
                $keep_ids[] = (int) $parent->term_id;
            }
        }

        $target = holyprofweb_get_or_create_category_term( $target_slug, $config['canonical_name'], $config['parent_slug'] );
        if ( $target ) {
            $keep_ids[] = (int) $target->term_id;
        }
    }

    $keep_ids = array_values( array_unique( array_filter( array_map( 'intval', $keep_ids ) ) ) );
    if ( empty( $keep_ids ) ) {
        return;
    }

    $current_ids = array_map( 'intval', wp_get_post_categories( $post_id ) );
    sort( $current_ids );
    $updated_ids = $keep_ids;
    sort( $updated_ids );

    if ( $changed || $current_ids !== $updated_ids ) {
        wp_set_post_categories( $post_id, $updated_ids, false );
    }
}

function holyprofweb_maybe_expand_topic_title( $post_id ) {
    $title = trim( holyprofweb_get_decoded_post_title( $post_id ) );
    $word_count = count( preg_split( '/\s+/', $title ) );
    if ( mb_strlen( $title ) >= 12 && $word_count >= 2 ) {
        return;
    }

    $cats = wp_list_pluck( get_the_category( $post_id ), 'slug' );
    $suffix = 'Full Guide';
    if ( array_intersect( array( 'reports', 'scam-legit' ), $cats ) ) {
        $suffix = 'Scam Alert and Warning Signs';
    } elseif ( in_array( 'salaries', $cats, true ) ) {
        $suffix = 'Salary Guide and Market Context';
    } elseif ( in_array( 'biography', $cats, true ) ) {
        $suffix = 'Biography and Key Facts';
    } elseif ( in_array( 'companies', $cats, true ) ) {
        $suffix = 'Company Profile and What to Know';
    } else {
        $suffix = 'Review, Pros, Cons, and What to Know';
    }

    wp_update_post( array(
        'ID'         => $post_id,
        'post_title' => trim( $title . ' ' . $suffix ),
    ) );
}

function holyprofweb_evaluate_draft_readiness( $post ) {
    $title      = trim( (string) $post->post_title );
    $plain      = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $post->post_content ) ) );
    $word_count = str_word_count( $plain );
    $minimum    = holyprofweb_get_draft_minimum_words();
    $floor      = holyprofweb_get_draft_publish_floor_words();
    $needs      = array();

    if ( mb_strlen( $title ) < 18 ) {
        $needs[] = 'title';
    }
    if ( $word_count < $floor ) {
        $needs[] = 'content';
    }
    if ( empty( get_the_category( $post->ID ) ) ) {
        $needs[] = 'category';
    }
    if ( ! has_post_thumbnail( $post->ID ) && ! get_post_meta( $post->ID, 'external_image', true ) ) {
        $needs[] = 'image';
    }
    if ( $word_count < $minimum && holyprofweb_content_looks_repetitive( $post->post_content ) ) {
        $needs[] = 'repetition';
    }

    return array(
        'ready'      => empty( $needs ),
        'needs'      => $needs,
        'word_count' => $word_count,
        'soft_ready' => ( $word_count >= $minimum ),
    );
}

function holyprofweb_attempt_draft_repairs( $post_id, $post = null ) {
    $post = $post ?: get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    holyprofweb_assign_smart_categories( $post_id, $post );
    holyprofweb_normalize_post_categories( $post_id );
    holyprofweb_maybe_expand_topic_title( $post_id );

    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    holyprofweb_expand_thin_post_content( $post_id, true );
    $post = get_post( $post_id );

    if ( $post instanceof WP_Post ) {
        $result = holyprofweb_evaluate_draft_readiness( $post );
        if ( in_array( 'content', $result['needs'], true ) || in_array( 'repetition', $result['needs'], true ) ) {
            holyprofweb_expand_thin_post_content( $post_id, true );
            $post = get_post( $post_id );
        }
    }

    if ( $post instanceof WP_Post ) {
        holyprofweb_auto_set_excerpt( $post_id, $post );
        holyprofweb_auto_set_tags( $post_id, $post );
        holyprofweb_cache_schema_type( $post_id );
        holyprofweb_cache_reading_time( $post_id, $post );

        if ( ! has_post_thumbnail( $post_id ) ) {
            holyprofweb_auto_featured_image( $post_id, $post, false );
        }
    }
}

add_action( 'save_post_post', function ( $post_id, $post ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
        return;
    }

    holyprofweb_normalize_post_categories( $post_id );
}, 30, 2 );

function holyprofweb_is_high_volume_publish_context() {
    if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
        return true;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return true;
    }

    if ( wp_doing_ajax() ) {
        return true;
    }

    if ( is_admin() ) {
        $action  = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
        $action2 = isset( $_REQUEST['action2'] ) ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '';

        if ( in_array( $action, array( 'edit', 'bulk-edit', 'inline-save', 'trash', 'untrash' ), true ) || in_array( $action2, array( 'edit', 'bulk-edit', 'trash', 'untrash' ), true ) ) {
            return true;
        }

        if ( ! empty( $_REQUEST['bulk_edit'] ) || ! empty( $_REQUEST['bulk_edit_posts'] ) ) {
            return true;
        }
    }

    return false;
}

function holyprofweb_maybe_publish_ready_draft( $post_id, $post = null ) {
    if ( ! get_option( 'hpw_enable_draft_autopublish', 1 ) ) {
        return;
    }

    $post = $post ?: get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || 'draft' !== $post->post_status ) {
        return;
    }

    // If this is a manual admin edit (not cron/import), do not auto-publish.
    if ( is_admin() && ! wp_doing_cron() && ! wp_doing_ajax() && ! ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) ) {
        return;
    }

    if ( holyprofweb_is_high_volume_publish_context() ) {
        return;
    }

    holyprofweb_attempt_draft_repairs( $post_id, $post );
    $post   = get_post( $post_id );
    $result = $post instanceof WP_Post ? holyprofweb_evaluate_draft_readiness( $post ) : array( 'ready' => false, 'needs' => array() );

    if ( ! $result['ready'] ) {
        holyprofweb_attempt_draft_repairs( $post_id, $post );
        $post   = get_post( $post_id );
        $result = $post instanceof WP_Post ? holyprofweb_evaluate_draft_readiness( $post ) : array( 'ready' => false, 'needs' => array() );
    }

    if ( $post instanceof WP_Post && $result['ready'] ) {
        remove_action( 'save_post_post', 'holyprofweb_maybe_publish_ready_draft_on_save', 40 );
        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        ) );
        add_action( 'save_post_post', 'holyprofweb_maybe_publish_ready_draft_on_save', 40, 2 );
    }
}

function holyprofweb_maybe_publish_ready_draft_on_save( $post_id, $post ) {
    holyprofweb_maybe_publish_ready_draft( $post_id, $post );
}
add_action( 'save_post_post', 'holyprofweb_maybe_publish_ready_draft_on_save', 40, 2 );

function holyprofweb_process_draft_queue() {
    if ( ! get_option( 'hpw_enable_draft_autopublish', 1 ) ) {
        return;
    }

    $existing_queue = holyprofweb_get_draft_publish_queue();
    $configured_limit = max( 5, absint( get_option( 'hpw_draft_publish_limit', 12 ) ) );
    $draft_totals = wp_count_posts( 'post' );
    $draft_count = isset( $draft_totals->draft ) ? (int) $draft_totals->draft : 0;
    $target_limit = $draft_count > 0 ? (int) ceil( $draft_count / 6 ) : 0;
    $limit = max( $configured_limit, $target_limit );
    $limit = max( 5, min( 100, $limit ) );

    $drafts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'draft',
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );
    $queue = array();

    foreach ( $drafts as $post ) {
        $previous = isset( $existing_queue[ $post->ID ] ) && is_array( $existing_queue[ $post->ID ] ) ? $existing_queue[ $post->ID ] : array();

        holyprofweb_attempt_draft_repairs( $post->ID, $post );
        $post = get_post( $post->ID );
        $result = holyprofweb_evaluate_draft_readiness( $post );

        if ( ! $result['ready'] ) {
            holyprofweb_attempt_draft_repairs( $post->ID, $post );
            $post = get_post( $post->ID );
            $result = holyprofweb_evaluate_draft_readiness( $post );
        }

        $attempts = isset( $previous['attempts'] ) ? (int) $previous['attempts'] + 1 : 1;
        $first_seen = isset( $previous['first_seen'] ) ? absint( $previous['first_seen'] ) : 0;
        if ( ! $first_seen ) {
            $first_seen = time();
        }

        $queue[ $post->ID ] = array(
            'title'      => get_the_title( $post->ID ),
            'word_count' => (int) $result['word_count'],
            'needs'      => $result['needs'],
            'country'    => get_post_meta( $post->ID, '_hpw_country_focus', true ),
            'attempts'   => $attempts,
            'first_seen' => $first_seen,
            'last_checked' => time(),
        );

        if ( $result['ready'] || holyprofweb_should_force_publish_draft( $post, $queue[ $post->ID ] ) ) {
            holyprofweb_publish_post_now( $post->ID );
            unset( $queue[ $post->ID ] );
        } else {
            holyprofweb_auto_set_excerpt( $post->ID, $post );
        }
    }

    update_option( 'holyprofweb_draft_publish_queue', $queue, false );
    update_option( 'holyprofweb_draft_audit_last_run', time(), false );
}
add_action( 'holyprofweb_draft_publish_audit', 'holyprofweb_process_draft_queue' );

function holyprofweb_retry_generated_image_upgrades() {
    if ( ! get_option( 'hpw_enable_remote_image_fetch', 1 ) ) {
        return;
    }

    $limit = max( 1, min( 8, absint( get_option( 'hpw_refresh_queue_limit', 25 ) ) ) );
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'meta_key'       => '_holyprofweb_gen_image_url',
        'fields'         => 'all',
        'no_found_rows'  => true,
    ) );

    foreach ( $posts as $post ) {
        holyprofweb_upgrade_generated_featured_image( $post->ID, $post );
    }
}
add_action( 'holyprofweb_draft_publish_audit', 'holyprofweb_retry_generated_image_upgrades', 20 );

function holyprofweb_build_ai_prompt_template( $title = '', $post_id = 0 ) {
    $title           = trim( (string) $title );
    $site_name       = get_bloginfo( 'name' );
    $tagline         = get_option( 'hpw_site_tagline', 'Research-first reviews, salary guides, company profiles, and practical explainers.' );
    $minimum_words   = holyprofweb_get_draft_minimum_words();
    $internal_links  = max( 2, absint( get_option( 'hpw_ai_internal_links', 3 ) ) );
    $faq_count       = max( 3, absint( get_option( 'hpw_ai_faq_count', 5 ) ) );
    $country_context = holyprofweb_get_active_country_context( $post_id );
    $voice           = trim( (string) get_option( 'hpw_ai_brand_voice', 'Calm, practical, trustworthy, globally readable, and naturally human.' ) );
    $notes           = trim( (string) get_option( 'hpw_ai_prompt_notes', '' ) );
    $country_focus   = ! empty( $country_context['post_focus'] ) ? $country_context['post_focus'] : $country_context['focus'];

    $prompt = array(
        "Write a publish-ready long-form article for {$site_name}.",
        '',
        "Brand direction: {$tagline}",
        "Voice: {$voice}",
        "Primary country focus: {$country_focus}",
        "Localization hook: {$country_context['hook']}",
        '',
        'Article requirements:',
        "- Minimum {$minimum_words} words unless the topic would become repetitive.",
        '- Sound human, specific, balanced, and useful. No robotic filler.',
        '- Match search intent closely and answer what the reader actually wants to know first.',
        '- Include a strong introduction, clear subheadings, and a practical conclusion with a verdict or takeaway.',
        '- Include at least one section on risks, warning signs, or what to verify before trusting the subject.',
        '- Add at least one section that mentions related alternatives, comparisons, or what users also search for.',
        "- Add {$faq_count} FAQs written in natural question format.",
        "- Add at least {$internal_links} natural internal-link suggestions inside the body using anchor-style wording.",
        '- If the topic is a review, include legitimacy, pricing/fees, complaints, support, pros, cons, and verdict.',
        '- If the topic is a salary page, include range context, location factors, seniority factors, and negotiation insight.',
        '- If the topic is a biography or political figure, include background, why the person matters, and region-specific interest.',
        '- If the topic is a report or scam pattern, include warning signs, user complaints, and how to stay safe.',
        '',
        'SEO requirements:',
        '- Create an SEO title variation if needed, but keep the page aligned with the provided title.',
        '- Use semantic variations naturally. No keyword stuffing.',
        '- Make the article useful enough to rank and satisfy user intent.',
        '- Add a compelling meta description under 160 characters.',
        '- Suggest a clean slug.',
        '- Suggest alt text and image hook text for the featured image.',
        '',
        'Formatting requirements:',
        '- Use short paragraphs.',
        '- Use H2/H3 headings where helpful.',
        '- Include a short summary box section near the top.',
        '- Include a final FAQ block.',
        '',
        'Output format:',
        '1. SEO Title',
        '2. Meta Description',
        '3. Suggested Slug',
        '4. Category',
        '5. Country Focus',
        '6. Verdict Badge',
        '7. Featured Image Hook',
        '8. Alt Text',
        '9. Article Body in clean HTML',
        '10. Internal Link Suggestions',
        '11. FAQ Schema JSON',
    );

    if ( $notes ) {
        $prompt[] = '';
        $prompt[] = 'Extra publishing notes:';
        $prompt[] = $notes;
    }

    $prompt[] = '';
    $prompt[] = 'Title: ' . ( $title ? $title : '[Insert title here]' );

    return implode( "\n", $prompt );
}


// =========================================
// ADMIN SETUP REFRESH — ?hpw_refresh_setup=1
// Allows admin to re-run menus + pages setup
// without re-activating the theme.
// =========================================

function holyprofweb_admin_refresh_setup() {
    if ( ! is_admin() ) return;
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( empty( $_GET['hpw_refresh_setup'] ) ) return;

    holyprofweb_enforce_permalink_structure();
    holyprofweb_create_menus();
    holyprofweb_create_static_pages();
    holyprofweb_schedule_content_audit();
    holyprofweb_run_content_audit();
    flush_rewrite_rules();

    wp_redirect( admin_url( '?hpw_refresh_done=1' ) );
    exit;
}
add_action( 'init', 'holyprofweb_admin_refresh_setup' );

function holyprofweb_admin_refresh_notice() {
    if ( ! empty( $_GET['hpw_refresh_done'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>HPW Setup:</strong> Menus and pages have been refreshed.</p></div>';
    }
}
add_action( 'admin_notices', 'holyprofweb_admin_refresh_notice' );


// =========================================
// CREATE CATEGORIES (parents + children)
// =========================================

function holyprofweb_create_categories() {

    // Parent categories
    $parents = array(
        'Reviews'   => 'reviews',
        'Companies' => 'companies',
        'Salaries'  => 'salaries',
        'Biography' => 'biography',
        'Reports'   => 'reports',
    );

    foreach ( $parents as $name => $slug ) {
        if ( ! term_exists( $slug, 'category' ) ) {
            wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
        }
    }

    // Child categories mapped to parent slugs
    $children = array(
        'reviews' => array(
            'Scam / Legit'   => 'scam-legit',
            'App Reviews'    => 'app-reviews',
            'Website Reviews'=> 'website-reviews',
            'Loan / Finance' => 'loan-finance',
            'Shopping'       => 'shopping',
            'Scholarship'    => 'scholarship',
            'Tech'           => 'tech',
            'Blog / Opinion' => 'blog-opinion',
        ),
        'companies' => array(
            'Fintech'   => 'fintech',
            'Banks'     => 'banks',
            'Startups'  => 'startups',
        ),
        'salaries' => array(
            'Nigeria'    => 'nigeria',
            'Remote'     => 'remote',
            'Tech Roles' => 'tech-roles',
        ),
        'biography' => array(
            'Founders'    => 'founders',
            'Influencers' => 'influencers',
        ),
        'reports' => array(
            'Scam Reports'    => 'scam-reports',
            'User Complaints' => 'user-complaints',
        ),
    );

    foreach ( $children as $parent_slug => $child_list ) {
        $parent_term = get_term_by( 'slug', $parent_slug, 'category' );
        if ( ! $parent_term ) continue;

        foreach ( $child_list as $child_name => $child_slug ) {
            if ( ! term_exists( $child_slug, 'category' ) ) {
                wp_insert_term( $child_name, 'category', array(
                    'slug'   => $child_slug,
                    'parent' => (int) $parent_term->term_id,
                ) );
            }
        }
    }
}

function holyprofweb_maybe_create_categories() {
    if ( get_option( 'holyprofweb_categories_bootstrapped', 0 ) ) {
        return;
    }

    holyprofweb_create_categories();
    update_option( 'holyprofweb_categories_bootstrapped', 1, false );
}
add_action( 'init', 'holyprofweb_maybe_create_categories', 5 );

function holyprofweb_enforce_permalink_structure() {
    global $wp_rewrite;

    $target = '/%postname%/';
    if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) ) {
        return;
    }

    if ( $wp_rewrite->permalink_structure !== $target ) {
        $wp_rewrite->set_permalink_structure( $target );
    }
}

function holyprofweb_sync_permalink_structure_admin() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $before = (string) get_option( 'permalink_structure' );
    holyprofweb_enforce_permalink_structure();

    if ( $before !== '/%postname%/' && get_option( 'permalink_structure' ) === '/%postname%/' ) {
        flush_rewrite_rules( false );
    }
}
add_action( 'admin_init', 'holyprofweb_sync_permalink_structure_admin', 5 );

function holyprofweb_get_required_placeholder_categories() {
    return array(
        'reviews',
        'companies',
        'salaries',
        'biography',
        'reports',
        'loan-apps',
        'crypto',
        'betting',
        'earning-platforms',
        'fintech',
        'banks',
        'startups',
        'nigeria',
        'remote',
        'tech-roles',
        'founders',
        'influencers',
        'scam-reports',
        'user-complaints',
    );
}

function holyprofweb_ensure_placeholder_posts() {
    if ( ! holyprofweb_is_local_environment() ) {
        return;
    }

    foreach ( holyprofweb_get_required_placeholder_categories() as $slug ) {
        $term = get_term_by( 'slug', $slug, 'category' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $existing = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => 1,
            'category__in'   => array( (int) $term->term_id ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );

        if ( ! empty( $existing ) ) {
            continue;
        }

        $parent = $term->parent ? get_term( $term->parent, 'category' ) : null;
        $post_id = wp_insert_post( array(
            'post_title'   => sprintf( '%s Placeholder Overview', $term->name ),
            'post_excerpt' => sprintf( 'Starter content for the %s category while the full page set is still being expanded.', $term->name ),
            'post_content' => sprintf( '<h2>Overview</h2><p>This is a starter page for the %s category. It exists so the category can be structured in the site while richer content is added.</p><h2>Category Notes</h2><p>Posts published here should follow the %s content flow on HolyprofWeb.</p>', esc_html( $term->name ), esc_html( $parent ? $parent->name : $term->name ) ),
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_category'=> array_filter( array( (int) $term->term_id, $term->parent ? (int) $term->parent : 0 ) ),
            'meta_input'   => array(
                '_hpw_placeholder_post' => 1,
            ),
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            $post = get_post( $post_id );
            if ( $post ) {
                holyprofweb_generate_post_image_modern( $post_id, $post );
            }
        }
    }
}

function holyprofweb_maybe_ensure_placeholder_posts() {
    if ( ! holyprofweb_is_local_environment() ) {
        return;
    }

    if ( get_option( 'holyprofweb_placeholders_bootstrapped', 0 ) ) {
        return;
    }

    holyprofweb_ensure_placeholder_posts();
    update_option( 'holyprofweb_placeholders_bootstrapped', 1, false );
}
add_action( 'init', 'holyprofweb_maybe_ensure_placeholder_posts', 25 );

function holyprofweb_get_category_seed_posts() {
    return array(
        'loan-apps' => array(
            'title'   => 'FairMoney Review for Quick Loans in Nigeria',
            'excerpt' => 'How FairMoney loans work, the fees to watch, and who should avoid instant loan apps.',
            'tags'    => array( 'fairmoney', 'loan apps', 'nigeria', 'personal finance' ),
            'content' => '<h2>Overview</h2><p>FairMoney is one of the most visible digital lenders in Nigeria. Users typically apply through the mobile app, complete identity verification, and receive a decision within minutes.</p><h2>How it works</h2><p>Loan size depends on repayment history, device data, and credit behavior. Repayment is usually monthly, and interest rates vary by customer profile.</p><h2>What to watch</h2><ul><li>Read the full repayment schedule before accepting.</li><li>Check whether the APR is worth the speed.</li><li>Only borrow what you can realistically repay on time.</li></ul><h2>Verdict</h2><p>FairMoney can be useful for short-term emergencies, but it should not replace a proper budgeting plan.</p>',
        ),
        'crypto' => array(
            'title'   => 'Luno Review for Nigerians Buying Crypto Carefully',
            'excerpt' => 'A simple breakdown of Luno for first-time crypto buyers, including fees and safety expectations.',
            'tags'    => array( 'luno', 'crypto', 'bitcoin', 'nigeria' ),
            'content' => '<h2>Overview</h2><p>Luno is a beginner-friendly crypto platform that focuses on a simpler buying and selling experience than advanced exchanges.</p><h2>How it works</h2><p>After KYC, users can fund their wallets and buy supported digital assets. The interface is easier to understand than many trading-first platforms.</p><h2>Pros</h2><ul><li>Clean mobile experience</li><li>Useful for beginners</li><li>Straightforward portfolio tracking</li></ul><h2>Risks</h2><p>Crypto remains volatile, and fees can feel expensive compared with advanced exchanges. It works best for users who value simplicity over deep trading features.</p>',
        ),
        'betting' => array(
            'title'   => 'Bet9ja Review: Payout Speed, Odds, and Caution Notes',
            'excerpt' => 'What regular users should know about Bet9ja before depositing money or expecting quick withdrawals.',
            'tags'    => array( 'bet9ja', 'betting', 'sports betting', 'nigeria' ),
            'content' => '<h2>Overview</h2><p>Bet9ja is one of the best-known betting brands in Nigeria, covering sports betting, virtual games, and casino products.</p><h2>User experience</h2><p>The platform is familiar to many users, but the real questions are always payout speed, account restrictions, and responsible usage.</p><h2>What matters most</h2><ul><li>Withdrawal consistency</li><li>Account verification process</li><li>Odds quality versus competitors</li><li>Responsible gambling discipline</li></ul><h2>Verdict</h2><p>Bet9ja is established and widely used, but it should be treated as entertainment, not income.</p>',
        ),
        'earning-platforms' => array(
            'title'   => 'Toloka Review: Can It Still Earn You Real Money?',
            'excerpt' => 'An honest look at microtask earnings, payout expectations, and the trade-offs behind task platforms.',
            'tags'    => array( 'toloka', 'earning platforms', 'remote work', 'microtasks' ),
            'content' => '<h2>Overview</h2><p>Toloka is a microtask platform where users complete small online jobs in exchange for modest payouts.</p><h2>How it works</h2><p>Tasks can include data labeling, search quality checks, and quick content judgments. Earnings depend on country, consistency, and task availability.</p><h2>Reality check</h2><p>It can pay, but it is not a replacement for a stable salary. Most users treat it as side income or a stepping stone into remote work.</p><h2>Verdict</h2><p>Toloka is best for people who want flexible extra income and understand that task volume can fluctuate.</p>',
        ),
        'fintech' => array(
            'title'   => 'Moniepoint Company Profile and Business Banking Snapshot',
            'excerpt' => 'A fast company overview covering Moniepoint\'s products, market position, and why businesses use it.',
            'tags'    => array( 'moniepoint', 'fintech', 'business banking', 'payments' ),
            'content' => '<h2>Overview</h2><p>Moniepoint has grown from agency banking roots into a major business banking and payments infrastructure player in Nigeria.</p><h2>Why it stands out</h2><p>Its strength is reliability for merchants, POS distribution, and business-focused financial tools.</p><h2>Core products</h2><ul><li>Business accounts</li><li>POS terminals</li><li>Collections and payments</li><li>Agency banking infrastructure</li></ul><h2>Verdict</h2><p>Moniepoint is one of the most important fintech infrastructure brands in the Nigerian market.</p>',
        ),
        'banks' => array(
            'title'   => 'GTBank Company Profile: Digital Banking Strength and Weak Spots',
            'excerpt' => 'A practical look at GTBank\'s products, digital experience, and where it still feels like a legacy bank.',
            'tags'    => array( 'gtbank', 'banks', 'nigeria', 'digital banking' ),
            'content' => '<h2>Overview</h2><p>GTBank remains one of the most recognizable banks in Nigeria, with strong brand trust and a large retail customer base.</p><h2>Customer experience</h2><p>Its apps and USSD remain widely used, but customer service and branch experience can vary by location and volume.</p><h2>Verdict</h2><p>GTBank combines traditional banking trust with relatively strong digital channels, though newer challengers now feel faster in some areas.</p>',
        ),
        'startups' => array(
            'title'   => 'Paystack Company Profile: Product Culture and Growth Story',
            'excerpt' => 'A clean overview of Paystack as a startup, the product reputation it built, and why founders respect it.',
            'tags'    => array( 'paystack', 'startups', 'payments', 'africa tech' ),
            'content' => '<h2>Overview</h2><p>Paystack became one of Africa\'s most respected startup success stories by making online payments easier for businesses.</p><h2>Why it matters</h2><p>The company earned strong trust for product quality, developer experience, and brand clarity long before its Stripe acquisition.</p><h2>Verdict</h2><p>Paystack remains a strong example of how African startups can win with product focus and operational discipline.</p>',
        ),
        'nigeria' => array(
            'title'   => 'Average Software Developer Salary in Nigeria (2026 Guide)',
            'excerpt' => 'Updated salary ranges for software developers in Nigeria, from junior roles to senior and remote work.',
            'tags'    => array( 'salary', 'developer', 'nigeria', 'tech roles' ),
            'content' => '<h2>Overview</h2><p>Software developer salaries in Nigeria vary widely by company stage, location, and whether the role is local or remote.</p><h2>Typical ranges</h2><ul><li>Junior: N200,000 to N450,000 monthly</li><li>Mid-level: N500,000 to N1.1M monthly</li><li>Senior: N1.2M to N2.8M monthly</li></ul><h2>Salary drivers</h2><p>Remote work, cloud skills, product companies, and strong system design ability usually push compensation higher.</p><h2>Verdict</h2><p>Negotiation works better when candidates bring real market benchmarks and clear evidence of impact.</p>',
        ),
        'remote' => array(
            'title'   => 'Remote Product Designer Salary Benchmarks for African Talent',
            'excerpt' => 'What remote product design roles are paying and the skills that push offers higher.',
            'tags'    => array( 'remote salary', 'product designer', 'tech salary', 'africa' ),
            'content' => '<h2>Overview</h2><p>Remote product designers often earn more than local equivalents when they can demonstrate strong systems thinking and product execution.</p><h2>What affects pay</h2><p>Portfolio quality, async communication, product sense, and experience with SaaS workflows all influence compensation.</p><h2>Verdict</h2><p>Remote salaries reward specialization and communication clarity as much as visual taste.</p>',
        ),
        'tech-roles' => array(
            'title'   => 'DevOps Engineer Salary Range Across African Product Companies',
            'excerpt' => 'Salary estimates for DevOps engineers working in cloud-heavy teams across Africa.',
            'tags'    => array( 'devops', 'salary', 'tech roles', 'cloud' ),
            'content' => '<h2>Overview</h2><p>DevOps roles remain among the better-paid technical paths because they reduce outages, improve delivery speed, and support scaling teams.</p><h2>Typical salary factors</h2><p>Kubernetes, AWS, CI/CD maturity, and security awareness tend to push compensation upward.</p><h2>Verdict</h2><p>Teams pay more when DevOps work is tied directly to uptime, deployment confidence, and cost control.</p>',
        ),
        'founders' => array(
            'title'   => 'Tosin Eniolorunda Biography: Moniepoint Founder Profile',
            'excerpt' => 'A concise biography covering Tosin Eniolorunda\'s background, career path, and company-building impact.',
            'tags'    => array( 'tosin eniolorunda', 'founders', 'moniepoint', 'biography' ),
            'content' => '<h2>Overview</h2><p>Tosin Eniolorunda is the co-founder and chief executive behind Moniepoint, one of the strongest fintech infrastructure stories in Nigeria.</p><h2>Career path</h2><p>He built a reputation around operational execution, fintech distribution, and solving payment reliability problems at scale.</p><h2>Why he matters</h2><p>His work helped shape how many Nigerian businesses access digital payments and agency banking services.</p>',
        ),
        'influencers' => array(
            'title'   => 'MrBeast Biography: Content Scale, Business Moves, and Influence',
            'excerpt' => 'A profile of MrBeast covering early growth, business expansion, and why his media strategy stands out.',
            'tags'    => array( 'mrbeast', 'influencers', 'biography', 'creator economy' ),
            'content' => '<h2>Overview</h2><p>MrBeast is one of the most influential digital creators of his generation, known for large-scale videos and aggressive reinvestment into content.</p><h2>Career growth</h2><p>His rise was built on experimentation, audience retention obsession, and turning viral entertainment into a broader business ecosystem.</p><h2>Why he matters</h2><p>He changed expectations around what creator-led media businesses can become.</p>',
        ),
        'scam-reports' => array(
            'title'   => 'Report Pattern: Fake Investment Sites Promising Guaranteed Returns',
            'excerpt' => 'A report-style page outlining common signs users notice when investment sites start looking suspicious.',
            'tags'    => array( 'scam reports', 'investment scam', 'fraud warning', 'user reports' ),
            'content' => '<h2>Overview</h2><p>Many suspicious investment sites use the same trust triggers: unrealistic returns, urgency, fake support, and withdrawal delays.</p><h2>Common warning signs</h2><ul><li>Guaranteed profits</li><li>Pressure to deposit quickly</li><li>No credible company footprint</li><li>Users reporting payout problems</li></ul><h2>Verdict</h2><p>Whenever profit claims sound frictionless, users should slow down and verify independently before sending money.</p>',
        ),
        'user-complaints' => array(
            'title'   => 'User Complaint Trends Around Delayed Withdrawals on Apps',
            'excerpt' => 'A report page tracking the most common user complaints around delayed withdrawals and support issues.',
            'tags'    => array( 'user complaints', 'withdrawals', 'support issues', 'reports' ),
            'content' => '<h2>Overview</h2><p>Delayed withdrawals remain one of the fastest ways platforms lose trust. Users usually complain when communication goes silent after money is locked in transit.</p><h2>What users report most</h2><ul><li>Pending withdrawals without clear timeline</li><li>Support tickets with no follow-up</li><li>Confusing verification loops</li><li>Inconsistent explanations from agents</li></ul><h2>Verdict</h2><p>Trust weakens quickly when platforms do not explain money delays clearly and consistently.</p>',
        ),
    );
}

function holyprofweb_seed_category_posts() {
    foreach ( holyprofweb_get_category_seed_posts() as $slug => $seed ) {
        $term = get_term_by( 'slug', $slug, 'category' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $existing_real = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => 1,
            'category__in'   => array( (int) $term->term_id ),
            'meta_query'     => array(
                array(
                    'key'     => '_hpw_placeholder_post',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );

        if ( ! empty( $existing_real ) ) {
            continue;
        }

        $placeholder = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => 1,
            'category__in'   => array( (int) $term->term_id ),
            'meta_key'       => '_hpw_placeholder_post',
            'meta_value'     => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );

        $categories = array( (int) $term->term_id );
        if ( ! empty( $term->parent ) ) {
            $categories[] = (int) $term->parent;
        }

        $post_data = array(
            'post_title'     => wp_strip_all_tags( $seed['title'] ),
            'post_excerpt'   => isset( $seed['excerpt'] ) ? $seed['excerpt'] : '',
            'post_content'   => $seed['content'],
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'post_author'    => 1,
            'post_category'  => $categories,
            'comment_status' => 'open',
            'meta_input'     => array(
                '_hpw_seed_post' => 1,
            ),
        );

        if ( ! empty( $placeholder ) ) {
            $post_data['ID'] = (int) $placeholder[0];
            $post_id = wp_update_post( $post_data, true );
            if ( ! is_wp_error( $post_id ) ) {
                delete_post_meta( $post_id, '_hpw_placeholder_post' );
                delete_post_meta( $post_id, '_holyprofweb_gen_image_url' );
                if ( has_post_thumbnail( $post_id ) ) {
                    delete_post_thumbnail( $post_id );
                }
            }
        } else {
            $post_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        if ( ! empty( $seed['tags'] ) ) {
            wp_set_post_tags( $post_id, $seed['tags'], false );
        }

        if ( ! has_post_thumbnail( $post_id ) ) {
            $post = get_post( $post_id );
            if ( $post ) {
                holyprofweb_generate_post_image_modern( $post_id, $post );
            }
        }
    }
}

function holyprofweb_maybe_seed_category_posts() {
    if ( get_option( 'holyprofweb_seed_posts_bootstrapped', 0 ) ) {
        return;
    }

    holyprofweb_seed_category_posts();
    update_option( 'holyprofweb_seed_posts_bootstrapped', 1, false );
}
add_action( 'init', 'holyprofweb_maybe_seed_category_posts', 26 );

function holyprofweb_get_uncategorized_term_id() {
    $term = get_term_by( 'slug', 'uncategorized', 'category' );
    return $term ? (int) $term->term_id : 0;
}

function holyprofweb_get_category_exclusions() {
    $exclude = array();
    $uncategorized_id = holyprofweb_get_uncategorized_term_id();
    if ( $uncategorized_id ) {
        $exclude[] = $uncategorized_id;
    }
    return $exclude;
}

function holyprofweb_cleanup_uncategorized_category() {
    $reviews = get_term_by( 'slug', 'reviews', 'category' );
    if ( ! $reviews || is_wp_error( $reviews ) ) {
        return;
    }

    update_option( 'default_category', (int) $reviews->term_id );

    $uncategorized_id = holyprofweb_get_uncategorized_term_id();
    if ( ! $uncategorized_id || (int) $reviews->term_id === $uncategorized_id ) {
        return;
    }

    if ( ! function_exists( 'wp_delete_category' ) ) {
        require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
    }

    if ( function_exists( 'wp_delete_category' ) ) {
        wp_delete_category( $uncategorized_id, (int) $reviews->term_id );
    }
}

function holyprofweb_maybe_cleanup_uncategorized_category() {
    if ( get_option( 'holyprofweb_uncategorized_cleaned', 0 ) ) {
        return;
    }

    holyprofweb_cleanup_uncategorized_category();
    update_option( 'holyprofweb_uncategorized_cleaned', 1, false );
}
add_action( 'init', 'holyprofweb_maybe_cleanup_uncategorized_category', 20 );

function holyprofweb_get_visible_categories( $args = array() ) {
    $defaults = array(
        'taxonomy'   => 'category',
        'hide_empty' => true,
        'exclude'    => holyprofweb_get_category_exclusions(),
    );

    $args = wp_parse_args( $args, $defaults );

    if ( empty( $args['exclude'] ) ) {
        unset( $args['exclude'] );
    }

    return get_terms( $args );
}

function holyprofweb_get_visible_category_count() {
    $terms = holyprofweb_get_visible_categories(
        array(
            'fields' => 'ids',
        )
    );

    return is_wp_error( $terms ) ? 0 : count( $terms );
}

function holyprofweb_get_frontpage_topic_categories( $limit = 8 ) {
    $priority_slugs = array(
        'reviews',
        'companies',
        'biography',
        'salaries',
        'reports',
        'websites',
        'influencers',
        'fintech',
        'banks',
        'startups',
        'scam-reports',
        'user-complaints',
        'loan-apps',
        'earning-platforms',
        'nigeria',
        'remote',
    );

    $terms = array();
    foreach ( $priority_slugs as $slug ) {
        $term = get_term_by( 'slug', $slug, 'category' );
        if ( ! $term || is_wp_error( $term ) || (int) $term->count < 1 ) {
            continue;
        }
        $terms[] = $term;
        if ( count( $terms ) >= $limit ) {
            break;
        }
    }

    return $terms;
}

function holyprofweb_get_blog_url() {
    return home_url( '/blog/' );
}

function holyprofweb_get_reports_url() {
    return home_url( '/reports/' );
}

function holyprofweb_get_virtual_archive_request_slug() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    if ( '' === $request_uri ) {
        return '';
    }

    $path = wp_parse_url( $request_uri, PHP_URL_PATH );
    $path = is_string( $path ) ? trim( $path, '/' ) : '';

    if ( '' === $path ) {
        return '';
    }

    $site_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $site_path = is_string( $site_path ) ? trim( $site_path, '/' ) : '';

    if ( $site_path && 0 === strpos( $path, $site_path . '/' ) ) {
        $path = substr( $path, strlen( $site_path ) + 1 );
    } elseif ( $site_path && $path === $site_path ) {
        $path = '';
    }

    return trim( (string) $path, '/' );
}

function holyprofweb_add_virtual_archive_rewrite() {
    add_rewrite_rule( '^blog/?$', 'index.php?hpw_blog_archive=1', 'top' );
    add_rewrite_rule( '^blog/page/([0-9]{1,})/?$', 'index.php?hpw_blog_archive=1&paged=$matches[1]', 'top' );
    add_rewrite_rule( '^reports/?$', 'index.php?hpw_reports_archive=1', 'top' );
    add_rewrite_rule( '^reports/page/([0-9]{1,})/?$', 'index.php?hpw_reports_archive=1&paged=$matches[1]', 'top' );
}
add_action( 'init', 'holyprofweb_add_virtual_archive_rewrite' );

add_filter(
    'query_vars',
    static function( $vars ) {
        $vars[] = 'hpw_blog_archive';
        $vars[] = 'hpw_reports_archive';
        return $vars;
    }
);

add_filter(
    'request',
    static function( $query_vars ) {
        $request_slug = holyprofweb_get_virtual_archive_request_slug();

        if ( preg_match( '#^blog(?:/page/([0-9]+))?$#', $request_slug, $matches ) ) {
            $vars = array( 'hpw_blog_archive' => 1 );
            if ( ! empty( $matches[1] ) ) {
                $vars['paged'] = (int) $matches[1];
            }
            return $vars;
        }

        if ( preg_match( '#^reports(?:/page/([0-9]+))?$#', $request_slug, $matches ) ) {
            $vars = array( 'hpw_reports_archive' => 1 );
            if ( ! empty( $matches[1] ) ) {
                $vars['paged'] = (int) $matches[1];
            }
            return $vars;
        }

        return $query_vars;
    }
);

add_action(
    'pre_get_posts',
    static function( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'hpw_blog_archive' ) ) {
            $query->set( 'post_type', 'post' );
            $query->set( 'post_status', 'publish' );
            $query->set( 'posts_per_page', holyprofweb_get_archive_posts_per_page() );
            $query->set( 'ignore_sticky_posts', true );
            $query->is_home    = false;
            $query->is_archive = true;
            $query->is_page    = false;
            $query->is_404     = false;
            return;
        }

        if ( $query->get( 'hpw_reports_archive' ) ) {
            $reports = get_term_by( 'slug', 'reports', 'category' );
            $term_ids = array();
            if ( $reports && ! is_wp_error( $reports ) ) {
                $term_ids[] = (int) $reports->term_id;
                $children = get_term_children( (int) $reports->term_id, 'category' );
                if ( ! is_wp_error( $children ) ) {
                    $term_ids = array_merge( $term_ids, array_map( 'intval', $children ) );
                }
            }

            $query->set( 'post_type', 'post' );
            $query->set( 'post_status', 'publish' );
            $query->set( 'posts_per_page', holyprofweb_get_archive_posts_per_page() );
            $query->set( 'ignore_sticky_posts', true );
            if ( ! empty( $term_ids ) ) {
                $query->set( 'category__in', array_values( array_unique( $term_ids ) ) );
            }
            $query->is_home    = false;
            $query->is_archive = true;
            $query->is_page    = false;
            $query->is_404     = false;
        }
    }
);

add_filter(
    'template_include',
    static function( $template ) {
        if ( get_query_var( 'hpw_blog_archive' ) || get_query_var( 'hpw_reports_archive' ) ) {
            $index_template = locate_template( array( 'index.php' ) );
            if ( $index_template ) {
                return $index_template;
            }
        }

        return $template;
    }
);

add_filter(
    'document_title_parts',
    static function( $parts ) {
        if ( get_query_var( 'hpw_blog_archive' ) ) {
            $parts['title'] = __( 'Blog', 'holyprofweb' );
        } elseif ( get_query_var( 'hpw_reports_archive' ) ) {
            $parts['title'] = __( 'Reports', 'holyprofweb' );
        }

        return $parts;
    }
);

function holyprofweb_post_in_category_tree( $post_id, $root_slug ) {
    $root = get_term_by( 'slug', $root_slug, 'category' );
    if ( ! $root || is_wp_error( $root ) ) {
        return false;
    }

    return has_category( $root->term_id, $post_id );
}

function holyprofweb_post_allows_wp_comments( $post_id = 0 ) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();
    if ( ! $post_id ) {
        return false;
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'blog-opinion' ) ) {
        return true;
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'reports' ) ) {
        return true;
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'biography' ) ) {
        return false;
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'reviews' ) ) {
        return false;
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'companies' ) ) {
        return false;
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'salaries' ) ) {
        return false;
    }

    return false;
}

add_filter( 'comments_open', function( $open, $post_id ) {
    if ( is_admin() ) {
        return $open;
    }

    return holyprofweb_post_allows_wp_comments( $post_id ) ? $open : false;
}, 20, 2 );


// =========================================
// CREATE & ASSIGN MENUS
// =========================================

function holyprofweb_create_menus() {

    // Primary Navigation
    $primary = wp_get_nav_menu_object( 'Primary Navigation' );
    if ( ! $primary ) {
        $primary_id = wp_create_nav_menu( 'Primary Navigation' );
    } else {
        $primary_id = $primary->term_id;
        foreach ( wp_get_nav_menu_items( $primary_id ) ?: array() as $item ) {
            wp_delete_post( $item->ID, true );
        }
    }

    if ( ! is_wp_error( $primary_id ) ) {
        $items = array(
            'Home'      => home_url( '/' ),
            'Reviews'   => home_url( '/category/reviews/' ),
            'Companies' => home_url( '/category/companies/' ),
            'Biography' => home_url( '/category/biography/' ),
            'Blog'      => holyprofweb_get_blog_url(),
            'Contact'   => home_url( '/contact/' ),
        );
        $i = 1;
        foreach ( $items as $label => $url ) {
            wp_update_nav_menu_item( $primary_id, 0, array(
                'menu-item-title'    => $label,
                'menu-item-url'      => $url,
                'menu-item-status'   => 'publish',
                'menu-item-type'     => 'custom',
                'menu-item-position' => $i++,
            ) );
        }
        $locs            = get_theme_mod( 'nav_menu_locations', array() );
        $locs['primary'] = $primary_id;
        set_theme_mod( 'nav_menu_locations', $locs );
    }

    // Footer Navigation
    $footer = wp_get_nav_menu_object( 'Footer Navigation' );
    if ( ! $footer ) {
        $footer_id = wp_create_nav_menu( 'Footer Navigation' );
    } else {
        $footer_id = $footer->term_id;
        foreach ( wp_get_nav_menu_items( $footer_id ) ?: array() as $item ) {
            wp_delete_post( $item->ID, true );
        }
    }

    if ( ! is_wp_error( $footer_id ) ) {
        $items = array(
            'About'     => '/about/',
            'Contact'   => '/contact/',
            'Privacy'   => '/privacy-policy/',
            'Advertise' => '/advertise/',
        );
        $i = 1;
        foreach ( $items as $label => $path ) {
            wp_update_nav_menu_item( $footer_id, 0, array(
                'menu-item-title'    => $label,
                'menu-item-url'      => home_url( $path ),
                'menu-item-status'   => 'publish',
                'menu-item-type'     => 'custom',
                'menu-item-position' => $i++,
            ) );
        }
        $locs           = get_theme_mod( 'nav_menu_locations', array() );
        $locs['footer'] = $footer_id;
        set_theme_mod( 'nav_menu_locations', $locs );
    }
}


// =========================================
// CLEANUP DEFAULT CONTENT
// =========================================

function holyprofweb_cleanup_defaults() {
    $hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
    if ( $hello ) wp_delete_post( $hello->ID, true );

    $sample = get_page_by_path( 'sample-page', OBJECT, 'page' );
    if ( $sample ) wp_delete_post( $sample->ID, true );
}


// =========================================
// CREATE SUBMIT PAGE
// =========================================

function holyprofweb_create_submit_page() {
    // Check if a page with slug 'submit' already exists
    $existing = get_page_by_path( 'submit', OBJECT, 'page' );
    if ( $existing ) return;

    wp_insert_post( array(
        'post_title'     => 'Submit a Review',
        'post_name'      => 'submit',
        'post_content'   => '',
        'post_status'    => 'publish',
        'post_type'      => 'page',
        'post_author'    => 1,
        'page_template'  => 'page-submit.php',
        'comment_status' => 'closed',
    ) );
}


// =========================================
// PLACEHOLDER ATTACHMENT HELPER
// =========================================

function holyprofweb_get_placeholder_id() {
    $cached = (int) get_option( 'holyprofweb_placeholder_id', 0 );
    if ( $cached && get_post( $cached ) ) return $cached;

    $upload = wp_upload_dir();
    if ( $upload['error'] ) return 0;

    $filename = 'holyprofweb-placeholder.jpg';
    $filepath = trailingslashit( $upload['path'] ) . $filename;
    $fileurl  = trailingslashit( $upload['url'] ) . $filename;

    if ( function_exists( 'imagecreatetruecolor' ) ) {
        $img  = imagecreatetruecolor( 1200, 630 );
        $gold = imagecolorallocate( $img, 184, 134, 11 );
        $dark = imagecolorallocate( $img, 14, 14, 17 );
        $mid  = imagecolorallocate( $img, 28, 28, 34 );
        $text = imagecolorallocate( $img, 230, 228, 220 );

        // Gradient fill
        for ( $y = 0; $y < 630; $y++ ) {
            $mix = $y / 629;
            $r   = (int) round( 14 + ( 28 - 14 ) * $mix );
            $g   = (int) round( 14 + ( 28 - 14 ) * $mix );
            $b   = (int) round( 17 + ( 34 - 17 ) * $mix );
            imageline( $img, 0, $y, 1199, $y, imagecolorallocate( $img, $r, $g, $b ) );
        }

        // Accent bar + footer
        imagefilledrectangle( $img, 0, 0, 8, 630, $gold );
        imagefilledrectangle( $img, 0, 566, 1200, 630, imagecolorallocate( $img, 8, 8, 10 ) );

        $label = 'HolyprofWeb';
        $font  = holyprofweb_get_image_font_file();
        if ( $font && function_exists( 'imagettftext' ) ) {
            imagettftext( $img, 52, 0, 60, 345, $gold, $font, $label );
            imagettftext( $img, 22, 0, 60, 400, imagecolorallocate( $img, 130, 128, 120 ), $font, 'Research · Reviews · Salaries · Biographies' );
            imagettftext( $img, 18, 0, 60, 608, imagecolorallocate( $img, 100, 98, 90 ), $font, 'holyprofweb.com' );
        } else {
            $fw = imagefontwidth( 5 );
            $x  = (int) ( ( 1200 - strlen( $label ) * $fw ) / 2 );
            imagestring( $img, 5, $x, 300, $label, $gold );
            imagestring( $img, 3, $x, 340, 'holyprofweb.com', $text );
        }

        imagejpeg( $img, $filepath, 90 );
        imagedestroy( $img );
    } else {
        $src = get_template_directory() . '/assets/images/placeholder.jpg';
        if ( file_exists( $src ) ) copy( $src, $filepath );
        else return 0;
    }

    if ( ! file_exists( $filepath ) ) return 0;

    $att_id = wp_insert_attachment( array(
        'guid'           => $fileurl,
        'post_mime_type' => 'image/jpeg',
        'post_title'     => 'HolyprofWeb Placeholder',
        'post_status'    => 'inherit',
    ), $filepath );

    if ( is_wp_error( $att_id ) ) return 0;

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $filepath ) );
    update_option( 'holyprofweb_placeholder_id', $att_id );

    return $att_id;
}

function holyprofweb_placeholder_url() {
    return get_template_directory_uri() . '/assets/images/placeholder.svg';
}

function holyprofweb_get_decoded_post_title( $post_id = 0 ) {
    return html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
}

function holyprofweb_the_decoded_title( $post_id = 0 ) {
    echo esc_html( holyprofweb_get_decoded_post_title( $post_id ) );
}

function holyprofweb_get_decoded_post_excerpt( $post_id = 0 ) {
    $excerpt = html_entity_decode( get_the_excerpt( $post_id ), ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
    $excerpt = preg_replace( '/Quick Summary.*$/i', '', (string) $excerpt );
    return trim( preg_replace( '/\s+/', ' ', (string) $excerpt ) );
}

function holyprofweb_get_post_image( $post_id, $size = 'large' ) {
    $thumbnail = get_the_post_thumbnail_url( $post_id, $size );
    if ( $thumbnail ) {
        return $thumbnail;
    }

    $external = trim( (string) get_post_meta( $post_id, 'external_image', true ) );
    if ( $external ) {
        return esc_url_raw( $external );
    }

    $remote_cached = trim( (string) get_post_meta( $post_id, '_holyprofweb_remote_image_url', true ) );
    if ( $remote_cached && ! holyprofweb_is_disallowed_remote_image_url( $remote_cached ) ) {
        return esc_url_raw( $remote_cached );
    }

    return 'full' === $size ? holyprofweb_get_generated_hero_image_url( $post_id ) : holyprofweb_get_generated_card_image_url( $post_id );
}

function holyprofweb_normalize_possible_url( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    if ( 0 === strpos( $value, '//' ) ) {
        $value = 'https:' . $value;
    } elseif ( ! preg_match( '#^[a-z][a-z0-9+\-.]*://#i', $value ) && preg_match( '#^(?:www\.)?[a-z0-9][a-z0-9\-\.]+\.[a-z]{2,}(?:/.*)?$#i', $value ) ) {
        $value = 'https://' . $value;
    }

    return esc_url_raw( $value );
}

function holyprofweb_is_disallowed_source_domain( $url ) {
    $host = holyprofweb_extract_domain( $url );
    if ( ! $host ) {
        return false;
    }

    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
    $site_host = $site_host ? strtolower( preg_replace( '/^www\./i', '', (string) $site_host ) ) : '';
    if ( $site_host ) {
        $normalized_host = strtolower( preg_replace( '/^www\./i', '', (string) $host ) );
        if ( $normalized_host === $site_host ) {
            return true;
        }
    }

    $blocked_hosts = array(
        'claude.ai',
        'anthropic.com',
        'chatgpt.com',
        'openai.com',
        'perplexity.ai',
        'gemini.google.com',
        'external-content.duckduckgo.com',
    );

    foreach ( $blocked_hosts as $blocked_host ) {
        if ( $host === $blocked_host || preg_match( '/(?:^|\.)' . preg_quote( $blocked_host, '/' ) . '$/i', $host ) ) {
            return true;
        }
    }

    return false;
}

function holyprofweb_is_disallowed_remote_image_url( $url ) {
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return false;
    }

    if ( holyprofweb_is_disallowed_source_domain( $url ) ) {
        return true;
    }

    return false;
}

function holyprofweb_get_raster_logo_path() {
    $candidates = array(
        get_template_directory() . '/assets/images/logo.png',
        get_template_directory() . '/assets/images/logoo.png',
        get_template_directory() . '/assets/images/logo-black.png',
    );

    foreach ( $candidates as $path ) {
        if ( file_exists( $path ) ) {
            return $path;
        }
    }

    return '';
}

function holyprofweb_load_image_resource( $path ) {
    if ( ! $path || ! file_exists( $path ) ) {
        return null;
    }

    $contents = @file_get_contents( $path );
    if ( false === $contents ) {
        return null;
    }

    $image = @imagecreatefromstring( $contents );
    return $image ?: null;
}

function holyprofweb_get_generated_image_filename( $post_id, $post ) {
    $title = holyprofweb_get_decoded_post_title( $post_id );
    $slug  = sanitize_title( $title );

    if ( ! $slug ) {
        $slug = sanitize_title( $post->post_title );
    }

    if ( ! $slug ) {
        $slug = 'holyprofweb-post-' . (int) $post_id;
    }

    return $slug . '-holyprofweb.jpg';
}

function holyprofweb_overlay_brand_logo( $canvas, $canvas_width, $canvas_height ) {
    if ( ! function_exists( 'imagecreatefromstring' ) ) {
        return;
    }

    $logo_path = holyprofweb_get_raster_logo_path();
    if ( ! $logo_path ) {
        return;
    }

    $logo = holyprofweb_load_image_resource( $logo_path );
    if ( ! $logo ) {
        return;
    }

    $logo_w = imagesx( $logo );
    $logo_h = imagesy( $logo );
    if ( ! $logo_w || ! $logo_h ) {
        imagedestroy( $logo );
        return;
    }

    $target_w = max( 120, (int) round( $canvas_width * 0.11 ) );
    $target_h = max( 28, (int) round( $target_w * ( $logo_h / $logo_w ) ) );
    $dest_x   = $canvas_width - $target_w - max( 28, (int) round( $canvas_width * 0.036 ) );
    $dest_y   = $canvas_height - $target_h - max( 24, (int) round( $canvas_height * 0.04 ) );

    imagealphablending( $canvas, true );
    imagesavealpha( $canvas, true );
    imagecopyresampled( $canvas, $logo, $dest_x, $dest_y, 0, 0, $target_w, $target_h, $logo_w, $logo_h );
    imagedestroy( $logo );
}

function holyprofweb_get_generated_image_styles() {
    return array(
        'editorial-dark' => array(
            'label'       => __( 'Editorial Dark', 'holyprofweb' ),
            'description' => __( 'Big headline card with a premium dark magazine feel.', 'holyprofweb' ),
        ),
        'split-banner' => array(
            'label'       => __( 'Split Banner', 'holyprofweb' ),
            'description' => __( 'Strong two-zone layout with a bold lower text band.', 'holyprofweb' ),
        ),
        'spotlight' => array(
            'label'       => __( 'Spotlight', 'holyprofweb' ),
            'description' => __( 'Centered headline with glow treatment and cleaner framing.', 'holyprofweb' ),
        ),
        'signal-grid' => array(
            'label'       => __( 'Signal Grid', 'holyprofweb' ),
            'description' => __( 'Structured grid look with a more data-report visual style.', 'holyprofweb' ),
        ),
        'minimal-poster' => array(
            'label'       => __( 'Minimal Poster', 'holyprofweb' ),
            'description' => __( 'Cleaner poster layout with lighter decoration and more breathing room.', 'holyprofweb' ),
        ),
    );
}

function holyprofweb_sanitize_generated_image_style( $value ) {
    $styles = holyprofweb_get_generated_image_styles();
    $value  = sanitize_key( (string) $value );
    return isset( $styles[ $value ] ) ? $value : 'editorial-dark';
}

function holyprofweb_get_generated_image_style() {
    return holyprofweb_sanitize_generated_image_style( get_option( 'hpw_generated_image_style', 'editorial-dark' ) );
}

function holyprofweb_regenerate_generated_images_batch() {
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
        'posts_per_page' => 20,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    $processed = 0;

    foreach ( $posts as $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            continue;
        }

        $has_image_work = holyprofweb_post_uses_generated_image_fallback( $post_id )
            || holyprofweb_post_has_generated_thumbnail_attachment( $post_id )
            || (bool) get_post_meta( $post_id, '_holyprofweb_gen_image_url', true )
            || (bool) get_post_meta( $post_id, '_holyprofweb_gen_attachment_id', true )
            || ( ! holyprofweb_post_has_trusted_featured_image( $post_id ) && ( holyprofweb_get_post_source_url( $post_id, $post ) || get_post_meta( $post_id, 'external_image', true ) ) );

        if ( ! $has_image_work ) {
            continue;
        }

        delete_post_meta( $post_id, '_holyprofweb_remote_image_url' );

        if ( ! holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
            $remote = holyprofweb_maybe_get_remote_post_image( $post_id, $post );
            if ( $remote ) {
                holyprofweb_attach_remote_image_to_post( $remote, $post_id, $post->post_title );
            }
        }

        if ( ! holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
            holyprofweb_generate_post_image_modern( $post_id, $post );
        }
        $processed++;
    }

    return $processed;
}

function holyprofweb_reset_generated_images_batch() {
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
        'posts_per_page' => 50,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    $processed = 0;

    foreach ( $posts as $post_id ) {
        if (
            ! get_post_meta( $post_id, '_holyprofweb_gen_image_url', true ) &&
            ! get_post_meta( $post_id, '_holyprofweb_gen_attachment_id', true ) &&
            ! holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) &&
            ! holyprofweb_post_uses_generated_image_fallback( $post_id ) &&
            ! get_post_meta( $post_id, '_holyprofweb_remote_image_url', true )
        ) {
            continue;
        }

        $attachment_id = (int) get_post_meta( $post_id, '_holyprofweb_gen_attachment_id', true );
        $thumbnail_id  = (int) get_post_thumbnail_id( $post_id );

        if ( $attachment_id > 0 && get_post( $attachment_id ) ) {
            if ( $thumbnail_id === $attachment_id ) {
                delete_post_thumbnail( $post_id );
            }
            wp_delete_attachment( $attachment_id, true );
        } elseif ( holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) && $thumbnail_id > 0 ) {
            delete_post_thumbnail( $post_id );
            wp_delete_attachment( $thumbnail_id, true );
        }

        delete_post_meta( $post_id, '_holyprofweb_gen_attachment_id' );
        delete_post_meta( $post_id, '_holyprofweb_gen_image_url' );
        delete_post_meta( $post_id, '_holyprofweb_gen_image_version' );
        delete_post_meta( $post_id, '_holyprofweb_remote_image_url' );

        $processed++;
    }

    return $processed;
}

function holyprofweb_enable_post_image_controls() {
    add_post_type_support( 'post', 'thumbnail' );
    add_post_type_support( 'post', 'custom-fields' );
}
add_action( 'init', 'holyprofweb_enable_post_image_controls' );

function holyprofweb_generated_image_version() {
    return '9-' . holyprofweb_get_generated_image_style();
}

function holyprofweb_clear_post_image_state( $post_id, $drop_generated_attachment = false ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 ) {
        return;
    }

    delete_post_meta( $post_id, '_holyprofweb_remote_image_url' );
    delete_post_meta( $post_id, '_holyprofweb_gen_image_url' );
    delete_post_meta( $post_id, '_holyprofweb_gen_image_version' );
    delete_transient( 'hpw_remote_image_retry_' . $post_id );

    $attachment_id = (int) get_post_meta( $post_id, '_holyprofweb_gen_attachment_id', true );
    if ( $attachment_id > 0 ) {
        if ( $drop_generated_attachment ) {
            $thumbnail_id = (int) get_post_thumbnail_id( $post_id );
            if ( $thumbnail_id === $attachment_id ) {
                delete_post_thumbnail( $post_id );
            }
            if ( get_post( $attachment_id ) ) {
                wp_delete_attachment( $attachment_id, true );
            }
        }
        delete_post_meta( $post_id, '_holyprofweb_gen_attachment_id' );
    }
}

function holyprofweb_force_regenerate_post_image( $post_id ) {
    $post_id = (int) $post_id;
    $post    = get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
        return false;
    }

    holyprofweb_clear_post_image_state( $post_id, true );

    if ( ! holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
        $external = trim( (string) get_post_meta( $post_id, 'external_image', true ) );
        if ( $external ) {
            return true;
        }

        $remote = holyprofweb_maybe_get_remote_post_image( $post_id, $post );
        if ( $remote ) {
            holyprofweb_attach_remote_image_to_post( $remote, $post_id, $post->post_title );
        }
    }

    if ( ! holyprofweb_post_has_trusted_featured_image( $post_id ) && ! get_post_meta( $post_id, 'external_image', true ) ) {
        holyprofweb_generate_post_image_modern( $post_id, $post );
    }

    return true;
}

function holyprofweb_refresh_post_image_state_on_save( $post_id, $post, $update ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || 'auto-draft' === $post->post_status ) {
        return;
    }

    $current_version = (string) get_post_meta( $post_id, '_holyprofweb_gen_image_version', true );
    $target_version  = holyprofweb_generated_image_version();
    $has_generated   = holyprofweb_post_has_generated_thumbnail_attachment( $post_id )
        || (bool) get_post_meta( $post_id, '_holyprofweb_gen_attachment_id', true )
        || (bool) get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );

    if ( $has_generated && $current_version !== $target_version ) {
        holyprofweb_clear_post_image_state( $post_id, true );
    } elseif ( get_post_meta( $post_id, '_holyprofweb_remote_image_url', true ) && holyprofweb_get_post_source_url( $post_id, $post ) ) {
        delete_transient( 'hpw_remote_image_retry_' . $post_id );
    }
}
add_action( 'save_post_post', 'holyprofweb_refresh_post_image_state_on_save', 15, 3 );

function holyprofweb_is_local_environment() {
    if ( function_exists( 'wp_get_environment_type' ) ) {
        $env = wp_get_environment_type();
        if ( in_array( $env, array( 'local', 'development' ), true ) ) {
            return true;
        }
    }

    $home = (string) home_url();
    return ( false !== strpos( $home, '.local' ) || false !== strpos( $home, 'localhost' ) );
}

function holyprofweb_is_placeholder_post( $post_id ) {
    return (bool) get_post_meta( $post_id, '_hpw_placeholder_post', true );
}

function holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) {
    $thumbnail_id = (int) get_post_thumbnail_id( $post_id );
    if ( $thumbnail_id <= 0 ) {
        return false;
    }

    $file = (string) get_post_meta( $thumbnail_id, '_wp_attached_file', true );
    if ( $file && false !== stripos( wp_basename( $file ), 'hpw-generated-' ) ) {
        return true;
    }

    $url = wp_get_attachment_url( $thumbnail_id );
    return $url && false !== stripos( $url, 'hpw-generated-' );
}

function holyprofweb_post_uses_generated_image_fallback( $post_id ) {
    if ( holyprofweb_is_placeholder_post( $post_id ) ) {
        return true;
    }

    if ( ! get_post_meta( $post_id, '_holyprofweb_gen_image_url', true ) && ! holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) ) {
        return false;
    }

    if ( get_post_meta( $post_id, 'external_image', true ) ) {
        return false;
    }

    if ( get_post_meta( $post_id, '_holyprofweb_remote_image_url', true ) ) {
        return false;
    }

    return true;
}

function holyprofweb_get_post_image_class( $post_id, $base = '' ) {
    $classes = preg_split( '/\s+/', trim( (string) $base ) );
    $classes = array_filter( $classes );

    if ( holyprofweb_post_uses_generated_image_fallback( $post_id ) ) {
        $classes[] = 'post-image--generated';
    } else {
        $classes[] = 'post-image--photo';
    }

    if ( ! holyprofweb_post_has_trusted_featured_image( $post_id ) && ( get_post_meta( $post_id, 'external_image', true ) || get_post_meta( $post_id, '_holyprofweb_remote_image_url', true ) ) ) {
        $classes[] = 'post-image--remote';
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'biography' ) || holyprofweb_post_in_category_tree( $post_id, 'founders' ) || holyprofweb_post_in_category_tree( $post_id, 'influencers' ) ) {
        $classes[] = 'post-image--person';
    } elseif ( holyprofweb_post_in_category_tree( $post_id, 'companies' ) ) {
        $classes[] = 'post-image--company';
    } elseif ( holyprofweb_post_in_category_tree( $post_id, 'reports' ) ) {
        $classes[] = 'post-image--report';
    }

    $classes = array_unique( array_map( 'sanitize_html_class', $classes ) );
    return implode( ' ', $classes );
}

function holyprofweb_get_generated_card_palette( $post_id ) {
    $cats     = get_the_category( $post_id );
    $cat_slug = ! empty( $cats ) ? $cats[0]->slug : '';

    $palettes = array(
        'reviews'   => array( 'bg1' => '#111827', 'bg2' => '#374151', 'accent' => '#f0b84a', 'soft' => 'rgba(240,184,74,0.20)' ),
        'companies' => array( 'bg1' => '#0f1720', 'bg2' => '#173b34', 'accent' => '#49d49d', 'soft' => 'rgba(73,212,157,0.18)' ),
        'salaries'  => array( 'bg1' => '#17130d', 'bg2' => '#50361b', 'accent' => '#f6c15a', 'soft' => 'rgba(246,193,90,0.18)' ),
        'biography' => array( 'bg1' => '#151320', 'bg2' => '#33214e', 'accent' => '#c8b5ff', 'soft' => 'rgba(200,181,255,0.18)' ),
        'reports'   => array( 'bg1' => '#1a1013', 'bg2' => '#5a272d', 'accent' => '#ff8a8a', 'soft' => 'rgba(255,138,138,0.18)' ),
    );

    foreach ( $palettes as $key => $palette ) {
        if ( false !== strpos( $cat_slug, $key ) ) {
            return $palette;
        }
    }

    return array( 'bg1' => '#111827', 'bg2' => '#374151', 'accent' => '#f0b84a', 'soft' => 'rgba(240,184,74,0.20)' );
}

function holyprofweb_post_has_trusted_featured_image( $post_id ) {
    if ( ! has_post_thumbnail( $post_id ) || holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) ) {
        return false;
    }

    $thumbnail_id = (int) get_post_thumbnail_id( $post_id );
    if ( $thumbnail_id <= 0 ) {
        return false;
    }

    $attached_file = get_attached_file( $thumbnail_id );
    if ( ! $attached_file || ! file_exists( $attached_file ) ) {
        return false;
    }

    return true;
}

function holyprofweb_get_generated_svg_image_url( $post_id, $variant = 'card' ) {
    $title    = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( holyprofweb_get_decoded_post_title( $post_id ) ) ) );
    $excerpt  = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( holyprofweb_get_decoded_post_excerpt( $post_id ) ) ) );
    $cats     = get_the_category( $post_id );
    $cat_name = ! empty( $cats ) ? strtoupper( $cats[0]->name ) : 'HOLYPROFWEB';
    $palette  = holyprofweb_get_generated_card_palette( $post_id );
    $is_hero  = 'hero' === $variant;
    $viewbox  = $is_hero ? '0 0 1200 675' : '0 0 640 400';
    $width    = $is_hero ? 1200 : 640;
    $height   = $is_hero ? 675 : 400;
    $radius   = $is_hero ? 34 : 28;
    $title_lines = holyprofweb_wrap_image_text( $title, $is_hero ? 3 : 2, $is_hero ? 18 : 20 );
    $deck_lines  = holyprofweb_wrap_image_text( $excerpt ?: __( 'Research-first review, company context, and practical signals.', 'holyprofweb' ), $is_hero ? 3 : 2, $is_hero ? 46 : 38 );

    $title_x      = $is_hero ? 64 : 32;
    $title_y_base = $is_hero ? 172 : 112;
    $title_gap    = $is_hero ? 56 : 36;
    $title_size   = $is_hero ? 52 : 28;
    $deck_x       = $is_hero ? 64 : 32;
    $deck_y_base  = $is_hero ? 372 : 196;
    $deck_gap     = $is_hero ? 30 : 22;
    $deck_size    = $is_hero ? 22 : 15;
    $badge_x      = $is_hero ? 64 : 32;
    $badge_y      = $is_hero ? 54 : 34;
    $badge_w      = $is_hero ? 220 : 160;
    $badge_h      = $is_hero ? 38 : 28;
    $badge_text_x = $is_hero ? 82 : 46;
    $badge_text_y = $is_hero ? 80 : 53;
    $footer_y     = $is_hero ? 562 : 332;
    $site_y       = $is_hero ? 602 : 360;
    $circle_big_x = $is_hero ? 1040 : 560;
    $circle_big_y = $is_hero ? 112 : 74;
    $circle_big_r = $is_hero ? 132 : 92;
    $circle_small_x = $is_hero ? 76 : 44;
    $circle_small_y = $is_hero ? 602 : 354;
    $circle_small_r = $is_hero ? 54 : 38;
    $brand_panel_w  = $is_hero ? 224 : 136;
    $brand_panel_h  = $is_hero ? 42 : 28;
    $brand_panel_x  = $width - $brand_panel_w - ( $is_hero ? 64 : 32 );
    $brand_panel_y  = $height - $brand_panel_h - ( $is_hero ? 48 : 26 );
    $brand_text_x   = $brand_panel_x + ( $is_hero ? 18 : 12 );
    $brand_text_y   = $brand_panel_y + ( $is_hero ? 27 : 19 );

    $title_svg = '';
    foreach ( $title_lines as $index => $line ) {
        $y = $title_y_base + ( $index * $title_gap );
        $title_svg .= '<text x="' . (int) $title_x . '" y="' . (int) $y . '" fill="#F8FAFC" font-size="' . (int) $title_size . '" font-weight="700" font-family="Inter, Segoe UI, Arial, sans-serif">' . htmlspecialchars( $line, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</text>';
    }

    $deck_svg = '';
    foreach ( array_slice( $deck_lines, 0, $is_hero ? 3 : 2 ) as $index => $line ) {
        $y = $deck_y_base + ( $index * $deck_gap );
        $deck_svg .= '<text x="' . (int) $deck_x . '" y="' . (int) $y . '" fill="#CBD5E1" font-size="' . (int) $deck_size . '" font-weight="500" font-family="Inter, Segoe UI, Arial, sans-serif">' . htmlspecialchars( $line, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</text>';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $viewbox . '" role="img" aria-label="' . htmlspecialchars( $title, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '">'
        . '<defs>'
        . '<linearGradient id="hpwBg" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="' . $palette['bg1'] . '"/>'
        . '<stop offset="100%" stop-color="' . $palette['bg2'] . '"/>'
        . '</linearGradient>'
        . '</defs>'
        . '<rect width="' . $width . '" height="' . $height . '" rx="' . $radius . '" fill="url(#hpwBg)"/>'
        . '<circle cx="' . $circle_big_x . '" cy="' . $circle_big_y . '" r="' . $circle_big_r . '" fill="' . $palette['soft'] . '"/>'
        . '<circle cx="' . $circle_small_x . '" cy="' . $circle_small_y . '" r="' . $circle_small_r . '" fill="rgba(255,255,255,0.08)"/>'
        . '<rect x="0" y="0" width="' . ( $is_hero ? 12 : 8 ) . '" height="' . $height . '" fill="' . $palette['accent'] . '"/>'
        . '<rect x="' . $badge_x . '" y="' . $badge_y . '" width="' . $badge_w . '" height="' . $badge_h . '" rx="' . ( $is_hero ? 14 : 10 ) . '" fill="' . $palette['accent'] . '"/>'
        . '<text x="' . $badge_text_x . '" y="' . $badge_text_y . '" fill="#111827" font-size="' . ( $is_hero ? 18 : 13 ) . '" font-weight="700" font-family="Inter, Segoe UI, Arial, sans-serif">' . htmlspecialchars( $cat_name, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</text>'
        . $title_svg
        . $deck_svg
        . '<rect x="' . ( $is_hero ? 64 : 32 ) . '" y="' . $footer_y . '" width="' . ( $is_hero ? 1072 : 576 ) . '" height="2" fill="rgba(255,255,255,0.12)"/>'
        . '<text x="' . ( $is_hero ? 64 : 32 ) . '" y="' . $site_y . '" fill="#CBD5E1" font-size="' . ( $is_hero ? 18 : 13 ) . '" font-weight="600" font-family="Inter, Segoe UI, Arial, sans-serif">holyprofweb.com</text>'
        . '<rect x="' . $brand_panel_x . '" y="' . $brand_panel_y . '" width="' . $brand_panel_w . '" height="' . $brand_panel_h . '" rx="' . ( $is_hero ? 16 : 12 ) . '" fill="rgba(8,12,19,0.52)" stroke="rgba(255,255,255,0.18)" stroke-width="1"/>'
        . '<text x="' . $brand_text_x . '" y="' . $brand_text_y . '" fill="#F8FAFC" font-size="' . ( $is_hero ? 17 : 11 ) . '" font-weight="700" font-family="Inter, Segoe UI, Arial, sans-serif">holyprofweb.com</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode( $svg );
}

function holyprofweb_get_generated_card_image_url( $post_id ) {
    if ( holyprofweb_post_uses_generated_image_fallback( $post_id ) || holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) ) {
        return holyprofweb_get_generated_svg_image_url( $post_id, 'card' );
    }

    $cached = trim( (string) get_post_meta( $post_id, '_holyprofweb_gen_image_url', true ) );
    if ( $cached ) {
        if ( 0 === strpos( $cached, 'data:image/svg+xml' ) ) {
            return holyprofweb_get_generated_svg_image_url( $post_id, 'card' );
        }
        return esc_url_raw( $cached );
    }

    if ( holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) ) {
        $thumb_url = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( $thumb_url ) {
            return esc_url_raw( $thumb_url );
        }
    }

    return holyprofweb_get_generated_svg_image_url( $post_id, 'card' );
}

function holyprofweb_get_generated_hero_image_url( $post_id ) {
    $cached = trim( (string) get_post_meta( $post_id, '_holyprofweb_gen_image_url', true ) );
    if ( $cached ) {
        if ( 0 === strpos( $cached, 'data:image/svg+xml' ) ) {
            return holyprofweb_get_generated_svg_image_url( $post_id, 'hero' );
        }
        return esc_url_raw( $cached );
    }

    if ( holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) ) {
        $thumb_url = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( $thumb_url ) {
            return esc_url_raw( $thumb_url );
        }
    }

    return holyprofweb_get_generated_svg_image_url( $post_id, 'hero' );
}

function holyprofweb_get_generic_card_image_url() {
    return holyprofweb_get_generated_svg_image_url( 0, 'card' );
}

function holyprofweb_get_front_page_card_image_url( $post_id ) {
    return holyprofweb_get_post_card_image_url( $post_id );
}

function holyprofweb_get_post_card_image_url( $post_id ) {
    if ( holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
        $thumb_url = get_the_post_thumbnail_url( $post_id, 'holyprofweb-card' );
        if ( $thumb_url ) {
            return esc_url_raw( $thumb_url );
        }

        $full_url = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( $full_url ) {
            return esc_url_raw( $full_url );
        }
    }

    $external = trim( (string) get_post_meta( $post_id, 'external_image', true ) );
    if ( $external ) {
        return esc_url_raw( $external );
    }

    $remote_cached = trim( (string) get_post_meta( $post_id, '_holyprofweb_remote_image_url', true ) );
    if ( $remote_cached && ! holyprofweb_is_disallowed_remote_image_url( $remote_cached ) ) {
        return esc_url_raw( $remote_cached );
    }

    $post = get_post( $post_id );
    if ( $post ) {
        $remote = holyprofweb_maybe_get_remote_post_image( $post_id, $post );
        if ( $remote ) {
            return esc_url_raw( $remote );
        }
    }

    return holyprofweb_get_generated_card_image_url( $post_id );
}

/**
 * Always returns a usable image URL for a post.
 * Priority: real thumbnail → cached generated URL → generate now → placeholder.
 *
 * @param int    $post_id
 * @param string $size   WP image size name
 * @return string
 */
function holyprofweb_get_post_image_url( $post_id, $size = 'holyprofweb-card' ) {
    $external      = trim( (string) get_post_meta( $post_id, 'external_image', true ) );
    $remote_cached = get_post_meta( $post_id, '_holyprofweb_remote_image_url', true );
    $generated     = trim( (string) get_post_meta( $post_id, '_holyprofweb_gen_image_url', true ) );
    $post          = get_post( $post_id );

    if ( ! holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
        if ( $external ) {
            return esc_url_raw( $external );
        }

        if ( $remote_cached ) {
            return esc_url_raw( $remote_cached );
        }

        if ( $generated ) {
            if ( 0 === strpos( $generated, 'data:image/svg+xml' ) ) {
                return 'full' === $size ? holyprofweb_get_generated_svg_image_url( $post_id, 'hero' ) : holyprofweb_get_generated_svg_image_url( $post_id, 'card' );
            }
            return esc_url_raw( $generated );
        }

        return 'full' === $size ? holyprofweb_get_generated_hero_image_url( $post_id ) : holyprofweb_get_generated_card_image_url( $post_id );
    }

    $image_size = $size;
    if ( holyprofweb_post_uses_generated_image_fallback( $post_id ) || holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) ) {
        $image_size = 'full';
    }

    $url = get_the_post_thumbnail_url( $post_id, $image_size );
    if ( $url ) {
        return $url;
    }

    $full_thumb = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( $full_thumb ) {
        return $full_thumb;
    }

    if ( $external ) {
        return esc_url_raw( $external );
    }

    if ( $remote_cached ) {
        return esc_url_raw( $remote_cached );
    }

    if ( $generated ) {
        if ( 0 === strpos( $generated, 'data:image/svg+xml' ) ) {
            return 'full' === $size ? holyprofweb_get_generated_svg_image_url( $post_id, 'hero' ) : holyprofweb_get_generated_svg_image_url( $post_id, 'card' );
        }
        return esc_url_raw( $generated );
    }

    if ( $post ) {
        $remote = holyprofweb_maybe_get_remote_post_image( $post_id, $post );
        if ( $remote ) {
            return esc_url_raw( $remote );
        }
    }

    return 'full' === $size ? holyprofweb_get_generated_hero_image_url( $post_id ) : holyprofweb_get_generated_card_image_url( $post_id );
}


// =========================================
// CREATE SAMPLE POSTS
// =========================================

function holyprofweb_create_sample_posts() {

    holyprofweb_create_categories();
    $placeholder_id = holyprofweb_get_placeholder_id();

    $posts = array(
        array(
            'title'    => 'PalmPay Review — What You Should Know Before Using It',
            'category' => 'reviews',
            'tags'     => array( 'palmpay', 'fintech', 'mobile payments', 'nigeria' ),
            'content'  => '<h2>What is PalmPay?</h2><p>PalmPay is a mobile money and digital payments platform operating primarily in Nigeria. Launched in 2019, it offers transfers, bill payments, airtime purchases, and savings — all from one app.</p><h2>How It Works</h2><p>After downloading and completing KYC verification, users receive a NUBAN account number. Fund your wallet via bank transfer and use the balance for transactions.</p><h2>Key Features</h2><ul><li>Free interbank transfers</li><li>Instant airtime and data top-up</li><li>Cashback and reward points</li><li>Virtual and physical Visa debit card</li></ul><h2>Pros and Cons</h2><p><strong>Pros:</strong> Generous cashback rewards, competitive fees, clean UI, CBN-licensed.</p><p><strong>Cons:</strong> Slow customer support, daily limits restrictive for high-volume users.</p><h2>Verdict</h2><p>PalmPay is a solid, CBN-licensed fintech app that rewards everyday spending. Especially useful for people who make frequent small transfers.</p>',
        ),
        array(
            'title'    => 'Is OPay Safe? Full Breakdown for New Users',
            'category' => 'reviews',
            'tags'     => array( 'opay', 'safety', 'fintech', 'nigeria' ),
            'content'  => '<h2>What is OPay?</h2><p>OPay (Opera Pay) is a digital financial services platform launched in 2018. It is one of Nigeria\'s largest mobile money operators, serving millions of customers.</p><h2>How It Works</h2><p>OPay functions as a mobile bank: users open an account, receive a unique account number, fund it, and transact digitally.</p><h2>Key Features</h2><ul><li>CBN-licensed mobile money operator</li><li>OWealth savings with competitive interest</li><li>Free transfers to OPay users</li><li>Agent banking network</li></ul><h2>Pros and Cons</h2><p><strong>Pros:</strong> CBN-regulated, large agent network, high savings interest.</p><p><strong>Cons:</strong> Occasional downtime, slow dispute resolution, no international transfers.</p><h2>Verdict</h2><p>OPay is safe to use. It is regulated by the CBN, maintains proper KYC, and uses SSL encryption. A trustworthy option for everyday transactions.</p>',
        ),
        array(
            'title'    => 'Binance — How It Works for Beginners',
            'category' => 'companies',
            'tags'     => array( 'binance', 'crypto', 'exchange', 'bitcoin' ),
            'content'  => '<h2>What is Binance?</h2><p>Binance is the world\'s largest cryptocurrency exchange by trading volume, founded in 2017. It provides a platform for buying, selling, and trading hundreds of cryptocurrencies.</p><h2>How It Works</h2><p>Create an account, complete KYC, deposit funds, and trade on spot or futures markets. Available 24/7 on web and mobile.</p><h2>Key Features</h2><ul><li>500+ cryptocurrencies</li><li>Spot, futures, and margin trading</li><li>Binance Earn — passive crypto income</li><li>P2P trading with local currency support</li></ul><h2>Pros and Cons</h2><p><strong>Pros:</strong> Highest global liquidity, low fees, advanced tools.</p><p><strong>Cons:</strong> Complex for beginners, regulatory pressure in multiple countries.</p><h2>Verdict</h2><p>Binance is the go-to exchange for serious traders. Beginners should start with Lite mode and always enable two-factor authentication immediately.</p>',
        ),
        array(
            'title'    => 'Elon Musk Biography — Early Life, Career and Net Worth',
            'category' => 'biography',
            'tags'     => array( 'elon musk', 'tesla', 'spacex', 'biography' ),
            'content'  => '<h2>Who is Elon Musk?</h2><p>Elon Reeve Musk (born June 28, 1971, in Pretoria, South Africa) is an entrepreneur and business magnate. Founder/CEO of Tesla, SpaceX, X, xAI, and Neuralink.</p><h2>Early Life and Education</h2><p>Musk taught himself programming at 10 and sold his first video game at 12. He studied economics and physics at the University of Pennsylvania, then briefly started a PhD at Stanford before leaving for entrepreneurship.</p><h2>Career Highlights</h2><ul><li>1995 — Co-founded Zip2, sold to Compaq for $307M</li><li>1999 — Co-founded X.com (later PayPal), sold to eBay for $1.5B</li><li>2002 — Founded SpaceX</li><li>2004 — Joined Tesla as chairman, later CEO</li></ul><h2>Achievements and Controversies</h2><p><strong>Achievements:</strong> First private company to send humans to the ISS. Transformed the EV market. Net worth estimated above $200 billion.</p><p><strong>Controversies:</strong> SEC disputes, labour practice scrutiny, erratic social media behavior.</p><h2>Verdict</h2><p>Elon Musk is one of the most consequential entrepreneurs of the 21st century, with undeniable impact on electric vehicles, spaceflight, and satellite internet.</p>',
        ),
        array(
            'title'    => 'Top 5 Loan Apps in Nigeria (2026 Guide)',
            'category' => 'reports',
            'tags'     => array( 'loans', 'nigeria', 'fintech', 'quick loans' ),
            'content'  => '<h2>What Are Loan Apps?</h2><p>Loan apps offer quick unsecured personal loans — processed within minutes — without collateral or branch visits. They assess creditworthiness using alternative data.</p><h2>How They Work</h2><p>Applicants download, complete KYC, and submit a loan request. Algorithms assess risk instantly. Approved funds go directly to your bank account.</p><h2>Top 5 Loan Apps (2026)</h2><ul><li><strong>Carbon</strong> — Loans up to ₦1M, transparent fees</li><li><strong>FairMoney</strong> — Fast approvals, up to ₦3M, CBN-licensed MFB</li><li><strong>Branch</strong> — US-backed, no interest on first loan</li><li><strong>Aella Credit</strong> — Strong for salaried and healthcare workers</li><li><strong>Renmoney</strong> — Larger loans up to ₦6M, 24-month tenure</li></ul><h2>Pros and Cons</h2><p><strong>Pros:</strong> Fast disbursement, no collateral, available 24/7, builds credit history.</p><p><strong>Cons:</strong> High effective APR (30–100%), aggressive collection, short tenures.</p><h2>Verdict</h2><p>Use loan apps for short-term emergencies only. Always read the full fee schedule before accepting any offer and never borrow more than you can repay in the stated tenure.</p>',
        ),
        array(
            'title'    => 'Working at Flutterwave — Salary, Culture and Experience',
            'category' => 'salaries',
            'tags'     => array( 'flutterwave', 'salary', 'fintech', 'africa' ),
            'content'  => '<h2>What is Flutterwave?</h2><p>Flutterwave is a pan-African fintech founded in 2016, providing payment infrastructure for businesses across Africa. HQ in San Francisco with major ops in Lagos and Nairobi.</p><h2>How the Company Works</h2><p>Flutterwave operates as a B2B payments API, powering payment processing for banks and e-commerce. Raised over $474M with a $3B peak valuation.</p><h2>Salary Ranges (2026)</h2><ul><li>Software Engineer (Mid): ₦600K – ₦1.2M/month</li><li>Senior Engineer: ₦1.2M – ₦2.5M/month</li><li>Product Manager: ₦800K – ₦1.8M/month</li><li>DevOps Engineer: ₦1M – ₦2M/month</li></ul><h2>Pros and Cons of Working There</h2><p><strong>Pros:</strong> Competitive pay, pan-African exposure, fast-paced environment, USD options for senior roles.</p><p><strong>Cons:</strong> High-pressure environment, challenging work-life balance, high turnover in some teams.</p><h2>Verdict</h2><p>Flutterwave is a strong career accelerator for engineering and product professionals. Research team culture thoroughly before accepting an offer.</p>',
        ),
        array(
            'title'    => 'Piggyvest Review — Is It Safe to Save Money Here?',
            'category' => 'reviews',
            'tags'     => array( 'piggyvest', 'savings', 'investment', 'nigeria' ),
            'content'  => '<h2>What is PiggyVest?</h2><p>PiggyVest (formerly Piggybank.ng) is a Nigerian savings and investment platform founded in 2016. Over 4 million users automate their savings and access vetted investment opportunities.</p><h2>How It Works</h2><p>Users set automatic savings schedules (daily, weekly, monthly). Funds lock until a chosen withdrawal date. Also offers Flex Dollar savings in USD and Investify investments.</p><h2>Key Features</h2><ul><li>Automatic scheduled savings (PiggyBank, Safelock)</li><li>Up to 13% annual interest on locked savings</li><li>Dollar savings (Flex Dollar)</li><li>Investify — vetted investment deals</li></ul><h2>Pros and Cons</h2><p><strong>Pros:</strong> Forces savings discipline, competitive interest, long track record, SEC-registered.</p><p><strong>Cons:</strong> Cannot break locked savings early without 10% penalty, not a full bank.</p><h2>Verdict</h2><p>PiggyVest is one of the safest savings platforms in Nigeria. The lock-in feature works exactly as intended for disciplined long-term savers.</p>',
        ),
        array(
            'title'    => 'Kuda Bank — Full Company Overview and Review',
            'category' => 'companies',
            'tags'     => array( 'kuda', 'neobank', 'banking', 'nigeria' ),
            'content'  => '<h2>What is Kuda Bank?</h2><p>Kuda is a CBN-licensed digital-only bank headquartered in Lagos, founded in 2019. It offers free banking to Nigerians with a focus on millennials and Gen Z.</p><h2>How It Works</h2><p>Kuda operates entirely via its mobile app. Open an account in minutes, get a free Mastercard debit card, and access all banking features in-app.</p><h2>Key Features</h2><ul><li>25 free bank transfers per month</li><li>Free physical and virtual Mastercard</li><li>Budgeting and spending analytics</li><li>Overdraft up to ₦150,000</li></ul><h2>Pros and Cons</h2><p><strong>Pros:</strong> Genuinely free banking, beautiful app, real-time notifications, NDIC deposit insurance.</p><p><strong>Cons:</strong> 25 free transfers may not suit business users, no ATM cash deposit.</p><h2>Verdict</h2><p>Kuda is one of the best neobanks in Nigeria for personal banking. The free transfer model gives it a significant edge over legacy banks.</p>',
        ),
        array(
            'title'    => 'Average Software Developer Salary in Nigeria (2026 Data)',
            'category' => 'salaries',
            'tags'     => array( 'salary', 'developer', 'tech', 'nigeria' ),
            'content'  => '<h2>What Does a Software Developer Earn in Nigeria?</h2><p>Software developer salaries vary significantly by experience, company type, and whether the role is local or remote. Data aggregated from Glassdoor Nigeria, Jobberman, and direct surveys (2025–2026).</p><h2>How Salaries Are Structured</h2><p>Most Nigerian companies pay monthly base salaries in naira. Remote workers increasingly negotiate USD-denominated salaries paid via international channels.</p><h2>Salary Ranges by Experience (2026)</h2><ul><li>Intern (0–6 months): ₦50K – ₦150K/month</li><li>Junior (6mo–2yr): ₦200K – ₦450K/month</li><li>Mid-level (2–5yr): ₦500K – ₦1.1M/month</li><li>Senior (5yr+): ₦1.2M – ₦2.8M/month</li><li>Remote (USD, 3yr+): $3,000 – $8,000/month</li></ul><h2>Factors That Influence Pay</h2><p><strong>Higher pay:</strong> Remote roles, fintech/Web3 specialisation, cloud or mobile expertise, equity at funded startups.</p><p><strong>Lower pay:</strong> Non-tech industries, public sector, SMEs outside Lagos.</p><h2>Verdict</h2><p>Nigerian software developers are among the highest-paid professionals in the country. Specialising in cloud, mobile, or AI engineering significantly increases earning potential.</p>',
        ),
        array(
            'title'    => 'MTN Nigeria — Company Profile, Services and Revenue',
            'category' => 'companies',
            'tags'     => array( 'mtn', 'telecom', 'nigeria', 'mobile' ),
            'content'  => '<h2>What is MTN Nigeria?</h2><p>MTN Nigeria Communications Plc is the largest mobile telecommunications network in Nigeria, a subsidiary of the South Africa-based MTN Group, listed on the Nigerian Stock Exchange in 2019.</p><h2>How It Works</h2><p>MTN operates 2G, 3G, 4G LTE, and expanding 5G across Nigeria. Revenue comes from voice, data, MoMo mobile money, and enterprise solutions.</p><h2>Key Features</h2><ul><li>Largest subscriber base — over 76 million</li><li>Nationwide 4G coverage in major cities</li><li>MoMo — CBN-licensed mobile money platform</li><li>MTN Business for enterprise clients</li></ul><h2>Pros and Cons</h2><p><strong>Pros:</strong> Widest network coverage, strong rural penetration, diverse services, transparent financials as a listed company.</p><p><strong>Cons:</strong> Data prices high vs. regional peers, historical regulatory friction.</p><h2>Verdict</h2><p>MTN Nigeria is the dominant telco in the country. For most users it remains the most reliable option for network coverage, especially in rural areas.</p>',
        ),
    );

    foreach ( $posts as $pd ) {
        $term   = get_term_by( 'slug', $pd['category'], 'category' );
        $cat_id = $term ? (int) $term->term_id : 0;

        $post_id = wp_insert_post( array(
            'post_title'     => wp_strip_all_tags( $pd['title'] ),
            'post_content'   => $pd['content'],
            'post_status'    => 'publish',
            'post_author'    => 1,
            'post_category'  => $cat_id ? array( $cat_id ) : array(),
            'comment_status' => 'open',
            'meta_input'     => array(
                '_hpw_sample_post' => 1,
            ),
        ) );

        if ( is_wp_error( $post_id ) || ! $post_id ) continue;

        if ( ! empty( $pd['tags'] ) ) {
            wp_set_post_tags( $post_id, $pd['tags'], false );
        }

        if ( $placeholder_id ) {
            set_post_thumbnail( $post_id, $placeholder_id );
        }

        // ── Niche-specific sample reviews ───────────────────────────────────
        $review_sets = array(
            // Reviews niche
            'palmpay'       => array(
                array( 'author' => 'Chisom O.',     'email' => 'chisom.o@example.com',     'rating' => 5, 'content' => 'PalmPay is honestly the best fintech app I have used in Nigeria. Transfers are instant, no charges on most transactions, and the interface is clean. I moved completely from my traditional bank.' ),
                array( 'author' => 'Emmanuel A.',   'email' => 'emmy.a@example.com',       'rating' => 4, 'content' => 'Very solid app. Customer service can be slow sometimes but the product itself works. Cashback rewards are a nice plus. Would recommend to anyone tired of paying bank charges.' ),
                array( 'author' => 'Blessing I.',   'email' => 'blessing.i@example.com',   'rating' => 3, 'content' => 'Good for everyday transfers but I had an issue with a failed transaction that took 5 days to reverse. Support eventually resolved it. The app has improved a lot this year though.' ),
            ),
            // Fintech / general reviews
            'piggyvest'     => array(
                array( 'author' => 'Tunde M.',      'email' => 'tunde.m@example.com',      'rating' => 5, 'content' => 'PiggyVest basically taught me how to save. The lock feature is genius — you literally cannot touch your money until the date you set. I saved ₦800K in 8 months without even trying hard.' ),
                array( 'author' => 'Amaka N.',      'email' => 'amaka.n@example.com',      'rating' => 4, 'content' => 'Interest rates are better than most banks and the app is reliable. My only complaint is the 10% penalty if you break your savings early. But that is kind of the point so I respect it.' ),
                array( 'author' => 'David K.',      'email' => 'david.k@example.com',      'rating' => 5, 'content' => 'Been using PiggyVest for 3 years. Never had a failed withdrawal, always got my interest on time. Investify is a bit risky but the core savings product is excellent. SEC-registered is a big deal for trust.' ),
            ),
            // Company profiles
            'flutterwave'   => array(
                array( 'author' => 'Segun A.',      'email' => 'segun.a@example.com',      'rating' => 4, 'content' => 'Worked at Flutterwave for 2 years as a backend engineer. Pay is competitive and the exposure to pan-African payments is unmatched. Fast-paced but you grow quickly. 4 stars because work-life balance needs work.' ),
                array( 'author' => 'Fatima B.',     'email' => 'fatima.b@example.com',     'rating' => 3, 'content' => 'Great company to add to your CV. Management can be unpredictable and there is some politics at the senior level. Junior engineers are well taken care of though. The engineering culture is strong.' ),
                array( 'author' => 'Rasheed T.',    'email' => 'rasheed.t@example.com',    'rating' => 5, 'content' => 'The best job I have had in fintech. USD salary option for senior roles is a huge deal in today\'s economy. The team is talented, the product vision is clear, and there is real ownership over your work.' ),
            ),
            // Salary data posts
            'software'      => array(
                array( 'author' => 'Kelechi U.',    'email' => 'kelechi.u@example.com',    'rating' => 4, 'content' => 'The salary data here matches what I have seen in the market. Mid-level developers in Lagos at product companies typically earn ₦600K–₦1M. Remote USD roles have changed the game for senior devs completely.' ),
                array( 'author' => 'Grace M.',      'email' => 'grace.m@example.com',      'rating' => 5, 'content' => 'Accurate and up to date. I used this page when negotiating my offer last month and it gave me solid data points. The breakdown by experience level is exactly what I needed. More roles please!' ),
            ),
            // Company overviews
            'kuda'          => array(
                array( 'author' => 'Ifeanyi C.',    'email' => 'ifeanyi.c@example.com',    'rating' => 5, 'content' => 'Kuda has changed how I do banking. No maintenance fees, 25 free transfers, instant notifications. I have recommended it to literally everyone in my family. The overdraft feature saved me twice.' ),
                array( 'author' => 'Ngozi P.',      'email' => 'ngozi.p@example.com',      'rating' => 4, 'content' => 'Great neobank. The app occasionally slows down on weekends but that is a minor issue. The budgeting feature is underrated — it actually helped me see where I was wasting money. Solid 4 stars.' ),
                array( 'author' => 'Ahmed S.',      'email' => 'ahmed.s@example.com',      'rating' => 3, 'content' => 'Good for personal use but I switched to a traditional bank for my business account because Kuda has limits that don\'t work for business volume. As a personal account it is excellent though.' ),
            ),
            'mtn'           => array(
                array( 'author' => 'Oluwaseun R.',  'email' => 'oluwaseun.r@example.com',  'rating' => 4, 'content' => 'MTN network is the most reliable for travel across Nigeria. Traveled from Lagos to Enugu to Kano — had coverage everywhere. Data plans are a bit pricey but the reliability justifies it.' ),
                array( 'author' => 'Zainab H.',     'email' => 'zainab.h@example.com',     'rating' => 3, 'content' => 'Good network but data prices need to come down. MoMo is a great product though, really useful for transfers without a bank account. Customer care is hit or miss depending on the agent.' ),
            ),
            // Biography posts
            'biography'     => array(
                array( 'author' => 'Adaeze L.',     'email' => 'adaeze.l@example.com',     'rating' => 5, 'content' => 'Well-researched and balanced profile. Got both sides — the achievements and the controversies. This is the kind of factual writeup I trust. No clickbait, just information.' ),
                array( 'author' => 'Victor O.',     'email' => 'victor.o@example.com',     'rating' => 4, 'content' => 'Good overview. Would love to see a timeline format added in future. But the content itself is accurate and well-sourced. Shared it with my study group.' ),
            ),
        );

        // Match post to a review set by checking title keywords
        $title_lower = strtolower( $pd['title'] );
        $matched_set = null;
        foreach ( $review_sets as $keyword => $revs ) {
            if ( strpos( $title_lower, $keyword ) !== false ) {
                $matched_set = $revs;
                break;
            }
        }
        // Fallback: use category-based default reviews
        if ( ! $matched_set ) {
            $cat_defaults = array(
                'biography' => $review_sets['biography'],
                'salaries'  => $review_sets['software'],
            );
            $matched_set = isset( $cat_defaults[ $pd['category'] ] ) ? $cat_defaults[ $pd['category'] ] : null;
        }

        if ( $matched_set ) {
            $rating_total = 0;
            $rating_count = 0;

            foreach ( $matched_set as $rev ) {
                $cid = wp_insert_comment( array(
                    'comment_post_ID'      => $post_id,
                    'comment_author'       => $rev['author'],
                    'comment_author_email' => $rev['email'],
                    'comment_content'      => $rev['content'],
                    'comment_type'         => 'review',
                    'comment_approved'     => 1,
                    'comment_date'         => gmdate( 'Y-m-d H:i:s', time() - wp_rand( 86400, 2592000 ) ),
                ) );

                if ( $cid && ! is_wp_error( $cid ) ) {
                    update_comment_meta( $cid, 'rating', (int) $rev['rating'] );
                    $rating_total += (int) $rev['rating'];
                    $rating_count++;
                }
            }

            if ( $rating_count > 0 ) {
                update_post_meta( $post_id, '_cached_rating', round( $rating_total / $rating_count, 1 ) );
            }
        }
    }
}


// =========================================
// LIVE SEARCH AJAX
// =========================================

function holyprofweb_live_search_ajax() {
    // Verify nonce
    $nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'holyprofweb_live_search' ) ) {
        wp_send_json_error( 'Invalid nonce', 403 );
    }

    // Get and validate search term
    $term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
    if ( mb_strlen( $term ) < 2 ) {
        wp_send_json_error( 'Query too short' );
    }

    // Query posts
    $post_query = new WP_Query( array(
        'posts_per_page' => 6,
        's'              => $term,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ) );

    $posts = array();
    if ( $post_query->have_posts() ) {
        while ( $post_query->have_posts() ) {
            $post_query->the_post();
            $post_id = get_the_ID();

            // Category name
            $cats      = get_the_category( $post_id );
            $cat_name  = ! empty( $cats ) ? $cats[0]->name : '';

            // Thumbnail URL
            $thumb_url = get_the_post_thumbnail_url( $post_id, 'holyprofweb-thumb' );
            if ( ! $thumb_url ) {
                $thumb_url = holyprofweb_placeholder_url();
            }

            // Excerpt — 80 chars
            $raw_excerpt = get_the_excerpt();
            if ( mb_strlen( $raw_excerpt ) > 80 ) {
                $raw_excerpt = mb_substr( $raw_excerpt, 0, 77 ) . '...';
            }

            $posts[] = array(
                'id'            => $post_id,
                'title'         => get_the_title(),
                'url'           => get_permalink(),
                'excerpt'       => $raw_excerpt,
                'category_name' => $cat_name,
                'thumb_url'     => $thumb_url,
            );
        }
        wp_reset_postdata();
    }

    // Query categories
    $cat_terms = get_terms( array(
        'taxonomy'   => 'category',
        'search'     => $term,
        'number'     => 4,
        'hide_empty' => true,
        'exclude'    => holyprofweb_get_category_exclusions(),
    ) );

    $categories = array();
    if ( ! is_wp_error( $cat_terms ) && ! empty( $cat_terms ) ) {
        foreach ( $cat_terms as $cat ) {
            $categories[] = array(
                'name'  => $cat->name,
                'url'   => get_category_link( $cat->term_id ),
                'count' => (int) $cat->count,
            );
        }
    }

    $suggestions = array();

    wp_send_json_success( compact( 'posts', 'categories', 'suggestions' ) );
}
add_action( 'wp_ajax_holyprofweb_search',        'holyprofweb_live_search_ajax' );
add_action( 'wp_ajax_nopriv_holyprofweb_search', 'holyprofweb_live_search_ajax' );


// =========================================
// REVIEW SYSTEM (separate from WP comments)
// =========================================

/**
 * Get approved reviews for a post (comment_type = 'review').
 */
function holyprofweb_get_post_reviews( $post_id, $number = 20 ) {
    return get_comments( array(
        'post_id' => $post_id,
        'status'  => 'approve',
        'type'    => 'review',
        'number'  => $number,
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
    ) );
}

/**
 * Count approved reviews for a post.
 */
function holyprofweb_get_review_count( $post_id ) {
    return (int) get_comments( array(
        'post_id' => $post_id,
        'status'  => 'approve',
        'type'    => 'review',
        'count'   => true,
    ) );
}

function holyprofweb_count_published_posts_in_category_tree( $slug ) {
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        return 0;
    }

    $term_ids = array( (int) $term->term_id );
    $children = get_term_children( (int) $term->term_id, 'category' );
    if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
        $term_ids = array_merge( $term_ids, array_map( 'intval', $children ) );
    }

    $query = new WP_Query( array(
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'no_found_rows'          => false,
        'ignore_sticky_posts'    => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'category__in'           => array_values( array_unique( array_filter( $term_ids ) ) ),
    ) );

    return max( 0, (int) $query->found_posts );
}

function holyprofweb_get_display_count( $count ) {
    $count = (int) $count;
    if ( $count <= 0 ) {
        return 0;
    }

    if ( $count < 100 ) {
        return $count * 10;
    }

    return $count;
}

function holyprofweb_format_display_count( $count ) {
    return number_format_i18n( holyprofweb_get_display_count( $count ) );
}

function holyprofweb_get_front_stat_display_count( $count, $mode = 'real' ) {
    $count = max( 0, (int) $count );

    if ( 'posts' === $mode || 'reviews' === $mode || 'companies' === $mode ) {
        if ( $count <= 0 ) {
            return 0;
        }
        if ( $count < 100 ) {
            return $count * 10;
        }
    }

    if ( 'companies' === $mode ) {
        if ( $count < 120 ) {
            return 120;
        }
    }

    return $count;
}

function holyprofweb_get_archive_posts_per_page() {
    $hpw_value = absint( get_option( 'hpw_posts_per_page', 0 ) );
    if ( $hpw_value > 0 ) {
        return $hpw_value;
    }

    return max( 1, (int) get_option( 'posts_per_page', 10 ) );
}

function holyprofweb_publish_overdue_drafts_now( $limit = 200 ) {
    $existing_queue = holyprofweb_get_draft_publish_queue();
    $drafts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'draft',
        'posts_per_page' => max( 1, min( 500, (int) $limit ) ),
        'orderby'        => 'date',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );
    $published = 0;

    foreach ( $drafts as $post ) {
        $queue_item = isset( $existing_queue[ $post->ID ] ) && is_array( $existing_queue[ $post->ID ] ) ? $existing_queue[ $post->ID ] : array();
        if ( ! holyprofweb_should_force_publish_draft( $post, $queue_item ) ) {
            continue;
        }

        holyprofweb_attempt_draft_repairs( $post->ID, $post );
        holyprofweb_publish_post_now( $post->ID );
        $published++;
    }

    return $published;
}

function holyprofweb_get_draft_debug_rows( $limit = 25 ) {
    $existing_queue = holyprofweb_get_draft_publish_queue();
    $drafts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'draft',
        'posts_per_page' => max( 1, min( 200, (int) $limit ) ),
        'orderby'        => 'date',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );
    $rows = array();

    foreach ( $drafts as $post ) {
        $queue_item = isset( $existing_queue[ $post->ID ] ) && is_array( $existing_queue[ $post->ID ] ) ? $existing_queue[ $post->ID ] : array();
        $attempts   = isset( $queue_item['attempts'] ) ? (int) $queue_item['attempts'] : 0;
        $remaining  = max( 0, holyprofweb_get_draft_force_publish_attempts() - $attempts );
        $first_seen = isset( $queue_item['first_seen'] ) ? absint( $queue_item['first_seen'] ) : 0;

        if ( ! $first_seen ) {
            $first_seen = strtotime( (string) $post->post_date_gmt . ' GMT' );
        }

        $rows[] = array(
            'post_id'         => (int) $post->ID,
            'title'           => get_the_title( $post->ID ),
            'attempts'        => $attempts,
            'checks_remaining'=> $remaining,
            'force_ready'     => holyprofweb_should_force_publish_draft( $post, $queue_item ),
            'needs'           => (array) ( $queue_item['needs'] ?? array() ),
            'last_checked'    => ! empty( $queue_item['last_checked'] ) ? (int) $queue_item['last_checked'] : 0,
            'first_seen'      => $first_seen,
        );
    }

    return $rows;
}

function holyprofweb_get_draft_minimum_words() {
    return max( 650, absint( get_option( 'hpw_ai_minimum_words', 700 ) ) );
}

function holyprofweb_get_draft_publish_floor_words() {
    return max( 260, min( 420, holyprofweb_get_draft_minimum_words() - 220 ) );
}

function holyprofweb_get_comment_count_by_type( $post_id, $comment_type ) {
    return (int) get_comments( array(
        'post_id' => $post_id,
        'status'  => 'approve',
        'type'    => $comment_type,
        'count'   => true,
    ) );
}

function holyprofweb_get_post_salary_submissions( $post_id, $number = 20 ) {
    return get_comments( array(
        'post_id' => $post_id,
        'status'  => 'approve',
        'type'    => 'salary_submission',
        'number'  => $number,
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
    ) );
}

function holyprofweb_has_comment_from_email( $post_id, $email, $comment_type ) {
    if ( ! $post_id || ! is_email( $email ) || ! $comment_type ) {
        return false;
    }

    $existing = get_comments( array(
        'post_id'      => $post_id,
        'author_email' => $email,
        'type'         => $comment_type,
        'status'       => 'all',
        'number'       => 1,
        'count'        => true,
    ) );

    return (int) $existing > 0;
}

function holyprofweb_is_comment_verified( $comment_id ) {
    return (bool) get_comment_meta( $comment_id, 'hpw_verified', true );
}

function holyprofweb_get_best_review_ids( $post_id, $limit = 2 ) {
    $reviews = holyprofweb_get_post_reviews( $post_id, 30 );
    if ( empty( $reviews ) ) {
        return array();
    }

    usort(
        $reviews,
        static function( $a, $b ) {
            $a_verified = holyprofweb_is_comment_verified( $a->comment_ID ) ? 1 : 0;
            $b_verified = holyprofweb_is_comment_verified( $b->comment_ID ) ? 1 : 0;
            if ( $a_verified !== $b_verified ) {
                return $b_verified <=> $a_verified;
            }

            $a_rating = (int) get_comment_meta( $a->comment_ID, 'rating', true );
            $b_rating = (int) get_comment_meta( $b->comment_ID, 'rating', true );
            if ( $a_rating !== $b_rating ) {
                return $b_rating <=> $a_rating;
            }

            return strtotime( $b->comment_date_gmt ) <=> strtotime( $a->comment_date_gmt );
        }
    );

    return array_slice( wp_list_pluck( $reviews, 'comment_ID' ), 0, $limit );
}

/**
 * Get average rating for a post from reviews (comment_type = 'review').
 */
function holyprofweb_get_post_rating( $post_id ) {
    $rating_override = get_post_meta( $post_id, '_hpw_rating_override', true );
    if ( '' !== (string) $rating_override && is_numeric( $rating_override ) ) {
        return round( max( 0, min( 5, (float) $rating_override ) ), 1 );
    }

    $reviews = get_comments( array(
        'post_id' => $post_id,
        'status'  => 'approve',
        'type'    => 'review',
        'number'  => 500,
    ) );

    if ( empty( $reviews ) ) return 0;

    $ratings = array_filter( array_map( function( $c ) {
        $r = get_comment_meta( $c->comment_ID, 'rating', true );
        return $r ? (int) $r : 0;
    }, $reviews ) );

    if ( empty( $ratings ) ) return 0;

    return round( array_sum( $ratings ) / count( $ratings ), 1 );
}

function holyprofweb_get_verdict_options() {
    return array(
        ''              => array( 'label' => __( 'Auto detect', 'holyprofweb' ),   'class' => '' ),
        'legit'         => array( 'label' => __( 'Legit', 'holyprofweb' ),         'class' => 'verdict-badge--legit' ),
        'complications' => array( 'label' => __( 'Complications', 'holyprofweb' ), 'class' => 'verdict-badge--caution' ),
        'caution'       => array( 'label' => __( 'Caution', 'holyprofweb' ),       'class' => 'verdict-badge--caution' ),
        'scam'          => array( 'label' => __( 'Scam Alert', 'holyprofweb' ),    'class' => 'verdict-badge--scam' ),
    );
}

/**
 * AJAX: Submit a review (stored as wp_comment with type='review').
 */
function holyprofweb_submit_review_ajax() {
    ob_start();

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'holyprofweb_submit_review' ) ) {
        ob_clean();
        wp_send_json_error( 'Security check failed.' );
    }

    $post_id  = isset( $_POST['post_id'] )         ? (int) $_POST['post_id']                                          : 0;
    $name     = isset( $_POST['reviewer_name'] )   ? sanitize_text_field( wp_unslash( $_POST['reviewer_name'] ) )    : '';
    $email    = isset( $_POST['reviewer_email'] )  ? sanitize_email( wp_unslash( $_POST['reviewer_email'] ) )        : '';
    $rating   = isset( $_POST['rating'] )          ? min( 5, max( 1, (int) $_POST['rating'] ) )                      : 0;
    $content  = isset( $_POST['review_content'] )  ? sanitize_textarea_field( wp_unslash( $_POST['review_content'] ) ) : '';
    $site_url = current_user_can( 'manage_options' ) && isset( $_POST['site_url'] ) ? esc_url_raw( wp_unslash( $_POST['site_url'] ) ) : '';
    $reviewer_type   = isset( $_POST['reviewer_type'] ) ? sanitize_key( wp_unslash( $_POST['reviewer_type'] ) ) : '';
    $company_role    = isset( $_POST['company_role'] ) ? sanitize_text_field( wp_unslash( $_POST['company_role'] ) ) : '';
    $company_location = isset( $_POST['company_location'] ) ? sanitize_text_field( wp_unslash( $_POST['company_location'] ) ) : '';
    $salary_range    = isset( $_POST['salary_range'] ) ? sanitize_text_field( wp_unslash( $_POST['salary_range'] ) ) : '';
    $interview_stage = isset( $_POST['interview_stage'] ) ? sanitize_text_field( wp_unslash( $_POST['interview_stage'] ) ) : '';
    $experience_issue = isset( $_POST['experience_issue'] ) ? sanitize_key( wp_unslash( $_POST['experience_issue'] ) ) : '';
    $content  = holyprofweb_strip_public_urls( $content );
    $is_company_post = holyprofweb_post_in_category_tree( $post_id, 'companies' );

    if ( ! $post_id || ! get_post( $post_id ) ) {
        wp_send_json_error( 'Invalid post.' );
    }
    if ( ! $name ) {
        wp_send_json_error( 'Please enter your name.' );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Please enter a valid email.' );
    }
    if ( ! $rating ) {
        wp_send_json_error( 'Please select a star rating.' );
    }
    $min_length = max( 10, (int) get_option( 'hpw_review_min_length', 20 ) );
    if ( mb_strlen( $content ) < $min_length ) {
        wp_send_json_error( sprintf( 'Review must be at least %d characters.', $min_length ) );
    }
    if ( $is_company_post && ! $reviewer_type ) {
        wp_send_json_error( 'Please choose how you know this company.' );
    }

    $spam_words = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) get_option( 'hpw_review_spam_words', '' ) ) ) );
    foreach ( $spam_words as $word ) {
        if ( $word && false !== stripos( $content, $word ) ) {
            wp_send_json_error( 'Your review needs manual moderation before it can be accepted.' );
        }
    }

    if ( holyprofweb_has_comment_from_email( $post_id, $email, 'review' ) ) {
        wp_send_json_error( 'You already submitted a review for this post.' );
    }

    $comment_id = wp_insert_comment( array(
        'comment_post_ID'      => $post_id,
        'comment_author'       => $name,
        'comment_author_email' => $email,
        'comment_content'      => $content,
        'comment_type'         => 'review',
        'comment_approved'     => get_option( 'hpw_review_auto_approve', 0 ) ? 1 : 0,
        'comment_date'         => current_time( 'mysql' ),
        'comment_date_gmt'     => current_time( 'mysql', 1 ),
        'comment_agent'        => 'HolyprofWeb Review',
    ) );

    if ( ! $comment_id || is_wp_error( $comment_id ) ) {
        ob_clean();
        wp_send_json_error( 'Could not save review. Please try again.' );
    }

    update_comment_meta( $comment_id, 'rating', $rating );
    update_comment_meta( $comment_id, 'hpw_submission_category', 'review' );
    if ( $site_url ) {
        update_comment_meta( $comment_id, 'site_url', esc_url_raw( $site_url ) );
    }
    if ( $is_company_post ) {
        $allowed_types = array(
            'staff',
            'former-staff',
            'interview-candidate',
            'partner-vendor',
            'customer-client',
            'affected-user',
            'scam-reporter',
            'job-seeker',
            'other',
        );

        if ( in_array( $reviewer_type, $allowed_types, true ) ) {
            update_comment_meta( $comment_id, 'reviewer_type', $reviewer_type );
        }
        if ( $company_role ) {
            update_comment_meta( $comment_id, 'company_role', $company_role );
        }
        if ( $company_location ) {
            update_comment_meta( $comment_id, 'company_location', $company_location );
        }
        if ( $salary_range ) {
            update_comment_meta( $comment_id, 'salary_range', $salary_range );
        }
        if ( $interview_stage ) {
            update_comment_meta( $comment_id, 'interview_stage', $interview_stage );
        }
        if ( $experience_issue ) {
            update_comment_meta( $comment_id, 'experience_issue', $experience_issue );
        }
    }

    // Recompute and cache rating
    $new_rating = holyprofweb_get_post_rating( $post_id );
    update_post_meta( $post_id, '_cached_rating', $new_rating );

    ob_clean();
    wp_send_json_success( array(
        'message'    => get_option( 'hpw_review_auto_approve', 0 ) ? 'Thank you for your review!' : 'Thank you. Your review is awaiting moderation.',
        'rating'     => $rating,
        'name'       => esc_html( $name ),
        'content'    => esc_html( $content ),
        'site_url'   => esc_url( $site_url ),
        'avg_rating' => $new_rating,
        'approved'   => get_option( 'hpw_review_auto_approve', 0 ) ? 1 : 0,
    ) );
}
add_action( 'wp_ajax_holyprofweb_submit_review',        'holyprofweb_submit_review_ajax' );
add_action( 'wp_ajax_nopriv_holyprofweb_submit_review', 'holyprofweb_submit_review_ajax' );

function holyprofweb_submit_salary_ajax() {
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'holyprofweb_submit_salary' ) ) {
        wp_send_json_error( 'Security check failed.' );
    }

    $post_id   = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $name      = isset( $_POST['submitter_name'] ) ? sanitize_text_field( wp_unslash( $_POST['submitter_name'] ) ) : '';
    $email     = isset( $_POST['submitter_email'] ) ? sanitize_email( wp_unslash( $_POST['submitter_email'] ) ) : '';
    $company   = isset( $_POST['salary_company'] ) ? sanitize_text_field( wp_unslash( $_POST['salary_company'] ) ) : '';
    $role      = isset( $_POST['salary_role'] ) ? sanitize_text_field( wp_unslash( $_POST['salary_role'] ) ) : '';
    $salary    = isset( $_POST['salary_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['salary_amount'] ) ) : '';
    $location  = isset( $_POST['salary_location'] ) ? sanitize_text_field( wp_unslash( $_POST['salary_location'] ) ) : '';
    $currency  = isset( $_POST['salary_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['salary_currency'] ) ) : '';
    $work_life = isset( $_POST['salary_work_life'] ) ? min( 5, max( 1, (float) $_POST['salary_work_life'] ) ) : 0;

    if ( ! $post_id || ! get_post( $post_id ) ) {
        wp_send_json_error( 'Invalid post.' );
    }
    if ( ! $name ) {
        wp_send_json_error( 'Please enter your name.' );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Please enter a valid email.' );
    }
    if ( ! $company ) {
        wp_send_json_error( 'Please enter the company name.' );
    }
    if ( ! $role ) {
        wp_send_json_error( 'Please enter the role.' );
    }
    if ( ! $salary ) {
        wp_send_json_error( 'Please enter the salary.' );
    }

    if ( holyprofweb_has_comment_from_email( $post_id, $email, 'salary_submission' ) ) {
        wp_send_json_error( 'You already submitted salary data for this page.' );
    }

    $content_parts = array(
        'Company: ' . $company,
        'Role: ' . $role,
        'Salary: ' . $salary . ( $currency ? ' ' . $currency : '' ),
    );
    if ( $location ) {
        $content_parts[] = 'Location: ' . $location;
    }
    if ( $work_life ) {
        $content_parts[] = 'Work-life score: ' . number_format( $work_life, 1 ) . '/5';
    }

    $comment_id = wp_insert_comment( array(
        'comment_post_ID'      => $post_id,
        'comment_author'       => $name,
        'comment_author_email' => $email,
        'comment_content'      => implode( "\n", $content_parts ),
        'comment_type'         => 'salary_submission',
        'comment_approved'     => 0,
        'comment_date'         => current_time( 'mysql' ),
        'comment_date_gmt'     => current_time( 'mysql', 1 ),
        'comment_agent'        => 'HolyprofWeb Salary Submission',
    ) );

    if ( ! $comment_id || is_wp_error( $comment_id ) ) {
        wp_send_json_error( 'Could not save salary submission. Please try again.' );
    }

    update_comment_meta( $comment_id, 'salary_company', $company );
    update_comment_meta( $comment_id, 'salary_role', $role );
    update_comment_meta( $comment_id, 'salary_amount', $salary );
    update_comment_meta( $comment_id, 'salary_location', $location );
    update_comment_meta( $comment_id, 'salary_currency', $currency );
    update_comment_meta( $comment_id, 'salary_work_life', $work_life );
    update_comment_meta( $comment_id, 'hpw_submission_category', 'salary_submission' );

    wp_send_json_success( array(
        'message' => 'Thank you. Your salary submission is now in admin review.',
    ) );
}
add_action( 'wp_ajax_holyprofweb_submit_salary', 'holyprofweb_submit_salary_ajax' );
add_action( 'wp_ajax_nopriv_holyprofweb_submit_salary', 'holyprofweb_submit_salary_ajax' );

function holyprofweb_strip_public_urls( $content ) {
    $content = preg_replace( '#https?://[^\s<]+#iu', '', (string) $content );
    $content = preg_replace( '#www\.[^\s<]+#iu', '', (string) $content );
    $content = preg_replace( '/\s{2,}/', ' ', (string) $content );
    return trim( (string) $content );
}

function holyprofweb_lock_comment_links( $commentdata ) {
    if ( current_user_can( 'manage_options' ) ) {
        return $commentdata;
    }

    if ( isset( $commentdata['comment_content'] ) ) {
        $commentdata['comment_content'] = holyprofweb_strip_public_urls( $commentdata['comment_content'] );
    }

    if ( isset( $commentdata['comment_author_url'] ) ) {
        $commentdata['comment_author_url'] = '';
    }

    return $commentdata;
}
add_filter( 'preprocess_comment', 'holyprofweb_lock_comment_links' );

/**
 * Render star display HTML (non-interactive).
 *
 * @param float $rating
 * @param int   $max
 * @return string
 */
function holyprofweb_render_stars( $rating, $max = 5 ) {
    $rating = (float) $rating;
    $out    = '<span class="star-display" aria-label="' . esc_attr( sprintf( 'Rating: %s out of %d', $rating, $max ) ) . '">';

    for ( $i = 1; $i <= $max; $i++ ) {
        if ( $rating >= $i ) {
            $out .= '<span class="star star-full">&#9733;</span>';
        } elseif ( $rating >= $i - 0.5 ) {
            $out .= '<span class="star star-half">&#9733;</span>';
        } else {
            $out .= '<span class="star star-empty">&#9733;</span>';
        }
    }

    $out .= '<span class="star-score">' . esc_html( $rating ) . '</span></span>';
    return $out;
}


// =========================================
// REACTIONS SYSTEM
// =========================================

function holyprofweb_get_reaction_cookie_name( $post_id ) {
    return 'hpw_reaction_' . (int) $post_id;
}

function holyprofweb_reaction_ajax() {
    // Verify nonce
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'holyprofweb_reaction' ) ) {
        wp_send_json_error( 'Invalid nonce', 403 );
    }

    $post_id  = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $reaction = isset( $_POST['reaction'] ) ? sanitize_key( wp_unslash( $_POST['reaction'] ) ) : '';
    $allowed  = array( 'legit', 'unsure', 'scam' );

    if ( ! $post_id || ! in_array( $reaction, $allowed, true ) ) {
        wp_send_json_error( 'Invalid request' );
    }

    $cookie_name = holyprofweb_get_reaction_cookie_name( $post_id );
    $existing    = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_key( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';

    if ( $existing && in_array( $existing, $allowed, true ) ) {
        if ( $existing === $reaction ) {
            wp_send_json_success( array(
                'reaction' => $reaction,
                'count'    => (int) get_post_meta( $post_id, '_reaction_' . $reaction, true ),
                'locked'   => true,
            ) );
        }

        $existing_count = max( 0, (int) get_post_meta( $post_id, '_reaction_' . $existing, true ) - 1 );
        update_post_meta( $post_id, '_reaction_' . $existing, $existing_count );
    }

    $meta_key = '_reaction_' . $reaction;
    $count    = (int) get_post_meta( $post_id, $meta_key, true );
    $count++;
    update_post_meta( $post_id, $meta_key, $count );

    setcookie( $cookie_name, $reaction, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
    $_COOKIE[ $cookie_name ] = $reaction;

    wp_send_json_success( array( 'reaction' => $reaction, 'count' => $count, 'locked' => true ) );
}
add_action( 'wp_ajax_holyprofweb_reaction',        'holyprofweb_reaction_ajax' );
add_action( 'wp_ajax_nopriv_holyprofweb_reaction', 'holyprofweb_reaction_ajax' );

/**
 * Get reaction counts for a post.
 *
 * @param int $post_id
 * @return array
 */
function holyprofweb_get_reactions( $post_id ) {
    return array(
        'legit'   => (int) get_post_meta( $post_id, '_reaction_legit', true ),
        'unsure'  => (int) get_post_meta( $post_id, '_reaction_unsure', true ),
        'scam'    => (int) get_post_meta( $post_id, '_reaction_scam',    true ),
    );
}


// =========================================
// AUTO-GENERATE FEATURED IMAGE
// =========================================

function holyprofweb_get_post_source_url( $post_id, $post = null ) {
    $keys = array(
        '_hpw_source_url',
        '_hpw_site_url',
        '_hpw_external_url',
        'source_url',
        'site_url',
        'url',
        'link',
    );

    foreach ( $keys as $key ) {
        $value = holyprofweb_normalize_possible_url( get_post_meta( $post_id, $key, true ) );
        if ( $value && filter_var( $value, FILTER_VALIDATE_URL ) && ! holyprofweb_is_disallowed_source_domain( $value ) ) {
            return esc_url_raw( $value );
        }
    }

    $post = $post ?: get_post( $post_id );
    if ( ! $post ) {
        return '';
    }

    if ( preg_match_all( '#https?://[^\s"\']+#i', $post->post_content, $matches ) ) {
        foreach ( (array) $matches[0] as $candidate ) {
            $candidate = holyprofweb_normalize_possible_url( $candidate );
            if ( filter_var( $candidate, FILTER_VALIDATE_URL ) && ! holyprofweb_is_disallowed_source_domain( $candidate ) ) {
                return esc_url_raw( $candidate );
            }
        }
    }

    $url_like_text = wp_strip_all_tags( $post->post_title . ' ' . $post->post_content );
    if ( preg_match_all( '#\b(?:www\.)?[a-z0-9][a-z0-9\-\.]+\.[a-z]{2,}(?:/[^\s"\']*)?#i', $url_like_text, $matches ) ) {
        foreach ( (array) $matches[0] as $candidate ) {
            $candidate = holyprofweb_normalize_possible_url( $candidate );
            if ( filter_var( $candidate, FILTER_VALIDATE_URL ) && ! holyprofweb_is_disallowed_source_domain( $candidate ) ) {
                return esc_url_raw( $candidate );
            }
        }
    }

    return '';
}

function holyprofweb_backfill_source_url_meta( $post_id, $post = null ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 ) {
        return '';
    }

    $saved = (string) get_post_meta( $post_id, '_hpw_source_url', true );
    if ( $saved && filter_var( $saved, FILTER_VALIDATE_URL ) && ! holyprofweb_is_disallowed_source_domain( $saved ) ) {
        return esc_url_raw( $saved );
    }

    $post       = $post ?: get_post( $post_id );
    $source_url = holyprofweb_get_post_source_url( $post_id, $post );

    if ( $source_url ) {
        update_post_meta( $post_id, '_hpw_source_url', esc_url_raw( $source_url ) );
    }

    return $source_url ? esc_url_raw( $source_url ) : '';
}

function holyprofweb_get_post_source_status( $post_id ) {
    $saved = (string) get_post_meta( $post_id, '_hpw_source_url', true );
    if ( $saved && filter_var( $saved, FILTER_VALIDATE_URL ) && ! holyprofweb_is_disallowed_source_domain( $saved ) ) {
        return array(
            'status' => 'saved',
            'url'    => esc_url_raw( $saved ),
            'label'  => __( 'Saved', 'holyprofweb' ),
        );
    }

    $detected = holyprofweb_get_post_source_url( $post_id );
    if ( $detected ) {
        return array(
            'status' => 'inferred',
            'url'    => esc_url_raw( $detected ),
            'label'  => __( 'Detected', 'holyprofweb' ),
        );
    }

    return array(
        'status' => 'missing',
        'url'    => '',
        'label'  => __( 'Missing', 'holyprofweb' ),
    );
}

function holyprofweb_get_review_verdict( $post_id ) {
    $override = trim( (string) get_post_meta( $post_id, '_hpw_verdict_override', true ) );
    if ( $override ) {
        $map = holyprofweb_get_verdict_options();
        if ( isset( $map[ $override ] ) ) {
            return $map[ $override ];
        }
    }

    $cat_slugs = wp_list_pluck( get_the_category( $post_id ), 'slug' );
    $title     = strtolower( holyprofweb_get_decoded_post_title( $post_id ) );
    $excerpt   = strtolower( holyprofweb_get_decoded_post_excerpt( $post_id ) );
    $content_raw = (string) get_post_field( 'post_content', $post_id );
    $content_raw = preg_replace( '/<!-- HPW-AUTO-CONTENT:START -->.*?<!-- HPW-AUTO-CONTENT:END -->/si', '', $content_raw );
    $content   = strtolower( wp_strip_all_tags( $content_raw ) );
    $haystack  = $title . ' ' . $excerpt . ' ' . $content;

    if ( array_intersect( array( 'reports', 'scam-reports', 'user-complaints', 'scam-legit' ), $cat_slugs ) ) {
        if ( false !== strpos( $haystack, 'scam' ) || false !== strpos( $haystack, 'fraud' ) || false !== strpos( $haystack, 'fake' ) ) {
            return array( 'label' => 'Scam Alert', 'class' => 'verdict-badge--scam' );
        }
        return array( 'label' => 'Caution', 'class' => 'verdict-badge--caution' );
    }

    if ( false !== strpos( $haystack, 'scam' ) || false !== strpos( $haystack, 'fraud' ) || false !== strpos( $haystack, 'fake' ) ) {
        return array( 'label' => 'Scam Alert', 'class' => 'verdict-badge--scam' );
    }

    if ( false !== strpos( $haystack, 'safe' ) || false !== strpos( $haystack, 'legit' ) || false !== strpos( $haystack, 'trusted' ) ) {
        return array( 'label' => 'Legit', 'class' => 'verdict-badge--legit' );
    }

    if ( false !== strpos( $haystack, 'warning' ) || false !== strpos( $haystack, 'complaint' ) || false !== strpos( $haystack, 'caution' ) ) {
        return array( 'label' => 'Caution', 'class' => 'verdict-badge--caution' );
    }

    return array( 'label' => 'Caution', 'class' => 'verdict-badge--caution' );
}

function holyprofweb_extract_domain( $url ) {
    if ( ! $url ) {
        return '';
    }

    $host = wp_parse_url( $url, PHP_URL_HOST );
    if ( ! $host ) {
        return '';
    }

    return preg_replace( '/^www\./i', '', strtolower( $host ) );
}

function holyprofweb_fetch_og_image_url( $source_url ) {
    if ( ! $source_url || ! get_option( 'hpw_enable_remote_image_fetch', 1 ) ) {
        return '';
    }

    $response = wp_remote_get(
        $source_url,
        array(
            'timeout'     => 6,
            'redirection' => 3,
        )
    );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return '';
    }

    $body = wp_remote_retrieve_body( $response );
    if ( ! $body ) {
        return '';
    }

    if ( preg_match( '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $matches ) ) {
        return esc_url_raw( $matches[1] );
    }
    if ( preg_match( '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $body, $matches ) ) {
        return esc_url_raw( $matches[1] );
    }

    return '';
}

function holyprofweb_fetch_remote_page_body( $source_url ) {
    if ( ! $source_url || ! get_option( 'hpw_enable_remote_image_fetch', 1 ) ) {
        return '';
    }

    $cache_key = '_holyprofweb_cached_remote_page_' . md5( $source_url );
    $cached = get_transient( $cache_key );
    if ( false !== $cached ) {
        return is_string( $cached ) ? $cached : '';
    }

    $response = wp_remote_get(
        $source_url,
        array(
            'timeout'     => 8,
            'redirection' => 4,
        )
    );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        set_transient( $cache_key, '', 2 * HOUR_IN_SECONDS );
        return '';
    }

    $body = (string) wp_remote_retrieve_body( $response );
    set_transient( $cache_key, $body, 6 * HOUR_IN_SECONDS );
    return $body;
}

function holyprofweb_resolve_remote_asset_url( $candidate, $base_url ) {
    $candidate = trim( (string) $candidate );
    if ( '' === $candidate ) {
        return '';
    }

    if ( 0 === strpos( $candidate, 'data:' ) || 0 === strpos( $candidate, 'javascript:' ) ) {
        return '';
    }

    if ( preg_match( '#^https?://#i', $candidate ) ) {
        return esc_url_raw( $candidate );
    }

    $base = wp_parse_url( $base_url );
    if ( empty( $base['scheme'] ) || empty( $base['host'] ) ) {
        return '';
    }

    $origin = $base['scheme'] . '://' . $base['host'];
    if ( isset( $base['port'] ) ) {
        $origin .= ':' . $base['port'];
    }

    if ( 0 === strpos( $candidate, '//' ) ) {
        return esc_url_raw( $base['scheme'] . ':' . $candidate );
    }

    if ( 0 === strpos( $candidate, '/' ) ) {
        return esc_url_raw( $origin . $candidate );
    }

    $path = isset( $base['path'] ) ? $base['path'] : '/';
    $dir  = trailingslashit( preg_replace( '#/[^/]*$#', '/', $path ) );

    return esc_url_raw( $origin . $dir . ltrim( $candidate, '/' ) );
}

function holyprofweb_get_site_visual_candidates( $source_url ) {
    $body = holyprofweb_fetch_remote_page_body( $source_url );
    if ( ! $body ) {
        return array();
    }

    $candidates = array();

    if ( preg_match_all( '/<img[^>]+(?:src|data-src)=["\']([^"\']+)["\'][^>]*(?:class|id|alt)=["\'][^"\']*(?:hero|banner|dashboard|screenshot|preview|app-shot)[^"\']*["\']/i', $body, $matches ) ) {
        foreach ( $matches[1] as $match ) {
            $candidates[] = holyprofweb_resolve_remote_asset_url( $match, $source_url );
        }
    }

    if ( preg_match( '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $match ) ) {
        $candidates[] = holyprofweb_resolve_remote_asset_url( $match[1], $source_url );
    }

    if ( preg_match_all( '/<img[^>]+(?:src|data-src)=["\']([^"\']+)["\'][^>]*(?:class|id|alt)=["\'][^"\']*(?:logo|brand|navbar-brand|site-logo|header-logo)[^"\']*["\']/i', $body, $matches ) ) {
        foreach ( $matches[1] as $match ) {
            $candidates[] = holyprofweb_resolve_remote_asset_url( $match, $source_url );
        }
    }

    if ( preg_match( '/<meta[^>]+property=["\']og:logo["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $match ) ) {
        $candidates[] = holyprofweb_resolve_remote_asset_url( $match[1], $source_url );
    }
    if ( preg_match( '/"logo"\s*:\s*"([^"]+)"/i', $body, $match ) ) {
        $candidates[] = holyprofweb_resolve_remote_asset_url( $match[1], $source_url );
    }

    $candidates = array_values( array_filter( array_unique( $candidates ) ) );
    return $candidates;
}

function holyprofweb_get_landing_page_capture_url( $source_url ) {
    return '';
}

function holyprofweb_pick_working_remote_image_url( $candidates ) {
    foreach ( (array) $candidates as $candidate ) {
        $candidate = esc_url_raw( (string) $candidate );
        if ( ! $candidate ) {
            continue;
        }

        $response = wp_remote_head(
            $candidate,
            array(
                'timeout'     => 5,
                'redirection' => 3,
            )
        );

        if ( is_wp_error( $response ) ) {
            continue;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 === $code || 301 === $code || 302 === $code ) {
            return $candidate;
        }
    }

    return '';
}

function holyprofweb_get_clearbit_logo_url( $domain ) {
    if ( ! $domain || ! get_option( 'hpw_enable_remote_image_fetch', 1 ) ) {
        return '';
    }

    $clearbit = 'https://logo.clearbit.com/' . rawurlencode( $domain );
    $response = wp_remote_head(
        $clearbit,
        array(
            'timeout'     => 5,
            'redirection' => 3,
        )
    );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return '';
    }

    return $clearbit;
}

function holyprofweb_maybe_get_remote_post_image( $post_id, $post = null ) {
    $retry_lock = 'hpw_remote_image_retry_' . (int) $post_id;
    if ( get_transient( $retry_lock ) ) {
        return '';
    }

    $cached = get_post_meta( $post_id, '_holyprofweb_remote_image_url', true );
    if ( $cached && ! holyprofweb_is_disallowed_remote_image_url( $cached ) ) {
        return esc_url_raw( $cached );
    }
    if ( $cached ) {
        delete_post_meta( $post_id, '_holyprofweb_remote_image_url' );
    }

    $post       = $post ?: get_post( $post_id );
    $source_url = holyprofweb_backfill_source_url_meta( $post_id, $post );
    $domain     = holyprofweb_extract_domain( $source_url );
    $image_url  = apply_filters( 'holyprofweb_automation_image_url', '', $post_id, $post, $domain, $source_url );

    if ( ! $image_url && $source_url ) {
        $image_url = holyprofweb_pick_working_remote_image_url( holyprofweb_get_site_visual_candidates( $source_url ) );
    }
    if ( ! $image_url && $source_url ) {
        $image_url = holyprofweb_fetch_og_image_url( $source_url );
    }
    if ( ! $image_url && $domain ) {
        $image_url = holyprofweb_get_clearbit_logo_url( $domain );
    }

    if ( $image_url ) {
        update_post_meta( $post_id, '_holyprofweb_remote_image_url', esc_url_raw( $image_url ) );
        delete_transient( $retry_lock );
    } else {
        set_transient( $retry_lock, '1', 6 * HOUR_IN_SECONDS );
    }

    return $image_url;
}

function holyprofweb_attach_remote_image_to_post( $image_url, $post_id, $description = '' ) {
    if ( ! $image_url ) {
        return 0;
    }

    if ( ! function_exists( 'media_sideload_image' ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $att_id = media_sideload_image( $image_url, $post_id, $description, 'id' );
    if ( is_wp_error( $att_id ) ) {
        return 0;
    }

    if ( ! holyprofweb_validate_remote_attachment_for_featured( (int) $att_id, $post_id ) ) {
        return 0;
    }

    holyprofweb_brand_attachment_image( (int) $att_id );
    set_post_thumbnail( $post_id, $att_id );
    delete_post_meta( $post_id, '_holyprofweb_gen_image_url' );
    delete_post_meta( $post_id, '_holyprofweb_gen_image_version' );
    return (int) $att_id;
}

function holyprofweb_upgrade_generated_featured_image( $post_id, $post = null ) {
    $post = $post ?: get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
        return false;
    }

    if ( ! holyprofweb_post_uses_generated_image_fallback( $post_id ) ) {
        return false;
    }

    $remote = holyprofweb_maybe_get_remote_post_image( $post_id, $post );
    if ( ! $remote ) {
        return false;
    }

    return holyprofweb_attach_remote_image_to_post( $remote, $post_id, $post->post_title ) > 0;
}

function holyprofweb_validate_remote_attachment_for_featured( $attachment_id, $post_id = 0 ) {
    $meta   = wp_get_attachment_metadata( $attachment_id );
    $width  = ! empty( $meta['width'] ) ? (int) $meta['width'] : 0;
    $height = ! empty( $meta['height'] ) ? (int) $meta['height'] : 0;
    $ratio  = ( $width > 0 && $height > 0 ) ? ( $width / max( 1, $height ) ) : 0;

    $is_usable = $width >= 600 && $height >= 315 && $ratio >= 1.2;
    if ( $is_usable ) {
        return true;
    }

    wp_delete_attachment( $attachment_id, true );
    if ( $post_id > 0 ) {
        delete_post_meta( $post_id, '_holyprofweb_remote_image_url' );
    }

    return false;
}

function holyprofweb_brand_attachment_image( $attachment_id ) {
    $attachment_id = (int) $attachment_id;
    if ( $attachment_id <= 0 ) {
        return;
    }

    $filepath = get_attached_file( $attachment_id );
    if ( ! is_string( $filepath ) || '' === $filepath || ! file_exists( $filepath ) || ! is_writable( $filepath ) || ! function_exists( 'imagecreatefromstring' ) ) {
        return;
    }

    $image = holyprofweb_load_image_resource( $filepath );
    if ( ! $image ) {
        return;
    }

    $width  = imagesx( $image );
    $height = imagesy( $image );
    if ( ! $width || ! $height ) {
        imagedestroy( $image );
        return;
    }

    holyprofweb_overlay_brand_logo( $image, $width, $height );

    $mime    = get_post_mime_type( $attachment_id );
    $written = false;

    if ( 'image/png' === $mime && function_exists( 'imagepng' ) ) {
        imagesavealpha( $image, true );
        $written = @imagepng( $image, $filepath, 6 );
    } elseif ( 'image/webp' === $mime && function_exists( 'imagewebp' ) ) {
        $written = @imagewebp( $image, $filepath, 90 );
    } elseif ( function_exists( 'imagejpeg' ) ) {
        $written = @imagejpeg( $image, $filepath, 90 );
    }

    imagedestroy( $image );

    if ( ! $written ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $filepath ) );
}

/**
 * On save_post: if no thumbnail, try OG image > Clearbit logo > generated image.
 */
function holyprofweb_should_defer_featured_image_generation() {
    return holyprofweb_is_high_volume_publish_context();
}

function holyprofweb_schedule_featured_image_generation( $post_id ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 ) {
        return;
    }

    if ( wp_next_scheduled( 'holyprofweb_generate_featured_image_async', array( $post_id ) ) ) {
        return;
    }

    wp_schedule_single_event( time() + 15, 'holyprofweb_generate_featured_image_async', array( $post_id ) );
}

function holyprofweb_run_scheduled_featured_image_generation( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    holyprofweb_auto_featured_image( (int) $post_id, $post, true );
}
add_action( 'holyprofweb_generate_featured_image_async', 'holyprofweb_run_scheduled_featured_image_generation' );

function holyprofweb_auto_featured_image( $post_id, $post, $update ) {
    // Guards: skip revisions, autosaves, non-posts, already has thumbnail
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
    if ( $post->post_type !== 'post' || $post->post_status === 'auto-draft' ) return;
    if ( get_post_meta( $post_id, '_holyprofweb_no_autothumb', true ) ) return;
    if ( get_post_meta( $post_id, 'external_image', true ) ) return;

    if ( has_post_thumbnail( $post_id ) ) {
        return;
    }

    $cached_remote = get_post_meta( $post_id, '_holyprofweb_remote_image_url', true );
    $cached_gen    = get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );
    if ( $cached_remote || $cached_gen ) return;

    if ( holyprofweb_should_defer_featured_image_generation() ) {
        holyprofweb_schedule_featured_image_generation( $post_id );
        return;
    }

    if ( ! has_post_thumbnail( $post_id ) ) {
        holyprofweb_generate_post_image_modern( $post_id, $post );
    }
}
add_action( 'save_post', 'holyprofweb_auto_featured_image', 20, 3 );

/**
 * Generate GD image immediately when a post is saved via Gutenberg (REST).
 * Gutenberg saves defer the full pipeline by 15s, so without this hook
 * only the SVG placeholder would appear on first load — this guarantees a
 * branded GD image is set right away. The deferred job will later upgrade
 * it to a remote/OG image if one is found.
 */
add_action( 'rest_after_insert_post', function ( $post ) {
    if ( ! $post instanceof WP_Post ) return;
    if ( 'auto-draft' === $post->post_status ) return;
    if ( has_post_thumbnail( $post->ID ) ) return;
    if ( get_post_meta( $post->ID, '_holyprofweb_no_autothumb', true ) ) return;
    if ( get_post_meta( $post->ID, 'external_image', true ) ) return;
    if ( get_post_meta( $post->ID, '_holyprofweb_gen_image_url', true ) ) return;
    if ( get_post_meta( $post->ID, '_holyprofweb_remote_image_url', true ) ) return;
    if ( ! get_option( 'hpw_enable_generated_images', 1 ) ) return;

    holyprofweb_generate_post_image_modern( $post->ID, $post );
}, 20 );

/**
 * GD fallback: generate a branded image with title + category text.
 *
 * @param int     $post_id
 * @param WP_Post $post
 */
function holyprofweb_generate_post_image( $post_id, $post ) {
    if ( ! get_option( 'hpw_enable_generated_images', 1 ) ) return;
    if ( ! function_exists( 'imagecreatetruecolor' ) ) return;

    $upload = wp_upload_dir();
    if ( $upload['error'] ) return;

    $filename = holyprofweb_get_generated_image_filename( $post_id, $post );
    $filepath = trailingslashit( $upload['path'] ) . $filename;
    $fileurl  = trailingslashit( $upload['url'] ) . $filename;

    $cats     = get_the_category( $post_id );
    $cat_slug = ! empty( $cats ) ? $cats[0]->slug : '';
    $cat_name = ! empty( $cats ) ? $cats[0]->name : 'General';
    $palettes = array(
        'reviews'   => array( 'bg' => array( 13, 24, 46 ), 'mid' => array( 26, 53, 92 ), 'accent' => array( 59, 130, 246 ) ),
        'companies' => array( 'bg' => array( 10, 32, 24 ), 'mid' => array( 18, 55, 40 ), 'accent' => array( 34, 197, 94 ) ),
        'salaries'  => array( 'bg' => array( 41, 21, 8 ), 'mid' => array( 88, 47, 14 ), 'accent' => array( 251, 146, 60 ) ),
        'biography' => array( 'bg' => array( 32, 12, 40 ), 'mid' => array( 63, 28, 84 ), 'accent' => array( 168, 85, 247 ) ),
        'reports'   => array( 'bg' => array( 42, 12, 14 ), 'mid' => array( 88, 24, 28 ), 'accent' => array( 239, 68, 68 ) ),
    );

    $palette = array( 'bg' => array( 19, 19, 24 ), 'mid' => array( 46, 46, 58 ), 'accent' => array( 184, 134, 11 ) );
    foreach ( $palettes as $key => $candidate ) {
        if ( false !== strpos( $cat_slug, $key ) ) {
            $palette = $candidate;
            break;
        }
    }

    $width  = 1200;
    $height = 630; // OG-image friendly 1.91:1 ratio
    $img    = imagecreatetruecolor( $width, $height );
    $accent = imagecolorallocate( $img, $palette['accent'][0], $palette['accent'][1], $palette['accent'][2] );
    $white  = imagecolorallocate( $img, 250, 248, 244 );
    $muted  = imagecolorallocate( $img, 180, 178, 170 );
    $dark   = imagecolorallocate( $img, 12, 12, 14 );

    // Vertical gradient background (dark top → slightly lighter bottom)
    for ( $y = 0; $y < $height; $y++ ) {
        $mix = $y / max( 1, $height - 1 );
        $r   = (int) round( $palette['bg'][0] + ( $palette['mid'][0] - $palette['bg'][0] ) * $mix );
        $g   = (int) round( $palette['bg'][1] + ( $palette['mid'][1] - $palette['bg'][1] ) * $mix );
        $b   = (int) round( $palette['bg'][2] + ( $palette['mid'][2] - $palette['bg'][2] ) * $mix );
        imageline( $img, 0, $y, $width, $y, imagecolorallocate( $img, $r, $g, $b ) );
    }

    // Accent stripe at left edge
    imagefilledrectangle( $img, 0, 0, 8, $height, $accent );

    // Bottom footer bar
    imagefilledrectangle( $img, 0, $height - 64, $width, $height, imagecolorallocate( $img, 8, 8, 10 ) );

    // Category badge background
    $badge_w = (int) ( strlen( $cat_name ) * 14 + 40 );
    imagefilledrectangle( $img, 60, 56, 60 + $badge_w, 100, $accent );

    $font_file = holyprofweb_get_image_font_file();
    $title     = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( get_the_title( $post_id ) ) ) );
    $wrapped   = holyprofweb_wrap_image_text( $title, 3, 32 );

    if ( $font_file && function_exists( 'imagettftext' ) ) {
        // Category badge text
        imagettftext( $img, 18, 0, 76, 88, $dark, $font_file, strtoupper( $cat_name ) );

        // Title — centered vertically
        $title_size = 56;
        $line_gap   = 76;
        $lines_count = count( $wrapped );
        $block_h    = $lines_count * $line_gap;
        $start_y    = (int) ( ( $height - 64 - $block_h ) / 2 ) + $line_gap;
        foreach ( $wrapped as $index => $line ) {
            imagettftext( $img, $title_size, 0, 60, $start_y + ( $index * $line_gap ), $white, $font_file, $line );
        }

        // Site name in footer
        imagettftext( $img, 20, 0, 60, $height - 22, $muted, $font_file, 'holyprofweb.com' );
    } else {
        // GD built-in bitmap fonts — simple but clean
        $fw = imagefontwidth( 5 );
        $badge_label = strtoupper( $cat_name );
        imagestring( $img, 5, 76, 68, $badge_label, $dark );

        $y = 200;
        foreach ( $wrapped as $line ) {
            imagestring( $img, 5, 60, $y, $line, $white );
            $y += 28;
        }
        imagestring( $img, 3, 60, $height - 48, 'holyprofweb.com', $muted );
    }

    imagejpeg( $img, $filepath, 92 );
    imagedestroy( $img );

    if ( ! file_exists( $filepath ) ) return;

    $att_id = wp_insert_attachment( array(
        'guid'           => $fileurl,
        'post_mime_type' => 'image/jpeg',
        'post_title'     => sanitize_text_field( $post->post_title ),
        'post_status'    => 'inherit',
    ), $filepath, $post_id );

    if ( ! is_wp_error( $att_id ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $filepath ) );
        set_post_thumbnail( $post_id, $att_id );
        update_post_meta( $post_id, '_holyprofweb_gen_image_url', $fileurl );
    }
}

function holyprofweb_generate_post_image_modern( $post_id, $post ) {
    if ( ! get_option( 'hpw_enable_generated_images', 1 ) ) return;
    if ( ! function_exists( 'imagecreatetruecolor' ) ) return;

    $upload = wp_upload_dir();
    if ( $upload['error'] ) return;

    $filename = 'hpw-generated-' . $post_id . '.jpg';
    $filepath = trailingslashit( $upload['path'] ) . $filename;
    $fileurl  = trailingslashit( $upload['url'] ) . $filename;

    $cats     = get_the_category( $post_id );
    $cat_slug = ! empty( $cats ) ? $cats[0]->slug : '';
    $cat_name = ! empty( $cats ) ? $cats[0]->name : 'General';
    $palettes = array(
        'reviews'   => array( 'bg' => array( 9, 17, 39 ), 'mid' => array( 22, 56, 118 ), 'accent' => array( 96, 165, 250 ) ),
        'companies' => array( 'bg' => array( 9, 28, 26 ), 'mid' => array( 14, 76, 60 ), 'accent' => array( 52, 211, 153 ) ),
        'salaries'  => array( 'bg' => array( 43, 22, 7 ), 'mid' => array( 122, 57, 13 ), 'accent' => array( 251, 191, 36 ) ),
        'biography' => array( 'bg' => array( 29, 11, 44 ), 'mid' => array( 88, 28, 135 ), 'accent' => array( 196, 181, 253 ) ),
        'reports'   => array( 'bg' => array( 53, 14, 20 ), 'mid' => array( 127, 29, 29 ), 'accent' => array( 252, 165, 165 ) ),
    );

    $palette = array( 'bg' => array( 15, 18, 28 ), 'mid' => array( 49, 46, 73 ), 'accent' => array( 240, 184, 74 ) );
    foreach ( $palettes as $key => $candidate ) {
        if ( false !== strpos( $cat_slug, $key ) ) {
            $palette = $candidate;
            break;
        }
    }

    $style = holyprofweb_get_generated_image_style();

    $width  = 1600;
    $height = 900;
    $img    = imagecreatetruecolor( $width, $height );
    imagealphablending( $img, true );
    imagesavealpha( $img, false );

    $accent       = imagecolorallocate( $img, $palette['accent'][0], $palette['accent'][1], $palette['accent'][2] );
    $accent_soft  = imagecolorallocatealpha( $img, $palette['accent'][0], $palette['accent'][1], $palette['accent'][2], 94 );
    $accent_glow  = imagecolorallocatealpha( $img, $palette['accent'][0], $palette['accent'][1], $palette['accent'][2], 110 );
    $white        = imagecolorallocate( $img, 247, 248, 251 );
    $muted        = imagecolorallocate( $img, 189, 198, 214 );
    $dark         = imagecolorallocate( $img, 11, 15, 23 );
    $panel        = imagecolorallocatealpha( $img, 7, 11, 19, 24 );
    $panel_border = imagecolorallocatealpha( $img, 255, 255, 255, 108 );
    $grid         = imagecolorallocatealpha( $img, 255, 255, 255, 122 );
    $card_fill    = imagecolorallocatealpha( $img, 8, 12, 19, 18 );
    $card_shadow  = imagecolorallocatealpha( $img, 0, 0, 0, 112 );
    $footer_panel = imagecolorallocatealpha( $img, 7, 11, 19, 58 );

    for ( $y = 0; $y < $height; $y++ ) {
        $mix = $y / max( 1, $height - 1 );
        $r   = (int) round( $palette['bg'][0] + ( $palette['mid'][0] - $palette['bg'][0] ) * $mix );
        $g   = (int) round( $palette['bg'][1] + ( $palette['mid'][1] - $palette['bg'][1] ) * $mix );
        $b   = (int) round( $palette['bg'][2] + ( $palette['mid'][2] - $palette['bg'][2] ) * $mix );
        imageline( $img, 0, $y, $width, $y, imagecolorallocate( $img, $r, $g, $b ) );
    }

    imagefilledellipse( $img, 1080, 120, 540, 540, $accent_soft );
    imagefilledellipse( $img, 1200, 700, 560, 560, $accent_glow );
    imagefilledellipse( $img, 360, 120, 240, 240, imagecolorallocatealpha( $img, 255, 255, 255, 120 ) );

    for ( $x = 0; $x < $width; $x += 96 ) {
        imageline( $img, $x, 0, $x - 280, $height, $grid );
    }

    imagefilledrectangle( $img, 0, 0, 14, $height, $accent );
    holyprofweb_image_filled_rounded_rectangle( $img, 160, 66, $width - 160, $height - 66, $panel, 30 );
    imagerectangle( $img, 160, 66, $width - 160, $height - 66, $panel_border );
    holyprofweb_image_filled_rounded_rectangle( $img, 230, 144, 1120, 712, $card_shadow, 30 );
    holyprofweb_image_filled_rounded_rectangle( $img, 220, 132, 1110, 700, $card_fill, 30 );
    holyprofweb_image_filled_rounded_rectangle( $img, 220, 740, $width - 220, 810, $footer_panel, 18 );

    $badge_w = (int) ( strlen( $cat_name ) * 18 + 86 );
    holyprofweb_image_filled_rounded_rectangle( $img, 262, 162, 262 + $badge_w, 224, $accent, 24 );

    $font_file = holyprofweb_get_image_font_file();
    $title     = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( holyprofweb_get_decoded_post_title( $post_id ) ) ) );
    $wrapped   = holyprofweb_wrap_image_text( $title, 3, 'minimal-poster' === $style ? 24 : 20 );
    $excerpt   = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( holyprofweb_get_decoded_post_excerpt( $post_id ) ) ) );
    $search_hook_map = array(
        'reviews'   => 'Is it legit? Fees, features, complaints, and user feedback.',
        'companies' => 'Company profile, products, reputation, salaries, and red flags.',
        'salaries'  => 'Salary range, pay trends, role scope, and hiring signals.',
        'biography' => 'Background, career story, net worth claims, and key facts.',
        'reports'   => 'Scam alert, complaint trends, warning signs, and user issues.',
    );
    $search_hook = isset( $search_hook_map[ $cat_slug ] ) ? $search_hook_map[ $cat_slug ] : 'Reviews, research, warning signs, pricing, and key facts.';
    $deck_source = $excerpt ?: $search_hook;
    if ( $excerpt && mb_strlen( $excerpt ) < 72 ) {
        $deck_source = $excerpt . ' | ' . $search_hook;
    }
    $deck = holyprofweb_wrap_image_text( $deck_source, 3, 'minimal-poster' === $style ? 46 : 56 );

    switch ( $style ) {
        case 'split-banner':
            holyprofweb_image_filled_rounded_rectangle( $img, 192, 92, 1408, 808, imagecolorallocatealpha( $img, 10, 14, 22, 26 ), 34 );
            holyprofweb_image_filled_rounded_rectangle( $img, 220, 118, 1380, 494, imagecolorallocatealpha( $img, 255, 255, 255, 118 ), 24 );
            holyprofweb_image_filled_rounded_rectangle( $img, 220, 522, 1380, 780, imagecolorallocatealpha( $img, 8, 11, 18, 18 ), 24 );
            imagefilledellipse( $img, 1100, 148, 460, 460, $accent_soft );
            imagefilledellipse( $img, 380, 762, 230, 230, imagecolorallocatealpha( $img, 255, 255, 255, 120 ) );

            if ( $font_file && function_exists( 'imagettftext' ) ) {
                imagettftext( $img, 19, 0, 280, 182, $dark, $font_file, strtoupper( $cat_name ) );
                $title_size = mb_strlen( $title ) > 60 ? 54 : 64;
                $line_gap   = $title_size + 14;
                foreach ( $wrapped as $index => $line ) {
                    imagettftext( $img, $title_size, 0, 274, 308 + ( $index * $line_gap ), $white, $font_file, $line );
                }
                foreach ( array_slice( $deck, 0, 2 ) as $index => $line ) {
                    imagettftext( $img, 24, 0, 274, 604 + ( $index * 40 ), $muted, $font_file, $line );
                }
                imagettftext( $img, 18, 0, 272, 744, $muted, $font_file, 'holyprofweb.com' );
            } else {
                imagestring( $img, 5, 270, 162, strtoupper( $cat_name ), $dark );
                $y = 280;
                foreach ( $wrapped as $line ) {
                    imagestring( $img, 5, 268, $y, $line, $white );
                    $y += 52;
                }
            }
            break;

        case 'spotlight':
            imagefilledellipse( $img, 800, 300, 980, 980, imagecolorallocatealpha( $img, 255, 255, 255, 122 ) );
            holyprofweb_image_filled_rounded_rectangle( $img, 220, 124, 1380, 776, imagecolorallocatealpha( $img, 8, 12, 19, 36 ), 40 );
            holyprofweb_image_filled_rounded_rectangle( $img, 300, 194, 1300, 706, imagecolorallocatealpha( $img, 9, 13, 21, 18 ), 34 );
            holyprofweb_image_filled_rounded_rectangle( $img, 578, 152, 1022, 220, $accent, 22 );

            if ( $font_file && function_exists( 'imagettftext' ) ) {
                imagettftext( $img, 18, 0, 670, 196, $dark, $font_file, strtoupper( $cat_name ) );
                $title_size = mb_strlen( $title ) > 54 ? 56 : 68;
                $line_gap   = $title_size + 18;
                $start_y    = 360;
                foreach ( $wrapped as $index => $line ) {
                    $bbox = imagettfbbox( $title_size, 0, $font_file, $line );
                    $line_width = is_array( $bbox ) ? abs( $bbox[4] - $bbox[0] ) : 0;
                    $x = (int) max( 360, ( $width - $line_width ) / 2 );
                    imagettftext( $img, $title_size, 0, $x, $start_y + ( $index * $line_gap ), $white, $font_file, $line );
                }
                foreach ( array_slice( $deck, 0, 2 ) as $index => $line ) {
                    $bbox = imagettfbbox( 23, 0, $font_file, $line );
                    $line_width = is_array( $bbox ) ? abs( $bbox[4] - $bbox[0] ) : 0;
                    $x = (int) max( 360, ( $width - $line_width ) / 2 );
                    imagettftext( $img, 23, 0, $x, 610 + ( $index * 38 ), $muted, $font_file, $line );
                }
            }
            break;

        case 'signal-grid':
            holyprofweb_image_filled_rounded_rectangle( $img, 200, 92, 1400, 808, imagecolorallocatealpha( $img, 8, 12, 19, 20 ), 30 );
            for ( $grid_x = 230; $grid_x <= 1370; $grid_x += 170 ) {
                imageline( $img, $grid_x, 110, $grid_x, 790, imagecolorallocatealpha( $img, 255, 255, 255, 120 ) );
            }
            for ( $grid_y = 138; $grid_y <= 772; $grid_y += 108 ) {
                imageline( $img, 230, $grid_y, 1370, $grid_y, imagecolorallocatealpha( $img, 255, 255, 255, 120 ) );
            }
            holyprofweb_image_filled_rounded_rectangle( $img, 246, 132, 720, 206, $accent, 18 );
            holyprofweb_image_filled_rounded_rectangle( $img, 246, 246, 1354, 634, imagecolorallocatealpha( $img, 6, 10, 17, 18 ), 24 );
            holyprofweb_image_filled_rounded_rectangle( $img, 246, 670, 1040, 770, imagecolorallocatealpha( $img, 6, 10, 17, 18 ), 20 );

            if ( $font_file && function_exists( 'imagettftext' ) ) {
                imagettftext( $img, 20, 0, 282, 180, $dark, $font_file, strtoupper( $cat_name ) );
                $title_size = mb_strlen( $title ) > 58 ? 52 : 62;
                $line_gap   = $title_size + 14;
                foreach ( $wrapped as $index => $line ) {
                    imagettftext( $img, $title_size, 0, 284, 346 + ( $index * $line_gap ), $white, $font_file, $line );
                }
                foreach ( array_slice( $deck, 0, 2 ) as $index => $line ) {
                    imagettftext( $img, 22, 0, 284, 724 + ( $index * 34 ), $muted, $font_file, $line );
                }
            }
            break;

        case 'minimal-poster':
            imagefilledrectangle( $img, 0, 0, $width, $height, imagecolorallocate( $img, $palette['bg'][0], $palette['bg'][1], $palette['bg'][2] ) );
            imagefilledrectangle( $img, 0, 0, 18, $height, $accent );
            holyprofweb_image_filled_rounded_rectangle( $img, 220, 120, 1380, 782, imagecolorallocatealpha( $img, 255, 255, 255, 122 ), 18 );
            holyprofweb_image_filled_rounded_rectangle( $img, 252, 160, 1348, 742, imagecolorallocatealpha( $img, 12, 16, 24, 4 ), 12 );
            holyprofweb_image_filled_rounded_rectangle( $img, 252, 160, 530, 218, $accent, 14 );

            if ( $font_file && function_exists( 'imagettftext' ) ) {
                imagettftext( $img, 18, 0, 284, 198, $dark, $font_file, strtoupper( $cat_name ) );
                $title_size = mb_strlen( $title ) > 62 ? 54 : 66;
                $line_gap   = $title_size + 18;
                foreach ( $wrapped as $index => $line ) {
                    imagettftext( $img, $title_size, 0, 284, 338 + ( $index * $line_gap ), $white, $font_file, $line );
                }
                foreach ( array_slice( $deck, 0, 3 ) as $index => $line ) {
                    imagettftext( $img, 24, 0, 284, 612 + ( $index * 40 ), $muted, $font_file, $line );
                }
                imagettftext( $img, 18, 0, 284, 724, $muted, $font_file, 'holyprofweb.com' );
            }
            break;

        case 'editorial-dark':
        default:
            if ( $font_file && function_exists( 'imagettftext' ) ) {
                imagettftext( $img, 20, 0, 300, 202, $dark, $font_file, strtoupper( $cat_name ) );

                $title_size  = mb_strlen( $title ) > 58 ? 56 : 68;
                $line_gap    = $title_size + 16;
                $lines_count = count( $wrapped );
                $block_h     = max( 1, $lines_count ) * $line_gap;
                $start_y     = 342;

                foreach ( $wrapped as $index => $line ) {
                    imagettftext( $img, $title_size, 0, 274, $start_y + ( $index * $line_gap ), $white, $font_file, $line );
                }

                if ( ! empty( $deck ) ) {
                    $deck_y = $start_y + $block_h + 34;
                    foreach ( array_slice( $deck, 0, 2 ) as $index => $line ) {
                        imagettftext( $img, 24, 0, 276, $deck_y + ( $index * 38 ), $muted, $font_file, $line );
                    }
                }

                imagettftext( $img, 18, 0, 274, 787, $muted, $font_file, 'holyprofweb.com' );
                imagettftext( $img, 18, 0, 1080, 787, $muted, $font_file, 'Research first. Decide with confidence.' );
            } else {
                $badge_label = strtoupper( $cat_name );
                imagestring( $img, 5, 288, 180, $badge_label, $dark );

                $y = 324;
                foreach ( $wrapped as $line ) {
                    imagestring( $img, 5, 272, $y, $line, $white );
                    $y += 56;
                }

                if ( ! empty( $deck ) ) {
                    foreach ( array_slice( $deck, 0, 2 ) as $line ) {
                        imagestring( $img, 4, 272, $y + 16, $line, $muted );
                        $y += 34;
                    }
                }

                imagestring( $img, 3, 274, 772, 'holyprofweb.com', $muted );
                imagestring( $img, 3, 1064, 772, 'Research first. Decide with confidence.', $muted );
            }
            break;
    }

    holyprofweb_overlay_brand_logo( $img, $width, $height );

    imagejpeg( $img, $filepath, 94 );
    imagedestroy( $img );

    if ( ! file_exists( $filepath ) ) return;

    $att_id = (int) get_post_meta( $post_id, '_holyprofweb_gen_attachment_id', true );
    if ( ! $att_id ) {
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            $attached_file = get_attached_file( $thumb_id );
            if ( $attached_file && wp_basename( $attached_file ) === $filename ) {
                $att_id = (int) $thumb_id;
            }
        }
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';

    if ( $att_id && get_post( $att_id ) ) {
        update_attached_file( $att_id, $filepath );
        wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $filepath ) );
        set_post_thumbnail( $post_id, $att_id );
    } else {
        $att_id = wp_insert_attachment( array(
            'guid'           => $fileurl,
            'post_mime_type' => 'image/jpeg',
            'post_title'     => sanitize_text_field( $post->post_title ),
            'post_status'    => 'inherit',
        ), $filepath, $post_id );

        if ( is_wp_error( $att_id ) ) {
            return;
        }

        wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $filepath ) );
        set_post_thumbnail( $post_id, $att_id );
    }

    update_post_meta( $post_id, '_holyprofweb_gen_attachment_id', (int) $att_id );
    update_post_meta( $post_id, '_holyprofweb_gen_image_url', $fileurl );
    update_post_meta( $post_id, '_holyprofweb_gen_image_version', holyprofweb_generated_image_version() );
}

function holyprofweb_image_filled_rounded_rectangle( $image, $x1, $y1, $x2, $y2, $color, $radius ) {
    imagefilledrectangle( $image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color );
    imagefilledrectangle( $image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color );
    imagefilledellipse( $image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color );
    imagefilledellipse( $image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color );
    imagefilledellipse( $image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color );
    imagefilledellipse( $image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color );
}

function holyprofweb_get_image_font_file() {
    $candidates = array(
        get_template_directory() . '/assets/fonts/Inter-Bold.ttf',
        get_template_directory() . '/assets/fonts/AlbertSans-Bold.ttf',
        get_template_directory() . '/assets/fonts/Manrope-Bold.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/segoeuib.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
    );

    foreach ( $candidates as $path ) {
        if ( $path && file_exists( $path ) ) {
            return $path;
        }
    }

    return '';
}

function holyprofweb_wrap_image_text( $text, $max_lines = 3, $max_chars = 26 ) {
    $words  = preg_split( '/\s+/', trim( (string) $text ) );
    $lines  = array();
    $buffer = '';
    $overflowed = false;

    foreach ( $words as $word ) {
        $trial = '' === $buffer ? $word : $buffer . ' ' . $word;
        if ( mb_strlen( $trial ) > $max_chars && '' !== $buffer ) {
            $lines[] = $buffer;
            $buffer  = $word;
            if ( count( $lines ) >= $max_lines ) {
                $overflowed = true;
                break;
            }
        } else {
            $buffer = $trial;
        }
    }

    if ( '' !== $buffer ) {
        $lines[] = $buffer;
    }

    $lines = array_slice( $lines, 0, $max_lines );
    $last_index = count( $lines ) - 1;
    if ( $last_index >= 0 && ( $overflowed || mb_strlen( $lines[ $last_index ] ) > $max_chars ) ) {
        $line = $lines[ $last_index ];
        $line = mb_substr( $line, 0, $max_chars - 3 );
        $line = rtrim( $line, " \t\n\r\0\x0B.,-" ) . '...';
        $lines[ $last_index ] = $line;
    }

    return $lines;
}


// =========================================
// LEFT SIDEBAR NAVIGATION (CATEGORIES)
// =========================================

function holyprofweb_left_sidebar() {
    $icon_map = array(
        'reviews'   => '&#9733;',
        'companies' => '&#127970;',
        'biography' => '&#128100;',
        'reports'   => '&#128203;',
        'banks'     => '&#127974;',
        'fintech'   => '&#128181;',
        'startups'  => '&#128640;',
        'tech'      => '&#128187;',
        'salaries'  => '&#128176;',
    );
    $current_term = is_category() ? get_queried_object() : null;
    $parents = get_terms(
        array(
            'taxonomy'   => 'category',
            'parent'     => 0,
            'hide_empty' => false,
            'exclude'    => holyprofweb_get_category_exclusions(),
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if ( is_wp_error( $parents ) || empty( $parents ) ) {
        return;
    }

    echo '<aside class="left-sidebar" id="left-sidebar" aria-label="' . esc_attr__( 'Category Navigation', 'holyprofweb' ) . '">';
    echo '<nav class="left-nav">';
    echo '<p class="left-nav-heading">' . esc_html__( 'Browse Topics', 'holyprofweb' ) . '</p>';

    foreach ( $parents as $parent ) {
        if ( ! $parent instanceof WP_Term ) {
            continue;
        }

        $child_terms = get_terms(
            array(
                'taxonomy'   => 'category',
                'parent'     => $parent->term_id,
                'hide_empty' => false,
                'exclude'    => holyprofweb_get_category_exclusions(),
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );
        if ( is_wp_error( $child_terms ) ) {
            $child_terms = array();
        }

        $child_terms = array_values(
            array_filter(
                $child_terms,
                static function( $term ) {
                    return $term instanceof WP_Term && (int) $term->count >= 5;
                }
            )
        );

        if ( (int) $parent->count < 5 && empty( $child_terms ) ) {
            continue;
        }

        $has_children = ! empty( $child_terms );
        $icon         = isset( $icon_map[ $parent->slug ] ) ? $icon_map[ $parent->slug ] : '&#128193;';
        $is_active    = $current_term instanceof WP_Term && ( (int) $current_term->term_id === (int) $parent->term_id || term_is_ancestor_of( $parent->term_id, $current_term->term_id, 'category' ) );

        echo '<div class="left-nav-group' . ( $is_active ? ' is-open' : '' ) . '">';
        echo '<button class="left-nav-parent left-nav-parent--static" aria-expanded="true" type="button">';
        echo '<span class="left-nav-icon" aria-hidden="true">' . $icon . '</span>';
        echo '<span class="left-nav-parent-main">';
        echo '<a href="' . esc_url( get_category_link( $parent->term_id ) ) . '" class="left-nav-parent-link">' . esc_html( $parent->name ) . '</a>';
        echo '<span class="left-nav-count">(' . esc_html( holyprofweb_format_display_count( (int) $parent->count ) ) . ')</span>';
        echo '</span>';
        echo '</button>';

        if ( $has_children ) {
            echo '<ul class="left-nav-children">';
            foreach ( $child_terms as $child ) {
                $active_child = $current_term instanceof WP_Term && (int) $current_term->term_id === (int) $child->term_id;
                echo '<li' . ( $active_child ? ' class="is-active"' : '' ) . '>';
                echo '<a href="' . esc_url( get_category_link( $child->term_id ) ) . '">';
                echo esc_html( $child->name );
                echo ' <span class="left-nav-count">(' . esc_html( holyprofweb_format_display_count( (int) $child->count ) ) . ')</span>';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }

        echo '</div>';
    }

    echo '</nav>';
    echo '</aside>';
}

function holyprofweb_should_show_page_shell_loader() {
    return ! is_admin() && ( is_archive() || is_search() || is_home() || is_single() );
}

function holyprofweb_page_shell_body_class( $classes ) {
    if ( holyprofweb_should_show_page_shell_loader() ) {
        $classes[] = 'hpw-has-page-shell';
    }
    return $classes;
}
add_filter( 'body_class', 'holyprofweb_page_shell_body_class' );

function holyprofweb_render_page_shell_loader() {
    if ( ! holyprofweb_should_show_page_shell_loader() ) {
        return;
    }
    echo '<div class="hpw-page-shell" aria-hidden="true">';
    echo '<div class="hpw-page-shell__inner">';
    echo '<div class="hpw-page-shell__sidebar"></div>';
    echo '<div class="hpw-page-shell__main">';
    echo '<div class="hpw-page-shell__hero"></div>';
    echo '<div class="hpw-page-shell__row"></div>';
    echo '<div class="hpw-page-shell__row"></div>';
    echo '<div class="hpw-page-shell__row"></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
add_action( 'wp_body_open', 'holyprofweb_render_page_shell_loader', 5 );

function holyprofweb_render_page_shell_loader_script() {
    if ( ! holyprofweb_should_show_page_shell_loader() ) {
        return;
    }
    ?>
    <script>
    document.documentElement.classList.add('hpw-shell-pending');
    (function () {
        function markReady() {
            document.documentElement.classList.add('hpw-shell-ready');
            document.body.classList.add('hpw-shell-ready');
        }
        document.addEventListener('DOMContentLoaded', markReady, { once: true });
        window.addEventListener('load', markReady, { once: true });
    })();
    </script>
    <?php
}
add_action( 'wp_head', 'holyprofweb_render_page_shell_loader_script', 1 );


// =========================================
// EMAIL CAPTURE AJAX
// =========================================

/**
 * Save subscriber email to wp_options list.
 */
function holyprofweb_email_capture_ajax() {
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'holyprofweb_live_search' ) ) {
        wp_send_json_error( 'Invalid nonce', 403 );
    }

    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Invalid email' );
    }

    $list   = get_option( 'holyprofweb_email_list', array() );
    $key    = md5( strtolower( $email ) );

    if ( ! isset( $list[ $key ] ) ) {
        $list[ $key ] = array(
            'email' => $email,
            'ts'    => time(),
        );
        update_option( 'holyprofweb_email_list', $list, false );
    }

    wp_send_json_success( array( 'email' => $email ) );
}
add_action( 'wp_ajax_holyprofweb_email_capture',        'holyprofweb_email_capture_ajax' );
add_action( 'wp_ajax_nopriv_holyprofweb_email_capture', 'holyprofweb_email_capture_ajax' );

/**
 * Email subscribers admin page under Appearance.
 */
function holyprofweb_subscribers_admin_menu() {
    return;
}
add_action( 'admin_menu', 'holyprofweb_subscribers_admin_menu' );

function holyprofweb_subscribers_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $list  = get_option( 'holyprofweb_email_list', array() );
    $total = count( $list );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Email Subscribers', 'holyprofweb' ); ?></h1>
        <p><?php echo esc_html( sprintf( __( 'Total subscribers: %d', 'holyprofweb' ), $total ) ); ?></p>
        <?php if ( $total > 0 ) : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Email', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'holyprofweb' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $list as $entry ) : ?>
                <tr>
                    <td><?php echo esc_html( $entry['email'] ); ?></td>
                    <td><?php echo esc_html( gmdate( 'Y-m-d H:i', $entry['ts'] ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p><?php esc_html_e( 'No subscribers yet.', 'holyprofweb' ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}


// =========================================
// AUTO-INTERLINKING (content filter)
// =========================================

/**
 * Scan post content and auto-link detected post titles to their URLs.
 * Runs only on singular post views, limits to first occurrence.
 *
 * @param string $content
 * @return string
 */
function holyprofweb_auto_interlink( $content ) {
    if ( ! is_singular( 'post' ) || is_admin() ) {
        return $content;
    }

    static $link_map = null;

    if ( null === $link_map ) {
        $link_map = array();
        $posts    = get_posts( array(
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        global $post;
        $current_id = $post ? (int) $post->ID : 0;

        foreach ( $posts as $pid ) {
            if ( (int) $pid === $current_id ) continue;
            $title = get_the_title( $pid );
            if ( mb_strlen( $title ) < 5 ) continue; // skip very short titles
            $link_map[ $title ] = get_permalink( $pid );
        }

        // Sort by length desc to match longest titles first
        uksort( $link_map, function( $a, $b ) {
            return mb_strlen( $b ) - mb_strlen( $a );
        } );
    }

    if ( empty( $link_map ) ) {
        return $content;
    }

    // Placeholder-based approach: replace existing <a> blocks so we never
    // accidentally nest a link inside another link.
    $anchors     = array();
    $anchor_idx  = 0;

    $content = preg_replace_callback(
        '/<a\b[^>]*>.*?<\/a>/si',
        function ( $m ) use ( &$anchors, &$anchor_idx ) {
            $placeholder            = '<!--HPWLNK' . $anchor_idx . '-->';
            $anchors[ $placeholder ] = $m[0];
            $anchor_idx++;
            return $placeholder;
        },
        $content
    );

    // Now safely replace first occurrence of each title in remaining text
    foreach ( $link_map as $title => $url ) {
        $escaped = preg_quote( $title, '/' );
        $content = preg_replace_callback(
            '/' . $escaped . '/iu',
            function ( $m ) use ( $url ) {
                return '<a href="' . esc_url( $url ) . '" class="auto-link">' . esc_html( $m[0] ) . '</a>';
            },
            $content,
            1
        );
    }

    // Restore original anchors
    foreach ( $anchors as $placeholder => $anchor ) {
        $content = str_replace( $placeholder, $anchor, $content );
    }

    return $content;
}
add_filter( 'the_content', 'holyprofweb_auto_interlink', 15 );


// =========================================
// SALARY / COMPANY META BOX
// =========================================

/**
 * Register salary & company data meta box on posts.
 */
function holyprofweb_register_salary_meta_box() {
    add_meta_box(
        'hpw_salary_data',
        __( 'HPW — Salary & Company Data', 'holyprofweb' ),
        'holyprofweb_salary_meta_box_render',
        'post',
        'normal',
        'high'
    );

    add_meta_box(
        'hpw_post_ops',
        __( 'HPW - Post Controls', 'holyprofweb' ),
        'holyprofweb_post_ops_meta_box_render',
        'post',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'holyprofweb_register_salary_meta_box' );

function holyprofweb_salary_meta_box_render( $post ) {
    wp_nonce_field( 'hpw_save_salary_meta', 'hpw_salary_meta_nonce' );

    $fields = array(
        '_hpw_salary_min'      => array( 'label' => 'Min Salary (number only)',   'type' => 'number' ),
        '_hpw_salary_max'      => array( 'label' => 'Max Salary (number only)',   'type' => 'number' ),
        '_hpw_salary_currency' => array( 'label' => 'Currency symbol (e.g. ₦ $)', 'type' => 'text'   ),
        '_hpw_salary_period'   => array( 'label' => 'Period (e.g. /month /year)', 'type' => 'text'   ),
        '_hpw_salary_role'     => array( 'label' => 'Role name (e.g. Senior Engineer)', 'type' => 'text' ),
        '_hpw_company_size'    => array( 'label' => 'Company size (e.g. 500–1000 staff)', 'type' => 'text' ),
        '_hpw_work_score'      => array( 'label' => 'Work/Life score (1–5)',       'type' => 'number' ),
    );

    echo '<table class="form-table" style="max-width:600px;">';
    foreach ( $fields as $key => $f ) {
        $val = esc_attr( get_post_meta( $post->ID, $key, true ) );
        $step = ( $f['type'] === 'number' && $key === '_hpw_work_score' ) ? ' step="0.1" min="1" max="5"' : '';
        printf(
            '<tr><th scope="row"><label for="%s">%s</label></th><td>
            <input type="%s" id="%s" name="%s" value="%s" class="regular-text"%s /></td></tr>',
            esc_attr( $key ), esc_html( $f['label'] ),
            esc_attr( $f['type'] ), esc_attr( $key ), esc_attr( $key ), $val, $step
        );
    }
    echo '</table>';
    echo '<p class="description">' . esc_html__( 'These fields power the Salaries category template. Leave blank if not applicable.', 'holyprofweb' ) . '</p>';
}

function holyprofweb_post_ops_meta_box_render( $post ) {
    wp_nonce_field( 'hpw_save_post_ops_meta', 'hpw_post_ops_meta_nonce' );

    $source_url = get_post_meta( $post->ID, '_hpw_source_url', true );
    $external_image = get_post_meta( $post->ID, 'external_image', true );
    $country_focus = get_post_meta( $post->ID, '_hpw_country_focus', true );
    $verdict_override = get_post_meta( $post->ID, '_hpw_verdict_override', true );
    $rating_override = get_post_meta( $post->ID, '_hpw_rating_override', true );
    $verdict_options = holyprofweb_get_verdict_options();
    ?>
    <p>
        <label for="hpw_source_url"><strong><?php esc_html_e( 'Reviewed Site URL', 'holyprofweb' ); ?></strong></label><br>
        <input type="text" inputmode="url" autocapitalize="off" spellcheck="false" id="hpw_source_url" name="hpw_source_url" value="<?php echo esc_attr( $source_url ); ?>" class="widefat" placeholder="example.com or https://example.com" />
        <span class="description"><?php esc_html_e( 'Add the official website, landing page, app store page, or trusted profile URL here. This is the safest way for HPW to fetch a better featured image automatically after publish.', 'holyprofweb' ); ?></span>
    </p>
    <p>
        <label for="hpw_external_image"><strong><?php esc_html_e( 'External Image URL', 'holyprofweb' ); ?></strong></label><br>
        <input type="url" id="hpw_external_image" name="hpw_external_image" value="<?php echo esc_attr( $external_image ); ?>" class="widefat" />
        <span class="description"><?php esc_html_e( 'Optional direct image override. Use this only if you want to force a specific image instead of automatic fetching.', 'holyprofweb' ); ?></span>
    </p>
    <p>
        <label for="hpw_country_focus"><strong><?php esc_html_e( 'Country Focus', 'holyprofweb' ); ?></strong></label><br>
        <input type="text" id="hpw_country_focus" name="hpw_country_focus" value="<?php echo esc_attr( $country_focus ); ?>" class="widefat" placeholder="Nigeria, USA, France" />
    </p>
    <p>
        <label for="hpw_rating_override"><strong><?php esc_html_e( 'Display Rating Override', 'holyprofweb' ); ?></strong></label><br>
        <input type="number" id="hpw_rating_override" name="hpw_rating_override" value="<?php echo esc_attr( $rating_override ); ?>" class="small-text" min="0" max="5" step="0.1" />
        <span class="description"><?php esc_html_e( 'Leave blank to keep the live review average.', 'holyprofweb' ); ?></span>
    </p>
    <p>
        <label for="hpw_verdict_override"><strong><?php esc_html_e( 'Verdict Badge', 'holyprofweb' ); ?></strong></label><br>
        <select id="hpw_verdict_override" name="hpw_verdict_override" class="widefat">
            <option value="" <?php selected( $verdict_override, '' ); ?>><?php esc_html_e( 'Auto', 'holyprofweb' ); ?></option>
            <?php foreach ( $verdict_options as $value => $option ) : ?>
            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $verdict_override, $value ); ?>><?php echo esc_html( $option['label'] ); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function holyprofweb_save_salary_meta( $post_id ) {
    if ( ! isset( $_POST['hpw_salary_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hpw_salary_meta_nonce'] ) ), 'hpw_save_salary_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $text_fields   = array( '_hpw_salary_currency', '_hpw_salary_period', '_hpw_salary_role', '_hpw_company_size' );
    $number_fields = array( '_hpw_salary_min', '_hpw_salary_max', '_hpw_work_score' );

    foreach ( $text_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
        }
    }
    foreach ( $number_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            $val = wp_unslash( $_POST[ $key ] );
            update_post_meta( $post_id, $key, is_numeric( $val ) ? (float) $val : '' );
        }
    }

    if ( isset( $_POST['hpw_post_ops_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hpw_post_ops_meta_nonce'] ) ), 'hpw_save_post_ops_meta' ) ) {
        $posted_source_url = isset( $_POST['hpw_source_url'] ) ? holyprofweb_normalize_possible_url( wp_unslash( $_POST['hpw_source_url'] ) ) : '';
        update_post_meta( $post_id, '_hpw_source_url', $posted_source_url );
        update_post_meta( $post_id, 'external_image', isset( $_POST['hpw_external_image'] ) ? holyprofweb_normalize_possible_url( wp_unslash( $_POST['hpw_external_image'] ) ) : '' );
        update_post_meta( $post_id, '_hpw_country_focus', isset( $_POST['hpw_country_focus'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_country_focus'] ) ) : '' );
        update_post_meta( $post_id, '_hpw_verdict_override', isset( $_POST['hpw_verdict_override'] ) ? sanitize_key( wp_unslash( $_POST['hpw_verdict_override'] ) ) : '' );
        $rating_override = isset( $_POST['hpw_rating_override'] ) ? trim( (string) wp_unslash( $_POST['hpw_rating_override'] ) ) : '';
        update_post_meta( $post_id, '_hpw_rating_override', ( '' !== $rating_override && is_numeric( $rating_override ) ) ? round( max( 0, min( 5, (float) $rating_override ) ), 1 ) : '' );

        if ( ! $posted_source_url ) {
            holyprofweb_backfill_source_url_meta( $post_id );
        }
    }
}
add_action( 'save_post', 'holyprofweb_save_salary_meta' );

function holyprofweb_add_source_status_admin_column( $columns ) {
    $updated = array();

    foreach ( $columns as $key => $label ) {
        $updated[ $key ] = $label;
        if ( 'title' === $key ) {
            $updated['hpw_source_status'] = __( 'Source URL', 'holyprofweb' );
        }
    }

    if ( ! isset( $updated['hpw_source_status'] ) ) {
        $updated['hpw_source_status'] = __( 'Source URL', 'holyprofweb' );
    }

    return $updated;
}
add_filter( 'manage_post_posts_columns', 'holyprofweb_add_source_status_admin_column' );

function holyprofweb_render_source_status_admin_column( $column, $post_id ) {
    if ( 'hpw_source_status' !== $column ) {
        return;
    }

    $status = holyprofweb_get_post_source_status( $post_id );
    $domain = $status['url'] ? holyprofweb_extract_domain( $status['url'] ) : '';
    $color  = '#d63638';
    $external_image = (string) get_post_meta( $post_id, 'external_image', true );

    if ( 'saved' === $status['status'] ) {
        $color = '#1a7f37';
    } elseif ( 'inferred' === $status['status'] ) {
        $color = '#b8860b';
    }

    echo '<span style="display:inline-flex;align-items:center;gap:6px;font-weight:600;">';
    echo '<span aria-hidden="true" style="width:10px;height:10px;border-radius:999px;background:' . esc_attr( $color ) . ';display:inline-block;"></span>';
    echo esc_html( $status['label'] );
    echo '</span>';

    if ( $domain ) {
        echo '<div style="margin-top:4px;color:#50575e;font-size:12px;line-height:1.4;">' . esc_html( $domain ) . '</div>';
    } else {
        echo '<div style="margin-top:4px;color:#50575e;font-size:12px;line-height:1.4;">' . esc_html__( 'Add Reviewed Site URL in the HPW meta box.', 'holyprofweb' ) . '</div>';
    }

    echo '<div class="hpw-quick-edit-data" hidden data-source-url="' . esc_attr( (string) $status['url'] ) . '" data-external-image="' . esc_attr( $external_image ) . '"></div>';
}
add_action( 'manage_post_posts_custom_column', 'holyprofweb_render_source_status_admin_column', 10, 2 );

function holyprofweb_render_post_quick_edit_fields( $column_name, $post_type ) {
    if ( 'post' !== $post_type || 'hpw_source_status' !== $column_name ) {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-left">
        <div class="inline-edit-col">
            <span class="title"><?php esc_html_e( 'HPW Links', 'holyprofweb' ); ?></span>
            <label>
                <span class="title"><?php esc_html_e( 'Site URL', 'holyprofweb' ); ?></span>
                <span class="input-text-wrap">
                    <input type="text" inputmode="url" autocapitalize="off" spellcheck="false" name="hpw_source_url" class="ptitle" value="">
                </span>
            </label>
            <label>
                <span class="title"><?php esc_html_e( 'Image URL', 'holyprofweb' ); ?></span>
                <span class="input-text-wrap">
                    <input type="url" name="hpw_external_image" class="ptitle" value="">
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'holyprofweb_render_post_quick_edit_fields', 10, 2 );

function holyprofweb_quick_edit_post_links_script() {
    $screen = get_current_screen();
    if ( ! $screen || 'edit-post' !== $screen->id ) {
        return;
    }
    ?>
    <script>
    (function() {
        const inlineEditPost = window.inlineEditPost;
        if (!inlineEditPost || !inlineEditPost.edit) {
            return;
        }

        const originalEdit = inlineEditPost.edit;
        inlineEditPost.edit = function(postId) {
            originalEdit.apply(this, arguments);

            let id = 0;
            if (typeof postId === 'object') {
                id = this.getId(postId);
            } else {
                id = parseInt(postId, 10);
            }

            if (!id) {
                return;
            }

            const postRow = document.getElementById('post-' + id);
            const editRow = document.getElementById('edit-' + id);
            if (!postRow || !editRow) {
                return;
            }

            const dataNode = postRow.querySelector('.hpw-quick-edit-data');
            const sourceInput = editRow.querySelector('input[name="hpw_source_url"]');
            const imageInput = editRow.querySelector('input[name="hpw_external_image"]');

            if (sourceInput) {
                sourceInput.value = dataNode ? (dataNode.getAttribute('data-source-url') || '') : '';
            }
            if (imageInput) {
                imageInput.value = dataNode ? (dataNode.getAttribute('data-external-image') || '') : '';
            }
        };
    })();
    </script>
    <?php
}
add_action( 'admin_footer-edit.php', 'holyprofweb_quick_edit_post_links_script' );

function holyprofweb_save_quick_edit_post_links( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['_inline_edit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
        return;
    }

    if ( isset( $_POST['hpw_source_url'] ) ) {
        update_post_meta( $post_id, '_hpw_source_url', holyprofweb_normalize_possible_url( wp_unslash( $_POST['hpw_source_url'] ) ) );
    }

    if ( isset( $_POST['hpw_external_image'] ) ) {
        update_post_meta( $post_id, 'external_image', holyprofweb_normalize_possible_url( wp_unslash( $_POST['hpw_external_image'] ) ) );
    }
}
add_action( 'save_post_post', 'holyprofweb_save_quick_edit_post_links' );

function holyprofweb_update_contact_page_links_once() {
    if ( get_option( 'holyprofweb_contact_link_upgrade_v1' ) ) {
        return;
    }

    $contact_url = home_url( '/contact/' );
    $slugs       = array( 'about', 'work-with-us', 'advertise', 'privacy-policy' );

    foreach ( $slugs as $slug ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page instanceof WP_Post ) {
            continue;
        }

        $content = (string) $page->post_content;
        $updated = str_replace(
            array(
                'href="mailto:admin@holyprofweb.com"',
                "href='mailto:admin@holyprofweb.com'",
            ),
            'href="' . esc_url( $contact_url ) . '"',
            $content
        );

        if ( $updated !== $content ) {
            wp_update_post(
                array(
                    'ID'           => $page->ID,
                    'post_content' => $updated,
                )
            );
        }
    }

    update_option( 'holyprofweb_contact_link_upgrade_v1', 1, false );
}
add_action( 'init', 'holyprofweb_update_contact_page_links_once', 70 );


// =========================================
// HPW SETTINGS ADMIN PANEL
// =========================================

/**
 * Register the HPW Settings top-level admin menu.
 */
function holyprofweb_register_settings_menu() {
    add_menu_page(
        __( 'HPW Settings', 'holyprofweb' ),
        __( 'HPW Settings', 'holyprofweb' ),
        'manage_options',
        'hpw-settings',
        'holyprofweb_settings_page',
        'dashicons-admin-settings',
        60
    );

    add_submenu_page( 'hpw-settings', __( 'Site & SEO',      'holyprofweb' ), __( 'Site & SEO',      'holyprofweb' ), 'manage_options', 'hpw-settings',             'holyprofweb_settings_page' );
    add_submenu_page( 'hpw-settings', __( 'Search & Audience','holyprofweb' ), __( 'Search & Audience','holyprofweb' ), 'manage_options', 'hpw-settings-search',      'holyprofweb_settings_search_page' );
    add_submenu_page( 'hpw-settings', __( 'Content & Reviews','holyprofweb' ), __( 'Content & Reviews','holyprofweb' ), 'manage_options', 'hpw-settings-reviews',     'holyprofweb_settings_reviews_page' );
    add_submenu_page( 'hpw-settings', __( 'Redirects',       'holyprofweb' ), __( 'Redirects',       'holyprofweb' ), 'manage_options', 'hpw-settings-redirects',   'holyprofweb_settings_redirects_page' );
    add_submenu_page( 'hpw-settings', __( 'Ads',             'holyprofweb' ), __( 'Ads',             'holyprofweb' ), 'manage_options', 'hpw-settings-ads',         'holyprofweb_ads_admin_page' );
    add_submenu_page( 'hpw-settings', __( 'Emails',          'holyprofweb' ), __( 'Emails',          'holyprofweb' ), 'manage_options', 'hpw-settings-emails',      'holyprofweb_settings_emails_page' );
    add_submenu_page( 'hpw-settings', __( 'Languages & Geo', 'holyprofweb' ), __( 'Languages & Geo', 'holyprofweb' ), 'manage_options', 'hpw-settings-languages',   'holyprofweb_settings_languages_page' );
    add_submenu_page( 'hpw-settings', __( 'Automation',      'holyprofweb' ), __( 'Automation',      'holyprofweb' ), 'manage_options', 'hpw-settings-automation',  'holyprofweb_settings_automation_page' );
    add_submenu_page( 'hpw-settings', __( 'SEO Debug',       'holyprofweb' ), __( 'SEO Debug',       'holyprofweb' ), 'manage_options', 'hpw-settings-seo-debug',   'holyprofweb_settings_seo_debug_page' );
}
add_action( 'admin_menu', 'holyprofweb_register_settings_menu' );

function holyprofweb_get_admin_alert_counts() {
    $pending_reviews = get_comments( array(
        'type'   => 'review',
        'status' => 'hold',
        'count'  => true,
    ) );

    $pending_salary = get_comments( array(
        'type'   => 'salary_submission',
        'status' => 'hold',
        'count'  => true,
    ) );

    $subscribers = get_option( 'holyprofweb_email_list', array() );
    $recent_subscribers = 0;
    $cutoff = time() - WEEK_IN_SECONDS;
    foreach ( $subscribers as $entry ) {
        if ( ! empty( $entry['ts'] ) && (int) $entry['ts'] >= $cutoff ) {
            $recent_subscribers++;
        }
    }

    return array(
        'pending_reviews'    => (int) $pending_reviews,
        'pending_salary'     => (int) $pending_salary,
        'recent_subscribers' => (int) $recent_subscribers,
        'total'              => (int) $pending_reviews + (int) $pending_salary + (int) $recent_subscribers,
    );
}

function holyprofweb_get_admin_content_filter_options() {
    return array(
        'all'           => __( 'All tracked content', 'holyprofweb' ),
        'companies'     => __( 'Companies', 'holyprofweb' ),
        'apps-websites' => __( 'Apps / Websites', 'holyprofweb' ),
        'blog'          => __( 'Blog / Reports', 'holyprofweb' ),
    );
}

function holyprofweb_get_admin_content_kind( $post_id ) {
    if ( holyprofweb_post_in_category_tree( $post_id, 'companies' ) ) {
        return __( 'Company', 'holyprofweb' );
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'reviews' ) ) {
        return __( 'App / Website', 'holyprofweb' );
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'blog-opinion' ) || holyprofweb_post_in_category_tree( $post_id, 'reports' ) ) {
        return __( 'Blog / Report', 'holyprofweb' );
    }

    return __( 'Post', 'holyprofweb' );
}

function holyprofweb_matches_admin_content_filter( $post_id, $filter ) {
    switch ( $filter ) {
        case 'companies':
            return holyprofweb_post_in_category_tree( $post_id, 'companies' );
        case 'apps-websites':
            return holyprofweb_post_in_category_tree( $post_id, 'reviews' );
        case 'blog':
            return holyprofweb_post_in_category_tree( $post_id, 'blog-opinion' ) || holyprofweb_post_in_category_tree( $post_id, 'reports' );
        case 'all':
        default:
            return holyprofweb_post_in_category_tree( $post_id, 'companies' )
                || holyprofweb_post_in_category_tree( $post_id, 'reviews' )
                || holyprofweb_post_in_category_tree( $post_id, 'blog-opinion' )
                || holyprofweb_post_in_category_tree( $post_id, 'reports' );
    }
}

function holyprofweb_get_admin_content_rows( $search = '', $filter = 'all', $limit = 40 ) {
    $query = new WP_Query(
        array(
            'post_type'              => 'post',
            'post_status'            => array( 'publish', 'draft', 'pending' ),
            'posts_per_page'         => (int) $limit,
            's'                      => $search,
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => true,
        )
    );

    if ( empty( $query->posts ) ) {
        return array();
    }

    return array_values(
        array_filter(
            $query->posts,
            static function( $post ) use ( $filter ) {
                return holyprofweb_matches_admin_content_filter( $post->ID, $filter );
            }
        )
    );
}

function holyprofweb_handle_reviews_admin_quick_update() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_POST['hpw_reviews_content_update'] ) || empty( $_POST['page'] ) || 'hpw-settings-reviews' !== $_POST['page'] ) {
        return;
    }

    check_admin_referer( 'hpw_reviews_content_update', 'hpw_reviews_content_nonce' );

    $post_id = isset( $_POST['hpw_content_post_id'] ) ? (int) $_POST['hpw_content_post_id'] : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( esc_html__( 'You cannot edit this content item.', 'holyprofweb' ) );
    }

    $rating_override  = isset( $_POST['hpw_rating_override'] ) ? trim( (string) wp_unslash( $_POST['hpw_rating_override'] ) ) : '';
    $verdict_override = isset( $_POST['hpw_verdict_override'] ) ? sanitize_key( wp_unslash( $_POST['hpw_verdict_override'] ) ) : '';
    $source_url       = isset( $_POST['hpw_source_url'] ) ? holyprofweb_normalize_possible_url( wp_unslash( $_POST['hpw_source_url'] ) ) : '';
    $external_image   = isset( $_POST['hpw_external_image'] ) ? holyprofweb_normalize_possible_url( wp_unslash( $_POST['hpw_external_image'] ) ) : '';
    $country_focus    = isset( $_POST['hpw_country_focus'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_country_focus'] ) ) : '';
    if ( ! array_key_exists( $verdict_override, holyprofweb_get_verdict_options() ) ) {
        $verdict_override = '';
    }

    update_post_meta( $post_id, '_hpw_rating_override', ( '' !== $rating_override && is_numeric( $rating_override ) ) ? round( max( 0, min( 5, (float) $rating_override ) ), 1 ) : '' );
    update_post_meta( $post_id, '_hpw_verdict_override', $verdict_override );
    update_post_meta( $post_id, '_hpw_source_url', $source_url );
    update_post_meta( $post_id, 'external_image', $external_image );
    update_post_meta( $post_id, '_hpw_country_focus', $country_focus );

    if ( ! empty( $_FILES['hpw_featured_upload']['name'] ) && ! empty( $_FILES['hpw_featured_upload']['tmp_name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $uploaded = media_handle_upload( 'hpw_featured_upload', $post_id );
        if ( ! is_wp_error( $uploaded ) ) {
            set_post_thumbnail( $post_id, (int) $uploaded );
            delete_post_meta( $post_id, '_holyprofweb_gen_image_url' );
            delete_post_meta( $post_id, '_holyprofweb_gen_image_version' );
            delete_post_meta( $post_id, '_holyprofweb_remote_image_url' );
        }
    }

    $redirect = add_query_arg(
        array(
            'page'                => 'hpw-settings-reviews',
            'hpw_content_updated' => 1,
            'hpw_content_post_id' => $post_id,
            'hpw_content_search'  => isset( $_POST['hpw_content_search'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_content_search'] ) ) : '',
            'hpw_content_type'    => isset( $_POST['hpw_content_type'] ) ? sanitize_key( wp_unslash( $_POST['hpw_content_type'] ) ) : 'all',
        ),
        admin_url( 'admin.php' )
    );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_init', 'holyprofweb_handle_reviews_admin_quick_update' );

function holyprofweb_admin_menu_badges() {
    global $menu, $submenu;

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $counts = holyprofweb_get_admin_alert_counts();
    $search_alerts = holyprofweb_get_search_alert_rows();
    if ( empty( $counts['total'] ) ) {
        $counts['total'] = 0;
    }
    $counts['total'] += count( $search_alerts );

    foreach ( $menu as &$item ) {
        if ( isset( $item[2] ) && 'hpw-settings' === $item[2] ) {
            $item[0] .= ' <span class="awaiting-mod update-plugins"><span class="plugin-count">' . (int) $counts['total'] . '</span></span>';
            break;
        }
    }

    if ( isset( $submenu['hpw-settings'] ) ) {
        foreach ( $submenu['hpw-settings'] as &$item ) {
            if ( 'hpw-settings-reviews' === $item[2] ) {
                $review_total = (int) $counts['pending_reviews'] + (int) $counts['pending_salary'];
                if ( $review_total > 0 ) {
                    $item[0] .= ' <span class="awaiting-mod update-plugins"><span class="plugin-count">' . $review_total . '</span></span>';
                }
            }
            if ( 'hpw-settings-emails' === $item[2] && ! empty( $counts['recent_subscribers'] ) ) {
                $item[0] .= ' <span class="awaiting-mod update-plugins"><span class="plugin-count">' . (int) $counts['recent_subscribers'] . '</span></span>';
            }
            if ( 'hpw-settings-search' === $item[2] && ! empty( $search_alerts ) ) {
                $item[0] .= ' <span class="awaiting-mod update-plugins"><span class="plugin-count">' . (int) count( $search_alerts ) . '</span></span>';
            }
        }
    }
}
add_action( 'admin_menu', 'holyprofweb_admin_menu_badges', 99 );

function holyprofweb_admin_alert_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen ) {
        return;
    }

    if ( false === strpos( (string) $screen->id, 'hpw-settings' ) ) {
        return;
    }

    $counts = holyprofweb_get_admin_alert_counts();
    $search_alerts = holyprofweb_get_search_alert_rows();
    if ( empty( $counts['total'] ) && empty( $search_alerts ) ) {
        return;
    }

    echo '<div class="notice notice-info"><p><strong>HPW Alerts:</strong> ';
    echo esc_html( sprintf(
        'Pending reviews: %d | Pending salary submissions: %d | New subscribers this week: %d | Search terms needing attention: %d',
        (int) $counts['pending_reviews'],
        (int) $counts['pending_salary'],
        (int) $counts['recent_subscribers'],
        (int) count( $search_alerts )
    ) );
    echo '</p></div>';
}
add_action( 'admin_notices', 'holyprofweb_admin_alert_notice' );

/**
 * Register all HPW option fields.
 */
function holyprofweb_register_settings() {
    // General
    register_setting( 'hpw_general',   'hpw_site_tagline',        array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'hpw_general',   'hpw_posts_per_page',       array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_general',   'hpw_show_email_capture',   array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_general',   'hpw_enable_copy_protection', array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_general',   'hpw_discourage_indexing',  array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_general',   'hpw_redirect_rules',       array( 'sanitize_callback' => 'holyprofweb_sanitize_redirect_rules' ) );
    register_setting( 'hpw_general',   'hpw_header_logo_height',   array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_general',   'hpw_footer_logo_height',   array( 'sanitize_callback' => 'absint' ) );

    // Search & Audience
    register_setting( 'hpw_search',    'hpw_show_trending',        array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_search',    'hpw_search_alert_threshold', array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_search',    'hpw_search_log_limit',     array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_search',    'hpw_search_auto_draft',    array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );

    // Reviews
    register_setting( 'hpw_reviews',   'hpw_review_auto_approve',  array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_reviews',   'hpw_review_min_length',    array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_reviews',   'hpw_review_spam_words',    array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
    register_setting( 'hpw_reviews',   'hpw_review_allow_anon',    array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );

    // Emails
    register_setting( 'hpw_emails',    'hpw_email_from_name',      array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'hpw_emails',    'hpw_email_from_address',   array( 'sanitize_callback' => 'sanitize_email' ) );
    register_setting( 'hpw_emails',    'hpw_email_notify_review',  array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_emails',    'hpw_email_notify_address', array( 'sanitize_callback' => 'sanitize_email' ) );
    register_setting( 'hpw_emails',    'hpw_email_welcome_subject',array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'hpw_emails',    'hpw_email_welcome_body',   array( 'sanitize_callback' => 'sanitize_textarea_field' ) );

    // Languages
    register_setting( 'hpw_languages', 'hpw_multilingual_enabled', array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_languages', 'hpw_default_language',     array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'hpw_languages', 'hpw_country_mode',         array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'hpw_languages', 'hpw_enabled_languages',    array( 'sanitize_callback' => 'holyprofweb_sanitize_enabled_languages' ) );

    // Automation
    register_setting( 'hpw_automation', 'hpw_enable_remote_image_fetch', array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_automation', 'hpw_enable_generated_images',   array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_automation', 'hpw_generated_image_style',     array( 'sanitize_callback' => 'holyprofweb_sanitize_generated_image_style' ) );
    register_setting( 'hpw_automation', 'hpw_enable_draft_autopublish',  array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_automation', 'hpw_draft_publish_limit',       array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_automation', 'hpw_rtl_support',               array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_automation', 'hpw_refresh_days',              array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_automation', 'hpw_refresh_queue_limit',       array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_automation', 'hpw_ai_minimum_words',          array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_automation', 'hpw_ai_internal_links',         array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_automation', 'hpw_ai_faq_count',              array( 'sanitize_callback' => 'absint' ) );
    register_setting( 'hpw_automation', 'hpw_ai_brand_voice',            array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
    register_setting( 'hpw_automation', 'hpw_ai_prompt_notes',           array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
}
add_action( 'admin_init', 'holyprofweb_register_settings' );

/* ── General ──────────────────────────────────────────────────────────────── */

function holyprofweb_get_search_log_summary() {
    $log = holyprofweb_get_search_log();
    $summary = array(
        'tracked_terms'   => count( $log ),
        'total_searches'  => 0,
        'flagged_terms'   => 0,
        'countries'       => array(),
        'referrers'       => array(),
    );

    $threshold = holyprofweb_get_search_alert_threshold();

    foreach ( $log as $entry ) {
        $summary['total_searches'] += (int) $entry['count'];
        if ( (int) $entry['count'] >= $threshold ) {
            $summary['flagged_terms']++;
        }

        foreach ( (array) $entry['countries'] as $country => $count ) {
            if ( empty( $summary['countries'][ $country ] ) ) {
                $summary['countries'][ $country ] = 0;
            }
            $summary['countries'][ $country ] += (int) $count;
        }

        foreach ( (array) $entry['referrers'] as $referrer => $count ) {
            if ( empty( $summary['referrers'][ $referrer ] ) ) {
                $summary['referrers'][ $referrer ] = 0;
            }
            $summary['referrers'][ $referrer ] += (int) $count;
        }
    }

    arsort( $summary['countries'] );
    arsort( $summary['referrers'] );

    return $summary;
}

function holyprofweb_handle_search_admin_actions() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_POST['hpw_search_action'] ) || empty( $_POST['page'] ) || 'hpw-settings-search' !== $_POST['page'] ) {
        return;
    }

    check_admin_referer( 'hpw_search_admin_action', 'hpw_search_admin_nonce' );

    $action = sanitize_key( wp_unslash( $_POST['hpw_search_action'] ) );
    if ( 'clear_search_log' === $action ) {
        update_option( 'holyprofweb_search_log', array(), false );
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'              => 'hpw-settings-search',
                'hpw_search_action' => 'done',
            ),
            admin_url( 'admin.php' )
        )
    );
    exit;
}
add_action( 'admin_init', 'holyprofweb_handle_search_admin_actions' );

function holyprofweb_render_admin_live_filter_script( $form_id, $field_selectors = 'input[type="search"], select' ) {
    $form_id         = trim( (string) $form_id );
    $field_selectors = trim( (string) $field_selectors );
    if ( '' === $form_id || '' === $field_selectors ) {
        return;
    }
    ?>
    <script>
    (function () {
        var form = document.getElementById(<?php echo wp_json_encode( $form_id ); ?>);
        if (!form) {
            return;
        }

        var fields = form.querySelectorAll(<?php echo wp_json_encode( $field_selectors ); ?>);
        if (!fields.length) {
            return;
        }

        var timer = null;
        var submitForm = function () {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(function () {
                form.submit();
            }, 280);
        };

        fields.forEach(function (field) {
            var tag = (field.tagName || '').toLowerCase();
            var eventName = tag === 'select' ? 'change' : 'input';

            field.addEventListener(eventName, function () {
                submitForm();
            });

            if (tag !== 'select') {
                field.addEventListener('search', function () {
                    submitForm();
                });
            }
        });
    })();
    </script>
    <?php
}

function holyprofweb_settings_search_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Search settings saved.', 'holyprofweb' ), 'updated' );
    }
    if ( isset( $_GET['hpw_search_action'] ) && 'done' === $_GET['hpw_search_action'] ) {
        add_settings_error( 'hpw_messages', 'hpw_search_done', __( 'Search log action completed.', 'holyprofweb' ), 'updated' );
    }
    settings_errors( 'hpw_messages' );

    $query_filter   = isset( $_GET['hpw_search_term'] ) ? sanitize_text_field( wp_unslash( $_GET['hpw_search_term'] ) ) : '';
    $country_filter = isset( $_GET['hpw_search_country'] ) ? sanitize_text_field( wp_unslash( $_GET['hpw_search_country'] ) ) : '';
    $summary        = holyprofweb_get_search_log_summary();
    $threshold      = holyprofweb_get_search_alert_threshold();
    $rows           = holyprofweb_get_search_log();

    if ( $query_filter || $country_filter ) {
        $rows = array_filter(
            $rows,
            static function( $entry ) use ( $query_filter, $country_filter ) {
                $matches_query   = '' === $query_filter || false !== stripos( (string) $entry['term'], $query_filter );
                $matches_country = '' === $country_filter || ! empty( $entry['countries'][ $country_filter ] );
                return $matches_query && $matches_country;
            }
        );
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'HPW Settings — Search & Audience', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'search' ); ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin:0 0 20px;">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;"><strong style="display:block;font-size:1.8rem;line-height:1;"><?php echo esc_html( number_format_i18n( $summary['tracked_terms'] ) ); ?></strong><span><?php esc_html_e( 'Tracked search terms', 'holyprofweb' ); ?></span></div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;"><strong style="display:block;font-size:1.8rem;line-height:1;"><?php echo esc_html( number_format_i18n( $summary['total_searches'] ) ); ?></strong><span><?php esc_html_e( 'Total search events', 'holyprofweb' ); ?></span></div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;"><strong style="display:block;font-size:1.8rem;line-height:1;"><?php echo esc_html( number_format_i18n( $summary['flagged_terms'] ) ); ?></strong><span><?php echo esc_html( sprintf( __( 'Terms searched %d+ times', 'holyprofweb' ), $threshold ) ); ?></span></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin:0 0 24px;">
            <form method="post" action="options.php" style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px;">
                <h2 style="margin-top:0;"><?php esc_html_e( 'Search Controls', 'holyprofweb' ); ?></h2>
                <p style="margin-top:0;color:#646970;"><?php esc_html_e( 'Keep search logging useful and intentional. Repeated terms can become content ideas, but silent draft creation stays off unless you explicitly enable it.', 'holyprofweb' ); ?></p>
                <?php settings_fields( 'hpw_search' ); ?>
                <table class="form-table" style="margin-top:0;">
                    <tr><th><?php esc_html_e( 'Alert threshold', 'holyprofweb' ); ?></th><td><input type="number" name="hpw_search_alert_threshold" value="<?php echo esc_attr( get_option( 'hpw_search_alert_threshold', 2 ) ); ?>" min="2" max="50" class="small-text" /><p class="description"><?php esc_html_e( 'When a term reaches this many searches, it shows as an admin alert.', 'holyprofweb' ); ?></p></td></tr>
                    <tr><th><?php esc_html_e( 'Tracked term limit', 'holyprofweb' ); ?></th><td><input type="number" name="hpw_search_log_limit" value="<?php echo esc_attr( get_option( 'hpw_search_log_limit', 250 ) ); ?>" min="100" max="1000" class="small-text" /><p class="description"><?php esc_html_e( 'Keeps the log trimmed so admin stays fast.', 'holyprofweb' ); ?></p></td></tr>
                    <tr><th><?php esc_html_e( 'Auto-create draft from missing searches', 'holyprofweb' ); ?></th><td><label><input type="checkbox" name="hpw_search_auto_draft" value="1" <?php checked( 1, get_option( 'hpw_search_auto_draft', 0 ) ); ?> /> <?php esc_html_e( 'Allow repeated missing searches to create a draft idea automatically.', 'holyprofweb' ); ?></label><p class="description"><?php esc_html_e( 'Off by default so search does not silently create draft posts.', 'holyprofweb' ); ?></p></td></tr>
                    <tr><th><?php esc_html_e( 'Front-end trending blocks', 'holyprofweb' ); ?></th><td><label><input type="checkbox" name="hpw_show_trending" value="1" <?php checked( 1, get_option( 'hpw_show_trending', 1 ) ); ?> /> <?php esc_html_e( 'Allow search trend blocks where the theme still uses them.', 'holyprofweb' ); ?></label></td></tr>
                </table>
                <?php submit_button( __( 'Save Search Settings', 'holyprofweb' ) ); ?>
            </form>

            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px;">
                <h2 style="margin-top:0;"><?php esc_html_e( 'Audience Snapshot', 'holyprofweb' ); ?></h2>
                <p style="margin-top:0;color:#646970;"><?php esc_html_e( 'See where search demand comes from so you can prioritize location-specific content, comparisons, and internal links.', 'holyprofweb' ); ?></p>
                <div style="display:grid;gap:14px;">
                    <div>
                        <strong style="display:block;margin:0 0 6px;"><?php esc_html_e( 'Top search countries', 'holyprofweb' ); ?></strong>
                        <?php if ( ! empty( $summary['countries'] ) ) : foreach ( array_slice( $summary['countries'], 0, 6, true ) as $country => $count ) : ?><div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php echo esc_html( $country ); ?></span><strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong></div><?php endforeach; else : ?><p><?php esc_html_e( 'No country search data yet.', 'holyprofweb' ); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <strong style="display:block;margin:0 0 6px;"><?php esc_html_e( 'Top search referrers', 'holyprofweb' ); ?></strong>
                        <?php if ( ! empty( $summary['referrers'] ) ) : foreach ( array_slice( $summary['referrers'], 0, 6, true ) as $referrer => $count ) : ?><div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php echo esc_html( $referrer ); ?></span><strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong></div><?php endforeach; else : ?><p><?php esc_html_e( 'No search referrer data yet.', 'holyprofweb' ); ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px;margin:0 0 20px;">
            <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-end;flex-wrap:wrap;">
                <div>
                    <h2 style="margin:0 0 6px;"><?php esc_html_e( 'Tracked Search Terms', 'holyprofweb' ); ?></h2>
                    <p style="margin:0;color:#646970;"><?php esc_html_e( 'Filter this table by term or country to see which topics need content, local variants, or stronger internal linking.', 'holyprofweb' ); ?></p>
                </div>
                <form method="post" action="">
                    <?php wp_nonce_field( 'hpw_search_admin_action', 'hpw_search_admin_nonce' ); ?>
                    <input type="hidden" name="page" value="hpw-settings-search" />
                    <input type="hidden" name="hpw_search_action" value="clear_search_log" />
                    <?php submit_button( __( 'Clear Search Log', 'holyprofweb' ), 'delete', 'submit', false, array( 'onclick' => "return confirm('Clear the tracked search log?');" ) ); ?>
                </form>
            </div>

            <form id="hpw-search-log-filter" method="get" action="" style="margin:18px 0 14px;display:grid;grid-template-columns:minmax(240px,1fr) minmax(220px,300px) auto;gap:12px;align-items:end;">
                <input type="hidden" name="page" value="hpw-settings-search" />
                <p style="margin:0;"><label for="hpw-search-term-filter" style="display:block;font-weight:600;margin:0 0 6px;"><?php esc_html_e( 'Search term', 'holyprofweb' ); ?></label><input id="hpw-search-term-filter" type="search" name="hpw_search_term" value="<?php echo esc_attr( $query_filter ); ?>" class="regular-text" style="width:100%;max-width:none;" placeholder="<?php esc_attr_e( 'Search Moniepoint, Stripe, salary, biography...', 'holyprofweb' ); ?>" /></p>
                <p style="margin:0;"><label for="hpw-search-country-filter" style="display:block;font-weight:600;margin:0 0 6px;"><?php esc_html_e( 'Country', 'holyprofweb' ); ?></label><select id="hpw-search-country-filter" name="hpw_search_country" style="width:100%;"><option value=""><?php esc_html_e( 'All countries', 'holyprofweb' ); ?></option><?php foreach ( array_keys( $summary['countries'] ) as $country_name ) : ?><option value="<?php echo esc_attr( $country_name ); ?>" <?php selected( $country_filter, $country_name ); ?>><?php echo esc_html( $country_name ); ?></option><?php endforeach; ?></select></p>
                <p style="margin:0;"><?php submit_button( __( 'Filter', 'holyprofweb' ), 'secondary', 'submit', false ); ?></p>
            </form>
            <?php holyprofweb_render_admin_live_filter_script( 'hpw-search-log-filter', 'input[type="search"], select' ); ?>

            <?php if ( empty( $rows ) ) : ?>
                <p><?php esc_html_e( 'No tracked search terms match this filter yet.', 'holyprofweb' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Term', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Searches', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Last results', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Top country', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Last referrer', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Last seen', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Action cue', 'holyprofweb' ); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ( $rows as $entry ) : $needs_attention = (int) $entry['count'] >= $threshold; $top_country = ! empty( $entry['countries'] ) ? (string) array_key_first( $entry['countries'] ) : ''; ?>
                        <tr<?php echo $needs_attention ? ' style="background:#fff8e5;"' : ''; ?>>
                            <td><strong><?php echo esc_html( $entry['term'] ); ?></strong><?php if ( ! empty( $entry['auto_draft_id'] ) ) : ?><div><a href="<?php echo esc_url( get_edit_post_link( (int) $entry['auto_draft_id'] ) ); ?>"><?php esc_html_e( 'Open related draft/post', 'holyprofweb' ); ?></a></div><?php endif; ?></td>
                            <td><?php echo esc_html( number_format_i18n( (int) $entry['count'] ) ); ?></td>
                            <td><?php echo esc_html( number_format_i18n( (int) $entry['last_results'] ) ); ?></td>
                            <td><?php echo esc_html( $top_country ? $top_country : __( 'Unknown', 'holyprofweb' ) ); ?></td>
                            <td><?php echo esc_html( ! empty( $entry['last_referrer'] ) ? $entry['last_referrer'] : __( 'Direct', 'holyprofweb' ) ); ?></td>
                            <td><?php echo esc_html( ! empty( $entry['ts'] ) ? wp_date( 'M j, Y g:i a', (int) $entry['ts'] ) : '—' ); ?></td>
                            <td><?php if ( $needs_attention ) { esc_html_e( 'Needs coverage or stronger internal linking', 'holyprofweb' ); } elseif ( 0 === (int) $entry['last_results'] ) { esc_html_e( 'No exact results yet', 'holyprofweb' ); } else { esc_html_e( 'Covered', 'holyprofweb' ); } ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function holyprofweb_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Settings saved.', 'holyprofweb' ), 'updated' );
    }
    settings_errors( 'hpw_messages' );
    $counts = holyprofweb_get_admin_alert_counts();
    ?>
    <div class="wrap">
        <h1>&#9881; <?php esc_html_e( 'HPW Settings — Site & SEO', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'general' ); ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin:0 0 20px;">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <strong style="display:block;font-size:1.8rem;line-height:1;"><?php echo esc_html( $counts['pending_reviews'] ); ?></strong>
                <span><?php esc_html_e( 'Pending reviews', 'holyprofweb' ); ?></span>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <strong style="display:block;font-size:1.8rem;line-height:1;"><?php echo esc_html( $counts['pending_salary'] ); ?></strong>
                <span><?php esc_html_e( 'Pending salary submissions', 'holyprofweb' ); ?></span>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <strong style="display:block;font-size:1.8rem;line-height:1;"><?php echo esc_html( $counts['recent_subscribers'] ); ?></strong>
                <span><?php esc_html_e( 'New subscribers this week', 'holyprofweb' ); ?></span>
            </div>
        </div>
        <?php $visit_stats = get_option( 'holyprofweb_visit_stats', array() ); ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin:0 0 24px;">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <h2 style="margin:0 0 10px;font-size:16px;"><?php esc_html_e( 'Top Visitor Countries', 'holyprofweb' ); ?></h2>
                <?php if ( ! empty( $visit_stats['countries'] ) ) : foreach ( array_slice( $visit_stats['countries'], 0, 5, true ) as $name => $value ) : ?>
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php echo esc_html( $name ); ?></span><strong><?php echo esc_html( number_format_i18n( $value ) ); ?></strong></div>
                <?php endforeach; else : ?><p><?php esc_html_e( 'No visit data yet.', 'holyprofweb' ); ?></p><?php endif; ?>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <h2 style="margin:0 0 10px;font-size:16px;"><?php esc_html_e( 'Top Referrers', 'holyprofweb' ); ?></h2>
                <?php if ( ! empty( $visit_stats['referrers'] ) ) : foreach ( array_slice( $visit_stats['referrers'], 0, 5, true ) as $name => $value ) : ?>
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php echo esc_html( $name ); ?></span><strong><?php echo esc_html( number_format_i18n( $value ) ); ?></strong></div>
                <?php endforeach; else : ?><p><?php esc_html_e( 'No referrer data yet.', 'holyprofweb' ); ?></p><?php endif; ?>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <h2 style="margin:0 0 10px;font-size:16px;"><?php esc_html_e( 'Site Visibility', 'holyprofweb' ); ?></h2>
                <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php esc_html_e( 'Search indexing', 'holyprofweb' ); ?></span><strong><?php echo get_option( 'hpw_discourage_indexing', 0 ) ? esc_html__( 'Discouraged', 'holyprofweb' ) : esc_html__( 'Allowed', 'holyprofweb' ); ?></strong></div>
                <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php esc_html_e( 'Copy protection', 'holyprofweb' ); ?></span><strong><?php echo get_option( 'hpw_enable_copy_protection', 0 ) ? esc_html__( 'On', 'holyprofweb' ) : esc_html__( 'Off', 'holyprofweb' ); ?></strong></div>
                <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php esc_html_e( 'Archive/search posts per page', 'holyprofweb' ); ?></span><strong><?php echo esc_html( (int) get_option( 'hpw_posts_per_page', 12 ) ); ?></strong></div>
            </div>
        </div>
        <?php
        $country_filter = isset( $_GET['hpw_country_views'] ) ? sanitize_text_field( wp_unslash( $_GET['hpw_country_views'] ) ) : '';
        $viewed_posts   = holyprofweb_get_most_viewed_posts( 6, $country_filter );
        ?>
        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:0 0 24px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
                <h2 style="margin:0;font-size:16px;"><?php esc_html_e( 'Most Viewed Posts', 'holyprofweb' ); ?></h2>
                <form method="get" action="">
                    <input type="hidden" name="page" value="hpw-settings" />
                    <select name="hpw_country_views">
                        <option value=""><?php esc_html_e( 'All countries', 'holyprofweb' ); ?></option>
                        <?php foreach ( array_keys( (array) ( $visit_stats['countries'] ?? array() ) ) as $country_name ) : ?>
                            <option value="<?php echo esc_attr( $country_name ); ?>" <?php selected( $country_filter, $country_name ); ?>><?php echo esc_html( $country_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'holyprofweb' ); ?></button>
                </form>
            </div>
            <?php if ( empty( $viewed_posts ) ) : ?>
                <p><?php esc_html_e( 'No post view data yet.', 'holyprofweb' ); ?></p>
            <?php else : ?>
                <table class="widefat striped" style="margin-top:12px;">
                    <thead><tr><th><?php esc_html_e( 'Post', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Views', 'holyprofweb' ); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ( $viewed_posts as $post_id => $view_count ) : ?>
                        <tr>
                            <td><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></td>
                            <td><?php echo esc_html( number_format_i18n( $view_count ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="notice notice-info inline" style="margin:0 0 16px;">
            <p><strong><?php esc_html_e( 'Setup help:', 'holyprofweb' ); ?></strong> <?php esc_html_e( 'Use this page for launch toggles. For translations, pair the Languages tab with Polylang or WPML. For robots.txt, WordPress can serve a virtual file at /robots.txt even when no physical robots.txt file exists in the theme or site root.', 'holyprofweb' ); ?></p>
            <p><?php echo esc_html( sprintf( __( 'Current robots.txt URL: %s', 'holyprofweb' ), home_url( '/robots.txt' ) ) ); ?></p>
        </div>
        <form method="post" action="options.php">
            <?php settings_fields( 'hpw_general' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Site tagline (header sub-text)', 'holyprofweb' ); ?></th>
                    <td><input type="text" name="hpw_site_tagline" value="<?php echo esc_attr( get_option( 'hpw_site_tagline', '' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Posts per page (archive/search)', 'holyprofweb' ); ?></th>
                    <td>
                        <input type="number" name="hpw_posts_per_page" value="<?php echo esc_attr( (int) get_option( 'hpw_posts_per_page', 0 ) ); ?>" min="0" max="50" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Set 0 to inherit WordPress General Settings > Reading > Blog pages show at most.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Header logo height', 'holyprofweb' ); ?></th>
                    <td>
                        <input type="number" name="hpw_header_logo_height" value="<?php echo esc_attr( max( 36, (int) get_option( 'hpw_header_logo_height', 86 ) ) ); ?>" min="36" max="180" class="small-text" /> px
                        <p class="description"><?php esc_html_e( 'Use Appearance -> Customize -> Site Identity to change the logo itself. Use this setting to make the header logo bigger or smaller.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Footer logo height', 'holyprofweb' ); ?></th>
                    <td>
                        <input type="number" name="hpw_footer_logo_height" value="<?php echo esc_attr( max( 28, (int) get_option( 'hpw_footer_logo_height', 56 ) ) ); ?>" min="28" max="140" class="small-text" /> px
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Show email capture widget', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_show_email_capture" value="1" <?php checked( 1, get_option( 'hpw_show_email_capture', 1 ) ); ?> /> <?php esc_html_e( 'Enabled', 'holyprofweb' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Copy protection', 'holyprofweb' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="hpw_enable_copy_protection" value="1" <?php checked( 1, get_option( 'hpw_enable_copy_protection', 1 ) ); ?> /> <?php esc_html_e( 'Prevent copy, right-click, and text selection on the public site', 'holyprofweb' ); ?></label>
                        <p class="description"><?php esc_html_e( 'Turn this off anytime if you want blog posts and pages to be copyable normally.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Discourage search indexing', 'holyprofweb' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="hpw_discourage_indexing" value="1" <?php checked( 1, get_option( 'hpw_discourage_indexing', 0 ) ); ?> /> <?php esc_html_e( 'Send a sitewide noindex, nofollow signal while you are still updating the site.', 'holyprofweb' ); ?></label>
                        <p class="description"><?php esc_html_e( 'Turn this off when the site is ready for Google and other search engines to crawl again.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Redirect rules', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="hpw_redirect_rules" rows="10" class="large-text code" placeholder="/old-path/,/new-path/&#10;/old-ranking-url/|/reviews/new-ranking-url/&#10;/old-url/,https://example.com/new-url/"><?php echo esc_textarea( get_option( 'hpw_redirect_rules', '' ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'One redirect per line. Use old path first, then new path. Accepted formats: /old-path/,/new-path/ or /old-path/|/new-path/ or /old-url/,https://example.com/new-url/', 'holyprofweb' ); ?></p>
                        <p class="description"><?php esc_html_e( 'The theme will normalize the paths automatically and send a 301 redirect. Published URL changes and deleted posts/pages are also captured automatically for SEO.', 'holyprofweb' ); ?></p>
                        <?php $redirect_preview = holyprofweb_parse_redirect_rules(); ?>
                        <?php if ( ! empty( $redirect_preview ) ) : ?>
                        <div style="margin-top:14px;border:1px solid #e6dcc7;border-radius:16px;background:#fffdfa;overflow:hidden;">
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:0;background:#f7f2e7;padding:10px 14px;font-weight:700;">
                                <span><?php esc_html_e( 'Old URL', 'holyprofweb' ); ?></span>
                                <span><?php esc_html_e( 'New URL', 'holyprofweb' ); ?></span>
                            </div>
                            <div style="max-height:220px;overflow:auto;">
                                <?php foreach ( $redirect_preview as $old_path => $new_path ) : ?>
                                <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;padding:10px 14px;border-top:1px solid #eee6d6;">
                                    <code><?php echo esc_html( $old_path ); ?></code>
                                    <code><?php echo esc_html( $new_path ); ?></code>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save General Settings', 'holyprofweb' ) ); ?>
        </form>

        <?php
        // ── IndexNow panel ────────────────────────────────────────────────────
        $in_key      = holyprofweb_indexnow_key();
        $in_enabled  = (bool) get_option( 'hpw_indexnow_enabled', 1 );
        $in_file_ok  = holyprofweb_indexnow_key_file_exists( $in_key );
        $in_key_url  = holyprofweb_indexnow_key_url( $in_key );
        $in_log      = (array) get_option( 'hpw_indexnow_last_log', array() );
        $in_queue    = count( (array) get_option( 'hpw_indexnow_queue', array() ) );

        if ( isset( $_GET['hpw_indexnow'] ) ) {
            $msg = sanitize_key( $_GET['hpw_indexnow'] );
            $notice_map = array(
                'key_regenerated' => 'IndexNow key regenerated and key file rewritten.',
                'file_written'    => 'Key file written to server root.',
                'pinged'          => 'Submitted ' . absint( $_GET['count'] ?? 0 ) . ' URL(s) to IndexNow.',
                'toggled'         => 'IndexNow ' . ( $in_enabled ? 'enabled' : 'disabled' ) . '.',
            );
            if ( isset( $notice_map[ $msg ] ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice_map[ $msg ] ) . '</p></div>';
            }
        }

        $status_color  = $in_enabled ? ( $in_file_ok ? '#1a7f37' : '#b45309' ) : '#888';
        $status_label  = ! $in_enabled ? 'Disabled' : ( $in_file_ok ? 'Active' : 'Key file missing' );
        ?>

        <hr style="margin:36px 0 24px;">
        <h2>&#127760; <?php esc_html_e( 'IndexNow — Real-time Search Engine Pings', 'holyprofweb' ); ?></h2>
        <p style="color:#555;max-width:680px;">
            IndexNow notifies Bing, Yandex, and all participating engines <strong>instantly</strong> when you publish, update, or delete a post — no crawl delay, no waiting. One API call covers all engines.
        </p>

        <table class="form-table" role="presentation" style="max-width:680px;">
            <tr>
                <th>Status</th>
                <td>
                    <span style="display:inline-block;padding:3px 12px;border-radius:3px;background:<?php echo esc_attr( $status_color ); ?>;color:#fff;font-weight:600;font-size:12px;"><?php echo esc_html( $status_label ); ?></span>
                    &nbsp;
                    <form method="post" style="display:inline;">
                        <?php wp_nonce_field( 'hpw_indexnow_admin', 'hpw_indexnow_nonce' ); ?>
                        <input type="hidden" name="hpw_indexnow_action" value="toggle">
                        <button type="submit" class="button button-small"><?php echo $in_enabled ? 'Disable' : 'Enable'; ?></button>
                    </form>
                </td>
            </tr>
            <tr>
                <th>API Key</th>
                <td>
                    <code style="font-size:13px;background:#f5f5f5;padding:4px 10px;border-radius:3px;user-select:all;"><?php echo esc_html( $in_key ); ?></code>
                </td>
            </tr>
            <tr>
                <th>Key file URL</th>
                <td>
                    <a href="<?php echo esc_url( $in_key_url ); ?>" target="_blank" style="font-size:12px;"><?php echo esc_html( $in_key_url ); ?></a>
                    &nbsp;
                    <?php if ( $in_file_ok ) : ?>
                        <span style="color:#1a7f37;font-size:12px;font-weight:600;">&#10003; File exists on server</span>
                    <?php else : ?>
                        <span style="color:#c0392b;font-size:12px;font-weight:600;">&#10005; File missing — click "Write key file" below</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ( $in_log ) : ?>
            <tr>
                <th>Last submission</th>
                <td style="font-size:12px;color:#555;">
                    <?php echo esc_html( $in_log['time'] ?? '' ); ?> &mdash;
                    <?php echo esc_html( $in_log['count'] ?? 0 ); ?> URL(s) &mdash;
                    HTTP <?php echo esc_html( $in_log['code'] ?? '?' ); ?>
                    <?php if ( ! empty( $in_log['error'] ) ) : ?>
                        <span style="color:#c0392b;">&mdash; <?php echo esc_html( $in_log['error'] ); ?></span>
                    <?php endif; ?>
                    <br>
                    <?php foreach ( (array) ( $in_log['urls'] ?? array() ) as $u ) : ?>
                        <span style="color:#888;"><?php echo esc_html( $u ); ?></span><br>
                    <?php endforeach; ?>
                    <?php if ( ( $in_log['count'] ?? 0 ) > 10 ) : ?>
                        <span style="color:#888;">… and <?php echo esc_html( (int) $in_log['count'] - 10 ); ?> more</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ( $in_queue > 0 ) : ?>
            <tr>
                <th>Import queue</th>
                <td style="color:#b45309;font-size:12px;"><?php echo esc_html( $in_queue ); ?> URL(s) queued — will be submitted automatically on next page load.</td>
            </tr>
            <?php endif; ?>
        </table>

        <h3 style="margin-top:24px;"><?php esc_html_e( 'Actions', 'holyprofweb' ); ?></h3>
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;">

            <form method="post">
                <?php wp_nonce_field( 'hpw_indexnow_admin', 'hpw_indexnow_nonce' ); ?>
                <input type="hidden" name="hpw_indexnow_action" value="write_key_file">
                <button type="submit" class="button">&#128190; Write key file to server</button>
                <p class="description" style="margin-top:4px;">Rewrites the <code><?php echo esc_html( $in_key ); ?>.txt</code> file to your web root.</p>
            </form>

            <form method="post">
                <?php wp_nonce_field( 'hpw_indexnow_admin', 'hpw_indexnow_nonce' ); ?>
                <input type="hidden" name="hpw_indexnow_action" value="ping_all">
                <button type="submit" class="button" onclick="return confirm('Submit 50 most recent posts to IndexNow now?');">&#128228; Ping 50 recent posts</button>
                <p class="description" style="margin-top:4px;">Sends your 50 newest published URLs to Bing + all IndexNow engines.</p>
            </form>

            <form method="post">
                <?php wp_nonce_field( 'hpw_indexnow_admin', 'hpw_indexnow_nonce' ); ?>
                <input type="hidden" name="hpw_indexnow_action" value="ping_url">
                <input type="url" name="hpw_indexnow_url" placeholder="https://holyprofweb.com/specific-post/" style="width:360px;" required>
                <button type="submit" class="button">&#128205; Ping this URL</button>
            </form>

            <form method="post">
                <?php wp_nonce_field( 'hpw_indexnow_admin', 'hpw_indexnow_nonce' ); ?>
                <input type="hidden" name="hpw_indexnow_action" value="regenerate_key">
                <button type="submit" class="button button-link-delete" onclick="return confirm('This will generate a new key and delete the old key file. Are you sure?');">&#128260; Regenerate key</button>
                <p class="description" style="margin-top:4px;">Only do this if your key is compromised.</p>
            </form>

        </div>

        <h3 style="margin-top:28px;">How it works on this site</h3>
        <ul style="list-style:disc;margin-left:20px;color:#555;font-size:13px;line-height:1.8;">
            <li><strong>Publish</strong> — post URL + all its category &amp; tag pages + homepage are pinged immediately.</li>
            <li><strong>Update</strong> — same URLs pinged on every save while published.</li>
            <li><strong>Delete / Trash</strong> — homepage + category pages pinged so engines re-index the listing.</li>
            <li><strong>Category/Tag edit</strong> — that archive URL is pinged on term change.</li>
            <li><strong>XML Import</strong> — URLs are queued and batch-submitted in one API call at the end.</li>
            <li>All submissions go to <code>api.indexnow.org</code> which distributes to <strong>Bing, Yandex, Seznam, Naver</strong> and all participating engines.</li>
        </ul>

    </div>
    <?php
}

/* ── Reviews ──────────────────────────────────────────────────────────────── */

function holyprofweb_settings_redirects_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['hpw_redirect_saved'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_redirect_saved', __( 'Redirect saved.', 'holyprofweb' ), 'updated' );
    }
    if ( isset( $_GET['hpw_year_replaced'] ) ) {
        $count = (int) $_GET['hpw_year_replaced'];
        add_settings_error( 'hpw_messages', 'hpw_year_replaced', sprintf( __( 'Year replacement completed. Updated %d item(s).', 'holyprofweb' ), $count ), 'updated' );
    }
    settings_errors( 'hpw_messages' );
    $redirect_preview = holyprofweb_parse_redirect_rules();
    ?>
    <div class="wrap">
        <h1>&#10145; <?php esc_html_e( 'HPW Settings - Redirects', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'redirects' ); ?>

        <div class="hpw-search-card" style="background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:18px;margin:0 0 18px;">
            <h2 style="margin:0 0 8px;"><?php esc_html_e( 'Add Redirect', 'holyprofweb' ); ?></h2>
            <p style="margin:0 0 14px;color:#646970;"><?php esc_html_e( 'Use this for old URL to new URL redirects. The redirect works immediately after save.', 'holyprofweb' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-redirects' ) ); ?>" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;gap:12px;align-items:end;">
                <?php wp_nonce_field( 'hpw_redirect_add_action', 'hpw_redirect_add_nonce' ); ?>
                <input type="hidden" name="page" value="hpw-settings-redirects" />
                <input type="hidden" name="hpw_redirect_action" value="add_redirect" />
                <label>
                    <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Old URL or path', 'holyprofweb' ); ?></span>
                    <input type="text" name="hpw_redirect_old_url" class="regular-text" style="width:100%;" placeholder="/old-url/ or https://holyprofweb.com/old-url/" />
                </label>
                <label>
                    <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'New URL or path', 'holyprofweb' ); ?></span>
                    <input type="text" name="hpw_redirect_new_url" class="regular-text" style="width:100%;" placeholder="/new-url/ or https://holyprofweb.com/new-url/" />
                </label>
                <?php submit_button( __( 'Save Redirect', 'holyprofweb' ), 'primary', '', false ); ?>
            </form>
        </div>

        <div class="hpw-search-card" style="background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:18px;margin:0 0 18px;">
            <h2 style="margin:0 0 8px;"><?php esc_html_e( 'Bulk Year Replace', 'holyprofweb' ); ?></h2>
            <p style="margin:0 0 14px;color:#646970;"><?php esc_html_e( 'Change years in titles and slugs fast. If a published URL changes, the redirect is stored immediately.', 'holyprofweb' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-redirects' ) ); ?>" style="display:grid;grid-template-columns:120px 120px 220px auto;gap:12px;align-items:end;">
                <?php wp_nonce_field( 'hpw_redirect_year_action', 'hpw_redirect_year_nonce' ); ?>
                <input type="hidden" name="page" value="hpw-settings-redirects" />
                <input type="hidden" name="hpw_redirect_action" value="replace_year" />
                <label>
                    <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'From year', 'holyprofweb' ); ?></span>
                    <input type="number" name="hpw_year_from" min="2000" max="2099" class="small-text" style="width:100%;" value="2024" />
                </label>
                <label>
                    <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'To year', 'holyprofweb' ); ?></span>
                    <input type="number" name="hpw_year_to" min="2000" max="2099" class="small-text" style="width:100%;" value="2026" />
                </label>
                <label>
                    <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Replace scope', 'holyprofweb' ); ?></span>
                    <select name="hpw_year_scope" style="width:100%;">
                        <option value="titles-slugs"><?php esc_html_e( 'Titles and slugs only', 'holyprofweb' ); ?></option>
                        <option value="all"><?php esc_html_e( 'Titles, slugs, and content', 'holyprofweb' ); ?></option>
                    </select>
                </label>
                <?php submit_button( __( 'Run Year Update', 'holyprofweb' ), 'secondary', '', false, array( 'onclick' => "return confirm('Run the year replacement across posts and pages now?');" ) ); ?>
            </form>
        </div>

        <div class="hpw-search-card" style="background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:18px;">
            <h2 style="margin:0 0 8px;"><?php esc_html_e( 'Current Redirect Map', 'holyprofweb' ); ?></h2>
            <p style="margin:0 0 14px;color:#646970;"><?php esc_html_e( 'These redirects are already active sitewide.', 'holyprofweb' ); ?></p>
            <?php if ( empty( $redirect_preview ) ) : ?>
                <p><?php esc_html_e( 'No redirect rules yet.', 'holyprofweb' ); ?></p>
            <?php else : ?>
                <div style="border:1px solid #e6dcc7;border-radius:16px;background:#fffdfa;overflow:hidden;">
                    <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:0;background:#f7f2e7;padding:10px 14px;font-weight:700;">
                        <span><?php esc_html_e( 'Old URL', 'holyprofweb' ); ?></span>
                        <span><?php esc_html_e( 'New URL', 'holyprofweb' ); ?></span>
                    </div>
                    <div style="max-height:420px;overflow:auto;">
                        <?php foreach ( $redirect_preview as $old_path => $new_path ) : ?>
                        <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;padding:10px 14px;border-top:1px solid #eee6d6;">
                            <code><?php echo esc_html( $old_path ); ?></code>
                            <code><?php echo esc_html( $new_path ); ?></code>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function holyprofweb_settings_reviews_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Settings saved.', 'holyprofweb' ), 'updated' );
    }
    if ( isset( $_GET['hpw_content_updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_content_saved', __( 'Content controls updated.', 'holyprofweb' ), 'updated' );
    }
    $content_search  = isset( $_GET['hpw_content_search'] ) ? sanitize_text_field( wp_unslash( $_GET['hpw_content_search'] ) ) : '';
    $content_type    = isset( $_GET['hpw_content_type'] ) ? sanitize_key( wp_unslash( $_GET['hpw_content_type'] ) ) : 'all';
    $filter_options  = holyprofweb_get_admin_content_filter_options();
    if ( ! isset( $filter_options[ $content_type ] ) ) {
        $content_type = 'all';
    }
    $content_rows    = holyprofweb_get_admin_content_rows( $content_search, $content_type, 30 );
    $verdict_options = holyprofweb_get_verdict_options();
    settings_errors( 'hpw_messages' );
    ?>
    <div class="wrap">
        <h1>&#9733; <?php esc_html_e( 'HPW Settings — Content & Reviews', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'reviews' ); ?>
        <style>
            @media (max-width: 960px) {
                .hpw-content-desk form[method="get"] {
                    grid-template-columns: 1fr !important;
                }
                .hpw-content-desk table thead {
                    display: none;
                }
                .hpw-content-desk table,
                .hpw-content-desk tbody,
                .hpw-content-desk tr,
                .hpw-content-desk td {
                    display: block;
                    width: 100%;
                }
                .hpw-content-desk tr {
                    margin-bottom: 14px;
                    border: 1px solid #ece4d4;
                    border-radius: 18px;
                    overflow: hidden;
                }
            }
        </style>
        <form method="post" action="options.php">
            <?php settings_fields( 'hpw_reviews' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Auto-approve reviews', 'holyprofweb' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="hpw_review_auto_approve" value="1" <?php checked( 1, get_option( 'hpw_review_auto_approve', 0 ) ); ?> /></label>
                        <p class="description"><?php esc_html_e( 'When OFF, reviews await admin approval before showing publicly.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Minimum review length (characters)', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_review_min_length" value="<?php echo esc_attr( get_option( 'hpw_review_min_length', 20 ) ); ?>" min="10" max="500" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Allow anonymous name (no real name required)', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_review_allow_anon" value="1" <?php checked( 1, get_option( 'hpw_review_allow_anon', 0 ) ); ?> /></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Spam / blocked words (one per line)', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="hpw_review_spam_words" rows="6" class="large-text"><?php echo esc_textarea( get_option( 'hpw_review_spam_words', '' ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Reviews containing any of these words will be held for moderation.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Review Settings', 'holyprofweb' ) ); ?>
        </form>

        <hr>
        <div class="hpw-content-desk" style="background:linear-gradient(180deg,#ffffff 0%,#fbfaf7 100%);border:1px solid #e7dec9;border-radius:22px;padding:22px 22px 18px;box-shadow:0 18px 40px rgba(15,23,42,0.05);">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0 0 6px;"><?php esc_html_e( 'Content Control Desk', 'holyprofweb' ); ?></h2>
                <p style="margin:0;color:#5f6470;max-width:760px;"><?php esc_html_e( 'Search companies, apps, websites, and blog/report posts. Update the display score, trust label, source URL, and location focus without opening each full editor.', 'holyprofweb' ); ?></p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="verdict-badge verdict-badge--legit"><?php esc_html_e( 'Legit', 'holyprofweb' ); ?></span>
                <span class="verdict-badge verdict-badge--caution"><?php esc_html_e( 'Complications', 'holyprofweb' ); ?></span>
                <span class="verdict-badge verdict-badge--scam"><?php esc_html_e( 'Scam Alert', 'holyprofweb' ); ?></span>
            </div>
        </div>
        <form id="hpw-content-desk-filter" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:grid;grid-template-columns:minmax(280px,1.4fr) minmax(180px,0.6fr) auto;gap:12px;align-items:end;margin:18px 0 20px;">
            <input type="hidden" name="page" value="hpw-settings-reviews">
            <div>
                <label for="hpw-content-search"><strong><?php esc_html_e( 'Search', 'holyprofweb' ); ?></strong></label><br>
                <input type="search" id="hpw-content-search" name="hpw_content_search" value="<?php echo esc_attr( $content_search ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. GTBank, Bet9ja, Moniepoint', 'holyprofweb' ); ?>" style="width:100%;max-width:none;">
            </div>
            <div>
                <label for="hpw-content-type"><strong><?php esc_html_e( 'Type', 'holyprofweb' ); ?></strong></label><br>
                <select id="hpw-content-type" name="hpw_content_type" style="width:100%;">
                    <?php foreach ( $filter_options as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $content_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php submit_button( __( 'Search Content', 'holyprofweb' ), 'secondary', '', false ); ?>
        </form>
        <?php holyprofweb_render_admin_live_filter_script( 'hpw-content-desk-filter', 'input[type="search"], select' ); ?>
        <?php if ( empty( $content_rows ) ) : ?>
        <p><?php esc_html_e( 'No tracked content matched your search yet.', 'holyprofweb' ); ?></p>
        <?php else : ?>
        <table class="widefat striped" style="border:0;box-shadow:none;background:transparent;">
            <thead><tr>
                <th><?php esc_html_e( 'Title', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Type', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Live Score', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Quick Controls', 'holyprofweb' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $content_rows as $content_post ) : ?>
                <?php
                $post_id          = (int) $content_post->ID;
                $live_rating      = holyprofweb_get_post_rating( $post_id );
                $review_count     = holyprofweb_get_review_count( $post_id );
                $rating_override  = get_post_meta( $post_id, '_hpw_rating_override', true );
                $verdict_override = (string) get_post_meta( $post_id, '_hpw_verdict_override', true );
                $source_url       = (string) get_post_meta( $post_id, '_hpw_source_url', true );
                $external_image   = (string) get_post_meta( $post_id, 'external_image', true );
                $country_focus    = (string) get_post_meta( $post_id, '_hpw_country_focus', true );
                $status_object    = get_post_status_object( get_post_status( $post_id ) );
                $verdict_preview  = holyprofweb_get_review_verdict( $post_id );
                $has_featured     = holyprofweb_post_has_trusted_featured_image( $post_id );
                ?>
            <tr style="background:#fff;">
                <td style="padding:18px 16px;vertical-align:top;">
                    <strong><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></strong><br>
                    <span><?php echo esc_html( $status_object ? $status_object->label : ucfirst( (string) get_post_status( $post_id ) ) ); ?></span>
                    <span style="color:#6b7280;"> • <?php echo esc_html( mysql2date( 'Y-m-d', $content_post->post_modified ) ); ?></span><br>
                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View live', 'holyprofweb' ); ?></a>
                </td>
                <td style="padding:18px 16px;vertical-align:top;">
                    <strong><?php echo esc_html( holyprofweb_get_admin_content_kind( $post_id ) ); ?></strong><br>
                    <span><?php echo esc_html( implode( ', ', wp_list_pluck( get_the_category( $post_id ), 'name' ) ) ); ?></span>
                </td>
                <td style="padding:18px 16px;vertical-align:top;">
                    <strong><?php echo $live_rating ? esc_html( number_format_i18n( $live_rating, 1 ) ) . '/5' : esc_html__( 'No rating yet', 'holyprofweb' ); ?></strong><br>
                    <span><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $review_count, 'holyprofweb' ), $review_count ) ); ?></span><br>
                    <span class="verdict-badge <?php echo esc_attr( $verdict_preview['class'] ); ?>" style="margin-top:8px;"><?php echo esc_html( $verdict_preview['label'] ); ?></span>
                </td>
                <td style="min-width:340px;padding:18px 16px;vertical-align:top;">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-reviews' ) ); ?>" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 12px;padding:14px;border:1px solid #ece4d4;border-radius:18px;background:linear-gradient(180deg,#fffdfa 0%,#ffffff 100%);">
                        <?php wp_nonce_field( 'hpw_reviews_content_update', 'hpw_reviews_content_nonce' ); ?>
                        <input type="hidden" name="page" value="hpw-settings-reviews">
                        <input type="hidden" name="hpw_reviews_content_update" value="1">
                        <input type="hidden" name="hpw_content_post_id" value="<?php echo esc_attr( $post_id ); ?>">
                        <input type="hidden" name="hpw_content_search" value="<?php echo esc_attr( $content_search ); ?>">
                        <input type="hidden" name="hpw_content_type" value="<?php echo esc_attr( $content_type ); ?>">
                        <label>
                            <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Display rating', 'holyprofweb' ); ?></span>
                            <input type="number" name="hpw_rating_override" min="0" max="5" step="0.1" value="<?php echo esc_attr( $rating_override ); ?>" class="small-text" style="width:100%;">
                        </label>
                        <label>
                            <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Verdict', 'holyprofweb' ); ?></span>
                            <select name="hpw_verdict_override" style="width:100%;">
                                <?php foreach ( $verdict_options as $value => $option ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $verdict_override, $value ); ?>><?php echo esc_html( $option['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label style="grid-column:1 / -1;">
                            <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Source URL', 'holyprofweb' ); ?></span>
                            <input type="text" inputmode="url" autocapitalize="off" spellcheck="false" name="hpw_source_url" value="<?php echo esc_attr( $source_url ); ?>" class="regular-text" style="width:100%;" placeholder="example.com or https://example.com">
                        </label>
                        <label style="grid-column:1 / -1;">
                            <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Image URL override', 'holyprofweb' ); ?></span>
                            <input type="url" name="hpw_external_image" value="<?php echo esc_attr( $external_image ); ?>" class="regular-text" style="width:100%;">
                        </label>
                        <label style="grid-column:1 / -1;">
                            <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Upload featured image', 'holyprofweb' ); ?></span>
                            <input type="file" name="hpw_featured_upload" accept="image/*" style="width:100%;">
                            <span style="display:block;margin-top:6px;color:#6b7280;font-size:12px;"><?php echo esc_html( $has_featured ? __( 'A real featured image is already set. Uploading here replaces it directly.', 'holyprofweb' ) : __( 'Pick an image from your device and save. It uploads directly without opening the media library.', 'holyprofweb' ) ); ?></span>
                        </label>
                        <label>
                            <span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Country focus', 'holyprofweb' ); ?></span>
                            <input type="text" name="hpw_country_focus" value="<?php echo esc_attr( $country_focus ); ?>" class="regular-text" style="width:100%;">
                        </label>
                        <div style="align-self:end;">
                            <?php submit_button( __( 'Save', 'holyprofweb' ), 'primary small', '', false ); ?>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>

        <hr>
        <h2><?php esc_html_e( 'Pending Reviews', 'holyprofweb' ); ?></h2>
        <?php
        $pending = get_comments( array(
            'type'    => 'review',
            'status'  => 'hold',
            'number'  => 30,
        ) );
        if ( empty( $pending ) ) :
        ?>
        <p><?php esc_html_e( 'No reviews pending moderation.', 'holyprofweb' ); ?></p>
        <?php else : ?>
        <p><?php echo esc_html( sprintf( __( '%d review(s) awaiting approval.', 'holyprofweb' ), count( $pending ) ) ); ?></p>
        <table class="widefat striped">
            <thead><tr>
                <th><?php esc_html_e( 'Post', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Reviewer', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Rating', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Excerpt', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Date', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'holyprofweb' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $pending as $rev ) :
                $approve_url = wp_nonce_url(
                    admin_url( 'comment.php?action=approvecomment&c=' . (int) $rev->comment_ID ),
                    'approve-comment_' . (int) $rev->comment_ID
                );
                $trash_url = wp_nonce_url(
                    admin_url( 'comment.php?action=trashcomment&c=' . (int) $rev->comment_ID ),
                    'delete-comment_' . (int) $rev->comment_ID
                );
                $rating = get_comment_meta( $rev->comment_ID, 'rating', true );
            ?>
            <tr>
                <td><a href="<?php echo esc_url( get_permalink( $rev->comment_post_ID ) ); ?>"><?php echo esc_html( get_the_title( $rev->comment_post_ID ) ); ?></a></td>
                <td><?php echo esc_html( $rev->comment_author ); ?></td>
                <td><?php echo $rating ? esc_html( $rating ) . '/5' : '—'; ?></td>
                <td><?php echo esc_html( mb_substr( wp_strip_all_tags( $rev->comment_content ), 0, 80 ) ); ?>…</td>
                <td><?php echo esc_html( mysql2date( 'Y-m-d', $rev->comment_date ) ); ?></td>
                <td>
                    <a href="<?php echo esc_url( $approve_url ); ?>" class="button button-small button-primary"><?php esc_html_e( 'Approve', 'holyprofweb' ); ?></a>
                    <a href="<?php echo esc_url( $trash_url ); ?>" class="button button-small" onclick="return confirm('Trash this review?')"><?php esc_html_e( 'Trash', 'holyprofweb' ); ?></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <hr>
        <h2><?php esc_html_e( 'Recent Approved Reviews', 'holyprofweb' ); ?></h2>
        <?php
        $approved_reviews = get_comments( array(
            'type'   => 'review',
            'status' => 'approve',
            'number' => 12,
        ) );
        if ( empty( $approved_reviews ) ) :
        ?>
        <p><?php esc_html_e( 'No approved reviews yet.', 'holyprofweb' ); ?></p>
        <?php else : ?>
        <table class="widefat striped">
            <thead><tr>
                <th><?php esc_html_e( 'Post', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Reviewer', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Email', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Verified', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'holyprofweb' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $approved_reviews as $rev ) :
                $is_verified = holyprofweb_is_comment_verified( $rev->comment_ID );
                $toggle_url  = wp_nonce_url(
                    admin_url( 'admin.php?page=hpw-settings-reviews&hpw_verify_comment=' . (int) $rev->comment_ID . '&verified=' . ( $is_verified ? 0 : 1 ) ),
                    'hpw_verify_comment_' . (int) $rev->comment_ID
                );
            ?>
            <tr>
                <td><a href="<?php echo esc_url( get_permalink( $rev->comment_post_ID ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $rev->comment_post_ID ) ); ?></a></td>
                <td><?php echo esc_html( $rev->comment_author ); ?></td>
                <td><a href="mailto:<?php echo esc_attr( $rev->comment_author_email ); ?>"><?php echo esc_html( $rev->comment_author_email ); ?></a></td>
                <td><?php echo $is_verified ? esc_html__( 'Verified', 'holyprofweb' ) : esc_html__( 'Not verified', 'holyprofweb' ); ?></td>
                <td><a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small"><?php echo $is_verified ? esc_html__( 'Remove Badge', 'holyprofweb' ) : esc_html__( 'Mark Verified', 'holyprofweb' ); ?></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <hr>
        <h2><?php esc_html_e( 'Latest Salary Submissions', 'holyprofweb' ); ?></h2>
        <?php
        $salary_items = get_comments( array(
            'type'   => 'salary_submission',
            'status' => 'all',
            'number' => 20,
        ) );
        if ( empty( $salary_items ) ) :
        ?>
        <p><?php esc_html_e( 'No salary submissions yet.', 'holyprofweb' ); ?></p>
        <?php else : ?>
        <table class="widefat striped">
            <thead><tr>
                <th><?php esc_html_e( 'Post', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Company / Role', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Salary / Location', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Submitter', 'holyprofweb' ); ?></th>
                <th><?php esc_html_e( 'Status', 'holyprofweb' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $salary_items as $item ) : ?>
            <tr>
                <td><a href="<?php echo esc_url( get_permalink( $item->comment_post_ID ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $item->comment_post_ID ) ); ?></a></td>
                <td>
                    <strong><?php echo esc_html( get_comment_meta( $item->comment_ID, 'salary_company', true ) ); ?></strong><br>
                    <span><?php echo esc_html( get_comment_meta( $item->comment_ID, 'salary_role', true ) ); ?></span>
                </td>
                <td>
                    <strong><?php echo esc_html( get_comment_meta( $item->comment_ID, 'salary_amount', true ) ); ?></strong><br>
                    <span><?php echo esc_html( get_comment_meta( $item->comment_ID, 'salary_location', true ) ); ?></span>
                </td>
                <td>
                    <?php echo esc_html( $item->comment_author ); ?><br>
                    <a href="mailto:<?php echo esc_attr( $item->comment_author_email ); ?>"><?php echo esc_html( $item->comment_author_email ); ?></a>
                </td>
                <td><?php echo '1' === (string) $item->comment_approved ? esc_html__( 'Approved', 'holyprofweb' ) : esc_html__( 'Pending', 'holyprofweb' ); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

/* ── Emails ───────────────────────────────────────────────────────────────── */

function holyprofweb_settings_emails_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Settings saved.', 'holyprofweb' ), 'updated' );
    }
    settings_errors( 'hpw_messages' );
    ?>
    <div class="wrap">
        <h1>&#128231; <?php esc_html_e( 'HPW Settings — Emails', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'emails' ); ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'hpw_emails' ); ?>
            <h2><?php esc_html_e( 'Sender Identity', 'holyprofweb' ); ?></h2>
            <p class="description"><?php esc_html_e( 'All system emails (review notifications, subscriber welcome) will come from this identity.', 'holyprofweb' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'From Name', 'holyprofweb' ); ?></th>
                    <td><input type="text" name="hpw_email_from_name" value="<?php echo esc_attr( get_option( 'hpw_email_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'From Email Address', 'holyprofweb' ); ?></th>
                    <td><input type="email" name="hpw_email_from_address" value="<?php echo esc_attr( get_option( 'hpw_email_from_address', get_option( 'admin_email' ) ) ); ?>" class="regular-text" /></td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Review Notifications', 'holyprofweb' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Notify admin on new review', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_email_notify_review" value="1" <?php checked( 1, get_option( 'hpw_email_notify_review', 1 ) ); ?> /> <?php esc_html_e( 'Send email when a new review is submitted', 'holyprofweb' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Notification recipient email', 'holyprofweb' ); ?></th>
                    <td><input type="email" name="hpw_email_notify_address" value="<?php echo esc_attr( get_option( 'hpw_email_notify_address', get_option( 'admin_email' ) ) ); ?>" class="regular-text" /></td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Subscriber Welcome Email', 'holyprofweb' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Sent automatically when someone subscribes via the email capture widget. Use {name} if available.', 'holyprofweb' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Subject line', 'holyprofweb' ); ?></th>
                    <td><input type="text" name="hpw_email_welcome_subject" value="<?php echo esc_attr( get_option( 'hpw_email_welcome_subject', "Welcome to HolyprofWeb — You're on the list!" ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Email body', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="hpw_email_welcome_body" rows="8" class="large-text"><?php echo esc_textarea( get_option( 'hpw_email_welcome_body', "Hi,\n\nThanks for subscribing to HolyprofWeb — your trusted source for company reviews, salary data, and career insights.\n\nWe'll send updates directly to your inbox.\n\nStay informed,\nThe HolyprofWeb Team" ) ); ?></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Email Settings', 'holyprofweb' ) ); ?>
        </form>
    </div>
    <?php
}

/* ── Languages ────────────────────────────────────────────────────────────── */

function holyprofweb_settings_languages_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Settings saved.', 'holyprofweb' ), 'updated' );
    }
    settings_errors( 'hpw_messages' );

    $languages = array(
        'en_US' => 'English (US)',
        'en_GB' => 'English (UK)',
        'fr_FR' => 'French (Français)',
        'es_ES' => 'Spanish (Español)',
        'pt_BR' => 'Portuguese (Português)',
        'ar'    => 'Arabic (العربية) — RTL',
        'yo'    => 'Yoruba',
        'ig_NG' => 'Igbo',
        'ha'    => 'Hausa',
        'sw'    => 'Swahili',
        'zh_CN' => 'Chinese Simplified',
    );
    $languages = holyprofweb_get_language_catalog();
    $enabled_languages = holyprofweb_get_enabled_languages();
    $locale = holyprofweb_detect_visitor_locale();
    ?>
    <div class="wrap">
        <h1>&#127758; <?php esc_html_e( 'HPW Settings — Languages & Geo', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'languages' ); ?>
        <div class="notice notice-info inline" style="margin:0 0 16px;">
            <p><strong><?php esc_html_e( 'How country targeting works:', 'holyprofweb' ); ?></strong> <?php esc_html_e( 'HPW checks your CDN or proxy country header first, then falls back to browser language or IP lookup depending on your mode. Posts with a matching Country Focus, like USA or Nigeria, are then prioritized first in key discovery sections.', 'holyprofweb' ); ?></p>
            <p><?php echo esc_html( sprintf( __( 'Current detected country on this request: %1$s (%2$s via %3$s)', 'holyprofweb' ), $locale['country'], $locale['region'], $locale['source'] ) ); ?></p>
        </div>
        <form method="post" action="options.php">
            <?php settings_fields( 'hpw_languages' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enable multilingual mode', 'holyprofweb' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="hpw_multilingual_enabled" value="1" <?php checked( 1, get_option( 'hpw_multilingual_enabled', 0 ) ); ?> /></label>
                        <p class="description"><?php esc_html_e( 'When enabled, theme strings are fully translatable. Pair with WPML or Polylang for full front-end translation.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Default language', 'holyprofweb' ); ?></th>
                    <td>
                        <select name="hpw_default_language">
                            <?php foreach ( $languages as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( get_option( 'hpw_default_language', 'en_US' ), $code ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Languages in the globe menu', 'holyprofweb' ); ?></th>
                    <td>
                        <select name="hpw_enabled_languages[]" multiple size="7" style="min-width:280px;">
                            <?php foreach ( $languages as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( in_array( $code, $enabled_languages, true ) ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Choose the languages the header globe should show. Hold Ctrl or Cmd to pick multiple languages.', 'holyprofweb' ); ?></p>
                        <p class="description"><?php esc_html_e( 'If Polylang or WPML is active, the globe will use real translated URLs automatically. Without a translation plugin, the theme still shows the configured language list so the interface stays launch-ready.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Country targeting mode', 'holyprofweb' ); ?></th>
                    <td>
                        <select name="hpw_country_mode">
                            <option value="headers" <?php selected( get_option( 'hpw_country_mode', 'headers' ), 'headers' ); ?>><?php esc_html_e( 'Use CDN / proxy country headers when available', 'holyprofweb' ); ?></option>
                            <option value="language" <?php selected( get_option( 'hpw_country_mode', 'headers' ), 'language' ); ?>><?php esc_html_e( 'Use browser language only', 'holyprofweb' ); ?></option>
                            <option value="manual" <?php selected( get_option( 'hpw_country_mode', 'headers' ), 'manual' ); ?>><?php esc_html_e( 'Manual content focus only', 'holyprofweb' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Use headers mode when your live server or CDN sends country codes such as CF-IPCountry.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Enable RTL layout support', 'holyprofweb' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="hpw_rtl_support" value="1" <?php checked( 1, get_option( 'hpw_rtl_support', 0 ) ); ?> /></label>
                        <p class="description"><?php esc_html_e( 'Flips layout direction for Arabic, Hebrew, etc. Only needed if you serve RTL content without WPML.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Translation Plugin Compatibility', 'holyprofweb' ); ?></h2>
            <div class="notice notice-info inline" style="margin:0 0 16px;">
                <p>
                    <?php esc_html_e( 'HolyprofWeb is fully compatible with WPML and Polylang. All theme strings are wrapped in __() / esc_html__(). Install either plugin and the theme will automatically expose all strings for translation.', 'holyprofweb' ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Recommended setup:', 'holyprofweb' ); ?></strong>
                    <?php esc_html_e( 'Install Polylang (free) → Add languages → Translate posts and categories via the Posts screen. No extra config needed.', 'holyprofweb' ); ?>
                </p>
            </div>
            <h2><?php esc_html_e( 'Geo Header Detection', 'holyprofweb' ); ?></h2>
            <?php $geo_headers = holyprofweb_get_geo_header_debug(); ?>
            <?php if ( empty( $geo_headers ) ) : ?>
                <p><?php esc_html_e( 'No country headers detected on this request yet. That is normal on local development. When live, your CDN or reverse proxy can send them automatically.', 'holyprofweb' ); ?></p>
            <?php else : ?>
                <table class="widefat striped" style="max-width:760px;">
                    <thead><tr><th><?php esc_html_e( 'Header', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Value', 'holyprofweb' ); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ( $geo_headers as $key => $value ) : ?>
                        <tr><td><?php echo esc_html( $key ); ?></td><td><?php echo esc_html( $value ); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php submit_button( __( 'Save Language Settings', 'holyprofweb' ) ); ?>
        </form>
    </div>
    <?php
}

/* ── Settings nav helper ──────────────────────────────────────────────────── */

function holyprofweb_settings_nav( $active = 'general' ) {
    $tabs = array(
        'general'    => array( 'label' => 'Site & SEO',         'slug' => 'hpw-settings',            'icon' => '&#9881;' ),
        'seo-debug'  => array( 'label' => 'SEO Debug',          'slug' => 'hpw-settings-seo-debug',  'icon' => '&#128295;' ),
        'search'     => array( 'label' => 'Search & Audience',  'slug' => 'hpw-settings-search',     'icon' => '&#128269;' ),
        'reviews'    => array( 'label' => 'Content & Reviews',  'slug' => 'hpw-settings-reviews',    'icon' => '&#9733;' ),
        'redirects'  => array( 'label' => 'Redirects',          'slug' => 'hpw-settings-redirects',  'icon' => '&#10145;' ),
        'ads'        => array( 'label' => 'Ads',                'slug' => 'hpw-settings-ads',        'icon' => '&#128250;' ),
        'languages'  => array( 'label' => 'Languages & Geo',    'slug' => 'hpw-settings-languages',  'icon' => '&#127758;' ),
        'emails'     => array( 'label' => 'Emails',             'slug' => 'hpw-settings-emails',     'icon' => '&#128231;' ),
        'automation' => array( 'label' => 'Automation',         'slug' => 'hpw-settings-automation', 'icon' => '&#9889;' ),
    );
    echo '<nav class="nav-tab-wrapper" style="margin-bottom:20px;">';
    foreach ( $tabs as $key => $tab ) {
        $class = ( $key === $active ) ? 'nav-tab nav-tab-active' : 'nav-tab';
        printf(
            '<a href="%s" class="%s">%s %s</a>',
            esc_url( admin_url( 'admin.php?page=' . $tab['slug'] ) ),
            esc_attr( $class ),
            $tab['icon'],
            esc_html( $tab['label'] )
        );
    }
    echo '</nav>';
    echo '<div class="hpw-settings-search" style="margin:0 0 18px;">';
    echo '<label for="hpw-settings-search-input" style="display:block;font-weight:600;margin:0 0 6px;">' . esc_html__( 'Search settings', 'holyprofweb' ) . '</label>';
    echo '<input id="hpw-settings-search-input" type="search" placeholder="' . esc_attr__( 'Type copy, email, redirect, image, review...', 'holyprofweb' ) . '" style="width:100%;max-width:420px;padding:10px 12px;border:1px solid #d0d7de;border-radius:10px;" />';
    echo '<p style="margin:6px 0 0;color:#646970;">' . esc_html__( 'This filters the current HPW settings page only.', 'holyprofweb' ) . '</p>';
    echo '</div>';
}

function holyprofweb_settings_search_script() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || false === strpos( (string) $screen->id, 'hpw-settings' ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('hpw-settings-search-input');
        if (!input) return;

        var rows = Array.prototype.slice.call(document.querySelectorAll('.form-table tr, .widefat tr, .hpw-search-card, .hpw-search-block'));
        input.addEventListener('input', function () {
            var query = (input.value || '').toLowerCase().trim();
            rows.forEach(function (row) {
                var text = (row.textContent || '').toLowerCase();
                row.style.display = !query || text.indexOf(query) !== -1 ? '' : 'none';
            });
        });
    });
    </script>
    <?php
}
add_action( 'admin_footer', 'holyprofweb_settings_search_script' );

function holyprofweb_admin_editor_helpers() {
    if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'post' !== $screen->base || 'post' !== $screen->post_type ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var salaryMeta = document.getElementById('hpw_salary_data');
        if (!salaryMeta) return;

        function hasCompaniesCategory() {
            return Array.prototype.slice.call(document.querySelectorAll('input[name="post_category[]"]:checked')).some(function (input) {
                var label = document.querySelector('label[for="' + input.id + '"]');
                return !!(label && /companies/i.test(label.textContent || ''));
            });
        }

        function syncSalaryMetaBox() {
            salaryMeta.style.display = hasCompaniesCategory() ? '' : 'none';
        }

        document.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'post_category[]') {
                syncSalaryMetaBox();
            }
        });

        syncSalaryMetaBox();
    });
    </script>
    <?php
}
add_action( 'admin_footer', 'holyprofweb_admin_editor_helpers', 30 );

// ── Image Desk: shared helpers ────────────────────────────────────────────────

function holyprofweb_get_post_image_state( $post_id ) {
    if ( holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
        return array( 'label' => 'Showing now: Featured image', 'type' => 'trusted', 'detail' => 'Real attached image' );
    }
    if ( get_post_meta( $post_id, 'external_image', true ) ) {
        return array( 'label' => 'Showing now: Manual image link', 'type' => 'external', 'detail' => 'Custom external image URL' );
    }
    if ( get_post_meta( $post_id, '_holyprofweb_remote_image_url', true ) ) {
        return array( 'label' => 'Showing now: Remote / OG image', 'type' => 'remote', 'detail' => 'Fetched from source site / logo' );
    }
    if ( holyprofweb_post_has_generated_thumbnail_attachment( $post_id ) ) {
        return array( 'label' => 'Generated — GD', 'type' => 'gd' );
    }
    if ( get_post_meta( $post_id, '_holyprofweb_gen_image_url', true ) ) {
        return array( 'label' => 'Generated — SVG', 'type' => 'svg' );
    }
    return array( 'label' => 'No image', 'type' => 'none' );
}

/**
 * Query Image Desk posts with all active filters.
 *
 * @param array $f Keys: s, type, status, cat_id, country, orderby
 */
function holyprofweb_query_image_desk_posts( $f ) {
    $f = array_merge( array(
        's'       => '',
        'type'    => '',
        'status'  => '',
        'cat_id'  => 0,
        'country' => '',
        'orderby' => 'date',
    ), (array) $f );

    $args = array(
        'post_type'      => 'post',
        'post_status'    => $f['status'] ? array( sanitize_key( $f['status'] ) ) : array( 'publish', 'draft', 'pending', 'future' ),
        'posts_per_page' => 30,
        's'              => $f['s'],
        'orderby'        => in_array( $f['orderby'], array( 'date', 'modified', 'title' ), true ) ? $f['orderby'] : 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    );

    if ( $f['cat_id'] > 0 ) {
        $args['cat'] = (int) $f['cat_id'];
    }

    // Image-type meta queries (DB-level where possible).
    $meta_q = array();
    switch ( $f['type'] ) {
        case 'none':
            $meta_q = array(
                'relation' => 'AND',
                array( 'key' => '_holyprofweb_gen_image_url',      'compare' => 'NOT EXISTS' ),
                array( 'key' => '_holyprofweb_remote_image_url',   'compare' => 'NOT EXISTS' ),
                array( 'key' => 'external_image',                  'compare' => 'NOT EXISTS' ),
                array( 'key' => '_thumbnail_id',                   'compare' => 'NOT EXISTS' ),
            );
            break;
        case 'remote':
            $meta_q = array( array( 'key' => '_holyprofweb_remote_image_url', 'compare' => 'EXISTS' ) );
            break;
        case 'generated':
            $meta_q = array( array( 'key' => '_holyprofweb_gen_image_url', 'compare' => 'EXISTS' ) );
            break;
        case 'external':
            $meta_q = array( array( 'key' => 'external_image', 'compare' => 'EXISTS' ) );
            break;
        case 'featured':
            $meta_q = array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) );
            break;
    }

    if ( $f['country'] ) {
        $country_q = array( 'key' => '_hpw_country_focus', 'value' => sanitize_text_field( $f['country'] ) );
        $meta_q    = $meta_q ? array_merge( array( 'relation' => 'AND' ), array( $meta_q ), array( $country_q ) ) : array( $country_q );
    }

    if ( $meta_q ) {
        $args['meta_query'] = $meta_q; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    }

    $posts = get_posts( $args );

    // PHP-level post-filter for GD vs SVG (can't distinguish at DB level).
    if ( in_array( $f['type'], array( 'gd', 'svg' ), true ) ) {
        $posts = array_values( array_filter( $posts, function( $p ) use ( $f ) {
            $state = holyprofweb_get_post_image_state( $p->ID );
            return $state['type'] === $f['type'];
        } ) );
    }

    return $posts;
}

function holyprofweb_render_image_desk_rows( $posts, $filters = array() ) {
    $search = is_string( $filters ) ? $filters : ( $filters['s'] ?? '' );

    $badge_colors = array(
        'trusted'  => '#1a7f37',
        'external' => '#0366d6',
        'remote'   => '#6f42c1',
        'gd'       => '#b45309',
        'svg'      => '#b45309',
        'none'     => '#c0392b',
    );

    if ( empty( $posts ) ) {
        echo '<p style="color:#666;padding:12px 0;">' . esc_html__( 'No posts matched the current filters.', 'holyprofweb' ) . '</p>';
        return;
    }

    $action_url = esc_url( admin_url( 'admin.php?page=hpw-settings-automation' ) );

    echo '<p style="color:#888;font-size:12px;margin:0 0 8px;">' . esc_html( count( $posts ) ) . ' post(s) shown</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__( 'Post', 'holyprofweb' ) . '</th>';
    echo '<th>' . esc_html__( 'Image Type', 'holyprofweb' ) . '</th>';
    echo '<th>' . esc_html__( 'Category', 'holyprofweb' ) . '</th>';
    echo '<th>' . esc_html__( 'Country', 'holyprofweb' ) . '</th>';
    echo '<th>' . esc_html__( 'Status', 'holyprofweb' ) . '</th>';
    echo '<th>' . esc_html__( 'Modified', 'holyprofweb' ) . '</th>';
    echo '<th>' . esc_html__( 'Actions', 'holyprofweb' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $posts as $post ) {
        $pid   = (int) $post->ID;
        $state = holyprofweb_get_post_image_state( $pid );
        $color = $badge_colors[ $state['type'] ] ?? '#888';
        $nonce = wp_create_nonce( 'hpw_regenerate_post_image' );
        $state_label  = $state['label'];
        $state_detail = $state['detail'] ?? '';

        if ( 'gd' === $state['type'] ) {
            $state_label  = 'Showing now: Generated GD';
            $state_detail = 'JPEG file fallback';
        } elseif ( 'svg' === $state['type'] ) {
            $generated    = (string) get_post_meta( $pid, '_holyprofweb_gen_image_url', true );
            $state_label  = 'Showing now: Generated SVG';
            $state_detail = 0 === strpos( $generated, 'data:image/svg+xml' ) ? 'SVG data fallback' : 'Generated fallback meta';
        } elseif ( 'none' === $state['type'] ) {
            $state_label  = 'Showing now: No image';
            $state_detail = 'Nothing attached yet';
        }

        $cats     = get_the_category( $pid );
        $cat_name = $cats ? $cats[0]->name : '—';
        $country  = (string) get_post_meta( $pid, '_hpw_country_focus', true ) ?: 'General';
        $status   = ucfirst( $post->post_status );

        echo '<tr>';
        echo '<td style="max-width:240px;"><a href="' . esc_url( get_permalink( $pid ) ) . '" target="_blank" style="font-weight:500;">' . esc_html( get_the_title( $pid ) ) . '</a> <a href="' . esc_url( get_edit_post_link( $pid ) ) . '" title="Edit post" style="color:#999;font-size:11px;margin-left:4px;" target="_blank">&#9998;</a></td>';
        echo '<td><span style="display:inline-block;padding:2px 8px;border-radius:3px;background:' . esc_attr( $color ) . ';color:#fff;font-size:11px;font-weight:600;white-space:nowrap;">'
            . esc_html( $state_label ) . '</span>';
        if ( $state_detail ) {
            echo '<div style="margin-top:4px;color:#666;font-size:11px;line-height:1.35;">' . esc_html( $state_detail ) . '</div>';
        }
        echo '</td>';
        echo '<td>' . esc_html( $cat_name ) . '</td>';
        echo '<td>' . esc_html( $country ) . '</td>';
        echo '<td>' . esc_html( $status ) . '</td>';
        echo '<td>' . esc_html( get_the_modified_date( 'Y-m-d', $pid ) ) . '</td>';
        echo '<td>';

        $base_inputs = '<input type="hidden" name="hpw_regenerate_post_image_nonce" value="' . esc_attr( $nonce ) . '">'
                     . '<input type="hidden" name="hpw_post_id" value="' . esc_attr( $pid ) . '">'
                     . '<input type="hidden" name="hpw_image_search" value="' . esc_attr( $search ) . '">';

        echo '<form method="post" action="' . $action_url . '" style="display:inline-flex;gap:4px;flex-wrap:wrap;">';
        echo $base_inputs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo '<button type="submit" name="hpw_action" value="regenerate_post_image" class="button button-small">Regenerate</button>';

        if ( 'gd' === $state['type'] ) {
            echo ' <button type="submit" name="hpw_action" value="switch_to_svg" class="button button-small" style="color:#b45309;border-color:#b45309;" title="Switch to SVG fallback">↔ SVG</button>';
        } elseif ( 'svg' === $state['type'] ) {
            echo ' <button type="submit" name="hpw_action" value="switch_to_gd" class="button button-small" style="color:#b45309;border-color:#b45309;" title="Generate real GD file">↔ GD</button>';
        } elseif ( 'none' === $state['type'] ) {
            echo ' <button type="submit" name="hpw_action" value="regenerate_post_image" class="button button-small button-primary">Generate</button>';
        }

        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
}

// AJAX handler for live image desk search + filter.
add_action( 'wp_ajax_hpw_image_desk_search', function () {
    check_ajax_referer( 'hpw_image_desk_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();

    $filters = array(
        's'       => isset( $_POST['s'] )       ? sanitize_text_field( wp_unslash( $_POST['s'] ) )       : '',
        'type'    => isset( $_POST['type'] )    ? sanitize_key( wp_unslash( $_POST['type'] ) )            : '',
        'status'  => isset( $_POST['status'] )  ? sanitize_key( wp_unslash( $_POST['status'] ) )          : '',
        'cat_id'  => isset( $_POST['cat_id'] )  ? absint( wp_unslash( $_POST['cat_id'] ) )                : 0,
        'country' => isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) )  : '',
        'orderby' => isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) )         : 'date',
    );

    $posts = holyprofweb_query_image_desk_posts( $filters );
    holyprofweb_render_image_desk_rows( $posts, $filters );
    wp_die();
} );

// Switch image type action handler.
add_action( 'admin_init', function () {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! isset( $_POST['hpw_action'] ) || ! in_array( $_POST['hpw_action'], array( 'switch_to_gd', 'switch_to_svg' ), true ) ) {
        return;
    }
    check_admin_referer( 'hpw_regenerate_post_image', 'hpw_regenerate_post_image_nonce' );
    $post_id = isset( $_POST['hpw_post_id'] ) ? absint( wp_unslash( $_POST['hpw_post_id'] ) ) : 0;
    if ( ! $post_id ) {
        return;
    }
    $action = sanitize_key( $_POST['hpw_action'] );
    if ( 'switch_to_gd' === $action ) {
        holyprofweb_clear_post_image_state( $post_id, true );
        $post = get_post( $post_id );
        if ( $post ) {
            holyprofweb_generate_post_image_modern( $post_id, $post );
        }
    } else {
        // switch_to_svg: delete the GD file attachment, set SVG meta
        holyprofweb_clear_post_image_state( $post_id, true );
        $svg_url = holyprofweb_get_generated_svg_image_url( $post_id, 'card' );
        if ( $svg_url ) {
            update_post_meta( $post_id, '_holyprofweb_gen_image_url', $svg_url );
        }
    }
    $search = isset( $_POST['hpw_image_search'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_image_search'] ) ) : '';
    wp_safe_redirect( add_query_arg( array(
        'page'                 => 'hpw-settings-automation',
        'hpw_image_switched'   => $post_id,
        'hpw_image_switched_to'=> 'gd' === substr( $action, -2 ) ? 'gd' : 'svg',
        'hpw_image_search'     => $search,
    ), admin_url( 'admin.php' ) ) );
    exit;
} );

add_action( 'admin_init', function () {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_POST['hpw_debug_action'] ) || empty( $_GET['page'] ) || 'hpw-settings-seo-debug' !== $_GET['page'] ) {
        return;
    }

    check_admin_referer( 'hpw_debug_actions', 'hpw_debug_actions_nonce' );

    $action = sanitize_key( wp_unslash( $_POST['hpw_debug_action'] ) );
    $count  = 0;

    if ( 'run_draft_audit' === $action ) {
        holyprofweb_process_draft_queue();
        $count = count( holyprofweb_get_draft_publish_queue() );
    } elseif ( 'run_wp_cron' === $action ) {
        if ( function_exists( 'wp_cron' ) ) {
            wp_cron();
        }
        update_option( 'holyprofweb_wp_cron_last_run', time(), false );
    } elseif ( 'publish_overdue' === $action ) {
        $count = holyprofweb_publish_overdue_drafts_now();
        holyprofweb_process_draft_queue();
    } elseif ( 'run_content_audit' === $action ) {
        holyprofweb_run_content_audit();
        $count = count( holyprofweb_get_content_refresh_queue() );
    } elseif ( 'reschedule_crons' === $action ) {
        holyprofweb_unschedule_content_audit();
        holyprofweb_schedule_content_audit();
    } else {
        return;
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'             => 'hpw-settings-seo-debug',
                'hpw_debug_action' => $action,
                'hpw_debug_count'  => $count,
            ),
            admin_url( 'admin.php' )
        )
    );
    exit;
} );

function holyprofweb_settings_automation_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Settings saved.', 'holyprofweb' ), 'updated' );
    }
    if ( isset( $_GET['hpw_images_regenerated'] ) ) {
        add_settings_error(
            'hpw_messages',
            'hpw_images_regenerated',
            sprintf( __( 'Generated images refreshed: %d post(s).', 'holyprofweb' ), absint( $_GET['hpw_images_regenerated'] ) ),
            'updated'
        );
    }
    if ( isset( $_GET['hpw_images_reset'] ) ) {
        add_settings_error(
            'hpw_messages',
            'hpw_images_reset',
            sprintf( __( 'Generated image cache cleared: %d post(s).', 'holyprofweb' ), absint( $_GET['hpw_images_reset'] ) ),
            'updated'
        );
    }
    if ( isset( $_GET['hpw_image_regened'] ) ) {
        $regened_id    = absint( $_GET['hpw_image_regened'] );
        $regened_title = $regened_id ? get_the_title( $regened_id ) : '';
        add_settings_error(
            'hpw_messages',
            'hpw_image_regened',
            $regened_title
                ? sprintf( __( 'Image regenerated for: %s', 'holyprofweb' ), $regened_title )
                : __( 'Image regenerated.', 'holyprofweb' ),
            'updated'
        );
    }
    if ( isset( $_GET['hpw_image_switched'] ) ) {
        $switched_id    = absint( $_GET['hpw_image_switched'] );
        $switched_title = $switched_id ? get_the_title( $switched_id ) : '';
        $switched_type  = sanitize_key( $_GET['hpw_image_switched_to'] ?? '' );
        add_settings_error(
            'hpw_messages',
            'hpw_image_switched',
            $switched_title
                ? sprintf( __( 'Switched to %s for: %s', 'holyprofweb' ), strtoupper( $switched_type ), $switched_title )
                : __( 'Image type switched.', 'holyprofweb' ),
            'updated'
        );
    }
    settings_errors( 'hpw_messages' );

    $generated_posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_holyprofweb_gen_image_url',
    ) );
    $refresh_queue   = holyprofweb_get_content_refresh_queue();
    $last_audit_run  = absint( get_option( 'holyprofweb_content_audit_last_run', 0 ) );
    $image_styles    = holyprofweb_get_generated_image_styles();
    $current_style   = holyprofweb_get_generated_image_style();
    $sample_post_id  = 0;
    $sample_posts    = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    if ( ! empty( $sample_posts ) ) {
        $sample_post_id = (int) $sample_posts[0];
    }
    $sample_title = $sample_post_id ? get_the_title( $sample_post_id ) : 'How Safe Is [Brand Name] for New Users in 2026?';
    $prompt_text  = holyprofweb_build_ai_prompt_template( $sample_title, $sample_post_id );
    ?>
    <div class="wrap">
        <h1>&#9889; <?php esc_html_e( 'HPW Settings — Automation', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'automation' ); ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'hpw_automation' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Enable remote image fetch', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_enable_remote_image_fetch" value="1" <?php checked( 1, get_option( 'hpw_enable_remote_image_fetch', 1 ) ); ?> /> <?php esc_html_e( 'Allow OG image and Clearbit logo lookups when source URLs are available.', 'holyprofweb' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Enable generated fallback images', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_enable_generated_images" value="1" <?php checked( 1, get_option( 'hpw_enable_generated_images', 1 ) ); ?> /> <?php esc_html_e( 'Always create a branded image when no remote or featured image is available.', 'holyprofweb' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Generated image style', 'holyprofweb' ); ?></th>
                    <td>
                        <select name="hpw_generated_image_style">
                            <?php foreach ( $image_styles as $style_key => $style_meta ) : ?>
                                <option value="<?php echo esc_attr( $style_key ); ?>" <?php selected( $current_style, $style_key ); ?>><?php echo esc_html( $style_meta['label'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html( $image_styles[ $current_style ]['description'] ?? '' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Auto-publish ready drafts', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_enable_draft_autopublish" value="1" <?php checked( 1, get_option( 'hpw_enable_draft_autopublish', 1 ) ); ?> /> <?php esc_html_e( 'Let the 5-minute cron review imported drafts and publish only the ones that pass quality checks.', 'holyprofweb' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Drafts checked per run', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_draft_publish_limit" value="<?php echo esc_attr( get_option( 'hpw_draft_publish_limit', 12 ) ); ?>" min="1" max="100" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'RTL support', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_rtl_support" value="1" <?php checked( 1, get_option( 'hpw_rtl_support', 0 ) ); ?> /> <?php esc_html_e( 'Enable right-to-left layout support.', 'holyprofweb' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Refresh content after (days)', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_refresh_days" value="<?php echo esc_attr( get_option( 'hpw_refresh_days', 21 ) ); ?>" min="3" max="365" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Refresh queue limit per audit', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_refresh_queue_limit" value="<?php echo esc_attr( get_option( 'hpw_refresh_queue_limit', 25 ) ); ?>" min="1" max="200" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'AI minimum target words', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_ai_minimum_words" value="<?php echo esc_attr( get_option( 'hpw_ai_minimum_words', 700 ) ); ?>" min="650" max="5000" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Internal link targets per article', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_ai_internal_links" value="<?php echo esc_attr( get_option( 'hpw_ai_internal_links', 3 ) ); ?>" min="1" max="10" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'FAQ count target', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_ai_faq_count" value="<?php echo esc_attr( get_option( 'hpw_ai_faq_count', 5 ) ); ?>" min="3" max="10" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Brand voice', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="hpw_ai_brand_voice" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'hpw_ai_brand_voice', 'Calm, practical, trustworthy, globally readable, and naturally human.' ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'This powers the automatic article prompt format.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Extra publishing notes', 'holyprofweb' ); ?></th>
                    <td><textarea name="hpw_ai_prompt_notes" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'hpw_ai_prompt_notes', '' ) ); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Automation Settings', 'holyprofweb' ) ); ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Automation Notes', 'holyprofweb' ); ?></h2>
        <p><?php esc_html_e( 'Future screenshot automation can inject a remote image URL through the holyprofweb_automation_image_url filter without changing this theme.', 'holyprofweb' ); ?></p>
        <p><?php echo esc_html( sprintf( __( 'Generated image cache is active. Sample detected posts: %d', 'holyprofweb' ), count( $generated_posts ) ) ); ?></p>
        <p><?php echo esc_html( sprintf( __( 'Current generated image style: %s', 'holyprofweb' ), $image_styles[ $current_style ]['label'] ?? $current_style ) ); ?></p>
        <p><?php echo esc_html( sprintf( __( 'Content refresh queue items: %d', 'holyprofweb' ), count( $refresh_queue ) ) ); ?></p>
        <p><?php echo esc_html( $last_audit_run ? sprintf( __( 'Last audit run: %s', 'holyprofweb' ), wp_date( 'Y-m-d H:i', $last_audit_run ) ) : __( 'Last audit run: not yet', 'holyprofweb' ) ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-automation' ) ); ?>" style="margin:16px 0 0;">
            <?php wp_nonce_field( 'hpw_regenerate_generated_images', 'hpw_regenerate_generated_images_nonce' ); ?>
            <input type="hidden" name="hpw_action" value="regenerate_generated_images" />
            <?php submit_button( __( 'Regenerate All Generated Images Now', 'holyprofweb' ), 'secondary', 'submit', false ); ?>
        </form>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-automation' ) ); ?>" style="margin:10px 0 0;">
            <?php wp_nonce_field( 'hpw_reset_generated_images', 'hpw_reset_generated_images_nonce' ); ?>
            <input type="hidden" name="hpw_action" value="reset_generated_images" />
            <?php submit_button( __( 'Reset Generated Image Cache', 'holyprofweb' ), 'delete', 'submit', false ); ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Posts Image Desk', 'holyprofweb' ); ?></h2>
        <p style="color:#666;"><?php esc_html_e( 'Filter and search posts. Results update live — no page reload needed. Switch between GD (real file) and SVG (on-the-fly) for any post.', 'holyprofweb' ); ?></p>

        <?php
        // Collect unique countries for filter dropdown.
        global $wpdb;
        $desk_countries = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_hpw_country_focus' AND meta_value != '' ORDER BY meta_value ASC LIMIT 60"
        );
        $desk_categories = get_categories( array( 'hide_empty' => true, 'number' => 80 ) );

        $desk_search  = isset( $_GET['hpw_image_search'] )  ? sanitize_text_field( wp_unslash( $_GET['hpw_image_search'] ) )  : '';
        $desk_type    = isset( $_GET['hpw_image_type'] )    ? sanitize_key( wp_unslash( $_GET['hpw_image_type'] ) )            : '';
        $desk_status  = isset( $_GET['hpw_image_status'] )  ? sanitize_key( wp_unslash( $_GET['hpw_image_status'] ) )          : '';
        $desk_cat     = isset( $_GET['hpw_image_cat'] )     ? absint( wp_unslash( $_GET['hpw_image_cat'] ) )                   : 0;
        $desk_country = isset( $_GET['hpw_image_country'] ) ? sanitize_text_field( wp_unslash( $_GET['hpw_image_country'] ) )  : '';
        $desk_orderby = isset( $_GET['hpw_image_orderby'] ) ? sanitize_key( wp_unslash( $_GET['hpw_image_orderby'] ) )         : 'date';
        ?>

        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin:16px 0 20px;padding:16px;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:4px;">

            <div>
                <label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Search</label>
                <input id="hpw-image-search" type="search" value="<?php echo esc_attr( $desk_search ); ?>"
                       class="regular-text" style="width:220px;"
                       placeholder="Title, brand, keyword…" autocomplete="off" />
            </div>

            <div>
                <label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Image type</label>
                <select id="hpw-image-type" style="width:160px;">
                    <option value="">All types</option>
                    <option value="none"      <?php selected( $desk_type, 'none' ); ?>>No image</option>
                    <option value="trusted"   <?php selected( $desk_type, 'trusted' ); ?>>Featured image</option>
                    <option value="remote"    <?php selected( $desk_type, 'remote' ); ?>>Remote / OG</option>
                    <option value="gd"        <?php selected( $desk_type, 'gd' ); ?>>Generated — GD</option>
                    <option value="svg"       <?php selected( $desk_type, 'svg' ); ?>>Generated — SVG</option>
                    <option value="generated" <?php selected( $desk_type, 'generated' ); ?>>Generated (any)</option>
                    <option value="external"  <?php selected( $desk_type, 'external' ); ?>>Manual link</option>
                </select>
            </div>

            <div>
                <label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Status</label>
                <select id="hpw-image-status" style="width:130px;">
                    <option value="">All statuses</option>
                    <option value="publish" <?php selected( $desk_status, 'publish' ); ?>>Published</option>
                    <option value="draft"   <?php selected( $desk_status, 'draft' ); ?>>Draft</option>
                    <option value="pending" <?php selected( $desk_status, 'pending' ); ?>>Pending</option>
                    <option value="future"  <?php selected( $desk_status, 'future' ); ?>>Scheduled</option>
                </select>
            </div>

            <?php if ( ! empty( $desk_categories ) ) : ?>
            <div>
                <label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Category</label>
                <select id="hpw-image-cat" style="width:170px;">
                    <option value="0">All categories</option>
                    <?php foreach ( $desk_categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $desk_cat, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?> (<?php echo esc_html( $cat->count ); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $desk_countries ) ) : ?>
            <div>
                <label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Country</label>
                <select id="hpw-image-country" style="width:140px;">
                    <option value="">All countries</option>
                    <?php foreach ( $desk_countries as $c ) : ?>
                    <option value="<?php echo esc_attr( $c ); ?>" <?php selected( $desk_country, $c ); ?>><?php echo esc_html( $c ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Sort by</label>
                <select id="hpw-image-orderby" style="width:130px;">
                    <option value="date"     <?php selected( $desk_orderby, 'date' ); ?>>Date added</option>
                    <option value="modified" <?php selected( $desk_orderby, 'modified' ); ?>>Last modified</option>
                    <option value="title"    <?php selected( $desk_orderby, 'title' ); ?>>Title A–Z</option>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:8px;padding-top:18px;">
                <span id="hpw-image-search-spinner" style="display:none;">
                    <span class="spinner is-active" style="float:none;margin:0;vertical-align:middle;"></span>
                </span>
            </div>
        </div>

        <div id="hpw-image-desk-results">
        <?php
        $desk_filters = array(
            's'       => $desk_search,
            'type'    => $desk_type,
            'status'  => $desk_status,
            'cat_id'  => $desk_cat,
            'country' => $desk_country,
            'orderby' => $desk_orderby,
        );
        holyprofweb_render_image_desk_rows( holyprofweb_query_image_desk_posts( $desk_filters ), $desk_filters );
        ?>
        </div>

        <script>
        (function(){
            var results = document.getElementById('hpw-image-desk-results');
            var spinner = document.getElementById('hpw-image-search-spinner');
            var nonce   = <?php echo wp_json_encode( wp_create_nonce( 'hpw_image_desk_ajax' ) ); ?>;

            var ids = ['hpw-image-search','hpw-image-type','hpw-image-status','hpw-image-cat','hpw-image-country','hpw-image-orderby'];
            var fields = {};
            ids.forEach(function(id){ fields[id] = document.getElementById(id); });

            function getParams() {
                return {
                    action:  'hpw_image_desk_search',
                    nonce:   nonce,
                    s:       fields['hpw-image-search']  ? fields['hpw-image-search'].value  : '',
                    type:    fields['hpw-image-type']    ? fields['hpw-image-type'].value    : '',
                    status:  fields['hpw-image-status']  ? fields['hpw-image-status'].value  : '',
                    cat_id:  fields['hpw-image-cat']     ? fields['hpw-image-cat'].value     : '0',
                    country: fields['hpw-image-country'] ? fields['hpw-image-country'].value : '',
                    orderby: fields['hpw-image-orderby'] ? fields['hpw-image-orderby'].value : 'date',
                };
            }

            var timer = null;
            function refresh(delay) {
                clearTimeout(timer);
                timer = setTimeout(function(){
                    if (spinner) spinner.style.display = 'inline-block';
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams(getParams())
                    })
                    .then(function(r){ return r.text(); })
                    .then(function(html){
                        if (results) results.innerHTML = html;
                        if (spinner) spinner.style.display = 'none';
                    })
                    .catch(function(){ if (spinner) spinner.style.display = 'none'; });
                }, delay || 280);
            }

            ids.forEach(function(id){
                var el = fields[id];
                if (!el) return;
                var ev = el.tagName.toLowerCase() === 'select' ? 'change' : 'input';
                el.addEventListener(ev, function(){ refresh(ev === 'change' ? 0 : 280); });
                if (ev === 'input') el.addEventListener('search', function(){ refresh(0); });
            });
        })();
        </script>

        <hr>
        <h2><?php esc_html_e( 'Draft Publish Queue', 'holyprofweb' ); ?></h2>
        <?php $draft_queue = holyprofweb_get_draft_publish_queue(); ?>
        <?php if ( empty( $draft_queue ) ) : ?>
            <p><?php esc_html_e( 'No drafts are currently blocked. Imported drafts that meet the checks will publish automatically.', 'holyprofweb' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e( 'Draft', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Word Count', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Needs', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Country', 'holyprofweb' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $draft_queue as $post_id => $item ) : ?>
                    <tr>
                        <td><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></td>
                        <td><?php echo esc_html( number_format_i18n( (int) $item['word_count'] ) ); ?></td>
                        <td><?php echo esc_html( implode( ', ', (array) $item['needs'] ) ); ?></td>
                        <td><?php echo esc_html( ! empty( $item['country'] ) ? $item['country'] : 'General' ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr>
        <h2><?php esc_html_e( 'AI Publishing Prompt Template', 'holyprofweb' ); ?></h2>
        <p><?php esc_html_e( 'Use this format for your automatic publishing workflow. It is shaped for HolyprofWeb SEO, structure, FAQs, internal links, and image hooks.', 'holyprofweb' ); ?></p>
        <textarea readonly rows="24" class="large-text code"><?php echo esc_textarea( $prompt_text ); ?></textarea>
    </div>
    <?php
}

/* ── Hook sender identity into wp_mail ───────────────────────────────────── */

add_filter( 'wp_mail_from',      function( $email ) {
    $from = get_option( 'hpw_email_from_address', '' );
    return is_email( $from ) ? $from : $email;
} );
add_filter( 'wp_mail_from_name', function( $name ) {
    $from = get_option( 'hpw_email_from_name', '' );
    return $from ? $from : $name;
} );

/* ── Notify admin on new review ──────────────────────────────────────────── */

function holyprofweb_notify_new_review( $comment_id, $comment ) {
    $type = $comment->comment_type ?? '';
    if ( ! in_array( $type, array( 'review', 'salary_submission' ), true ) ) return;
    if ( ! get_option( 'hpw_email_notify_review', 1 ) ) return;

    $to      = get_option( 'hpw_email_notify_address', get_option( 'admin_email' ) );
    $post    = get_post( $comment->comment_post_ID );
    $title   = $post ? $post->post_title : __( 'Unknown post', 'holyprofweb' );
    $rating  = get_comment_meta( $comment_id, 'rating', true );
    $subject = 'review' === $type
        ? sprintf( __( 'New review on "%s"', 'holyprofweb' ), $title )
        : sprintf( __( 'New salary submission on "%s"', 'holyprofweb' ), $title );
    $body    = 'review' === $type
        ? sprintf(
            "%s\n\nPost: %s\nReviewer: %s\nRating: %s/5\nReview:\n%s\n\nModerate at: %s",
            __( 'A new review has been submitted.', 'holyprofweb' ),
            $title,
            $comment->comment_author,
            $rating,
            wp_strip_all_tags( $comment->comment_content ),
            admin_url( 'admin.php?page=hpw-settings-reviews' )
        )
        : sprintf(
            "%s\n\nPost: %s\nSubmitter: %s\nEmail: %s\nDetails:\n%s\n\nModerate at: %s",
            __( 'A new salary submission has been received.', 'holyprofweb' ),
            $title,
            $comment->comment_author,
            $comment->comment_author_email,
            wp_strip_all_tags( $comment->comment_content ),
            admin_url( 'admin.php?page=hpw-settings-reviews' )
        );
    wp_mail( $to, $subject, $body );
}
add_action( 'wp_insert_comment', 'holyprofweb_notify_new_review', 10, 2 );

function holyprofweb_handle_comment_verification_actions() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_GET['hpw_verify_comment'] ) || empty( $_GET['_wpnonce'] ) ) {
        return;
    }

    $comment_id = absint( $_GET['hpw_verify_comment'] );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hpw_verify_comment_' . $comment_id ) ) {
        return;
    }

    $verified = isset( $_GET['verified'] ) ? absint( $_GET['verified'] ) : 0;
    update_comment_meta( $comment_id, 'hpw_verified', $verified ? 1 : 0 );

    wp_safe_redirect( admin_url( 'admin.php?page=hpw-settings-reviews' ) );
    exit;
}
add_action( 'admin_init', 'holyprofweb_handle_comment_verification_actions' );

function holyprofweb_handle_generated_image_regeneration_action() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_POST['hpw_action'] ) || 'regenerate_generated_images' !== $_POST['hpw_action'] ) {
        return;
    }

    check_admin_referer( 'hpw_regenerate_generated_images', 'hpw_regenerate_generated_images_nonce' );

    $processed = holyprofweb_regenerate_generated_images_batch();

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'                       => 'hpw-settings-automation',
                'hpw_images_regenerated'    => $processed,
            ),
            admin_url( 'admin.php' )
        )
    );
    exit;
}
add_action( 'admin_init', 'holyprofweb_handle_generated_image_regeneration_action' );

function holyprofweb_handle_generated_image_reset_action() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_POST['hpw_action'] ) || 'reset_generated_images' !== $_POST['hpw_action'] ) {
        return;
    }

    check_admin_referer( 'hpw_reset_generated_images', 'hpw_reset_generated_images_nonce' );

    $processed = holyprofweb_reset_generated_images_batch();

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'             => 'hpw-settings-automation',
                'hpw_images_reset' => $processed,
            ),
            admin_url( 'admin.php' )
        )
    );
    exit;
}
add_action( 'admin_init', 'holyprofweb_handle_generated_image_reset_action' );

function holyprofweb_handle_single_post_image_regeneration_action() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_POST['hpw_action'] ) || 'regenerate_post_image' !== $_POST['hpw_action'] ) {
        return;
    }

    check_admin_referer( 'hpw_regenerate_post_image', 'hpw_regenerate_post_image_nonce' );

    $post_id = isset( $_POST['hpw_post_id'] ) ? absint( wp_unslash( $_POST['hpw_post_id'] ) ) : 0;
    holyprofweb_force_regenerate_post_image( $post_id );
    $image_search = isset( $_POST['hpw_image_search'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_image_search'] ) ) : '';

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'               => 'hpw-settings-automation',
                'hpw_image_regened'  => $post_id,
                'hpw_image_search'   => $image_search,
            ),
            admin_url( 'admin.php' )
        )
    );
    exit;
}
add_action( 'admin_init', 'holyprofweb_handle_single_post_image_regeneration_action' );

function holyprofweb_parse_redirect_rules() {
    $raw   = (string) get_option( 'hpw_redirect_rules', '' );
    $lines = preg_split( '/\r\n|\r|\n/', $raw );
    $rules = array();

    foreach ( $lines as $line ) {
        $pair = holyprofweb_parse_redirect_line( $line );
        if ( empty( $pair['from'] ) || empty( $pair['to'] ) ) {
            continue;
        }

        $rules[ untrailingslashit( $pair['from'] ) ] = $pair['to'];
    }

    return $rules;
}

function holyprofweb_parse_redirect_line( $line ) {
    $line = trim( (string) $line );
    if ( '' === $line ) {
        return array();
    }

    $parts = array();

    if ( false !== strpos( $line, '|' ) ) {
        $parts = array_map( 'trim', explode( '|', $line, 2 ) );
    } elseif ( false !== strpos( $line, ',' ) ) {
        $parts = array_map( 'trim', explode( ',', $line, 2 ) );
    } elseif ( preg_match( '/\s{2,}|\t/', $line ) ) {
        $parts = preg_split( '/\s{2,}|\t/', $line, 2 );
        $parts = array_map( 'trim', $parts );
    }

    if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
        return array();
    }

    $from = '/' . ltrim( wp_parse_url( $parts[0], PHP_URL_PATH ) ?: $parts[0], '/' );
    $to   = trim( $parts[1] );

    if ( 0 !== strpos( $to, 'http://' ) && 0 !== strpos( $to, 'https://' ) ) {
        $to = '/' . ltrim( wp_parse_url( $to, PHP_URL_PATH ) ?: $to, '/' );
    }

    return array(
        'from' => untrailingslashit( $from ),
        'to'   => $to,
    );
}

function holyprofweb_sanitize_redirect_rules( $value ) {
    $lines      = preg_split( '/\r\n|\r|\n/', (string) $value );
    $normalized = array();

    foreach ( $lines as $line ) {
        $pair = holyprofweb_parse_redirect_line( $line );
        if ( empty( $pair['from'] ) || empty( $pair['to'] ) ) {
            continue;
        }

        $normalized[] = $pair['from'] . ' | ' . $pair['to'];
    }

    return implode( "\n", array_values( array_unique( $normalized ) ) );
}

function holyprofweb_store_redirect_rule( $from, $to ) {
    $pair = holyprofweb_parse_redirect_line( $from . ' | ' . $to );
    if ( empty( $pair['from'] ) || empty( $pair['to'] ) || $pair['from'] === $pair['to'] ) {
        return false;
    }

    $rules = holyprofweb_parse_redirect_rules();
    $rules[ $pair['from'] ] = $pair['to'];

    $lines = array();
    foreach ( $rules as $rule_from => $rule_to ) {
        $lines[] = $rule_from . ' | ' . $rule_to;
    }

    update_option( 'hpw_redirect_rules', implode( "\n", $lines ), false );
    return true;
}

function holyprofweb_apply_year_replacement( $from_year, $to_year, $scope = 'titles-slugs' ) {
    $from_year = preg_replace( '/\D+/', '', (string) $from_year );
    $to_year   = preg_replace( '/\D+/', '', (string) $to_year );

    if ( 4 !== strlen( $from_year ) || 4 !== strlen( $to_year ) || $from_year === $to_year ) {
        return 0;
    }

    $posts = get_posts(
        array(
            'post_type'      => array( 'post', 'page' ),
            'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );

    $updated = 0;

    foreach ( $posts as $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post instanceof WP_Post ) {
            continue;
        }

        $new_title   = str_replace( $from_year, $to_year, (string) $post->post_title );
        $new_slug    = str_replace( $from_year, $to_year, (string) $post->post_name );
        $new_content = (string) $post->post_content;

        if ( 'all' === $scope ) {
            $new_content = str_replace( $from_year, $to_year, $new_content );
        }

        if ( $new_title === $post->post_title && $new_slug === $post->post_name && $new_content === $post->post_content ) {
            continue;
        }

        $update_args = array(
            'ID'         => $post_id,
            'post_title' => $new_title,
            'post_name'  => sanitize_title( $new_slug ),
        );

        if ( 'all' === $scope ) {
            $update_args['post_content'] = $new_content;
        }

        wp_update_post( $update_args );
        $updated++;
    }

    return $updated;
}

function holyprofweb_handle_redirect_admin_actions() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_POST['page'] ) || 'hpw-settings-redirects' !== $_POST['page'] ) {
        return;
    }

    if ( ! empty( $_POST['hpw_redirect_action'] ) && 'add_redirect' === $_POST['hpw_redirect_action'] ) {
        check_admin_referer( 'hpw_redirect_add_action', 'hpw_redirect_add_nonce' );

        $old_url = isset( $_POST['hpw_redirect_old_url'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_redirect_old_url'] ) ) : '';
        $new_url = isset( $_POST['hpw_redirect_new_url'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_redirect_new_url'] ) ) : '';
        holyprofweb_store_redirect_rule( $old_url, $new_url );

        wp_safe_redirect( admin_url( 'admin.php?page=hpw-settings-redirects&hpw_redirect_saved=1' ) );
        exit;
    }

    if ( ! empty( $_POST['hpw_redirect_action'] ) && 'replace_year' === $_POST['hpw_redirect_action'] ) {
        check_admin_referer( 'hpw_redirect_year_action', 'hpw_redirect_year_nonce' );

        $from_year = isset( $_POST['hpw_year_from'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_year_from'] ) ) : '';
        $to_year   = isset( $_POST['hpw_year_to'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_year_to'] ) ) : '';
        $scope     = isset( $_POST['hpw_year_scope'] ) ? sanitize_key( wp_unslash( $_POST['hpw_year_scope'] ) ) : 'titles-slugs';
        if ( ! in_array( $scope, array( 'titles-slugs', 'all' ), true ) ) {
            $scope = 'titles-slugs';
        }

        $updated = holyprofweb_apply_year_replacement( $from_year, $to_year, $scope );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'                 => 'hpw-settings-redirects',
                    'hpw_year_replaced'    => $updated,
                    'hpw_year_from_done'   => preg_replace( '/\D+/', '', (string) $from_year ),
                    'hpw_year_to_done'     => preg_replace( '/\D+/', '', (string) $to_year ),
                ),
                admin_url( 'admin.php' )
            )
        );
        exit;
    }
}
add_action( 'admin_init', 'holyprofweb_handle_redirect_admin_actions' );

function holyprofweb_get_redirect_fallback_target( $post_id ) {
    if ( ! $post_id ) {
        return home_url( '/' );
    }

    if ( 'page' === get_post_type( $post_id ) ) {
        return home_url( '/' );
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'companies' ) ) {
        return home_url( '/category/companies/' );
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'reviews' ) ) {
        return home_url( '/category/reviews/' );
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'biography' ) ) {
        return home_url( '/category/biography/' );
    }

    if ( holyprofweb_post_in_category_tree( $post_id, 'reports' ) || holyprofweb_post_in_category_tree( $post_id, 'blog-opinion' ) ) {
        return holyprofweb_get_blog_url();
    }

    return home_url( '/' );
}

function holyprofweb_capture_permalink_redirect_on_update( $post_id, $post_after, $post_before ) {
    if ( ! $post_after instanceof WP_Post || ! $post_before instanceof WP_Post ) {
        return;
    }

    if ( 'post' !== $post_after->post_type && 'page' !== $post_after->post_type ) {
        return;
    }

    if ( 'publish' !== $post_before->post_status || 'publish' !== $post_after->post_status ) {
        return;
    }

    $old_path = wp_parse_url( get_permalink( $post_before ), PHP_URL_PATH );
    $new_path = wp_parse_url( get_permalink( $post_after ), PHP_URL_PATH );

    if ( ! $old_path || ! $new_path || untrailingslashit( $old_path ) === untrailingslashit( $new_path ) ) {
        return;
    }

    holyprofweb_store_redirect_rule( $old_path, $new_path );
}
add_action( 'post_updated', 'holyprofweb_capture_permalink_redirect_on_update', 10, 3 );

function holyprofweb_capture_redirect_before_delete( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
        return;
    }

    if ( 'publish' !== $post->post_status && 'trash' !== $post->post_status ) {
        return;
    }

    $old_path = wp_parse_url( get_permalink( $post_id ), PHP_URL_PATH );
    if ( ! $old_path ) {
        return;
    }

    holyprofweb_store_redirect_rule( $old_path, holyprofweb_get_redirect_fallback_target( $post_id ) );
}
add_action( 'trashed_post', 'holyprofweb_capture_redirect_before_delete', 10, 1 );
add_action( 'before_delete_post', 'holyprofweb_capture_redirect_before_delete', 10, 1 );

function holyprofweb_handle_redirect_rules() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
    if ( ! $request_uri ) {
        return;
    }

    $request_key = untrailingslashit( trailingslashit( $request_uri ) );
    $rules       = holyprofweb_parse_redirect_rules();

    if ( empty( $rules[ $request_key ] ) ) {
        return;
    }

    $target = $rules[ $request_key ];
    if ( 0 !== strpos( $target, 'http://' ) && 0 !== strpos( $target, 'https://' ) ) {
        $target = home_url( $target );
    }

    wp_redirect( $target, 301 );
    exit;
}
add_action( 'template_redirect', 'holyprofweb_handle_redirect_rules', 1 );

add_filter( 'wp_robots', function( $robots ) {
    if ( get_option( 'hpw_discourage_indexing', 0 ) ) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        return $robots;
    }

    if ( is_search() || is_404() ) {
        $robots['noindex'] = true;
        return $robots;
    }

    if ( is_singular( 'post' ) && holyprofweb_is_placeholder_post( get_queried_object_id() ) ) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
} );

function holyprofweb_virtual_robots_txt( $output, $public ) {
    $lines = array(
        'User-agent: *',
    );

    if ( get_option( 'hpw_discourage_indexing', 0 ) || ! $public ) {
        $lines[] = 'Disallow: /';
    } else {
        $lines[] = 'Allow: /';
        $lines[] = '';
        $lines[] = 'User-agent: GPTBot';
        $lines[] = 'Allow: /';
        $lines[] = '';
        $lines[] = 'User-agent: OAI-SearchBot';
        $lines[] = 'Allow: /';
        $lines[] = '';
        $lines[] = 'User-agent: CCBot';
        $lines[] = 'Allow: /';
        $lines[] = 'Sitemap: ' . esc_url_raw( home_url( '/wp-sitemap.xml' ) );
    }

    return implode( "\n", $lines ) . "\n";
}
add_filter( 'robots_txt', 'holyprofweb_virtual_robots_txt', 10, 2 );

function holyprofweb_render_dynamic_brand_css() {
    $header_logo_height = max( 36, min( 180, (int) get_option( 'hpw_header_logo_height', 86 ) ) );
    $footer_logo_height = max( 28, min( 140, (int) get_option( 'hpw_footer_logo_height', 56 ) ) );
    ?>
    <style id="holyprofweb-dynamic-brand-css">
    :root {
        --hpw-header-logo-height: <?php echo esc_html( $header_logo_height ); ?>px;
        --hpw-footer-logo-height: <?php echo esc_html( $footer_logo_height ); ?>px;
    }
    </style>
    <?php
}
add_action( 'wp_head', 'holyprofweb_render_dynamic_brand_css', 20 );

function holyprofweb_primary_menu_cleanup( $items, $args ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $items;
    }

    $is_blog_archive    = (bool) get_query_var( 'hpw_blog_archive' );
    $is_reports_archive = (bool) get_query_var( 'hpw_reports_archive' );

    foreach ( $items as $index => $item ) {
        $title_slug = sanitize_title( $item->title );

        if ( 'blog' === $title_slug ) {
            $items[ $index ]->url = holyprofweb_get_blog_url();

            if ( $is_blog_archive ) {
                $items[ $index ]->current = true;
                $items[ $index ]->current_item_parent = true;
                if ( ! in_array( 'current-menu-item', $items[ $index ]->classes, true ) ) {
                    $items[ $index ]->classes[] = 'current-menu-item';
                }
            }
        }

        if ( 'reports' === $title_slug ) {
            unset( $items[ $index ] );
        }

        if ( ( $is_blog_archive || $is_reports_archive ) && 'home' === $title_slug ) {
            $items[ $index ]->current = false;
            $items[ $index ]->current_item_parent = false;
            $items[ $index ]->classes = array_values(
                array_diff(
                    (array) $items[ $index ]->classes,
                    array( 'current-menu-item', 'current_page_item', 'current-menu-parent', 'current_page_parent' )
                )
            );
        }
    }

    return array_values( $items );
}
add_filter( 'wp_nav_menu_objects', 'holyprofweb_primary_menu_cleanup', 10, 2 );

/* ── RTL support hook ────────────────────────────────────────────────────── */

add_action( 'wp_enqueue_scripts', function() {
    if ( get_option( 'hpw_rtl_support', 0 ) ) {
        wp_add_inline_style( 'holyprofweb-style', 'body { direction: rtl; text-align: right; } .platform-wrap { direction: rtl; } .left-sidebar { order: 2; } .platform-main { order: 1; border-left: none; border-right: 1px solid var(--color-border); padding-left: 0; padding-right: 32px; }' );
    }
}, 20 );


// =========================================
// ON-PUBLISH PIPELINE (SEO + image + data)
// =========================================

/**
 * Master hook: runs every time a post transitions TO "publish".
 * Handles excerpt, tags, schema type, reading time, featured image.
 * Hooked on transition_post_status so it fires exactly once per publish event
 * (not on every save).
 */
function holyprofweb_on_post_publish( $new_status, $old_status, $post ) {
    if ( $new_status !== 'publish' ) return;
    if ( $post->post_type !== 'post' ) return;
    if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) return;

    // Keep publish lightweight: defer heavy work to cron.
    if ( ! has_post_thumbnail( $post->ID ) ) {
        holyprofweb_schedule_featured_image_generation( $post->ID );
    }
}

function holyprofweb_settings_seo_debug_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $draft_queue     = holyprofweb_get_draft_publish_queue();
    $draft_debug_rows = holyprofweb_get_draft_debug_rows( 30 );
    $next_draft_cron = wp_next_scheduled( 'holyprofweb_draft_publish_audit' );
    $next_audit_cron = wp_next_scheduled( 'holyprofweb_daily_content_audit' );
    $last_draft_cron = absint( get_option( 'holyprofweb_draft_audit_last_run', 0 ) );
    $last_wp_cron    = absint( get_option( 'holyprofweb_wp_cron_last_run', 0 ) );
    $draft_totals    = wp_count_posts( 'post' );
    $sample_post_ids = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    $seo_provider = function_exists( 'holyprofweb_detect_active_seo_provider' ) ? holyprofweb_detect_active_seo_provider() : array( 'label' => 'HPW Native SEO', 'native_enabled' => true );
    $draft_cron_overdue = $next_draft_cron && $next_draft_cron < time();
    $audit_cron_overdue = $next_audit_cron && $next_audit_cron < time();
    $wp_cron_disabled  = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
    $php_binary        = defined( 'PHP_BINARY' ) ? PHP_BINARY : '';
    $cron_command      = $php_binary ? ( $php_binary . ' -q ' . ABSPATH . 'wp-cron.php' ) : ( 'php -q ' . ABSPATH . 'wp-cron.php' );
    $server_time       = time();
    $wp_time           = current_time( 'timestamp' );
    $wp_timezone       = wp_timezone_string();
    $cron_array        = function_exists( '_get_cron_array' ) ? _get_cron_array() : array();
    $cron_due_count    = 0;
    if ( is_array( $cron_array ) ) {
        $now = time();
        foreach ( $cron_array as $timestamp => $hooks ) {
            if ( (int) $timestamp > $now ) {
                continue;
            }
            if ( is_array( $hooks ) ) {
                foreach ( $hooks as $hook => $events ) {
                    if ( ! empty( $events ) ) {
                        $cron_due_count += count( $events );
                    }
                }
            }
        }
    }
    if ( isset( $_GET['hpw_debug_action'] ) ) {
        $action = sanitize_key( wp_unslash( $_GET['hpw_debug_action'] ) );
        $count  = absint( $_GET['hpw_debug_count'] ?? 0 );
        $messages = array(
            'run_draft_audit'       => sprintf( __( 'Draft audit completed. %d draft(s) remain in queue.', 'holyprofweb' ), $count ),
            'publish_overdue'       => sprintf( __( 'Published %d overdue draft(s) immediately.', 'holyprofweb' ), $count ),
            'run_content_audit'     => sprintf( __( 'Content audit completed. %d refresh item(s) are queued.', 'holyprofweb' ), $count ),
            'reschedule_crons'      => __( 'HPW cron events were refreshed.', 'holyprofweb' ),
            'run_wp_cron'           => __( 'WP-Cron executed. Check "Last WP-Cron run" and due events.', 'holyprofweb' ),
        );
        if ( isset( $messages[ $action ] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $action ] ) . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>&#128295; <?php esc_html_e( 'HPW Settings — SEO Debug', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'seo-debug' ); ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin:0 0 24px;">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <strong style="display:block;font-size:1.6rem;line-height:1;"><?php echo esc_html( $seo_provider['label'] ); ?></strong>
                <span><?php esc_html_e( 'Active SEO provider', 'holyprofweb' ); ?></span>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <strong style="display:block;font-size:1.6rem;line-height:1;"><?php echo $seo_provider['native_enabled'] ? esc_html__( 'On', 'holyprofweb' ) : esc_html__( 'Off', 'holyprofweb' ); ?></strong>
                <span><?php esc_html_e( 'HPW native SEO tags', 'holyprofweb' ); ?></span>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <strong style="display:block;font-size:1.6rem;line-height:1;"><?php echo get_option( 'hpw_discourage_indexing', 0 ) ? esc_html__( 'Noindex', 'holyprofweb' ) : esc_html__( 'Index', 'holyprofweb' ); ?></strong>
                <span><?php esc_html_e( 'Theme indexing signal', 'holyprofweb' ); ?></span>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
                <strong style="display:block;font-size:1.6rem;line-height:1;"><?php echo get_option( 'blog_public', 1 ) ? esc_html__( 'Public', 'holyprofweb' ) : esc_html__( 'Hidden', 'holyprofweb' ); ?></strong>
                <span><?php esc_html_e( 'WordPress search engine visibility', 'holyprofweb' ); ?></span>
            </div>
        </div>

        <?php if ( $draft_cron_overdue || $audit_cron_overdue ) : ?>
            <div class="notice notice-warning inline" style="margin:0 0 16px;">
                <p><?php esc_html_e( 'One or more scheduled HPW cron jobs are overdue. This usually means WP-Cron has not been triggered yet by a site request, or the server cron is not calling wp-cron.php reliably.', 'holyprofweb' ); ?></p>
            </div>
        <?php endif; ?>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:0 0 24px;">
            <h2 style="margin-top:0;"><?php esc_html_e( 'Automation Controls', 'holyprofweb' ); ?></h2>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-seo-debug' ) ); ?>">
                    <?php wp_nonce_field( 'hpw_debug_actions', 'hpw_debug_actions_nonce' ); ?>
                    <input type="hidden" name="hpw_debug_action" value="run_draft_audit" />
                    <?php submit_button( __( 'Run Draft Audit Now', 'holyprofweb' ), 'secondary', 'submit', false ); ?>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-seo-debug' ) ); ?>">
                    <?php wp_nonce_field( 'hpw_debug_actions', 'hpw_debug_actions_nonce' ); ?>
                    <input type="hidden" name="hpw_debug_action" value="run_wp_cron" />
                    <?php submit_button( __( 'Run WP-Cron Now', 'holyprofweb' ), 'secondary', 'submit', false ); ?>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-seo-debug' ) ); ?>">
                    <?php wp_nonce_field( 'hpw_debug_actions', 'hpw_debug_actions_nonce' ); ?>
                    <input type="hidden" name="hpw_debug_action" value="publish_overdue" />
                    <?php submit_button( __( 'Publish Overdue Drafts Now', 'holyprofweb' ), 'secondary', 'submit', false ); ?>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-seo-debug' ) ); ?>">
                    <?php wp_nonce_field( 'hpw_debug_actions', 'hpw_debug_actions_nonce' ); ?>
                    <input type="hidden" name="hpw_debug_action" value="run_content_audit" />
                    <?php submit_button( __( 'Run Content Audit Now', 'holyprofweb' ), 'secondary', 'submit', false ); ?>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-seo-debug' ) ); ?>">
                    <?php wp_nonce_field( 'hpw_debug_actions', 'hpw_debug_actions_nonce' ); ?>
                    <input type="hidden" name="hpw_debug_action" value="reschedule_crons" />
                    <?php submit_button( __( 'Refresh HPW Cron Schedule', 'holyprofweb' ), 'secondary', 'submit', false ); ?>
                </form>
            </div>
            <p class="description"><?php esc_html_e( 'These buttons only run HPW automation tasks. They do not change normal WordPress publishing settings.', 'holyprofweb' ); ?></p>
        </div>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:0 0 24px;">
            <h2 style="margin-top:0;"><?php esc_html_e( 'Indexing Checks', 'holyprofweb' ); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><td><?php esc_html_e( 'Home URL', 'holyprofweb' ); ?></td><td><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/' ) ); ?></a></td></tr>
                    <tr><td><?php esc_html_e( 'robots.txt', 'holyprofweb' ); ?></td><td><a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/robots.txt' ) ); ?></a></td></tr>
                    <tr><td><?php esc_html_e( 'Sitemap', 'holyprofweb' ); ?></td><td><a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/wp-sitemap.xml' ) ); ?></a></td></tr>
                    <tr><td><?php esc_html_e( 'WP-Cron disabled in wp-config.php', 'holyprofweb' ); ?></td><td><?php echo esc_html( $wp_cron_disabled ? 'Yes' : 'No' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Last WP-Cron run', 'holyprofweb' ); ?></td><td><?php echo esc_html( $last_wp_cron ? wp_date( 'Y-m-d H:i:s', $last_wp_cron ) : 'Not recorded yet' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Due cron events', 'holyprofweb' ); ?></td><td><?php echo esc_html( $cron_due_count ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Server time', 'holyprofweb' ); ?></td><td><?php echo esc_html( wp_date( 'Y-m-d H:i:s', $server_time ) ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'WordPress time', 'holyprofweb' ); ?></td><td><?php echo esc_html( wp_date( 'Y-m-d H:i:s', $wp_time ) . ( $wp_timezone ? ' (' . $wp_timezone . ')' : '' ) ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Server cron command', 'holyprofweb' ); ?></td><td><code><?php echo esc_html( $cron_command ); ?></code></td></tr>
                    <tr><td><?php esc_html_e( 'Theme archive posts per page', 'holyprofweb' ); ?></td><td><?php echo esc_html( holyprofweb_get_archive_posts_per_page() ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'WordPress general posts per page', 'holyprofweb' ); ?></td><td><?php echo esc_html( (int) get_option( 'posts_per_page', 10 ) ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Draft force-publish rule', 'holyprofweb' ); ?></td><td><?php echo esc_html( holyprofweb_get_draft_force_publish_attempts() . ' checks or ' . floor( holyprofweb_get_draft_force_publish_window() / MINUTE_IN_SECONDS ) . ' minutes' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Last draft cron run', 'holyprofweb' ); ?></td><td><?php echo esc_html( $last_draft_cron ? wp_date( 'Y-m-d H:i:s', $last_draft_cron ) : 'Not recorded yet' ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Next draft cron', 'holyprofweb' ); ?></td><td><?php echo esc_html( $next_draft_cron ? wp_date( 'Y-m-d H:i:s', $next_draft_cron ) : 'Not scheduled' ); ?><?php echo $draft_cron_overdue ? esc_html( ' (Overdue)' ) : ''; ?></td></tr>
                    <tr><td><?php esc_html_e( 'Next content audit cron', 'holyprofweb' ); ?></td><td><?php echo esc_html( $next_audit_cron ? wp_date( 'Y-m-d H:i:s', $next_audit_cron ) : 'Not scheduled' ); ?><?php echo $audit_cron_overdue ? esc_html( ' (Overdue)' ) : ''; ?></td></tr>
                    <tr><td><?php esc_html_e( 'Current draft count', 'holyprofweb' ); ?></td><td><?php echo esc_html( isset( $draft_totals->draft ) ? (int) $draft_totals->draft : 0 ); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:0 0 24px;">
            <h2 style="margin-top:0;"><?php esc_html_e( 'Draft Queue Debug', 'holyprofweb' ); ?></h2>
            <?php if ( empty( $draft_debug_rows ) ) : ?>
                <p><?php esc_html_e( 'There are no draft posts right now.', 'holyprofweb' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Draft', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Attempts', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Checks left', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Status', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Needs', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'Last checked', 'holyprofweb' ); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ( $draft_debug_rows as $row ) : ?>
                        <tr>
                            <td><a href="<?php echo esc_url( get_edit_post_link( $row['post_id'] ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a></td>
                            <td><?php echo esc_html( (int) $row['attempts'] ); ?></td>
                            <td><?php echo esc_html( (int) $row['checks_remaining'] ); ?></td>
                            <td><?php echo esc_html( $row['force_ready'] ? 'Ready to force-publish' : 'Waiting for next checks' ); ?></td>
                            <td><?php echo esc_html( implode( ', ', (array) $row['needs'] ) ); ?></td>
                            <td><?php echo esc_html( ! empty( $row['last_checked'] ) ? wp_date( 'Y-m-d H:i:s', (int) $row['last_checked'] ) : 'Not checked yet' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px;">
            <h2 style="margin-top:0;"><?php esc_html_e( 'Published Post Samples', 'holyprofweb' ); ?></h2>
            <?php if ( empty( $sample_post_ids ) ) : ?>
                <p><?php esc_html_e( 'No published posts found yet.', 'holyprofweb' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Post', 'holyprofweb' ); ?></th><th><?php esc_html_e( 'URL', 'holyprofweb' ); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ( $sample_post_ids as $post_id ) : ?>
                        <tr>
                            <td><?php echo esc_html( get_the_title( $post_id ) ); ?></td>
                            <td><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank"><?php echo esc_html( get_permalink( $post_id ) ); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
add_action( 'transition_post_status', 'holyprofweb_on_post_publish', 10, 3 );

function holyprofweb_run_post_publish_pipeline_async( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
        return;
    }

    holyprofweb_on_post_publish( 'publish', 'draft', $post );
}
add_action( 'holyprofweb_process_post_publish_async', 'holyprofweb_run_post_publish_pipeline_async' );
add_action( 'holyprofweb_run_content_audit_async', 'holyprofweb_run_content_audit' );

function holyprofweb_on_post_publish_rest_safe( $new_status, $old_status, $post ) {
    if ( ! ( $post instanceof WP_Post ) ) return;
    if ( $new_status !== 'publish' ) return;
    if ( $post->post_type !== 'post' ) return;
    if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) return;

    if ( holyprofweb_is_high_volume_publish_context() ) {
        if ( ! wp_next_scheduled( 'holyprofweb_process_post_publish_async', array( $post->ID ) ) ) {
            wp_schedule_single_event( time() + 15, 'holyprofweb_process_post_publish_async', array( $post->ID ) );
        }
        return;
    }

    holyprofweb_on_post_publish( $new_status, $old_status, $post );
}
remove_action( 'transition_post_status', 'holyprofweb_on_post_publish', 10 );
add_action( 'transition_post_status', 'holyprofweb_on_post_publish_rest_safe', 10, 3 );

// =========================================
// INDEXNOW — REAL-TIME SEARCH ENGINE PINGS
// =========================================

/**
 * Get or generate the IndexNow key (stored in wp_options).
 */
function holyprofweb_indexnow_key() {
    $key = (string) get_option( 'hpw_indexnow_key', '' );
    if ( '' === $key || strlen( $key ) < 8 ) {
        $key = wp_generate_password( 32, false );
        update_option( 'hpw_indexnow_key', $key );
        holyprofweb_indexnow_write_key_file( $key );
    }
    return $key;
}

/**
 * URL where the key verification file lives.
 */
function holyprofweb_indexnow_key_url( $key = '' ) {
    if ( '' === $key ) $key = holyprofweb_indexnow_key();
    return home_url( '/' . $key . '.txt' );
}

/**
 * Write the key file to the web root so search engines can verify ownership.
 */
function holyprofweb_indexnow_write_key_file( $key = '' ) {
    if ( '' === $key ) $key = (string) get_option( 'hpw_indexnow_key', '' );
    if ( '' === $key ) return false;

    $path = rtrim( ABSPATH, '/' ) . '/' . preg_replace( '/[^a-zA-Z0-9\-_]/', '', $key ) . '.txt';
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    return false !== file_put_contents( $path, $key );
}

/**
 * Verify the key file is accessible on the server.
 */
function holyprofweb_indexnow_key_file_exists( $key = '' ) {
    if ( '' === $key ) $key = (string) get_option( 'hpw_indexnow_key', '' );
    if ( '' === $key ) return false;
    $path = rtrim( ABSPATH, '/' ) . '/' . preg_replace( '/[^a-zA-Z0-9\-_]/', '', $key ) . '.txt';
    return file_exists( $path );
}

/**
 * Collect all URLs that should be pinged for a given post:
 * the post permalink, its category pages, tag pages, and homepage.
 */
function holyprofweb_indexnow_post_urls( $post_id ) {
    $urls   = array();
    $urls[] = (string) get_permalink( $post_id );
    $urls[] = home_url( '/' );

    foreach ( get_the_category( $post_id ) as $cat ) {
        $urls[] = (string) get_category_link( $cat->term_id );
    }
    foreach ( wp_get_post_tags( $post_id ) as $tag ) {
        $urls[] = (string) get_tag_link( $tag->term_id );
    }

    return array_values( array_unique( array_filter( $urls ) ) );
}

/**
 * Send a batch of URLs to IndexNow (Bing endpoint distributes to all participants).
 * Stores the last submission log in wp_options.
 *
 * @param string[] $urls
 */
function holyprofweb_indexnow_submit( array $urls ) {
    $urls = array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
    if ( empty( $urls ) ) return;

    $key     = holyprofweb_indexnow_key();
    $host    = (string) wp_parse_url( home_url(), PHP_URL_HOST );
    $key_url = holyprofweb_indexnow_key_url( $key );

    // Chunk into max 500 per request (API limit is 10k, we keep it conservative).
    foreach ( array_chunk( $urls, 500 ) as $chunk ) {
        $body = wp_json_encode( array(
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => $key_url,
            'urlList'     => $chunk,
        ) );

        $response = wp_remote_post(
            'https://api.indexnow.org/indexnow',
            array(
                'headers'   => array( 'Content-Type' => 'application/json; charset=utf-8' ),
                'body'      => $body,
                'timeout'   => 8,
                'sslverify' => true,
            )
        );

        $code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

        $log = array(
            'time'  => current_time( 'mysql' ),
            'count' => count( $chunk ),
            'code'  => $code,
            'urls'  => array_slice( $chunk, 0, 10 ), // store first 10 as sample
            'error' => is_wp_error( $response ) ? $response->get_error_message() : '',
        );
        update_option( 'hpw_indexnow_last_log', $log );

        // On 422 (invalid key file), regenerate.
        if ( 422 === $code ) {
            holyprofweb_indexnow_write_key_file( $key );
        }
    }
}

/**
 * Queue URLs for batch submission (used during XML imports to avoid API spam).
 */
function holyprofweb_indexnow_enqueue( array $urls ) {
    $queue = (array) get_option( 'hpw_indexnow_queue', array() );
    $queue = array_unique( array_merge( $queue, $urls ) );
    update_option( 'hpw_indexnow_queue', array_values( $queue ) );
}

/**
 * Flush the queue — called on shutdown after a bulk import.
 */
function holyprofweb_indexnow_flush_queue() {
    $queue = (array) get_option( 'hpw_indexnow_queue', array() );
    if ( empty( $queue ) ) return;
    delete_option( 'hpw_indexnow_queue' );
    holyprofweb_indexnow_submit( $queue );
}

// ── Hooks ──────────────────────────────────────────────────────────────────────

/**
 * Ping on publish (new) or update (already published).
 */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) return;
    if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) return;
    if ( ! get_option( 'hpw_indexnow_enabled', 1 ) ) return;

    $is_publish_event = ( 'publish' === $new );
    $is_update_event  = ( 'publish' === $new && 'publish' === $old );

    if ( ! $is_publish_event ) return;

    $urls = holyprofweb_indexnow_post_urls( $post->ID );

    // During imports (many posts in quick succession) use the queue.
    if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
        holyprofweb_indexnow_enqueue( $urls );
        // Register queue flush once.
        if ( ! has_action( 'shutdown', 'holyprofweb_indexnow_flush_queue' ) ) {
            add_action( 'shutdown', 'holyprofweb_indexnow_flush_queue' );
        }
        return;
    }

    // Normal publish/update: fire after the request completes (shutdown).
    add_action( 'shutdown', function() use ( $urls ) {
        holyprofweb_indexnow_submit( $urls );
    } );
}, 20, 3 );

/**
 * Ping when a published post is trashed or deleted.
 */
add_action( 'trashed_post', function ( $post_id ) {
    if ( ! get_option( 'hpw_indexnow_enabled', 1 ) ) return;
    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) return;
    // Ping homepage + category so engines re-crawl the updated listing.
    $urls = array( home_url( '/' ) );
    foreach ( get_the_category( $post_id ) as $cat ) {
        $urls[] = (string) get_category_link( $cat->term_id );
    }
    add_action( 'shutdown', function() use ( $urls ) {
        holyprofweb_indexnow_submit( $urls );
    } );
} );

/**
 * Ping when a category/tag is edited (description, name change, etc.).
 */
add_action( 'edited_term', function ( $term_id, $tt_id, $taxonomy ) {
    if ( ! get_option( 'hpw_indexnow_enabled', 1 ) ) return;
    if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) return;
    $link = 'category' === $taxonomy ? get_category_link( $term_id ) : get_tag_link( $term_id );
    if ( $link && ! is_wp_error( $link ) ) {
        add_action( 'shutdown', function() use ( $link ) {
            holyprofweb_indexnow_submit( array( $link, home_url( '/' ) ) );
        } );
    }
}, 10, 3 );

/**
 * Ensure key file exists on every admin page load (self-healing).
 */
add_action( 'admin_init', function () {
    $key = (string) get_option( 'hpw_indexnow_key', '' );
    if ( $key && ! holyprofweb_indexnow_key_file_exists( $key ) ) {
        holyprofweb_indexnow_write_key_file( $key );
    }
} );

/**
 * Serve the key file via WordPress rewrite (backup if physical write failed).
 */
add_action( 'init', function () {
    $key = (string) get_option( 'hpw_indexnow_key', '' );
    if ( '' === $key ) return;
    add_rewrite_rule( '^' . preg_quote( $key, '/' ) . '\.txt$', 'index.php?hpw_indexnow_key=1', 'top' );
} );
add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'hpw_indexnow_key';
    return $vars;
} );
add_action( 'template_redirect', function () {
    if ( ! get_query_var( 'hpw_indexnow_key' ) ) return;
    $key = (string) get_option( 'hpw_indexnow_key', '' );
    if ( '' === $key ) {
        status_header( 404 );
        exit;
    }
    header( 'Content-Type: text/plain; charset=utf-8' );
    status_header( 200 );
    echo esc_html( $key );
    exit;
} );

/**
 * Handle the manual ping form on the Site & SEO settings page.
 */
add_action( 'admin_init', function () {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) return;
    if ( empty( $_POST['hpw_indexnow_action'] ) ) return;

    check_admin_referer( 'hpw_indexnow_admin', 'hpw_indexnow_nonce' );
    $action = sanitize_key( $_POST['hpw_indexnow_action'] );

    if ( 'regenerate_key' === $action ) {
        // Delete old key file before regenerating.
        $old_key = (string) get_option( 'hpw_indexnow_key', '' );
        if ( $old_key ) {
            $old_path = rtrim( ABSPATH, '/' ) . '/' . preg_replace( '/[^a-zA-Z0-9\-_]/', '', $old_key ) . '.txt';
            if ( file_exists( $old_path ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                @unlink( $old_path );
            }
        }
        delete_option( 'hpw_indexnow_key' );
        holyprofweb_indexnow_key(); // triggers generation + file write
        wp_safe_redirect( add_query_arg( array( 'page' => 'hpw-settings', 'hpw_indexnow' => 'key_regenerated' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( 'write_key_file' === $action ) {
        holyprofweb_indexnow_write_key_file();
        wp_safe_redirect( add_query_arg( array( 'page' => 'hpw-settings', 'hpw_indexnow' => 'file_written' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( 'ping_all' === $action ) {
        // Ping the 50 most recent published posts + homepage.
        $urls    = array( home_url( '/' ) );
        $posts   = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        foreach ( $posts as $pid ) {
            $urls[] = (string) get_permalink( $pid );
        }
        holyprofweb_indexnow_submit( $urls );
        wp_safe_redirect( add_query_arg( array( 'page' => 'hpw-settings', 'hpw_indexnow' => 'pinged', 'count' => count( $urls ) ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( 'ping_url' === $action ) {
        $url = isset( $_POST['hpw_indexnow_url'] ) ? esc_url_raw( wp_unslash( $_POST['hpw_indexnow_url'] ) ) : '';
        if ( $url ) {
            holyprofweb_indexnow_submit( array( $url ) );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => 'hpw-settings', 'hpw_indexnow' => 'pinged', 'count' => $url ? 1 : 0 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( 'toggle' === $action ) {
        $current = get_option( 'hpw_indexnow_enabled', 1 );
        update_option( 'hpw_indexnow_enabled', $current ? 0 : 1 );
        wp_safe_redirect( add_query_arg( array( 'page' => 'hpw-settings', 'hpw_indexnow' => 'toggled' ), admin_url( 'admin.php' ) ) );
        exit;
    }
} );

/**
 * Auto-generate key on theme activation.
 */
add_action( 'after_switch_theme', function () {
    holyprofweb_indexnow_key();
} );

/**
 * Auto-generate key on first admin load if not yet set.
 */
add_action( 'admin_init', function () {
    if ( '' === get_option( 'hpw_indexnow_key', '' ) ) {
        holyprofweb_indexnow_key();
    }
}, 5 );

/**
 * Auto-generate post excerpt from content (first ~155 chars, word-boundary cut).
 * Skips if the author has already written an excerpt.
 */
function holyprofweb_auto_set_excerpt( $post_id, $post ) {
    if ( ! empty( trim( $post->post_excerpt ) ) ) return;

    $text    = wp_strip_all_tags( $post->post_content );
    $text    = preg_replace( '/\s+/', ' ', trim( $text ) );
    $excerpt = mb_substr( $text, 0, 155 );
    if ( mb_strlen( $text ) > 155 ) {
        $last    = mb_strrpos( $excerpt, ' ' );
        $excerpt = ( $last > 80 ) ? mb_substr( $excerpt, 0, $last ) . '…' : $excerpt . '…';
    }
    if ( ! $excerpt ) return;

    // Prevent recursion
    remove_action( 'transition_post_status', 'holyprofweb_on_post_publish', 10 );
    wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => sanitize_text_field( $excerpt ) ) );
    add_action( 'transition_post_status', 'holyprofweb_on_post_publish', 10, 3 );
}

/**
 * Auto-tag from title keywords + child category name.
 * Never overwrites tags the author set manually.
 */
function holyprofweb_auto_set_tags( $post_id, $post ) {
    if ( ! empty( wp_get_post_tags( $post_id ) ) ) return;

    $tags = array();

    // Add child category names as tags
    foreach ( get_the_category( $post_id ) as $cat ) {
        if ( $cat->parent ) $tags[] = $cat->name;
    }

    // Extract significant words from title (≥4 chars, not stop-words)
    static $stop = null;
    if ( null === $stop ) {
        $stop = array_flip( explode( ',', 'a,an,the,and,or,but,in,on,at,to,for,of,with,by,from,is,are,was,were,be,been,have,has,had,do,does,did,will,would,could,should,may,might,can,how,what,when,where,which,who,this,that,these,those,it,its,full,quick,guide,complete,top,best,vs,review,reviews,profile,overview,salary,salaries,biography,company,report,reports,alert,latest,new,all,about,before,using,after,during,between' ) );
    }

    foreach ( preg_split( '/\s+/', $post->post_title ) as $word ) {
        $clean = preg_replace( '/[^a-z0-9]/i', '', strtolower( $word ) );
        if ( mb_strlen( $clean ) >= 4 && ! isset( $stop[ $clean ] ) ) {
            $tags[] = $clean;
        }
    }

    $tags = array_values( array_unique( $tags ) );
    if ( ! empty( $tags ) ) {
        wp_set_post_tags( $post_id, array_slice( $tags, 0, 10 ), false );
    }
}

/**
 * Detect and cache the Schema.org type for a post based on its categories.
 * Stored in _hpw_schema_type. Used by seo-append.php for JSON-LD output.
 *
 * Type map (most specific wins):
 *  biography / founders / influencers  → Person
 *  companies / fintech / banks / startups → Organization
 *  scam-reports / user-complaints / reports → NewsArticle
 *  salaries / nigeria / remote / tech-roles → Occupation
 *  reviews / loan-apps / crypto / betting / earning-platforms → ItemPage (aggregateRating)
 *  default → Article
 */
function holyprofweb_cache_schema_type( $post_id ) {
    $slugs = wp_list_pluck( get_the_category( $post_id ), 'slug' );

    if ( array_intersect( array( 'biography', 'founders', 'influencers' ), $slugs ) ) {
        $type = 'Person';
    } elseif ( array_intersect( array( 'companies', 'fintech', 'banks', 'startups' ), $slugs ) ) {
        $type = 'Organization';
    } elseif ( array_intersect( array( 'scam-reports', 'user-complaints', 'reports' ), $slugs ) ) {
        $type = 'NewsArticle';
    } elseif ( array_intersect( array( 'salaries', 'nigeria', 'remote', 'tech-roles' ), $slugs ) ) {
        $type = 'Occupation';
    } elseif ( array_intersect( array( 'reviews', 'loan-apps', 'crypto', 'betting', 'earning-platforms' ), $slugs ) ) {
        $type = 'ItemPage';
    } else {
        $type = 'Article';
    }

    update_post_meta( $post_id, '_hpw_schema_type', $type );
}

function holyprofweb_build_longform_sections( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'post' !== $post->post_type ) {
        return '';
    }

    $title          = holyprofweb_get_decoded_post_title( $post_id );
    $excerpt        = holyprofweb_get_decoded_post_excerpt( $post_id );
    $cats           = wp_list_pluck( get_the_category( $post_id ), 'slug' );
    $country        = get_post_meta( $post_id, '_hpw_country_focus', true );
    $country_phrase = $country ? ' in ' . $country : '';
    $plain_existing = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $post->post_content ) ) );
    $intro          = $excerpt ?: sprintf( '%s is a topic readers usually research when they want clear facts, fewer surprises, and a better decision before taking action.', $title );
    $summary_box    = 'Research-first summary: this page explains the core context, the practical issues to watch, the most important signals, and where readers should slow down before trusting any claim.';
    $overview       = sprintf( '%s%s deserves a fuller explanation because searchers usually want more than a quick definition. They want the context behind the headline, the practical meaning for regular users, and the small details that can change whether the experience feels useful, risky, expensive, or simply overhyped.', $title, $country_phrase );
    $checks         = 'The strongest pages in this space usually answer the same questions clearly: what the product, company, person, or report actually is; what changed recently; why people are searching now; what the good signs look like; and what warning signs should make readers pause before moving money, trusting a promise, or repeating what they heard elsewhere.';
    $reader_fit     = 'This article is built for readers who want plain-English context without fluff. That includes beginners doing first-time research, returning users comparing alternatives, and more experienced readers who want a cleaner summary of the biggest signals without wasting time on filler.';
    $alternatives   = 'A smart research flow also includes comparison. Readers should look at obvious alternatives, compare fees or compensation ranges, read complaint patterns, and note whether problems appear isolated or repeated across multiple sources. That comparison step often matters more than the headline itself because it shows whether the topic stands out for the right reasons or simply because it is noisy.';
    $update         = 'This page should keep evolving as more user feedback, complaint patterns, salary signals, policy changes, and company updates appear. A page like this performs best when it remains practical, current, and tightly focused on what readers need before making a decision.';

    if ( array_intersect( array( 'salaries', 'nigeria', 'remote', 'tech-roles' ), $cats ) ) {
        $summary_box = 'Research-first summary: this salary page breaks down market range, role level, location impact, company type, and what can increase or reduce compensation.';
        $overview    = sprintf( '%s%s matters because salary posts are rarely useful when they only throw out one number. Readers need context around seniority, remote versus local hiring, company stage, and whether the range reflects base pay, total compensation, or a temporary spike in demand.', $title, $country_phrase );
        $checks      = 'The best way to read salary content is to separate headline numbers from the real drivers underneath them. Role scope, niche skill depth, team maturity, product complexity, and negotiation leverage often explain why two people with a similar title can be paid very differently.';
        $reader_fit  = 'This page helps candidates preparing for interviews, professionals benchmarking their current pay, founders setting budgets, and recruiters trying to make more realistic offers in a competitive market.';
        $alternatives = 'Readers should compare this range with similar roles, neighboring markets, remote opportunities, and closely related titles. That wider benchmark helps prevent both underpricing and unrealistic expectations.';
    } elseif ( array_intersect( array( 'reports', 'scam-reports', 'user-complaints', 'scam-legit' ), $cats ) ) {
        $summary_box = 'Research-first summary: this report focuses on complaint patterns, trust signals, red flags, and what readers should verify before believing claims or sending money.';
        $overview    = sprintf( '%s%s usually gets attention because people feel urgency. They want to know whether the warning is isolated, whether other users are seeing the same thing, and whether the pattern suggests poor service, sloppy operations, or something more serious.', $title, $country_phrase );
        $checks      = 'The most useful warning-sign analysis looks beyond one angry post. It checks for repeated complaints, delayed withdrawals, fake urgency, vague support replies, changing explanations, hidden fees, and mismatches between marketing promises and what users report after signup.';
        $reader_fit  = 'This page is built for cautious readers who want to protect money, time, trust, and reputation before moving forward with a service or repeating a claim to others.';
        $alternatives = 'A strong research habit here is to compare multiple complaint sources, look for official statements, and watch whether the same problems keep appearing across regions, months, and user types.';
    } elseif ( array_intersect( array( 'companies', 'fintech', 'banks', 'startups' ), $cats ) ) {
        $summary_box = 'Research-first summary: this company page explains what the business does, why people search for it, where it looks strong, and where caution or verification still matters.';
        $overview    = sprintf( '%s%s is more useful when readers can see the business in context instead of just reading a polished company description. That means looking at product quality, user trust, public reputation, leadership clarity, customer experience, and how the business is positioned in its market.', $title, $country_phrase );
        $checks      = 'The strongest company research looks at product relevance, market traction, public complaints, hiring reputation, reliability, and whether the company communicates clearly when users face problems or changes.';
        $reader_fit  = 'This page helps users comparing services, job seekers researching employers, founders looking for references, and readers trying to understand why a company is being discussed right now.';
        $alternatives = 'Comparison matters here too. Readers should compare competitors, older incumbents, newer challengers, and any substitute products that solve the same problem differently.';
    } elseif ( array_intersect( array( 'biography', 'founders', 'influencers' ), $cats ) ) {
        $summary_box = 'Research-first summary: this biography focuses on background, relevance, major milestones, and why people are searching for this person now.';
        $overview    = sprintf( '%s%s should not read like a thin profile card. Readers usually want a clear timeline, why the person matters, what changed recently, and what part of their story keeps drawing search interest.', $title, $country_phrase );
        $checks      = 'Useful biography pages explain background, career moves, influence, public reputation, and why the person matters to a particular country, industry, or audience.';
        $reader_fit  = 'This page helps readers who want more than trivia. It is for people looking for relevance, context, and a cleaner understanding of why a public figure keeps showing up in search results.';
        $alternatives = 'The best follow-up habit is to compare this profile with related figures, peers, rivals, or predecessors so readers understand the person inside a wider story instead of as an isolated name.';
    }

    $sections = array(
        '<!-- HPW-AUTO-CONTENT:START -->',
        '<section class="hpw-auto-summary"><h2>Quick Summary</h2><p>' . esc_html( $summary_box ) . '</p></section>',
        '<section class="hpw-auto-overview"><h2>Overview</h2><p>' . esc_html( $intro ) . '</p><p>' . esc_html( $overview ) . '</p></section>',
        '<section class="hpw-auto-checks"><h2>What Readers Should Check First</h2><p>' . esc_html( $checks ) . '</p><p>' . esc_html( 'A page becomes more trustworthy when it helps readers slow down, compare facts, and understand where the biggest trade-offs sit. That is especially important when the search intent touches money, trust, credibility, hiring, or public reputation.' ) . '</p></section>',
        '<section class="hpw-auto-fit"><h2>Who This Page Is For</h2><p>' . esc_html( $reader_fit ) . '</p><p>' . esc_html( 'It is also useful for readers who found the topic through social chatter, Google autocomplete, or a trending conversation and now want a calmer explanation that gives them enough substance to think clearly.' ) . '</p></section>',
        '<section class="hpw-auto-signals"><h2>Useful Signals and Practical Context</h2><p>' . esc_html( 'Strong decisions usually come from a mix of public information, user-facing experience, and pattern recognition. In practice, that means looking at what the platform says, what users report, what has changed over time, and what the broader market suggests about quality or credibility.' ) . '</p><p>' . esc_html( 'Readers also benefit from separating marketing claims from lived outcomes. That gap between promise and experience is often where the most valuable insight sits, and it is where good long-form content can outperform shallow roundups.' ) . '</p></section>',
        '<section class="hpw-auto-alternatives"><h2>Alternatives, Comparisons, and Related Searches</h2><p>' . esc_html( $alternatives ) . '</p><p>' . esc_html( 'Related searches usually reveal the next layer of intent. People rarely stop at one query. They move into alternatives, complaints, payout questions, pricing checks, salary comparisons, or biography follow-ups. Good content should acknowledge that path and make the next step easier.' ) . '</p></section>',
        '<section class="hpw-auto-risks"><h2>Risks, Gaps, and What to Watch</h2><p>' . esc_html( 'No page should push readers into false certainty. Even when the overall picture looks positive, users still need to watch for changing terms, support quality, withdrawal or payout issues, shifting salary conditions, outdated public information, or region-specific differences that can change the experience.' ) . '</p><p>' . esc_html( 'That is why the safest approach is to treat this page as a strong starting point and a practical guide, not as permission to stop verifying facts that affect your money, career, reputation, or time.' ) . '</p></section>',
        '<section class="hpw-auto-update"><h2>Update Notes</h2><p>' . esc_html( $update ) . '</p><p>' . esc_html( 'As the topic changes, the strongest version of this page will keep tightening structure, improving clarity, and connecting readers to better internal comparisons across HolyprofWeb.' ) . '</p></section>',
        '<!-- HPW-AUTO-CONTENT:END -->',
    );

    return wp_kses_post( implode( '', $sections ) );
}

function holyprofweb_expand_thin_post_content( $post_id, $force = false ) {
    $post = get_post( $post_id );
    if ( ! $post || 'post' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'draft', 'pending' ), true ) ) {
        return;
    }

    $content = (string) $post->post_content;
    if ( false !== strpos( $content, '<!-- HPW-AUTO-CONTENT:START -->' ) ) {
        $content = preg_replace( '/<!-- HPW-AUTO-CONTENT:START -->.*?<!-- HPW-AUTO-CONTENT:END -->/si', '', $content );
    }

    $plain      = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $content ) ) );
    $word_count = str_word_count( $plain );
    $minimum    = holyprofweb_get_draft_minimum_words();

    if ( ! $force && $word_count >= $minimum && ! holyprofweb_content_looks_repetitive( $content ) ) {
        return;
    }

    $auto_content = holyprofweb_build_longform_sections( $post_id );
    if ( ! $auto_content ) {
        return;
    }

    $new_content = trim( (string) $content );
    if ( '' !== $new_content ) {
        $new_content .= "\n\n";
    }
    $new_content .= $auto_content;

    wp_update_post( array(
        'ID'           => $post_id,
        'post_content' => $new_content,
    ) );
}

function holyprofweb_expand_existing_thin_posts_once() {
    if ( get_option( 'holyprofweb_thin_content_expanded_v1' ) ) {
        return;
    }

    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    foreach ( $posts as $post_id ) {
        holyprofweb_expand_thin_post_content( $post_id );
    }

    update_option( 'holyprofweb_thin_content_expanded_v1', 1 );
}
add_action( 'init', 'holyprofweb_expand_existing_thin_posts_once', 60 );

add_filter( 'the_content', function( $content ) {
    if ( false === strpos( (string) $content, '<!-- HPW-AUTO-CONTENT:START -->' ) ) {
        return $content;
    }

    return preg_replace( '/<!-- HPW-AUTO-CONTENT:START -->.*?<!-- HPW-AUTO-CONTENT:END -->/si', '', (string) $content );
}, 5 );

/**
 * Calculate and cache estimated reading time (minutes) from word count.
 * Average reading speed: 200 wpm.
 */
function holyprofweb_cache_reading_time( $post_id, $post ) {
    $words   = str_word_count( wp_strip_all_tags( $post->post_content ) );
    $minutes = max( 1, (int) round( $words / 200 ) );
    update_post_meta( $post_id, '_hpw_reading_time', $minutes );
}


// =========================================
// STATIC PAGES — About, Work With Us, etc.
// =========================================

/**
 * Create essential static pages that must exist.
 * Safe to call multiple times — checks each page slug before inserting.
 */
function holyprofweb_create_static_pages() {
    $year = gmdate( 'F j, Y' );

    $pages = array(
        'about' => array(
            'title'   => 'About HolyprofWeb',
            'content' => '<h2>About Us</h2><p>HolyprofWeb is a global web intelligence platform built to help you research before you decide. We cover reviews, company profiles, salary data, biographies, and reports across industries.</p><h2>Our Mission</h2><p>We believe every financial and career decision deserves solid research. Our platform aggregates trusted information, user reviews, and verified data in one place.</p><h2>What We Cover</h2><ul><li><strong>Reviews</strong> — Honest user reviews of fintech apps, loan platforms, crypto exchanges, and more.</li><li><strong>Companies</strong> — Profiles of banks, fintechs, and startups across Africa.</li><li><strong>Salaries</strong> — Real salary data for tech and finance roles.</li><li><strong>Biography</strong> — Profiles of founders, influencers, and business leaders.</li><li><strong>Reports</strong> — Scam alerts, user complaint trends, and industry analysis.</li></ul><p>Have feedback or a tip? <a href="mailto:admin@holyprofweb.com">Contact us.</a></p>',
        ),
        'work-with-us' => array(
            'title'   => 'Work With Us',
            'content' => '<h2>Join the HolyprofWeb Team</h2><p>We are always looking for talented writers, researchers, and analysts who are passionate about financial intelligence and helping people make better decisions.</p><h2>Open Roles</h2><ul><li><strong>Content Writer</strong> — Research and write reviews, company profiles, and salary guides.</li><li><strong>Data Analyst</strong> — Compile and verify salary and market data.</li><li><strong>SEO Specialist</strong> — Help us reach more people searching for the information we publish.</li></ul><h2>How to Apply</h2><p>Send your CV, a brief intro, and two writing samples to <a href="mailto:admin@holyprofweb.com">admin@holyprofweb.com</a> with the subject line: <em>Work With Us — [Role Name]</em>.</p><p>We review applications on a rolling basis.</p>',
        ),
        'advertise' => array(
            'title'   => 'Advertise With Us',
            'content' => '<h2>Reach a Research-First Audience</h2><p>HolyprofWeb attracts an engaged audience actively researching financial products, companies, salaries, and career decisions. Your ads appear in front of users with real purchase and decision-making intent.</p><h2>Ad Formats Available</h2><ul><li>Header banner (728×90 leaderboard)</li><li>In-content placements (after 2nd and 4th paragraphs)</li><li>Sidebar placements (300×250)</li><li>Footer banner</li><li>Sponsored content / company profiles</li></ul><h2>Who Should Advertise</h2><p>Fintech products, banks, salary tools, job boards, HR platforms, crypto exchanges, and any brand that benefits from reaching financially-aware, research-driven users.</p><h2>Get in Touch</h2><p>Email us at <a href="mailto:admin@holyprofweb.com">admin@holyprofweb.com</a> with the subject line: <em>Advertising Enquiry</em>. Include your target audience, budget range, and desired placement.</p>',
        ),
        'privacy-policy' => array(
            'title'   => 'Privacy Policy',
            /* translators: %s = current date */
            'content' => sprintf( '<p><em>Last updated: %s</em></p><h2>1. Information We Collect</h2><p>We collect information you provide directly (such as review submissions and email subscriptions) and information collected automatically (such as search queries, pages viewed, and general device data via analytics).</p><h2>2. How We Use Your Information</h2><ul><li>To display your submitted reviews on the platform</li><li>To send occasional email updates if you subscribed</li><li>To improve platform content and performance</li><li>To detect and prevent spam or abuse</li></ul><h2>3. Cookies</h2><p>We use cookies to remember your preferences and measure traffic. You can disable cookies in your browser settings, though some features may not work as expected.</p><h2>4. Third-Party Services</h2><p>We use third-party services for analytics and fonts. These services may collect basic usage data under their own privacy policies.</p><h2>5. Data Retention</h2><p>We retain submitted reviews and subscriptions until you request deletion. Search and analytics data is anonymised and retained for up to 12 months.</p><h2>6. Your Rights</h2><p>You may request deletion of any personal data you submitted by emailing <a href="mailto:admin@holyprofweb.com">admin@holyprofweb.com</a>.</p><h2>7. Contact</h2><p>For privacy questions: <a href="mailto:admin@holyprofweb.com">admin@holyprofweb.com</a></p>', esc_html( $year ) ),
        ),
        'contact' => array(
            'title'         => 'Contact Us',
            'content'       => '',
            'page_template' => 'page-contact.php',
        ),
    );

    foreach ( $pages as $slug => $data ) {
        $existing = get_page_by_path( $slug, OBJECT, 'page' );

        if ( $existing ) {
            // Page exists — ensure the template is correctly set
            if ( ! empty( $data['page_template'] ) ) {
                $current_tpl = get_post_meta( $existing->ID, '_wp_page_template', true );
                if ( $current_tpl !== $data['page_template'] ) {
                    update_post_meta( $existing->ID, '_wp_page_template', $data['page_template'] );
                }
            }
            continue;
        }

        $post_arr = array(
            'post_title'     => $data['title'],
            'post_name'      => $slug,
            'post_content'   => $data['content'],
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => 1,
            'comment_status' => 'closed',
        );
        if ( ! empty( $data['page_template'] ) ) {
            $post_arr['page_template'] = $data['page_template'];
        }
        $new_id = wp_insert_post( $post_arr );
        if ( $new_id && ! is_wp_error( $new_id ) && ! empty( $data['page_template'] ) ) {
            update_post_meta( $new_id, '_wp_page_template', $data['page_template'] );
        }
    }
}
// Run on theme activation and once on first load for existing installs.
add_action( 'after_switch_theme', 'holyprofweb_create_static_pages', 11 );
add_action( 'init', function() {
    if ( ! get_option( 'holyprofweb_static_pages_v1' ) ) {
        holyprofweb_create_static_pages();
        update_option( 'holyprofweb_static_pages_v1', true );
    }
}, 31 );


// =========================================
// BIOGRAPHY — WIKIPEDIA PERSON IMAGE
// =========================================

/**
 * Fetch a person's photo from Wikipedia REST API using the post title.
 * Used as a fallback for biography-category posts with no source URL.
 *
 * @param string $title Post title.
 * @return string Image URL or empty string.
 */
function holyprofweb_get_wikipedia_person_image( $title ) {
    // Strip "Biography", "Profile", "Founder", "CEO", etc. from the title
    $name = preg_replace( '/\s*[-–—:]\s*.*/u', '', $title );
    $name = preg_replace( '/\b(biography|profile|net\s+worth|wiki|founder|ceo|influencer|overview)\b/iu', '', $name );
    $name = trim( $name );

    if ( mb_strlen( $name ) < 3 ) {
        return '';
    }

    $api_url  = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode( str_replace( ' ', '_', $name ) );
    $response = wp_remote_get( $api_url, array( 'timeout' => 6 ) );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return '';
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    return isset( $body['thumbnail']['source'] ) ? (string) $body['thumbnail']['source'] : '';
}

/**
 * Hook into the auto-image pipeline:
 * - Biography posts  → try Wikipedia person thumbnail
 * - Company/fintech/bank/startup posts → prefer Clearbit logo over OG image
 */
add_filter( 'holyprofweb_automation_image_url', function( $image_url, $post_id, $post, $domain, $source_url ) {
    if ( $image_url ) {
        return $image_url;
    }

    if ( $source_url && holyprofweb_is_disallowed_source_domain( $source_url ) ) {
        return '';
    }

    $cats      = get_the_category( $post_id );
    $cat_slugs = wp_list_pluck( $cats, 'slug' );

    $is_bio         = array_intersect( array( 'biography', 'founders', 'influencers' ), $cat_slugs );
    $is_company     = array_intersect( array( 'companies', 'fintech', 'banks', 'startups' ), $cat_slugs );
    $is_review_like = array_intersect( array( 'reviews', 'apps', 'loan-apps', 'crypto', 'betting', 'earning-platforms', 'websites', 'product-reviews' ), $cat_slugs );

    if ( $is_bio ) {
        // Try Wikipedia first; source URL OG will be tried by the caller after this filter
        $image_url = holyprofweb_get_wikipedia_person_image( $post->post_title );
    } elseif ( ( $is_company || $is_review_like ) && $source_url ) {
        $image_url = holyprofweb_pick_working_remote_image_url( holyprofweb_get_site_visual_candidates( $source_url ) );
        if ( ! $image_url && $domain ) {
            $image_url = holyprofweb_get_clearbit_logo_url( $domain );
        }
    }

    return $image_url;
}, 10, 5 );


// =========================================
// WP-CLI COMMANDS
// =========================================

if ( defined( 'WP_CLI' ) && WP_CLI ) {

    /**
     * HPW theme utilities.
     *
     * ## EXAMPLES
     *
     *   # Regenerate missing featured images
     *   $ wp hpw regenerate-images
     *
     *   # Force-regenerate ALL featured images (including existing)
     *   $ wp hpw regenerate-images --force
     *
     *   # Delete placeholder and sample posts, keep seed posts
     *   $ wp hpw clean-posts
     *
     *   # Recreate static pages (About, Work With Us, etc.)
     *   $ wp hpw create-pages
     */
    class HPW_CLI_Command {

        /**
         * Regenerate featured images for published posts.
         *
         * ## OPTIONS
         *
         * [--force]
         * : Re-generate even if a thumbnail already exists.
         *
         * ## EXAMPLES
         *
         *   wp hpw regenerate-images
         *   wp hpw regenerate-images --force
         *
         * @subcommand regenerate-images
         */
        public function regenerate_images( $args, $assoc_args ) {
            $force = ! empty( $assoc_args['force'] );

            $query = new WP_Query( array(
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'fields'         => 'ids',
            ) );

            $total     = count( $query->posts );
            $processed = 0;

            WP_CLI::line( "Found {$total} published posts." );

            foreach ( $query->posts as $post_id ) {
                if ( ! $force && has_post_thumbnail( $post_id ) ) {
                    continue;
                }

                $post = get_post( $post_id );
                if ( ! $post ) continue;

                // Clear all cached image URLs so a fresh fetch/generate is attempted
                delete_post_meta( $post_id, '_holyprofweb_remote_image_url' );
                delete_post_meta( $post_id, '_holyprofweb_gen_image_url' );

                if ( $force ) {
                    delete_post_thumbnail( $post_id );
                }

                holyprofweb_auto_featured_image( $post_id, $post, true );
                $processed++;

                $has = has_post_thumbnail( $post_id ) ? '✓ image set' : '– no image found';
                WP_CLI::line( "[{$post_id}] {$post->post_title} — {$has}" );
            }

            WP_CLI::success( "Done. Processed {$processed} post(s)." );
        }

        /**
         * Re-run HPW smart category assignment on existing posts.
         *
         * ## OPTIONS
         *
         * [--post_status=<status>]
         * : Limit by post status. Default: any
         *
         * ## EXAMPLES
         *
         *   wp hpw recategorize-posts
         *   wp hpw recategorize-posts --post_status=publish
         *
         * @subcommand recategorize-posts
         */
        public function recategorize_posts( $args, $assoc_args ) {
            $post_status = ! empty( $assoc_args['post_status'] ) ? sanitize_key( $assoc_args['post_status'] ) : 'any';

            $post_ids = get_posts( array(
                'post_type'      => 'post',
                'post_status'    => $post_status,
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ) );

            $total = count( $post_ids );
            $done  = 0;

            WP_CLI::line( "Found {$total} post(s) to reclassify." );

            foreach ( $post_ids as $post_id ) {
                $post = get_post( $post_id );
                if ( ! $post instanceof WP_Post ) {
                    continue;
                }

                holyprofweb_assign_smart_categories( $post_id, $post );
                holyprofweb_normalize_post_categories( $post_id );
                $done++;

                $categories = wp_list_pluck( get_the_category( $post_id ), 'name' );
                WP_CLI::line( "[{$post_id}] {$post->post_title} => " . implode( ', ', $categories ) );
            }

            WP_CLI::success( "Done. Reclassified {$done} post(s)." );
        }

        /**
         * Delete placeholder and sample posts, keeping only quality seed posts.
         *
         * ## EXAMPLES
         *
         *   wp hpw clean-posts
         *
         * @subcommand clean-posts
         */
        public function clean_posts( $args, $assoc_args ) {
            $meta_targets = array(
                '_hpw_placeholder_post' => 1,
                '_hpw_sample_post'      => 1,
            );

            $deleted = 0;

            foreach ( $meta_targets as $key => $value ) {
                $posts = get_posts( array(
                    'posts_per_page' => -1,
                    'post_type'      => 'post',
                    'post_status'    => 'any',
                    'meta_key'       => $key,
                    'meta_value'     => $value,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                ) );

                foreach ( $posts as $id ) {
                    $title = get_the_title( $id );
                    wp_delete_post( $id, true );
                    $deleted++;
                    WP_CLI::line( "Deleted [{$id}] {$title}" );
                }
            }

            WP_CLI::success( "Deleted {$deleted} post(s). Run 'wp hpw regenerate-images' to refresh featured images." );
        }

        /**
         * Create or recreate essential static pages.
         *
         * ## EXAMPLES
         *
         *   wp hpw create-pages
         *
         * @subcommand create-pages
         */
        public function create_pages( $args, $assoc_args ) {
            // Delete option flag so pages are checked fresh
            delete_option( 'holyprofweb_static_pages_v1' );
            holyprofweb_create_static_pages();
            update_option( 'holyprofweb_static_pages_v1', true );
            holyprofweb_create_menus();
            flush_rewrite_rules();
            WP_CLI::success( 'Static pages and nav menus refreshed.' );
        }
    }

    WP_CLI::add_command( 'hpw', 'HPW_CLI_Command' );
}


// Load SEO + submission helpers
require_once get_template_directory() . '/seo-append.php';
