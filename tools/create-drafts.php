<?php
/**
 * Create WordPress DRAFTS from tools/drafts-src/*.json (CLI only). Never publishes, never schedules.
 *   php tools/create-drafts.php [--apply]
 * The editorial brief (intent, questions, links, image and screenshot suggestions, schema, open checks) is
 * stored in the _hpw_draft_brief post meta and shown in a read-only box on the edit screen.
 */
if ( 'cli' !== php_sapi_name() ) { exit( 'CLI only' ); }
$opts = getopt( '', array( 'apply' ) );
require dirname( __DIR__, 4 ) . '/wp-load.php';
remove_all_actions( 'transition_post_status' ); remove_all_actions( 'save_post' ); remove_all_actions( 'post_updated' );
foreach ( glob( __DIR__ . '/drafts-src/*.json' ) as $f ) {
    $d = json_decode( file_get_contents( $f ), true );
    $existing = get_page_by_path( $d['slug'], OBJECT, 'post' );
    if ( $existing ) { echo "EXISTS {$d['slug']} (", $existing->post_status, ")\n"; continue; }
    $cat = get_term_by( 'slug', $d['section'], 'category' );
    $src = '<h2>Sources</h2><ol>';
    foreach ( $d['sources'] as $s ) { $src .= '<li><a href="' . esc_url( $s[1] ) . '" rel="noopener nofollow">' . esc_html( $s[0] ) . '</a></li>'; }
    $src .= '</ol>';
    echo isset( $opts['apply'] ) ? 'CREATE ' : 'DRY ', $d['slug'], "\n";
    if ( ! isset( $opts['apply'] ) ) { continue; }
    $id = wp_insert_post( array(
        'post_type' => 'post', 'post_status' => 'draft', 'post_author' => 1, 'post_title' => $d['title'], 'post_name' => $d['slug'],
        'post_excerpt' => $d['dek'], 'post_content' => $d['body'] . "\n" . $src, 'post_category' => $cat ? array( (int) $cat->term_id ) : array(),
    ), true );
    if ( is_wp_error( $id ) ) { echo '  ERROR ', $id->get_error_message(), "\n"; continue; }
    update_post_meta( $id, '_hpw_seo_title', $d['seo_title'] );
    update_post_meta( $id, '_hpw_seo_desc', $d['meta'] );
    update_post_meta( $id, '_hpw_primary_query', $d['topic'] );
    update_post_meta( $id, '_hpw_draft_brief', wp_json_encode( array_intersect_key( $d, array_flip( array( 'intent', 'topic', 'questions', 'links', 'image', 'shots', 'schema', 'needs' ) ) ) ) );
    echo '  draft id ', $id, "\n";
}
