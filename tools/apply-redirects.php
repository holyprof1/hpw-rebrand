<?php
/**
 * Apply 301 redirects from a CSV (CLI only): from,to,... Same-entity consolidation only.
 *   php tools/apply-redirects.php docs/audit/link-equity-redirects-2026-10.csv [--apply]
 * Skips a row when the target is not a live published page, when the source is itself a live page, or when it would
 * create a chain. Removes the source from the 410 registry so the redirect (not a 410) answers.
 */
if ( 'cli' !== php_sapi_name() ) { exit( 'CLI only' ); }
$file  = $argv[1] ?? '';
$apply = in_array( '--apply', $argv, true );
require dirname( __DIR__, 4 ) . '/wp-load.php';
$rows  = array_map( 'str_getcsv', file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) );
array_shift( $rows );
$gone  = (array) get_option( 'hpw_gone_paths', array() );
$raw   = (string) get_option( 'hpw_redirect_rules', '' );
$lines = array_values( array_filter( preg_split( '/\r\n|\r|\n/', $raw ) ) );
$have  = array();
foreach ( $lines as $l ) { if ( false !== strpos( $l, '|' ) ) { list( $a, $b ) = array_map( 'trim', explode( '|', $l, 2 ) ); $have[ $a ] = $b; } }
$n = 0;
foreach ( $rows as $r ) {
    list( $from, $to ) = $r;
    $target = url_to_postid( home_url( $to ) );
    if ( ! $target || 'publish' !== get_post_status( $target ) ) { echo "SKIP target not live: $from -> $to\n"; continue; }
    if ( url_to_postid( home_url( $from ) ) ) { echo "SKIP source is live: $from\n"; continue; }
    if ( isset( $have[ $to ] ) ) { echo "SKIP would chain: $from -> $to\n"; continue; }
    if ( isset( $have[ $from ] ) ) { echo "EXISTS $from\n"; continue; }
    echo ( $apply ? 'ADD ' : 'DRY ' ) . "$from -> $to\n";
    $lines[] = "$from | $to"; unset( $gone[ $from ], $gone[ rtrim( $from, '/' ) ] ); $n++;
}
if ( $apply && $n ) {
    update_option( 'hpw_redirect_rules', implode( "\n", $lines ), false );
    update_option( 'hpw_gone_paths', $gone, false );
}
echo "$n rule(s) " . ( $apply ? 'added' : 'would be added' ) . "\n";
