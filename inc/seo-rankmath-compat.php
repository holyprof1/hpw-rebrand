<?php
/**
 * Rank Math compatibility, favicon fallback, and SEO safety notes.
 *
 * Technical SEO checklist:
 * - Let Rank Math own titles, canonicals, robots, schema, and XML sitemaps when active.
 * - Keep country-personalization server-rendered and stable for bots to avoid cloaking signals.
 * - Do not output duplicate canonical tags or conflicting noindex directives.
 * - Keep homepage, singles, archives, and taxonomy pages indexable unless intentionally suppressed.
 * - Ensure Site Icon is configured in WordPress and provide a theme fallback only when missing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function holyprofweb_is_rank_math_active() {
    return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) || defined( 'RANK_MATH_FILE' );
}

function holyprofweb_disable_native_seo_head_when_plugin_active() {
    if ( holyprofweb_is_rank_math_active() && has_action( 'wp_head', 'holyprofweb_seo_head' ) ) {
        remove_action( 'wp_head', 'holyprofweb_seo_head', 2 );
    }

    if ( ! holyprofweb_is_rank_math_active() && has_action( 'wp_head', 'rel_canonical' ) ) {
        remove_action( 'wp_head', 'rel_canonical' );
    }
}
add_action( 'after_setup_theme', 'holyprofweb_disable_native_seo_head_when_plugin_active', 30 );

function holyprofweb_render_favicon_fallback() {
    if ( has_site_icon() ) {
        return;
    }

    $candidates = array(
        'assets/images/icon.svg'   => 'image/svg+xml',
        'assets/images/favicon.ico' => 'image/x-icon',
        'assets/images/favicon.png' => 'image/png',
        'assets/images/favicon.svg' => 'image/svg+xml',
        'assets/images/logo.png'    => 'image/png',
    );

    foreach ( $candidates as $relative => $mime ) {
        $path = get_template_directory() . '/' . $relative;
        if ( ! file_exists( $path ) ) {
            continue;
        }

        $url = trailingslashit( get_template_directory_uri() ) . ltrim( $relative, '/' );
        echo '<link rel="icon" href="' . esc_url( $url ) . '" type="' . esc_attr( $mime ) . '" />' . "\n";
        return;
    }
}
add_action( 'wp_head', 'holyprofweb_render_favicon_fallback', 5 );
