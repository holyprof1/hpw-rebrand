<?php
/**
 * Publish hand-written, sourced rewrites over existing posts (CLI only).
 *
 *   php tools/publish-rewrites.php [--apply] [--prepend=file.php]
 *
 * Each tools/rewrites/<post_id>.json replaces that post's title, excerpt and body, keeps its URL (slug),
 * assigns it to a publication section, clears the old review-farm data (verdict/rating overrides), removes
 * any noindex flag, and records the meaningful-update date. Dry run unless --apply is given.
 */
if ( 'cli' !== php_sapi_name() ) {
    exit( 'CLI only' );
}
$opts = getopt( '', array( 'apply', 'prepend::' ) );
if ( ! empty( $opts['prepend'] ) ) {
    require $opts['prepend'];
}
require dirname( __DIR__, 4 ) . '/wp-load.php';
$apply = isset( $opts['apply'] );

foreach ( glob( __DIR__ . '/rewrites/*.json' ) as $file ) {
    $d = json_decode( file_get_contents( $file ), true );
    if ( ! $d || empty( $d['post_id'] ) ) {
        echo "skip $file (bad json)\n";
        continue;
    }
    $post = get_post( (int) $d['post_id'] );
    if ( ! $post || 'post' !== $post->post_type ) {
        echo "skip {$d['post_id']} (post not found)\n";
        continue;
    }
    echo ( $apply ? 'APPLY ' : 'DRY   ' ) . $post->ID . '  ' . $post->post_name . "\n      old: " . $post->post_title . "\n      new: " . $d['title'] . "\n";
    if ( ! $apply ) {
        continue;
    }
    // Keep the old version in a revision-style meta so the rewrite is reversible.
    update_post_meta( $post->ID, '_hpw_pre_rewrite', wp_json_encode( array( 'title' => $post->post_title, 'excerpt' => $post->post_excerpt, 'content' => $post->post_content, 'date' => gmdate( 'c' ) ) ) );
    $cat_ids = array();
    foreach ( (array) ( $d['categories'] ?? array() ) as $slug ) {
        $t = get_term_by( 'slug', $slug, 'category' );
        if ( $t ) { $cat_ids[] = (int) $t->term_id; }
    }
    $res = wp_update_post( array(
        'ID'            => $post->ID,
        'post_title'    => $d['title'],
        'post_excerpt'  => $d['excerpt'],
        'post_content'  => $d['content_html'],
        'post_status'   => 'publish',
        'post_category' => $cat_ids ? $cat_ids : wp_get_post_categories( $post->ID ),
    ), true );
    if ( is_wp_error( $res ) ) {
        echo '      ERROR ' . $res->get_error_message() . "\n";
        continue;
    }
    foreach ( array( '_hpw_noindex', '_hpw_verdict_override', '_hpw_rating_override', '_cached_rating', '_hpw_content_expanded', '_hpw_view_stats' ) as $k ) {
        delete_post_meta( $post->ID, $k );
    }
    update_post_meta( $post->ID, '_hpw_rewritten', gmdate( 'Y-m-d' ) );
    update_post_meta( $post->ID, '_hpw_content_modified_gmt', gmdate( 'Y-m-d H:i:s' ) );
    update_post_meta( $post->ID, '_hpw_audit', 'KEEP|REWRITTEN_SOURCED|' . gmdate( 'Y-m-d' ) );
    clean_post_cache( $post->ID );
}
echo "Done.\n";
