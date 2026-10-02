<?php
/**
 * Category report (CLI only): what happens to every category archive after the Phase 2 cleanup.
 *   php tools/taxonomy-report.php
 */
if ( 'cli' !== php_sapi_name() ) {
    exit( 'CLI only' );
}
require dirname( __DIR__, 4 ) . '/wp-load.php';
$sections = holyprofweb_sections();
$cats     = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
$rows     = array();
$sum      = array( 'SECTION_HUB' => 0, 'NOINDEX_HAS_POSTS' => 0, 'GONE_410_EMPTY' => 0 );
foreach ( $cats as $c ) {
    $q = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'cat' => $c->term_id, 'fields' => 'ids', 'no_found_rows' => false, 'posts_per_page' => 1 ) );
    $published = (int) $q->found_posts;
    $real = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'cat' => $c->term_id, 'fields' => 'ids', 'posts_per_page' => 1, 'meta_query' => holyprofweb_real_posts_meta_query() ) );
    $indexable = (int) $real->found_posts;
    if ( isset( $sections[ $c->slug ] ) ) {
        $treat = 'SECTION_HUB';
    } elseif ( $indexable > 0 ) {
        $treat = 'NOINDEX_HAS_POSTS';
    } else {
        $treat = 'GONE_410_EMPTY';
    }
    $sum[ $treat ]++;
    $rows[] = array( $c->slug, $c->parent ? get_term( $c->parent )->slug : '', $published, $indexable, $treat );
}
echo 'Categories: ' . count( $cats ) . "\n";
foreach ( $sum as $k => $v ) { echo str_pad( $k, 22 ) . $v . "\n"; }
echo "\nslug,parent,published_posts,indexable_posts,treatment\n";
foreach ( $rows as $r ) {
    if ( $r[2] > 0 || 'SECTION_HUB' === $r[4] ) { echo implode( ',', $r ) . "\n"; }
}
