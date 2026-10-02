<?php
/**
 * IndexNow submission (CLI only).
 *
 *   php tools/indexnow-submit.php [--gone] [--urls=file.txt] [--apply]
 *
 * Verifies the key file is publicly served, then submits (a) the listed changed URLs and (b) with --gone every
 * path registered as removed. Intended for meaningful changes only: new or rewritten pages and removed pages.
 * Dry run unless --apply is given.
 */
if ( 'cli' !== php_sapi_name() ) {
    exit( 'CLI only' );
}
$opts = getopt( '', array( 'gone', 'urls::', 'apply', 'prepend::' ) );
if ( ! empty( $opts['prepend'] ) ) {
    require $opts['prepend'];
}
require dirname( __DIR__, 4 ) . '/wp-load.php';

$urls = array();
if ( ! empty( $opts['urls'] ) && is_readable( $opts['urls'] ) ) {
    foreach ( file( $opts['urls'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $u ) {
        $urls[] = trim( $u );
    }
}
$gone = array();
if ( isset( $opts['gone'] ) ) {
    foreach ( array_keys( (array) get_option( 'hpw_gone_paths', array() ) ) as $p ) {
        $gone[] = untrailingslashit( home_url( $p ) ) . '/';
    }
}
$all = array_values( array_unique( array_merge( $urls, $gone ) ) );

$key     = holyprofweb_indexnow_key();
$key_url = holyprofweb_indexnow_key_url( $key );
$check   = wp_remote_get( $key_url, array( 'timeout' => 15 ) );
$ok      = ! is_wp_error( $check ) && 200 === (int) wp_remote_retrieve_response_code( $check ) && trim( wp_remote_retrieve_body( $check ) ) === $key;
echo 'Key file ' . substr( $key, 0, 4 ) . '… at ' . preg_replace( '/' . preg_quote( $key, '/' ) . '/', '<key>', $key_url ) . ': ' . ( $ok ? 'served correctly' : 'NOT served correctly' ) . "\n";
echo 'URLs to submit: ' . count( $all ) . ' (changed ' . count( $urls ) . ', gone ' . count( $gone ) . ")\n";
if ( ! $ok ) {
    exit( "Aborting: fix the key file first.\n" );
}
if ( ! isset( $opts['apply'] ) ) {
    exit( "Dry run. Add --apply to submit.\n" );
}
$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
foreach ( array_chunk( $all, 500 ) as $i => $chunk ) {
    $res  = wp_remote_post( 'https://api.indexnow.org/indexnow', array(
        'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
        'body'    => wp_json_encode( array( 'host' => $host, 'key' => $key, 'keyLocation' => $key_url, 'urlList' => $chunk ) ),
        'timeout' => 30,
    ) );
    $code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
    echo 'Batch ' . ( $i + 1 ) . ': ' . count( $chunk ) . ' URLs -> HTTP ' . $code . ( is_wp_error( $res ) ? ' ' . $res->get_error_message() : '' ) . "\n";
}
update_option( 'hpw_indexnow_last_log', array( 'time' => current_time( 'mysql' ), 'count' => count( $all ), 'code' => $code ?? 0, 'urls' => array_slice( $all, 0, 10 ), 'error' => '' ) );
