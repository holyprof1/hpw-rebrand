<?php
/**
 * Apply content-audit decisions (CLI only).
 *
 *   php tools/apply-audit.php --audit=audit.json --archive-dir=DIR [--apply] [--prepend=file.php]
 *
 * Without --apply it is a dry run: it reports what it would do and writes nothing.
 * With --apply it:
 *   1. archives every post it is going to remove (post row, meta, terms, comments) to DIR,
 *   2. trashes REMOVE posts (direct SQL, so no publish/IndexNow/cache hooks fire) and registers their
 *      paths as 410 Gone,
 *   3. marks NOINDEX_TEMPORARILY posts with _hpw_noindex,
 *   4. reconciles the old redirect rules (a rule that points at removed content is dropped; its source is
 *      then handled by its own decision),
 *   5. sets approved spam comments to "spam" (a short owner whitelist is kept),
 *   6. records the decision on every post in _hpw_audit.
 * Nothing is permanently deleted: removed posts stay in the trash and in the archive file.
 */

if ( 'cli' !== php_sapi_name() ) {
    exit( 'CLI only' );
}
$opts = getopt( '', array( 'audit:', 'archive-dir:', 'apply', 'prepend::' ) );
if ( empty( $opts['audit'] ) || empty( $opts['archive-dir'] ) ) {
    fwrite( STDERR, "Usage: php tools/apply-audit.php --audit=audit.json --archive-dir=DIR [--apply]\n" );
    exit( 1 );
}
if ( ! empty( $opts['prepend'] ) ) {
    require $opts['prepend'];
}
require dirname( __DIR__, 4 ) . '/wp-load.php';

$apply = isset( $opts['apply'] );
$dir   = rtrim( $opts['archive-dir'], '/\\' );
if ( ! is_dir( $dir ) ) {
    mkdir( $dir, 0775, true );
}
$audit = json_decode( file_get_contents( $opts['audit'] ), true );
if ( ! is_array( $audit ) ) {
    exit( "Cannot read audit file\n" );
}
global $wpdb;

$by_id   = array();
$by_path = array();
foreach ( $audit as $row ) {
    $by_id[ (int) $row['post_id'] ] = $row;
    $by_path[ trim( (string) wp_parse_url( $row['url'], PHP_URL_PATH ), '/' ) ] = $row;
}

$remove = array();
$noidx  = array();
foreach ( $by_id as $id => $row ) {
    if ( 'REMOVE' === $row['decision'] ) { $remove[] = $id; }
    if ( 'NOINDEX_TEMPORARILY' === $row['decision'] ) { $noidx[] = $id; }
}

// ---- Redirect rules: drop rules whose target is not a surviving URL --------------------------------

$raw_rules   = (string) get_option( 'hpw_redirect_rules', '' );
$kept_lines  = array();
$dropped     = array();
$rule_gone   = array();
foreach ( preg_split( '/\r\n|\r|\n/', $raw_rules ) as $line ) {
    if ( '' === trim( $line ) ) { continue; }
    $pair = function_exists( 'holyprofweb_parse_redirect_line' ) ? holyprofweb_parse_redirect_line( $line ) : array();
    if ( empty( $pair['from'] ) || empty( $pair['to'] ) ) { $kept_lines[] = $line; continue; }
    $to_path = trim( (string) wp_parse_url( $pair['to'], PHP_URL_PATH ), '/' );
    $target  = $by_path[ $to_path ] ?? null;
    $alive   = $target ? in_array( $target['decision'], array( 'KEEP', 'IMPROVE', 'NOINDEX_TEMPORARILY' ), true ) : true; // non-post targets (pages) stay
    if ( $target && ! $alive ) {
        $dropped[]   = $line;
        $rule_gone[] = $pair['from'];
    } else {
        $kept_lines[] = $line;
    }
}

// A post decided REDIRECT whose rule was just dropped has no equivalent target left: it is removed instead.
foreach ( $by_id as $id => $row ) {
    if ( 'REDIRECT' === $row['decision'] ) {
        $tgt_path = trim( (string) wp_parse_url( $row['target'], PHP_URL_PATH ), '/' );
        $tgt      = $by_path[ $tgt_path ] ?? null;
        if ( ! $tgt || ! in_array( $tgt['decision'], array( 'KEEP', 'IMPROVE', 'NOINDEX_TEMPORARILY' ), true ) ) {
            $by_id[ $id ]['decision'] = 'REMOVE';
            $by_id[ $id ]['reasons'] .= '|REDIRECT_TARGET_REMOVED';
            $remove[] = $id;
        }
    }
}

$remove = array_values( array_unique( $remove ) );

// Retired legacy archives (the old scam-reports hub).
$retired_paths = array( '/reports/' );

// ---- Comments --------------------------------------------------------------------------------------

$owner_authors = array( 'tobi', 'tobi arowosegbe' );
$placeholders  = implode( ',', array_fill( 0, count( $owner_authors ), '%s' ) );
$spam_ids      = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved='1' AND LOWER(comment_author) NOT IN ($placeholders)", $owner_authors ) );
$kept_comments = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved='1' AND LOWER(comment_author) IN ($placeholders)", $owner_authors ) );

// ---- Report ----------------------------------------------------------------------------------------

$summary = array(
    'mode'                    => $apply ? 'APPLY' : 'DRY_RUN',
    'posts_in_audit'          => count( $by_id ),
    'to_remove_410'           => count( array_unique( $remove ) ),
    'to_noindex'              => count( $noidx ),
    'redirect_rules_total'    => count( $kept_lines ) + count( $dropped ),
    'redirect_rules_dropped'  => count( $dropped ),
    'spam_comments_to_mark'   => count( $spam_ids ),
    'owner_comments_kept'     => $kept_comments,
);
echo wp_json_encode( $summary, JSON_PRETTY_PRINT ) . "\n";
if ( ! $apply ) {
    echo "Dry run only. Re-run with --apply to execute.\n";
    exit( 0 );
}

// ---- 1. Archive ------------------------------------------------------------------------------------

$stamp   = gmdate( 'Ymd-His' );
$archive = array();
foreach ( array_chunk( $remove, 100 ) as $chunk ) {
    $in    = implode( ',', array_map( 'intval', $chunk ) );
    $posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID IN ($in)", ARRAY_A );
    foreach ( $posts as $p ) {
        $pid  = (int) $p['ID'];
        $meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", $pid ), ARRAY_A );
        $terms = wp_get_object_terms( $pid, array( 'category', 'post_tag' ), array( 'fields' => 'all' ) );
        $comm  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_post_ID=%d", $pid ), ARRAY_A );
        $archive[] = array(
            'post'     => $p,
            'meta'     => $meta,
            'terms'    => array_map( function ( $t ) { return array( 'taxonomy' => $t->taxonomy, 'slug' => $t->slug, 'name' => $t->name ); }, is_wp_error( $terms ) ? array() : $terms ),
            'comments' => $comm,
            'audit'    => $by_id[ $pid ],
        );
    }
}
$archive_file = "$dir/removed-posts-$stamp.json";
file_put_contents( $archive_file, wp_json_encode( $archive, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
if ( count( $archive ) !== count( $remove ) || ! filesize( $archive_file ) ) {
    exit( "Archive incomplete; aborting before any change.\n" );
}
echo 'Archived ' . count( $archive ) . " posts to $archive_file (" . round( filesize( $archive_file ) / 1048576, 2 ) . " MB)\n";

// ---- 2. Trash + 410 registry -------------------------------------------------------------------------

$urls = array();
foreach ( $remove as $id ) { $urls[] = $by_id[ $id ]['url']; }
foreach ( $rule_gone as $from ) { $urls[] = $from; }
foreach ( $retired_paths as $rp ) { $urls[] = $rp; }
$registered = holyprofweb_gone_register( $urls );
$trashed    = 0;
foreach ( array_chunk( $remove, 100 ) as $chunk ) {
    $in = implode( ',', array_map( 'intval', $chunk ) );
    $wpdb->query( "UPDATE {$wpdb->posts} SET post_status='trash' WHERE ID IN ($in) AND post_status='publish'" );
    $trashed += (int) $wpdb->rows_affected;
    foreach ( $chunk as $pid ) {
        update_post_meta( $pid, '_wp_trash_meta_status', 'publish' );
        update_post_meta( $pid, '_wp_trash_meta_time', time() );
        update_post_meta( $pid, '_hpw_audit', 'REMOVE|' . $by_id[ $pid ]['reasons'] . '|' . gmdate( 'Y-m-d' ) );
        clean_post_cache( $pid );
    }
}
echo "Trashed $trashed posts, registered $registered new 410 paths\n";

// ---- 3. Noindex ----------------------------------------------------------------------------------------

foreach ( $noidx as $pid ) {
    update_post_meta( $pid, '_hpw_noindex', 1 );
    update_post_meta( $pid, '_hpw_audit', 'NOINDEX_TEMPORARILY|' . $by_id[ $pid ]['reasons'] . '|' . gmdate( 'Y-m-d' ) );
}
foreach ( $by_id as $pid => $row ) {
    if ( ! in_array( $row['decision'], array( 'REMOVE', 'NOINDEX_TEMPORARILY' ), true ) ) {
        update_post_meta( $pid, '_hpw_audit', $row['decision'] . '|' . $row['reasons'] . '|' . gmdate( 'Y-m-d' ) );
    }
}
echo 'Marked ' . count( $noidx ) . " posts noindex\n";

// ---- 4. Redirect rules ---------------------------------------------------------------------------------

if ( $dropped ) {
    update_option( 'hpw_redirect_rules', implode( "\n", $kept_lines ) );
    file_put_contents( "$dir/dropped-redirect-rules-$stamp.txt", implode( "\n", $dropped ) );
}
echo 'Dropped ' . count( $dropped ) . " redirect rules that pointed at removed content\n";

// ---- 5. Comments -----------------------------------------------------------------------------------------

$marked = 0;
foreach ( array_chunk( $spam_ids, 200 ) as $chunk ) {
    $in = implode( ',', array_map( 'intval', $chunk ) );
    $wpdb->query( "UPDATE {$wpdb->comments} SET comment_approved='spam' WHERE comment_ID IN ($in) AND comment_approved='1'" );
    $marked += (int) $wpdb->rows_affected;
}
$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='post'" );
foreach ( $post_ids as $pid ) {
    wp_update_comment_count_now( (int) $pid );
}
echo "Marked $marked approved comments as spam (owner comments kept: $kept_comments)\n";

// ---- 6. Term counts and caches -----------------------------------------------------------------------------

$tt = $wpdb->get_col( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category'" );
wp_update_term_count_now( array_map( 'intval', $tt ), 'category' );
wp_cache_flush();
file_put_contents( "$dir/apply-summary-$stamp.json", wp_json_encode( $summary + array( 'trashed' => $trashed, 'gone_registered' => $registered, 'comments_marked_spam' => $marked, 'archive' => basename( $archive_file ) ), JSON_PRETTY_PRINT ) );
echo "Done.\n";
