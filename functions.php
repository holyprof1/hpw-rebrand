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
        'type'      => 'list',
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
        $ph = get_template_directory_uri() . '/assets/images/placeholder.svg';
        echo '<img src="' . esc_url( $ph ) . '" alt="" loading="lazy" class="post-card-placeholder-img" />';
    }

    echo $link ? '</a>' : '</div>';
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
 * Render the Ads settings page (6 slots).
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
        // Raw ad code storage — admin-only, intentionally allows script tags
        $slots = array( 'header', 'sidebar', 'sidebar_2', 'incontent', 'incontent_2', 'footer' );
        foreach ( $slots as $slot ) {
            $field = 'holyprofweb_ad_' . $slot;
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $code = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
            update_option( $field, $code );
        }

        $formats = array( 'leaderboard', 'rectangle', 'mobile', 'social' );
        foreach ( $formats as $format ) {
            $format_field = 'holyprofweb_ad_format_' . $format;
            $density_field = 'holyprofweb_ad_density_' . $format;
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $code = isset( $_POST[ $format_field ] ) ? wp_unslash( $_POST[ $format_field ] ) : '';
            $density = isset( $_POST[ $density_field ] ) ? sanitize_key( wp_unslash( $_POST[ $density_field ] ) ) : 'basic';
            update_option( $format_field, $code );
            update_option( $density_field, in_array( $density, array( 'basic', 'normal', 'rigid' ), true ) ? $density : 'basic' );
        }
        $saved = true;
    }
    ?>
    <div class="wrap">
        <h1>&#128250; <?php esc_html_e( 'HPW Settings — Ads', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'ads' ); ?>

        <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Ad codes saved successfully.', 'holyprofweb' ); ?></p>
        </div>
        <?php endif; ?>

        <p style="color:#666; margin-bottom:20px;">
            <?php esc_html_e( 'Paste raw ad code from Adsterra or any network. Leave empty to disable. No popups or popunders — display/native ads only.', 'holyprofweb' ); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'holyprofweb_save_ads', 'holyprofweb_ads_nonce' ); ?>

            <h2><?php esc_html_e( 'Adsterra Format Controls', 'holyprofweb' ); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Leaderboard / 728x90', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="holyprofweb_ad_format_leaderboard" rows="5" class="large-text code"><?php echo esc_textarea( get_option( 'holyprofweb_ad_format_leaderboard', '' ) ); ?></textarea>
                        <p><select name="holyprofweb_ad_density_leaderboard">
                            <option value="basic" <?php selected( holyprofweb_get_ad_density( 'leaderboard' ), 'basic' ); ?>><?php esc_html_e( 'Basic', 'holyprofweb' ); ?></option>
                            <option value="normal" <?php selected( holyprofweb_get_ad_density( 'leaderboard' ), 'normal' ); ?>><?php esc_html_e( 'Normal', 'holyprofweb' ); ?></option>
                            <option value="rigid" <?php selected( holyprofweb_get_ad_density( 'leaderboard' ), 'rigid' ); ?>><?php esc_html_e( 'Rigid', 'holyprofweb' ); ?></option>
                        </select></p>
                        <p class="description"><?php esc_html_e( 'Basic: top only. Normal: top + footer. Rigid: top + front/archive inline + footer.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Rectangle / 300x250 / 336x280', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="holyprofweb_ad_format_rectangle" rows="5" class="large-text code"><?php echo esc_textarea( get_option( 'holyprofweb_ad_format_rectangle', '' ) ); ?></textarea>
                        <p><select name="holyprofweb_ad_density_rectangle">
                            <option value="basic" <?php selected( holyprofweb_get_ad_density( 'rectangle' ), 'basic' ); ?>><?php esc_html_e( 'Basic', 'holyprofweb' ); ?></option>
                            <option value="normal" <?php selected( holyprofweb_get_ad_density( 'rectangle' ), 'normal' ); ?>><?php esc_html_e( 'Normal', 'holyprofweb' ); ?></option>
                            <option value="rigid" <?php selected( holyprofweb_get_ad_density( 'rectangle' ), 'rigid' ); ?>><?php esc_html_e( 'Rigid', 'holyprofweb' ); ?></option>
                        </select></p>
                        <p class="description"><?php esc_html_e( 'Basic: sidebar + first in-content. Normal: more front-page coverage. Rigid: sidebar, multiple in-content, archive/front inline.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Mobile 320x50 / 320x100', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="holyprofweb_ad_format_mobile" rows="5" class="large-text code"><?php echo esc_textarea( get_option( 'holyprofweb_ad_format_mobile', '' ) ); ?></textarea>
                        <p><select name="holyprofweb_ad_density_mobile">
                            <option value="basic" <?php selected( holyprofweb_get_ad_density( 'mobile' ), 'basic' ); ?>><?php esc_html_e( 'Basic', 'holyprofweb' ); ?></option>
                            <option value="normal" <?php selected( holyprofweb_get_ad_density( 'mobile' ), 'normal' ); ?>><?php esc_html_e( 'Normal', 'holyprofweb' ); ?></option>
                            <option value="rigid" <?php selected( holyprofweb_get_ad_density( 'mobile' ), 'rigid' ); ?>><?php esc_html_e( 'Rigid', 'holyprofweb' ); ?></option>
                        </select></p>
                        <p class="description"><?php esc_html_e( 'Use for mobile sticky or compact inline mobile placements.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Social Bar / Native Bar', 'holyprofweb' ); ?></th>
                    <td>
                        <textarea name="holyprofweb_ad_format_social" rows="5" class="large-text code"><?php echo esc_textarea( get_option( 'holyprofweb_ad_format_social', '' ) ); ?></textarea>
                        <p><select name="holyprofweb_ad_density_social">
                            <option value="basic" <?php selected( holyprofweb_get_ad_density( 'social' ), 'basic' ); ?>><?php esc_html_e( 'Basic', 'holyprofweb' ); ?></option>
                            <option value="normal" <?php selected( holyprofweb_get_ad_density( 'social' ), 'normal' ); ?>><?php esc_html_e( 'Normal', 'holyprofweb' ); ?></option>
                            <option value="rigid" <?php selected( holyprofweb_get_ad_density( 'social' ), 'rigid' ); ?>><?php esc_html_e( 'Rigid', 'holyprofweb' ); ?></option>
                        </select></p>
                        <p class="description"><?php esc_html_e( 'Sticky social-style bar. Use carefully on mobile.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>
            </table>

            <hr>
            <h2><?php esc_html_e( 'Legacy Slot Controls', 'holyprofweb' ); ?></h2>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        <label for="holyprofweb_ad_header">
                            <?php esc_html_e( 'Header Banner (728×90)', 'holyprofweb' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="holyprofweb_ad_header" name="holyprofweb_ad_header"
                                  rows="6" cols="60" class="large-text code"><?php
                            echo esc_textarea( get_option( 'holyprofweb_ad_header', '' ) );
                        ?></textarea>
                        <p class="description"><?php esc_html_e( 'Displayed in the site header area (728×90 leaderboard).', 'holyprofweb' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="holyprofweb_ad_sidebar">
                            <?php esc_html_e( 'Sidebar Ad 1 (300×250)', 'holyprofweb' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="holyprofweb_ad_sidebar" name="holyprofweb_ad_sidebar"
                                  rows="6" cols="60" class="large-text code"><?php
                            echo esc_textarea( get_option( 'holyprofweb_ad_sidebar', '' ) );
                        ?></textarea>
                        <p class="description"><?php esc_html_e( 'Displayed in the post sidebar, first slot.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="holyprofweb_ad_sidebar_2">
                            <?php esc_html_e( 'Sidebar Ad 2 (300×250)', 'holyprofweb' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="holyprofweb_ad_sidebar_2" name="holyprofweb_ad_sidebar_2"
                                  rows="6" cols="60" class="large-text code"><?php
                            echo esc_textarea( get_option( 'holyprofweb_ad_sidebar_2', '' ) );
                        ?></textarea>
                        <p class="description"><?php esc_html_e( 'Displayed in the post sidebar, second slot.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="holyprofweb_ad_incontent">
                            <?php esc_html_e( 'In-Content Ad 1 (after 2nd paragraph)', 'holyprofweb' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="holyprofweb_ad_incontent" name="holyprofweb_ad_incontent"
                                  rows="6" cols="60" class="large-text code"><?php
                            echo esc_textarea( get_option( 'holyprofweb_ad_incontent', '' ) );
                        ?></textarea>
                        <p class="description"><?php esc_html_e( 'Injected after the 2nd paragraph inside post content.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="holyprofweb_ad_incontent_2">
                            <?php esc_html_e( 'In-Content Ad 2 (after 4th paragraph)', 'holyprofweb' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="holyprofweb_ad_incontent_2" name="holyprofweb_ad_incontent_2"
                                  rows="6" cols="60" class="large-text code"><?php
                            echo esc_textarea( get_option( 'holyprofweb_ad_incontent_2', '' ) );
                        ?></textarea>
                        <p class="description"><?php esc_html_e( 'Injected after the 4th paragraph inside post content.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="holyprofweb_ad_footer">
                            <?php esc_html_e( 'Footer Banner (728×90)', 'holyprofweb' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="holyprofweb_ad_footer" name="holyprofweb_ad_footer"
                                  rows="6" cols="60" class="large-text code"><?php
                            echo esc_textarea( get_option( 'holyprofweb_ad_footer', '' ) );
                        ?></textarea>
                        <p class="description"><?php esc_html_e( 'Displayed above the footer across all pages.', 'holyprofweb' ); ?></p>
                    </td>
                </tr>

            </table>

            <?php submit_button( __( 'Save Ad Codes', 'holyprofweb' ) ); ?>
        </form>
    </div>
    <?php
}

/**
 * Retrieve ad code for a slot.
 * Returns empty string if not set.
 */
function holyprofweb_get_ad_code( $slot ) {
    return (string) get_option( 'holyprofweb_ad_' . sanitize_key( $slot ), '' );
}

function holyprofweb_get_ad_format_code( $format ) {
    $format = sanitize_key( $format );
    $code   = (string) get_option( 'holyprofweb_ad_format_' . $format, '' );

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
    return sanitize_key( (string) get_option( 'holyprofweb_ad_density_' . sanitize_key( $format ), 'basic' ) );
}

function holyprofweb_ad_density_allows( $format, $placement ) {
    $density = holyprofweb_get_ad_density( $format );
    $map = array(
        'leaderboard' => array(
            'basic'  => array( 'header' ),
            'normal' => array( 'header', 'footer' ),
            'rigid'  => array( 'header', 'front_inline', 'archive_inline', 'footer' ),
        ),
        'rectangle' => array(
            'basic'  => array( 'sidebar', 'incontent_1' ),
            'normal' => array( 'sidebar', 'sidebar_2', 'incontent_1', 'front_inline' ),
            'rigid'  => array( 'sidebar', 'sidebar_2', 'incontent_1', 'incontent_2', 'front_inline', 'archive_inline' ),
        ),
        'mobile' => array(
            'basic'  => array( 'mobile_sticky' ),
            'normal' => array( 'mobile_sticky', 'front_mobile' ),
            'rigid'  => array( 'mobile_sticky', 'front_mobile', 'archive_mobile' ),
        ),
        'social' => array(
            'basic'  => array( 'social_bar' ),
            'normal' => array( 'social_bar' ),
            'rigid'  => array( 'social_bar' ),
        ),
        'footer' => array(
            'basic'  => array( 'footer' ),
            'normal' => array( 'footer' ),
            'rigid'  => array( 'footer' ),
        ),
    );

    $allowed = $map[ $format ][ $density ] ?? array();
    return in_array( $placement, $allowed, true );
}

function holyprofweb_render_ad_format( $format, $placement, $extra_class = '' ) {
    if ( ! holyprofweb_ad_density_allows( $format, $placement ) ) {
        return;
    }

    $code = holyprofweb_get_ad_format_code( $format );
    if ( ! trim( $code ) ) {
        return;
    }

    $class = 'ad-container ad-format-' . esc_attr( $format ) . ' ad-placement-' . esc_attr( $placement );
    if ( $extra_class ) {
        $class .= ' ' . esc_attr( $extra_class );
    }

    echo '<div class="' . $class . '">';
    echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

    $code_1 = holyprofweb_ad_density_allows( 'rectangle', 'incontent_1' ) ? holyprofweb_get_ad_format_code( 'rectangle' ) : holyprofweb_get_ad_code( 'incontent' );
    $code_2 = holyprofweb_ad_density_allows( 'rectangle', 'incontent_2' ) ? holyprofweb_get_ad_format_code( 'rectangle' ) : holyprofweb_get_ad_code( 'incontent_2' );
    $code_3 = holyprofweb_ad_density_allows( 'rectangle', 'incontent_2' ) && 'rigid' === holyprofweb_get_ad_density( 'rectangle' ) ? holyprofweb_get_ad_format_code( 'rectangle' ) : '';

    $has_ad1 = ! empty( trim( $code_1 ) );
    $has_ad2 = ! empty( trim( $code_2 ) );
    $has_ad3 = ! empty( trim( $code_3 ) );

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

/**
 * Track search queries in wp_options.
 * Fires after main query is run but before template loads.
 */
function holyprofweb_track_search() {
    if ( ! is_search() || is_admin() ) {
        return;
    }

    $term = get_search_query();
    if ( empty( trim( $term ) ) ) {
        return;
    }

    $log = get_option( 'holyprofweb_search_log', array() );
    $key = md5( strtolower( trim( $term ) ) );

    if ( isset( $log[ $key ] ) ) {
        $log[ $key ]['count']++;
        $log[ $key ]['ts'] = time();
    } else {
        $log[ $key ] = array(
            'term'  => sanitize_text_field( $term ),
            'count' => 1,
            'ts'    => time(),
        );
    }

    // Keep the log to 200 entries, sorted by frequency
    uasort( $log, function( $a, $b ) { return $b['count'] - $a['count']; } );
    $log = array_slice( $log, 0, 200, true );

    update_option( 'holyprofweb_search_log', $log, false );
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
    $log = get_option( 'holyprofweb_search_log', array() );
    uasort( $log, function( $a, $b ) { return $b['count'] - $a['count']; } );
    return array_slice( $log, 0, $count, true );
}

function holyprofweb_detect_visitor_locale() {
    $country_map = array(
        'NG' => 'Nigeria',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'FR' => 'France',
        'DE' => 'Germany',
        'CA' => 'Canada',
        'ZA' => 'South Africa',
        'KE' => 'Kenya',
        'GH' => 'Ghana',
    );

    $geo_headers = array(
        'HTTP_CF_IPCOUNTRY',
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
        'HTTP_X_COUNTRY_CODE',
        'HTTP_X_GEO_COUNTRY',
        'HTTP_X_APPENGINE_COUNTRY',
    );

    $region = '';
    $mode = get_option( 'hpw_country_mode', 'headers' );
    if ( 'headers' === $mode ) {
        foreach ( $geo_headers as $header_key ) {
            if ( empty( $_SERVER[ $header_key ] ) ) {
                continue;
            }

            $candidate = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header_key ] ) ) );
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

    $country = $region && isset( $country_map[ $region ] ) ? $country_map[ $region ] : 'Unknown';

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
        'hook'       => '',
        'topics'     => '',
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
    return $schedules;
} );

function holyprofweb_schedule_content_audit() {
    if ( ! wp_next_scheduled( 'holyprofweb_daily_content_audit' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'holyprofweb_daily_content_audit' );
    }

    if ( ! wp_next_scheduled( 'holyprofweb_draft_publish_audit' ) ) {
        wp_schedule_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'every_five_minutes', 'holyprofweb_draft_publish_audit' );
    }
}

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

    $title = strtolower( holyprofweb_get_decoded_post_title( $post_id ) );
    $content = strtolower( wp_strip_all_tags( (string) $post->post_content ) );
    $haystack = $title . ' ' . $content;

    $primary  = 'reviews';
    $children = array();

    if ( preg_match( '/salary|pay|compensation|earn|earnings|benchmark|range/', $haystack ) ) {
        $primary = 'salaries';
    } elseif ( preg_match( '/biography|net worth|founder|ceo|president|minister|senator|governor|profile/', $haystack ) ) {
        $primary = 'biography';
    } elseif ( preg_match( '/report|complaint|withdrawal|scam|legit|warning sign|red flag|fraud/', $haystack ) ) {
        $primary = 'reports';
        $children[] = 'scam-legit';
    } elseif ( preg_match( '/company|bank|startup|fintech|telecom|profile|overview/', $haystack ) ) {
        $primary = 'companies';
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
    $minimum    = max( 1000, absint( get_option( 'hpw_ai_minimum_words', 1000 ) ) );
    $needs      = array();

    if ( mb_strlen( $title ) < 18 ) {
        $needs[] = 'title';
    }
    if ( $word_count < $minimum ) {
        $needs[] = 'content';
    }
    if ( empty( get_the_category( $post->ID ) ) ) {
        $needs[] = 'category';
    }
    if ( ! has_post_thumbnail( $post->ID ) && ! get_post_meta( $post->ID, 'external_image', true ) ) {
        $needs[] = 'image';
    }
    if ( holyprofweb_content_looks_repetitive( $post->post_content ) ) {
        $needs[] = 'repetition';
    }

    return array(
        'ready'      => empty( $needs ),
        'needs'      => $needs,
        'word_count' => $word_count,
    );
}

function holyprofweb_attempt_draft_repairs( $post_id, $post = null ) {
    $post = $post ?: get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    holyprofweb_assign_smart_categories( $post_id, $post );
    holyprofweb_maybe_expand_topic_title( $post_id );

    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    holyprofweb_expand_thin_post_content( $post_id, true );
    $post = get_post( $post_id );

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

function holyprofweb_process_draft_queue() {
    if ( ! get_option( 'hpw_enable_draft_autopublish', 1 ) ) {
        return;
    }

    $limit = max( 1, absint( get_option( 'hpw_draft_publish_limit', 5 ) ) );
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
        holyprofweb_attempt_draft_repairs( $post->ID, $post );
        $post = get_post( $post->ID );
        $result = holyprofweb_evaluate_draft_readiness( $post );

        if ( ! $result['ready'] ) {
            holyprofweb_attempt_draft_repairs( $post->ID, $post );
            $post = get_post( $post->ID );
            $result = holyprofweb_evaluate_draft_readiness( $post );
        }

        $queue[ $post->ID ] = array(
            'title'      => get_the_title( $post->ID ),
            'word_count' => (int) $result['word_count'],
            'needs'      => $result['needs'],
            'country'    => get_post_meta( $post->ID, '_hpw_country_focus', true ),
        );

        if ( $result['ready'] ) {
            wp_update_post( array(
                'ID'          => $post->ID,
                'post_status' => 'publish',
            ) );
            unset( $queue[ $post->ID ] );
        } else {
            holyprofweb_auto_set_excerpt( $post->ID, $post );
        }
    }

    update_option( 'holyprofweb_draft_publish_queue', $queue, false );
}
add_action( 'holyprofweb_draft_publish_audit', 'holyprofweb_process_draft_queue' );

function holyprofweb_build_ai_prompt_template( $title = '', $post_id = 0 ) {
    $title           = trim( (string) $title );
    $site_name       = get_bloginfo( 'name' );
    $tagline         = get_option( 'hpw_site_tagline', 'Research-first reviews, salary guides, company profiles, and practical explainers.' );
    $minimum_words   = max( 1000, absint( get_option( 'hpw_ai_minimum_words', 1000 ) ) );
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
add_action( 'init', 'holyprofweb_create_categories', 5 );

function holyprofweb_enforce_permalink_structure() {
    global $wp_rewrite;

    $target = '/%category%/%postname%/';
    if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) ) {
        return;
    }

    if ( $wp_rewrite->permalink_structure !== $target ) {
        $wp_rewrite->set_permalink_structure( $target );
    }
}

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
add_action( 'init', 'holyprofweb_ensure_placeholder_posts', 25 );

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
add_action( 'init', 'holyprofweb_seed_category_posts', 26 );

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
add_action( 'init', 'holyprofweb_cleanup_uncategorized_category', 20 );

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
        'companies',
        'fintech',
        'banks',
        'startups',
        'scam-legit',
        'app-reviews',
        'website-reviews',
        'loan-finance',
        'shopping',
        'scholarship',
        'tech',
        'reports',
        'blog-opinion',
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
    $posts_page_id = (int) get_option( 'page_for_posts' );
    if ( $posts_page_id ) {
        $url = get_permalink( $posts_page_id );
        if ( $url ) {
            return $url;
        }
    }

    return home_url( '/' );
}

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
    return html_entity_decode( get_the_excerpt( $post_id ), ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
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

    return holyprofweb_placeholder_url();
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

    $target_w = 220;
    $target_h = max( 42, (int) round( $target_w * ( $logo_h / $logo_w ) ) );
    $dest_x   = $canvas_width - $target_w - 58;
    $dest_y   = $canvas_height - $target_h - 38;

    imagealphablending( $canvas, true );
    imagesavealpha( $canvas, true );
    imagecopyresampled( $canvas, $logo, $dest_x, $dest_y, 0, 0, $target_w, $target_h, $logo_w, $logo_h );
    imagedestroy( $logo );
}

function holyprofweb_enable_post_image_controls() {
    add_post_type_support( 'post', 'thumbnail' );
    add_post_type_support( 'post', 'custom-fields' );
}
add_action( 'init', 'holyprofweb_enable_post_image_controls' );

function holyprofweb_generated_image_version() {
    return '6';
}

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

function holyprofweb_get_post_image_class( $post_id, $base = '' ) {
    $classes = preg_split( '/\s+/', trim( (string) $base ) );
    $classes = array_filter( $classes );

    if ( get_post_meta( $post_id, '_holyprofweb_gen_image_url', true ) || holyprofweb_is_placeholder_post( $post_id ) ) {
        $classes[] = 'post-image--generated';
    } else {
        $classes[] = 'post-image--photo';
    }

    $classes = array_unique( array_map( 'sanitize_html_class', $classes ) );
    return implode( ' ', $classes );
}

function holyprofweb_get_post_card_image_url( $post_id ) {
    $post          = get_post( $post_id );
    $generated_url = get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );
    $generated_ver = get_post_meta( $post_id, '_holyprofweb_gen_image_version', true );

    if ( $post && function_exists( 'imagecreatetruecolor' ) ) {
        if ( ! $generated_url || holyprofweb_generated_image_version() !== $generated_ver ) {
            holyprofweb_generate_post_image_modern( $post_id, $post );
            $generated_url = get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );
        }
    }

    if ( $generated_url ) {
        return esc_url_raw( $generated_url );
    }

    return holyprofweb_get_post_image_url( $post_id, 'holyprofweb-card' );
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
    $generated_url = get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );
    $generated_ver = get_post_meta( $post_id, '_holyprofweb_gen_image_version', true );
    $external      = trim( (string) get_post_meta( $post_id, 'external_image', true ) );
    $post          = get_post( $post_id );

    if ( $generated_url && holyprofweb_generated_image_version() !== $generated_ver && $post && function_exists( 'imagecreatetruecolor' ) ) {
        holyprofweb_generate_post_image_modern( $post_id, $post );
    }

    $image_size = $size;
    if ( $generated_url || holyprofweb_is_placeholder_post( $post_id ) ) {
        $image_size = 'full';
    }

    $url = get_the_post_thumbnail_url( $post_id, $image_size );
    if ( $url ) {
        return $url;
    }

    if ( $external ) {
        return esc_url_raw( $external );
    }

    $remote_cached = get_post_meta( $post_id, '_holyprofweb_remote_image_url', true );
    if ( $remote_cached ) {
        return esc_url_raw( $remote_cached );
    }

    $cached = get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );
    if ( $cached ) {
        return esc_url_raw( $cached );
    }

    if ( $post ) {
        $remote = holyprofweb_maybe_get_remote_post_image( $post_id, $post );
        if ( $remote ) {
            return esc_url_raw( $remote );
        }
    }

    if ( ! $external && ! $remote_cached && ! $cached && $post && function_exists( 'imagecreatetruecolor' ) ) {
        holyprofweb_generate_post_image_modern( $post_id, $post );
        $url = get_the_post_thumbnail_url( $post_id, $size );
        if ( $url ) {
            return $url;
        }

        $cached = get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );
        if ( $cached ) {
            return esc_url_raw( $cached );
        }
    }

    return holyprofweb_placeholder_url();
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

    // Trending suggestions
    $raw_suggestions = holyprofweb_get_trending_searches( 5 );
    $suggestions     = array();
    foreach ( $raw_suggestions as $entry ) {
        $suggestions[] = $entry['term'];
    }

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
// REACTIONS SYSTEM (helpful / scam / good)
// =========================================

function holyprofweb_reaction_ajax() {
    // Verify nonce
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'holyprofweb_reaction' ) ) {
        wp_send_json_error( 'Invalid nonce', 403 );
    }

    $post_id  = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $reaction = isset( $_POST['reaction'] ) ? sanitize_key( $_POST['reaction'] ) : '';
    $allowed  = array( 'helpful', 'scam', 'good' );

    if ( ! $post_id || ! in_array( $reaction, $allowed, true ) ) {
        wp_send_json_error( 'Invalid request' );
    }

    $meta_key = '_reaction_' . $reaction;
    $count    = (int) get_post_meta( $post_id, $meta_key, true );
    $count++;
    update_post_meta( $post_id, $meta_key, $count );

    wp_send_json_success( array( 'reaction' => $reaction, 'count' => $count ) );
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
        'helpful' => (int) get_post_meta( $post_id, '_reaction_helpful', true ),
        'scam'    => (int) get_post_meta( $post_id, '_reaction_scam',    true ),
        'good'    => (int) get_post_meta( $post_id, '_reaction_good',    true ),
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
        $value = get_post_meta( $post_id, $key, true );
        if ( $value && filter_var( $value, FILTER_VALIDATE_URL ) ) {
            return esc_url_raw( $value );
        }
    }

    $post = $post ?: get_post( $post_id );
    if ( ! $post ) {
        return '';
    }

    if ( preg_match( '#https?://[^\s"\']+#i', $post->post_content, $matches ) ) {
        return esc_url_raw( $matches[0] );
    }

    return '';
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
    $content   = strtolower( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
    $haystack  = $title . ' ' . $excerpt . ' ' . $content;

    if ( array_intersect( array( 'reports', 'scam-reports', 'user-complaints', 'scam-legit' ), $cat_slugs ) ) {
        if ( false !== strpos( $haystack, 'scam' ) || false !== strpos( $haystack, 'fraud' ) || false !== strpos( $haystack, 'fake' ) ) {
            return array( 'label' => 'Scam Alert', 'class' => 'verdict-badge--scam' );
        }
        return array( 'label' => 'Caution', 'class' => 'verdict-badge--caution' );
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
    if ( ! $source_url || ! get_option( 'hpw_enable_remote_image_fetch', 1 ) ) {
        return '';
    }

    return 'https://s.wordpress.com/mshots/v1/' . rawurlencode( $source_url ) . '?w=1200&h=630';
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
    $cached = get_post_meta( $post_id, '_holyprofweb_remote_image_url', true );
    if ( $cached ) {
        return esc_url_raw( $cached );
    }

    $post       = $post ?: get_post( $post_id );
    $source_url = holyprofweb_get_post_source_url( $post_id, $post );
    $domain     = holyprofweb_extract_domain( $source_url );
    $image_url  = apply_filters( 'holyprofweb_automation_image_url', '', $post_id, $post, $domain, $source_url );

    if ( ! $image_url && $source_url ) {
        $image_url = holyprofweb_pick_working_remote_image_url( holyprofweb_get_site_visual_candidates( $source_url ) );
    }
    if ( ! $image_url && $source_url ) {
        $image_url = holyprofweb_fetch_og_image_url( $source_url );
    }
    if ( ! $image_url && $source_url ) {
        $image_url = holyprofweb_get_landing_page_capture_url( $source_url );
    }
    if ( ! $image_url && $domain ) {
        $image_url = holyprofweb_get_clearbit_logo_url( $domain );
    }

    if ( $image_url ) {
        update_post_meta( $post_id, '_holyprofweb_remote_image_url', esc_url_raw( $image_url ) );
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
    return (int) $att_id;
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
    if ( ! $filepath || ! file_exists( $filepath ) || ! function_exists( 'imagecreatefromstring' ) ) {
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
        $written = imagepng( $image, $filepath, 6 );
    } elseif ( 'image/webp' === $mime && function_exists( 'imagewebp' ) ) {
        $written = imagewebp( $image, $filepath, 90 );
    } elseif ( function_exists( 'imagejpeg' ) ) {
        $written = imagejpeg( $image, $filepath, 90 );
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
    return defined( 'REST_REQUEST' ) && REST_REQUEST;
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
    if ( has_post_thumbnail( $post_id ) ) return;
    if ( get_post_meta( $post_id, '_holyprofweb_no_autothumb', true ) ) return;
    if ( get_post_meta( $post_id, 'external_image', true ) ) return;

    $cached_remote = get_post_meta( $post_id, '_holyprofweb_remote_image_url', true );
    $cached_gen    = get_post_meta( $post_id, '_holyprofweb_gen_image_url', true );
    if ( $cached_remote || $cached_gen ) return;

    if ( holyprofweb_should_defer_featured_image_generation() ) {
        holyprofweb_schedule_featured_image_generation( $post_id );
        return;
    }

    $image_url = holyprofweb_maybe_get_remote_post_image( $post_id, $post );
    if ( $image_url ) {
        holyprofweb_attach_remote_image_to_post( $image_url, $post_id, $post->post_title );
    }

    if ( ! has_post_thumbnail( $post_id ) ) {
        holyprofweb_generate_post_image_modern( $post_id, $post );
    }
}
add_action( 'save_post', 'holyprofweb_auto_featured_image', 20, 3 );

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
    $height = 630;
    $img    = imagecreatetruecolor( $width, $height );
    imagealphablending( $img, true );
    imagesavealpha( $img, false );

    $accent       = imagecolorallocate( $img, $palette['accent'][0], $palette['accent'][1], $palette['accent'][2] );
    $accent_soft  = imagecolorallocatealpha( $img, $palette['accent'][0], $palette['accent'][1], $palette['accent'][2], 88 );
    $white        = imagecolorallocate( $img, 245, 244, 240 );
    $muted        = imagecolorallocate( $img, 191, 187, 178 );
    $dark         = imagecolorallocate( $img, 13, 14, 18 );
    $panel        = imagecolorallocatealpha( $img, 255, 255, 255, 112 );
    $panel_border = imagecolorallocatealpha( $img, 255, 255, 255, 92 );

    for ( $y = 0; $y < $height; $y++ ) {
        $mix = $y / max( 1, $height - 1 );
        $r   = (int) round( $palette['bg'][0] + ( $palette['mid'][0] - $palette['bg'][0] ) * $mix );
        $g   = (int) round( $palette['bg'][1] + ( $palette['mid'][1] - $palette['bg'][1] ) * $mix );
        $b   = (int) round( $palette['bg'][2] + ( $palette['mid'][2] - $palette['bg'][2] ) * $mix );
        imageline( $img, 0, $y, $width, $y, imagecolorallocate( $img, $r, $g, $b ) );
    }

    imagefilledellipse( $img, 1000, 100, 360, 360, $accent_soft );
    imagefilledellipse( $img, 120, 560, 220, 220, imagecolorallocatealpha( $img, 255, 255, 255, 118 ) );
    imagefilledrectangle( $img, 0, 0, 10, $height, $accent );
    imagefilledrectangle( $img, 26, 26, $width - 26, $height - 26, $panel );
    imagerectangle( $img, 26, 26, $width - 26, $height - 26, $panel_border );
    imagefilledrectangle( $img, 26, $height - 82, $width - 26, $height - 26, imagecolorallocatealpha( $img, 10, 10, 14, 18 ) );

    $badge_w = (int) ( strlen( $cat_name ) * 14 + 50 );
    holyprofweb_image_filled_rounded_rectangle( $img, 68, 68, 68 + $badge_w, 114, $accent, 18 );

    $font_file = holyprofweb_get_image_font_file();
    $title     = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( holyprofweb_get_decoded_post_title( $post_id ) ) ) );
    $wrapped   = holyprofweb_wrap_image_text( $title, 2, 22 );
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
    if ( $excerpt && mb_strlen( $excerpt ) < 44 ) {
        $deck_source = $excerpt . ' | ' . $search_hook;
    }
    $deck = holyprofweb_wrap_image_text( $deck_source, 2, 52 );

    if ( $font_file && function_exists( 'imagettftext' ) ) {
        imagettftext( $img, 16, 0, 88, 98, $dark, $font_file, strtoupper( $cat_name ) );

        $title_size  = mb_strlen( $title ) > 40 ? 50 : 58;
        $line_gap    = $title_size + 8;
        $lines_count = count( $wrapped );
        $block_h     = max( 1, $lines_count ) * $line_gap;
        $start_y     = 198;

        foreach ( $wrapped as $index => $line ) {
            imagettftext( $img, $title_size, 0, 68, $start_y + ( $index * $line_gap ), $white, $font_file, $line );
        }

        if ( ! empty( $deck ) ) {
            $deck_y = $start_y + $block_h + 14;
            foreach ( $deck as $index => $line ) {
                imagettftext( $img, 21, 0, 70, $deck_y + ( $index * 30 ), $muted, $font_file, $line );
            }
        }

        imagettftext( $img, 15, 0, 70, $height - 44, $muted, $font_file, 'holyprofweb.com' );
    } else {
        $badge_label = strtoupper( $cat_name );
        imagestring( $img, 5, 86, 82, $badge_label, $dark );

        $y = 190;
        foreach ( $wrapped as $line ) {
            imagestring( $img, 5, 68, $y, $line, $white );
            $y += 36;
        }

        if ( ! empty( $deck ) ) {
            foreach ( $deck as $line ) {
                imagestring( $img, 4, 70, $y + 8, $line, $muted );
                $y += 24;
            }
        }

        imagestring( $img, 3, 70, $height - 54, 'holyprofweb.com', $muted );
    }

    holyprofweb_overlay_brand_logo( $img, $width, $height );

    imagejpeg( $img, $filepath, 92 );
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
    $parent_cats = array(
        'reviews'   => array( 'label' => 'Reviews',   'icon' => '&#9733;' ),
        'companies' => array( 'label' => 'Companies', 'icon' => '&#127970;' ),
        'biography' => array( 'label' => 'Biography', 'icon' => '&#128100;' ),
        'reports'   => array( 'label' => 'Blog',      'icon' => '&#128203;' ),
    );

    echo '<aside class="left-sidebar" id="left-sidebar" aria-label="' . esc_attr__( 'Category Navigation', 'holyprofweb' ) . '">';
    echo '<nav class="left-nav">';
    echo '<p class="left-nav-heading">' . esc_html__( 'Browse Topics', 'holyprofweb' ) . '</p>';

    foreach ( $parent_cats as $slug => $data ) {
        $parent = get_term_by( 'slug', $slug, 'category' );
        if ( ! $parent ) continue;

        // Skip parent if it has no posts AND no children with posts
        $child_terms = holyprofweb_get_visible_categories( array(
            'parent' => $parent->term_id,
        ) );
        $has_children = ! is_wp_error( $child_terms ) && ! empty( $child_terms );
        $has_own_posts = ( (int) $parent->count > 0 );

        // Show parent if it has its own posts OR has children with posts
        if ( ! $has_own_posts && ! $has_children ) continue;

        $parent_url = get_category_link( $parent->term_id );
        $child_ids  = $has_children ? wp_list_pluck( $child_terms, 'term_id' ) : array();

        $is_active = true;

        echo '<div class="left-nav-group' . ( $is_active ? ' is-open' : '' ) . '">';
        echo '<button class="left-nav-parent left-nav-parent--static" aria-expanded="true" type="button">';
        echo '<span class="left-nav-icon" aria-hidden="true">' . $data['icon'] . '</span>';
        echo '<span class="left-nav-parent-main">';
        echo '<a href="' . esc_url( $parent_url ) . '" class="left-nav-parent-link">' . esc_html( $data['label'] ) . '</a>';
        echo '<span class="left-nav-count">(' . esc_html( holyprofweb_format_display_count( (int) $parent->count ) ) . ')</span>';
        echo '</span>';
        if ( $has_children ) {
            echo '<svg class="left-nav-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';
        }
        echo '</button>';

        if ( $has_children ) {
            echo '<ul class="left-nav-children">';
            foreach ( $child_terms as $child ) {
                $active_child = is_category() && (int) get_query_var( 'cat' ) === (int) $child->term_id;
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
        <input type="url" id="hpw_source_url" name="hpw_source_url" value="<?php echo esc_attr( $source_url ); ?>" class="widefat" />
    </p>
    <p>
        <label for="hpw_external_image"><strong><?php esc_html_e( 'External Image URL', 'holyprofweb' ); ?></strong></label><br>
        <input type="url" id="hpw_external_image" name="hpw_external_image" value="<?php echo esc_attr( $external_image ); ?>" class="widefat" />
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
        update_post_meta( $post_id, '_hpw_source_url', isset( $_POST['hpw_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['hpw_source_url'] ) ) : '' );
        update_post_meta( $post_id, 'external_image', isset( $_POST['hpw_external_image'] ) ? esc_url_raw( wp_unslash( $_POST['hpw_external_image'] ) ) : '' );
        update_post_meta( $post_id, '_hpw_country_focus', isset( $_POST['hpw_country_focus'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_country_focus'] ) ) : '' );
        update_post_meta( $post_id, '_hpw_verdict_override', isset( $_POST['hpw_verdict_override'] ) ? sanitize_key( wp_unslash( $_POST['hpw_verdict_override'] ) ) : '' );
        $rating_override = isset( $_POST['hpw_rating_override'] ) ? trim( (string) wp_unslash( $_POST['hpw_rating_override'] ) ) : '';
        update_post_meta( $post_id, '_hpw_rating_override', ( '' !== $rating_override && is_numeric( $rating_override ) ) ? round( max( 0, min( 5, (float) $rating_override ) ), 1 ) : '' );
    }
}
add_action( 'save_post', 'holyprofweb_save_salary_meta' );


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

    add_submenu_page( 'hpw-settings', __( 'General',    'holyprofweb' ), __( 'General',    'holyprofweb' ), 'manage_options', 'hpw-settings',           'holyprofweb_settings_page' );
    add_submenu_page( 'hpw-settings', __( 'Reviews',    'holyprofweb' ), __( 'Reviews',    'holyprofweb' ), 'manage_options', 'hpw-settings-reviews',   'holyprofweb_settings_reviews_page' );
    add_submenu_page( 'hpw-settings', __( 'Ads',        'holyprofweb' ), __( 'Ads',        'holyprofweb' ), 'manage_options', 'hpw-settings-ads',       'holyprofweb_ads_admin_page' );
    add_submenu_page( 'hpw-settings', __( 'Emails',     'holyprofweb' ), __( 'Emails',     'holyprofweb' ), 'manage_options', 'hpw-settings-emails',    'holyprofweb_settings_emails_page' );
    add_submenu_page( 'hpw-settings', __( 'Languages',  'holyprofweb' ), __( 'Languages',  'holyprofweb' ), 'manage_options', 'hpw-settings-languages', 'holyprofweb_settings_languages_page' );
    add_submenu_page( 'hpw-settings', __( 'Automation', 'holyprofweb' ), __( 'Automation', 'holyprofweb' ), 'manage_options', 'hpw-settings-automation', 'holyprofweb_settings_automation_page' );
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
    $source_url       = isset( $_POST['hpw_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['hpw_source_url'] ) ) : '';
    $country_focus    = isset( $_POST['hpw_country_focus'] ) ? sanitize_text_field( wp_unslash( $_POST['hpw_country_focus'] ) ) : '';
    if ( ! array_key_exists( $verdict_override, holyprofweb_get_verdict_options() ) ) {
        $verdict_override = '';
    }

    update_post_meta( $post_id, '_hpw_rating_override', ( '' !== $rating_override && is_numeric( $rating_override ) ) ? round( max( 0, min( 5, (float) $rating_override ) ), 1 ) : '' );
    update_post_meta( $post_id, '_hpw_verdict_override', $verdict_override );
    update_post_meta( $post_id, '_hpw_source_url', $source_url );
    update_post_meta( $post_id, '_hpw_country_focus', $country_focus );

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
    if ( empty( $counts['total'] ) ) {
        return;
    }

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
    if ( empty( $counts['total'] ) ) {
        return;
    }

    echo '<div class="notice notice-info"><p><strong>HPW Alerts:</strong> ';
    echo esc_html( sprintf(
        'Pending reviews: %d | Pending salary submissions: %d | New subscribers this week: %d',
        (int) $counts['pending_reviews'],
        (int) $counts['pending_salary'],
        (int) $counts['recent_subscribers']
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
    register_setting( 'hpw_general',   'hpw_show_trending',        array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_general',   'hpw_show_email_capture',   array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_general',   'hpw_enable_copy_protection', array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );

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

    // Automation
    register_setting( 'hpw_automation', 'hpw_enable_remote_image_fetch', array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
    register_setting( 'hpw_automation', 'hpw_enable_generated_images',   array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
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
    register_setting( 'hpw_general',    'hpw_redirect_rules',            array( 'sanitize_callback' => 'holyprofweb_sanitize_redirect_rules' ) );
}
add_action( 'admin_init', 'holyprofweb_register_settings' );

/* ── General ──────────────────────────────────────────────────────────────── */

function holyprofweb_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Settings saved.', 'holyprofweb' ), 'updated' );
    }
    settings_errors( 'hpw_messages' );
    $counts = holyprofweb_get_admin_alert_counts();
    ?>
    <div class="wrap">
        <h1>&#9881; <?php esc_html_e( 'HPW Settings — General', 'holyprofweb' ); ?></h1>
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
                <h2 style="margin:0 0 10px;font-size:16px;"><?php esc_html_e( 'Top Searches', 'holyprofweb' ); ?></h2>
                <?php foreach ( holyprofweb_get_trending_searches( 5 ) as $item ) : ?>
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;"><span><?php echo esc_html( $item['term'] ); ?></span><strong><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?></strong></div>
                <?php endforeach; ?>
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
        <form method="post" action="options.php">
            <?php settings_fields( 'hpw_general' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Site tagline (header sub-text)', 'holyprofweb' ); ?></th>
                    <td><input type="text" name="hpw_site_tagline" value="<?php echo esc_attr( get_option( 'hpw_site_tagline', '' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Posts per page (archive/search)', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_posts_per_page" value="<?php echo esc_attr( get_option( 'hpw_posts_per_page', 12 ) ); ?>" min="1" max="50" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Show trending searches section', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_show_trending" value="1" <?php checked( 1, get_option( 'hpw_show_trending', 1 ) ); ?> /> <?php esc_html_e( 'Enabled', 'holyprofweb' ); ?></label></td>
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
    </div>
    <?php
}

/* ── Reviews ──────────────────────────────────────────────────────────────── */

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
        <h1>&#9733; <?php esc_html_e( 'HPW Settings — Reviews', 'holyprofweb' ); ?></h1>
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
        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:grid;grid-template-columns:minmax(280px,1.4fr) minmax(180px,0.6fr) auto;gap:12px;align-items:end;margin:18px 0 20px;">
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
                $country_focus    = (string) get_post_meta( $post_id, '_hpw_country_focus', true );
                $status_object    = get_post_status_object( get_post_status( $post_id ) );
                $verdict_preview  = holyprofweb_get_review_verdict( $post_id );
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
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=hpw-settings-reviews' ) ); ?>" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 12px;padding:14px;border:1px solid #ece4d4;border-radius:18px;background:linear-gradient(180deg,#fffdfa 0%,#ffffff 100%);">
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
                            <input type="url" name="hpw_source_url" value="<?php echo esc_attr( $source_url ); ?>" class="regular-text" style="width:100%;">
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
    ?>
    <div class="wrap">
        <h1>&#127758; <?php esc_html_e( 'HPW Settings — Languages', 'holyprofweb' ); ?></h1>
        <?php holyprofweb_settings_nav( 'languages' ); ?>
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
        'general'    => array( 'label' => 'General',    'slug' => 'hpw-settings',            'icon' => '&#9881;' ),
        'reviews'    => array( 'label' => 'Reviews',    'slug' => 'hpw-settings-reviews',    'icon' => '&#9733;' ),
        'ads'        => array( 'label' => 'Ads',        'slug' => 'hpw-settings-ads',        'icon' => '&#128250;' ),
        'languages'  => array( 'label' => 'Languages',  'slug' => 'hpw-settings-languages',  'icon' => '&#127758;' ),
        'emails'     => array( 'label' => 'Emails',     'slug' => 'hpw-settings-emails',     'icon' => '&#128231;' ),
        'automation' => array( 'label' => 'Automation', 'slug' => 'hpw-settings-automation', 'icon' => '&#9889;' ),
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
}

function holyprofweb_settings_automation_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'hpw_messages', 'hpw_saved', __( 'Settings saved.', 'holyprofweb' ), 'updated' );
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
                    <th><?php esc_html_e( 'Auto-publish ready drafts', 'holyprofweb' ); ?></th>
                    <td><label><input type="checkbox" name="hpw_enable_draft_autopublish" value="1" <?php checked( 1, get_option( 'hpw_enable_draft_autopublish', 1 ) ); ?> /> <?php esc_html_e( 'Let the 5-minute cron review imported drafts and publish only the ones that pass quality checks.', 'holyprofweb' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Drafts checked per run', 'holyprofweb' ); ?></th>
                    <td><input type="number" name="hpw_draft_publish_limit" value="<?php echo esc_attr( get_option( 'hpw_draft_publish_limit', 5 ) ); ?>" min="1" max="50" class="small-text" /></td>
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
                    <td><input type="number" name="hpw_ai_minimum_words" value="<?php echo esc_attr( get_option( 'hpw_ai_minimum_words', 1000 ) ); ?>" min="1000" max="5000" class="small-text" /></td>
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
        <p><?php echo esc_html( sprintf( __( 'Content refresh queue items: %d', 'holyprofweb' ), count( $refresh_queue ) ) ); ?></p>
        <p><?php echo esc_html( $last_audit_run ? sprintf( __( 'Last audit run: %s', 'holyprofweb' ), wp_date( 'Y-m-d H:i', $last_audit_run ) ) : __( 'Last audit run: not yet', 'holyprofweb' ) ); ?></p>

        <hr>
        <h2><?php esc_html_e( 'Stale Content Queue', 'holyprofweb' ); ?></h2>
        <?php if ( empty( $refresh_queue ) ) : ?>
            <p><?php esc_html_e( 'No posts currently flagged for refresh.', 'holyprofweb' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e( 'Post', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Word Count', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Needs', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Country Focus', 'holyprofweb' ); ?></th>
                    <th><?php esc_html_e( 'Last Modified', 'holyprofweb' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $refresh_queue as $post_id => $item ) : ?>
                    <tr>
                        <td><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></td>
                        <td><?php echo esc_html( number_format_i18n( (int) $item['word_count'] ) ); ?></td>
                        <td><?php echo esc_html( implode( ', ', $item['needs'] ) ); ?></td>
                        <td><?php echo esc_html( $item['country_focus'] ? $item['country_focus'] : 'General' ); ?></td>
                        <td><?php echo esc_html( mysql2date( 'Y-m-d', $item['modified_gmt'], false ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

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
    if ( is_singular( 'post' ) && holyprofweb_is_placeholder_post( get_queried_object_id() ) ) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
} );

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

    // 1. Auto-excerpt (powers meta description)
    holyprofweb_auto_set_excerpt( $post->ID, $post );

    // 2. Auto-tags from title + category
    holyprofweb_auto_set_tags( $post->ID, $post );

    // 3. Cache schema type (Article / Person / Organization / Review / etc.)
    holyprofweb_cache_schema_type( $post->ID );

    // 4. Store reading time
    holyprofweb_cache_reading_time( $post->ID, $post );

    // 5. Expand thin content so published pages do not look empty.
    holyprofweb_expand_thin_post_content( $post->ID );

    // 6. Featured image — run async-safe: defer to shutdown so all meta is saved first
    if ( ! has_post_thumbnail( $post->ID ) ) {
        add_action( 'shutdown', function() use ( $post ) {
            holyprofweb_auto_featured_image( $post->ID, $post, false );
        } );
    }

    holyprofweb_run_content_audit();
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

function holyprofweb_on_post_publish_rest_safe( $new_status, $old_status, $post ) {
    if ( ! ( $post instanceof WP_Post ) ) return;
    if ( $new_status !== 'publish' ) return;
    if ( $post->post_type !== 'post' ) return;
    if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) return;

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        if ( ! wp_next_scheduled( 'holyprofweb_process_post_publish_async', array( $post->ID ) ) ) {
            wp_schedule_single_event( time() + 15, 'holyprofweb_process_post_publish_async', array( $post->ID ) );
        }
        return;
    }

    holyprofweb_on_post_publish( $new_status, $old_status, $post );
}
remove_action( 'transition_post_status', 'holyprofweb_on_post_publish', 10 );
add_action( 'transition_post_status', 'holyprofweb_on_post_publish_rest_safe', 10, 3 );

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

    $word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
    $is_placeholder = holyprofweb_is_placeholder_post( $post_id );

    if ( ! $force && $word_count >= max( 1000, absint( get_option( 'hpw_ai_minimum_words', 1000 ) ) ) && ! $is_placeholder ) {
        return;
    }

    $extra = holyprofweb_build_longform_sections( $post_id );
    if ( ! $extra ) {
        return;
    }

    $content = (string) $post->post_content;
    if ( false !== strpos( $content, '<!-- HPW-AUTO-CONTENT:START -->' ) ) {
        $content = preg_replace( '/<!-- HPW-AUTO-CONTENT:START -->.*?<!-- HPW-AUTO-CONTENT:END -->/si', '', $content );
    }

    wp_update_post( array(
        'ID'           => $post_id,
        'post_content' => trim( $content ) . "\n\n" . $extra,
    ) );
    update_post_meta( $post_id, '_hpw_content_expanded', 1 );
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

    $cats      = get_the_category( $post_id );
    $cat_slugs = wp_list_pluck( $cats, 'slug' );

    $is_bio     = array_intersect( array( 'biography', 'founders', 'influencers' ), $cat_slugs );
    $is_company = array_intersect( array( 'companies', 'fintech', 'banks', 'startups' ), $cat_slugs );

    if ( $is_bio ) {
        // Try Wikipedia first; source URL OG will be tried by the caller after this filter
        $image_url = holyprofweb_get_wikipedia_person_image( $post->post_title );
    } elseif ( $is_company && $source_url ) {
        $image_url = holyprofweb_pick_working_remote_image_url( holyprofweb_get_site_visual_candidates( $source_url ) );
        if ( ! $image_url ) {
            $image_url = holyprofweb_get_landing_page_capture_url( $source_url );
        }
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
