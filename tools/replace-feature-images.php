<?php
/**
 * Replace the featured image of already-restored posts with the current tools/recovery/images/<id>.webp
 * (CLI only). Deletes the previous featured attachment when it was a recovery graphic of the same post.
 *   php tools/replace-feature-images.php [--apply] [--only=ID,ID]
 */
if ( 'cli' !== php_sapi_name() ) { exit( 'CLI only' ); }
$opts = getopt( '', array( 'apply', 'only::' ) );
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
$only = isset( $opts['only'] ) ? array_map( 'intval', explode( ',', $opts['only'] ) ) : array();
foreach ( glob( __DIR__ . '/recovery/*.json' ) as $f ) {
    $d  = json_decode( file_get_contents( $f ), true );
    $id = (int) $d['post_id'];
    if ( $only && ! in_array( $id, $only, true ) ) { continue; }
    $img = __DIR__ . '/recovery/images/' . $id . '.webp';
    $p   = get_post( $id );
    if ( ! $p || 'publish' !== $p->post_status || ! file_exists( $img ) ) { continue; }
    $old = (int) get_post_thumbnail_id( $id );
    echo ( $apply = isset( $opts['apply'] ) ) ? 'REPLACE ' : 'DRY ', $id, ' ', $p->post_name, "\n";
    if ( ! $apply ) { continue; }
    $tmp = wp_tempnam( $img ); copy( $img, $tmp );
    $name = sanitize_file_name( ( $d['image_slug'] ?? $p->post_name ) . '.webp' );
    $att  = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $id, $d['title'] );
    if ( is_wp_error( $att ) ) { echo '  ERROR ', $att->get_error_message(), "\n"; continue; }
    update_post_meta( $att, '_wp_attachment_image_alt', $d['image_alt'] ?? '' );
    set_post_thumbnail( $id, $att );
    if ( $old && $old !== $att && (int) get_post_field( 'post_parent', $old ) === $id && preg_match( '/\.webp$/', (string) get_post_meta( $old, '_wp_attached_file', true ) ) ) {
        wp_delete_attachment( $old, true );
    }
}
