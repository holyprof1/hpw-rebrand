<?php
/**
 * Restore and rewrite removed posts (CLI only).
 *
 *   php tools/recover.php [--apply] [--only=ID,ID] [--dir=tools/recovery] [--prepend=file.php]
 *
 * Reads <dir>/<post_id>.json (see tools/build-recovery.py for the format) and, per post:
 *   - sets the new title, excerpt (dek), body, section category and publishes it (status trash -> publish),
 *   - keeps the original slug, or moves to a clean slug and 301-redirects the old one,
 *   - clears the 410 registration, the old review-farm meta and any noindex flag,
 *   - stores the search title/description, entity facts and hand-picked related stories,
 *   - attaches <dir>/images/<post_id>.webp as the featured image (and an optional inline figure),
 *   - for each "merge" id: keeps that post removed but 301-redirects its URL to this page.
 * Dry run unless --apply. Restored URLs are queued for IndexNow at the end.
 */
if ( 'cli' !== php_sapi_name() ) {
    exit( 'CLI only' );
}
$opts = getopt( '', array( 'apply', 'only::', 'dir::', 'prepend::', 'noimages' ) );
if ( ! empty( $opts['prepend'] ) ) {
    require $opts['prepend'];
}
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$apply = isset( $opts['apply'] );
$dir   = isset( $opts['dir'] ) ? rtrim( $opts['dir'], '/\\' ) : __DIR__ . '/recovery';
$only  = isset( $opts['only'] ) ? array_map( 'intval', explode( ',', $opts['only'] ) ) : array();
global $wpdb;

// Do not let each restored post fire its own IndexNow ping; one batch is sent at the end.
remove_all_actions( 'transition_post_status' );
remove_all_actions( 'save_post' );
remove_all_actions( 'post_updated' );

$gone      = (array) get_option( 'hpw_gone_paths', array() );
$rules_raw = (string) get_option( 'hpw_redirect_rules', '' );
$rules     = array_filter( preg_split( '/\r\n|\r|\n/', $rules_raw ) );
$added_urls = array();
$report     = array();

$drop_meta = array( '_hpw_noindex', '_hpw_verdict_override', '_hpw_rating_override', '_cached_rating', '_hpw_content_expanded', '_hpw_view_stats',
    '_wp_trash_meta_status', '_wp_trash_meta_time', 'external_image', '_holyprofweb_remote_image_url', '_hpw_source_url', '_hpw_country_focus',
    '_yoast_wpseo_metadesc', '_yoast_wpseo_title', '_yoast_wpseo_focuskw', '_yoast_wpseo_opengraph-image', 'rank_math_title', 'rank_math_description',
    'rank_math_focus_keyword', 'rank_math_og_thumbnail', 'rank_math_internal_links_processed', '_aioseop_title', '_aioseop_description', '_hpw_schema_type', '_hpw_reading_time', '_hpw_seed_post', '_hpw_placeholder_post' );

function hpw_rec_path( $slug ) {
    return '/' . trim( $slug, '/' ) . '/';
}
function hpw_rec_add_rule( array &$rules, $from_path, $to_url ) {
    $from = untrailingslashit( $from_path );
    foreach ( $rules as $i => $line ) {
        $pair = function_exists( 'holyprofweb_parse_redirect_line' ) ? holyprofweb_parse_redirect_line( $line ) : array();
        if ( ! empty( $pair['from'] ) && untrailingslashit( $pair['from'] ) === $from ) {
            unset( $rules[ $i ] );
        }
    }
    $rules[] = $from . ' | ' . $to_url;
}

foreach ( glob( $dir . '/*.json' ) as $file ) {
    $d = json_decode( file_get_contents( $file ), true );
    if ( ! $d || empty( $d['post_id'] ) ) {
        echo 'skip ' . basename( $file ) . " (bad json)\n";
        continue;
    }
    $id = (int) $d['post_id'];
    if ( $only && ! in_array( $id, $only, true ) ) {
        continue;
    }
    $post = get_post( $id );
    if ( ! $post || 'post' !== $post->post_type ) {
        echo "skip $id (post not found)\n";
        continue;
    }
    $old_slug = $post->post_name;
    $new_slug = ! empty( $d['slug'] ) ? sanitize_title( $d['slug'] ) : $old_slug;
    $section  = get_term_by( 'slug', $d['section'], 'category' );
    echo ( $apply ? 'RESTORE ' : 'DRY     ' ) . $id . '  /' . $old_slug . '/' . ( $new_slug !== $old_slug ? '  ->  /' . $new_slug . '/' : '' ) . "\n        " . $d['title'] . "\n";
    if ( ! $section ) {
        echo "        ERROR: unknown section {$d['section']}\n";
        continue;
    }
    if ( ! $apply ) {
        continue;
    }

    // Make sure the new slug is free (another post, trashed or not, may hold it).
    $holder = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name=%s AND post_type='post' AND ID<>%d LIMIT 1", $new_slug, $id ) );
    if ( $holder ) {
        $new_slug .= '-' . $id;
    }

    $res = wp_update_post( array(
        'ID'            => $id,
        'post_title'    => $d['title'],
        'post_name'     => $new_slug,
        'post_excerpt'  => $d['dek'],
        'post_content'  => $d['content_html'],
        'post_status'   => 'publish',
        'post_category' => array( (int) $section->term_id ),
    ), true );
    if ( is_wp_error( $res ) ) {
        echo '        ERROR ' . $res->get_error_message() . "\n";
        continue;
    }
    $wpdb->update( $wpdb->posts, array( 'post_status' => 'publish', 'post_name' => $new_slug ), array( 'ID' => $id ) );
    wp_set_post_terms( $id, array(), 'post_tag' );
    foreach ( $drop_meta as $k ) {
        delete_post_meta( $id, $k );
    }
    update_post_meta( $id, '_hpw_seo_title', $d['seo_title'] );
    update_post_meta( $id, '_hpw_seo_desc', $d['meta_description'] );
    update_post_meta( $id, '_hpw_facts', $d['facts'] );
    update_post_meta( $id, '_hpw_related', implode( "\n", (array) ( $d['related'] ?? array() ) ) );
    update_post_meta( $id, '_hpw_primary_query', (string) ( $d['primary_query'] ?? '' ) );
    update_post_meta( $id, '_hpw_rewritten', gmdate( 'Y-m-d' ) );
    update_post_meta( $id, '_hpw_content_modified_gmt', gmdate( 'Y-m-d H:i:s' ) );
    update_post_meta( $id, '_hpw_audit', 'KEEP|RECOVERED_REWRITTEN|' . gmdate( 'Y-m-d' ) );
    update_post_meta( $id, '_hpw_stage', 'ready' );

    // 410 registry and redirects for the old slug.
    unset( $gone[ hpw_rec_path( $old_slug ) ], $gone[ hpw_rec_path( $new_slug ) ] );
    $permalink = get_permalink( $id );
    if ( $new_slug !== $old_slug ) {
        hpw_rec_add_rule( $rules, hpw_rec_path( $old_slug ), $permalink );
    }
    foreach ( (array) ( $d['merge'] ?? array() ) as $mid ) {
        $m = get_post( (int) $mid );
        if ( ! $m ) { continue; }
        unset( $gone[ hpw_rec_path( $m->post_name ) ] );
        hpw_rec_add_rule( $rules, hpw_rec_path( $m->post_name ), $permalink );
    }

    // Featured image.
    foreach ( array( 'feature' => true, 'inline' => false ) as $kind => $is_feature ) {
        $img = $dir . '/images/' . $id . ( 'inline' === $kind ? '-shot' : '' ) . '.webp';
        if ( isset( $opts['noimages'] ) || ! file_exists( $img ) ) {
            continue;
        }
        $tmp   = wp_tempnam( basename( $img ) );
        copy( $img, $tmp );
        $name  = sanitize_file_name( ( $d['image_slug'] ?? $new_slug ) . ( 'inline' === $kind ? '-screenshot' : '' ) . '.webp' );
        $att   = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $id, ( 'inline' === $kind ? ( $d['shot_title'] ?? $d['title'] ) : $d['title'] ) );
        if ( is_wp_error( $att ) ) {
            echo '        image ERROR ' . $att->get_error_message() . "\n";
            @unlink( $tmp );
            continue;
        }
        update_post_meta( $att, '_wp_attachment_image_alt', 'inline' === $kind ? ( $d['shot_alt'] ?? '' ) : ( $d['image_alt'] ?? '' ) );
        if ( 'inline' === $kind && ! empty( $d['shot_caption'] ) ) {
            wp_update_post( array( 'ID' => $att, 'post_excerpt' => $d['shot_caption'] ) );
        }
        if ( $is_feature ) {
            set_post_thumbnail( $id, $att );
        } else {
            // Insert the screenshot after the first paragraph.
            $fig = '<figure class="wp-block-image">' . wp_get_attachment_image( $att, 'large', false, array( 'alt' => $d['shot_alt'] ?? '', 'loading' => 'lazy' ) ) . '<figcaption>' . esc_html( $d['shot_caption'] ?? '' ) . '</figcaption></figure>';
            $c = get_post_field( 'post_content', $id );
            $pos = strpos( $c, '</p>' );
            if ( false !== $pos ) {
                $c = substr( $c, 0, $pos + 4 ) . "\n" . $fig . substr( $c, $pos + 4 );
                $wpdb->update( $wpdb->posts, array( 'post_content' => $c ), array( 'ID' => $id ) );
            }
        }
    }
    clean_post_cache( $id );
    $added_urls[] = $permalink;
    $report[]     = array( 'id' => $id, 'url' => $permalink, 'merged' => (array) ( $d['merge'] ?? array() ) );
}

if ( $apply ) {
    update_option( 'hpw_gone_paths', $gone, false );
    update_option( 'hpw_redirect_rules', implode( "\n", array_values( $rules ) ) );
    $tt = $wpdb->get_col( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category'" );
    wp_update_term_count_now( array_map( 'intval', $tt ), 'category' );
    wp_cache_flush();
    file_put_contents( $dir . '/last-urls.txt', implode( "\n", $added_urls ) );
    echo 'Restored ' . count( $added_urls ) . " posts. URLs written to last-urls.txt (submit with tools/indexnow-submit.php --urls=...)\n";
} else {
    echo "Dry run. Add --apply to execute.\n";
}
