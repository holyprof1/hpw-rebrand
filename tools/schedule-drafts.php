<?php
/**
 * Finish and schedule the drafts in tools/drafts-src/*.json (CLI only).
 *   php tools/schedule-drafts.php [--apply]
 * Updates title, slug, standfirst, body, search title/description, entity facts, related stories and the featured
 * image, then sets status "future" with the date in publish_at (site time, one story a day). WordPress's own scheduler
 * (the server cron already calls wp-cron.php every 30 minutes) publishes each one; publishing then triggers the
 * normal IndexNow ping. Entries without publish_at stay drafts.
 */
if ( 'cli' !== php_sapi_name() ) { exit( 'CLI only' ); }
$apply = in_array( '--apply', $argv, true );
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
foreach ( glob( __DIR__ . '/drafts-src/*.json' ) as $f ) {
    $d  = json_decode( file_get_contents( $f ), true );
    $id = (int) $d['post_id'];
    $p  = get_post( $id );
    if ( ! $p || ! in_array( $p->post_status, array( 'draft', 'future' ), true ) ) { echo "SKIP $id ({$d['slug']})\n"; continue; }
    $src = '<h2>Sources</h2><ol>';
    foreach ( $d['sources'] as $s ) { $src .= '<li><a href="' . esc_url( $s[1] ) . '" rel="noopener nofollow">' . esc_html( $s[0] ) . '</a></li>'; }
    $src .= '</ol>';
    $args = array( 'ID' => $id, 'post_title' => $d['title'], 'post_name' => $d['slug'], 'post_excerpt' => $d['dek'], 'post_content' => $d['body'] . "\n" . $src );
    if ( ! empty( $d['publish_at'] ) ) {
        $args += array( 'post_status' => 'future', 'post_date' => $d['publish_at'], 'post_date_gmt' => get_gmt_from_date( $d['publish_at'] ), 'edit_date' => true );
    }
    echo $apply ? 'UPDATE ' : 'DRY ', $id, ' ', $d['slug'], ' ', $d['publish_at'] ?: '(stays draft)', "\n";
    if ( ! $apply ) { continue; }
    $r = wp_update_post( $args, true );
    if ( is_wp_error( $r ) ) { echo '  ERROR ', $r->get_error_message(), "\n"; continue; }
    update_post_meta( $id, '_hpw_seo_title', $d['seo_title'] );
    update_post_meta( $id, '_hpw_seo_desc', $d['meta'] );
    update_post_meta( $id, '_hpw_primary_query', $d['title'] );
    update_post_meta( $id, '_hpw_facts', $d['facts'] );
    update_post_meta( $id, '_hpw_related', implode( "\n", (array) $d['related'] ) );
    update_post_meta( $id, '_hpw_draft_brief', wp_json_encode( array_intersect_key( $d, array_flip( array( 'intent', 'topic', 'questions', 'image', 'shots', 'schema', 'needs' ) ) ) ) );
    $img = __DIR__ . '/recovery/images/' . $id . '.webp';
    if ( ! has_post_thumbnail( $id ) && file_exists( $img ) ) {
        $tmp = wp_tempnam( $img ); copy( $img, $tmp );
        $att = media_handle_sideload( array( 'name' => sanitize_file_name( $d['slug'] . '.webp' ), 'tmp_name' => $tmp ), $id, $d['title'] );
        if ( ! is_wp_error( $att ) ) {
            update_post_meta( $att, '_wp_attachment_image_alt', 'Holyprofweb editorial graphic: ' . $d['facts']['name'] );
            set_post_thumbnail( $id, $att );
        } else { echo '  image ERROR ', $att->get_error_message(), "\n"; }
    }
}
