<?php
/**
 * Front page: a discovery publication for what is new online.
 */

get_header();

$sections = holyprofweb_sections();
$used     = array();
$no_ph    = holyprofweb_real_posts_meta_query();

// The lead is the newest non-farm story that has a real image; else the newest non-farm story.
$lead_pool = array();
foreach ( array_keys( $sections ) as $slug ) {
    $q = new WP_Query( holyprofweb_section_query_args( $slug, array( 'posts_per_page' => 30, 'fields' => 'ids', 'no_found_rows' => true ) ) );
    foreach ( $q->posts as $cand ) {
        if ( ! holyprofweb_pub_is_farm_title( $cand, true ) ) {
            $lead_pool[ $cand ] = get_post_time( 'U', true, $cand ) + ( holyprofweb_pub_has_real_image( $cand ) ? 100 * YEAR_IN_SECONDS : 0 );
        }
    }
}
arsort( $lead_pool );
$lead_id = $lead_pool ? (int) array_key_first( $lead_pool ) : 0;
if ( $lead_id ) {
    $used[] = $lead_id;
}

$section_term_ids = array();
foreach ( array_keys( $sections ) as $slug ) {
    $section_term_ids = array_merge( $section_term_ids, holyprofweb_section_term_ids( $slug ) );
}
$trending_ids = array();
foreach ( array_keys( $sections ) as $slug ) {
    $trending_ids = array_merge( $trending_ids, holyprofweb_pub_pick( $slug, 3, $used, true ) );
}
usort( $trending_ids, function ( $a, $b ) { return get_post_time( 'U', true, $b ) <=> get_post_time( 'U', true, $a ); } );
$trending_ids = array_slice( $trending_ids, 0, 6 );
$used = array_values( array_unique( array_merge( $used, $trending_ids ) ) );


$apps_ids     = holyprofweb_pub_pick( 'apps-websites', 4, $used, true );
$products_ids = holyprofweb_pub_pick( 'products-tech', 3, $used, true );
$people_ids   = holyprofweb_pub_pick( 'people', 4, $used );
$trends_ids   = holyprofweb_pub_pick( 'internet-trends', 3, $used, true );

// Latest: newest from the four sections (the full chronological archive stays at /blog/).
$latest_ids = array();
foreach ( array_keys( $sections ) as $slug ) {
    $latest_ids = array_merge( $latest_ids, holyprofweb_pub_pick( $slug, 4, $used ) );
}
usort( $latest_ids, function ( $a, $b ) { return get_post_time( 'U', true, $b ) <=> get_post_time( 'U', true, $a ); } );
$latest_ids = array_slice( $latest_ids, 0, 10 );

/** Section heading with "See all" link. */
$head = function ( $id, $title, $slug = '', $link_text = 'See all' ) {
    echo '<header class="pub-section__head"><h2 id="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</h2>';
    if ( $slug ) {
        echo '<a href="' . esc_url( 'blog' === $slug ? holyprofweb_get_blog_url() : holyprofweb_section_url( $slug ) ) . '">' . esc_html( $link_text ) . '</a>';
    }
    echo '</header>';
};
?>
<main id="primary" class="site-main pub-home">

    <section class="pub-hero">
        <div class="pub-wrap">
            <div class="pub-hero__intro">
                <h1 class="pub-hero__title"><?php esc_html_e( 'Discover what’s new online.', 'holyprofweb' ); ?></h1>
                <p class="pub-hero__sub"><?php esc_html_e( 'New apps, websites, products, people and internet trends explained clearly.', 'holyprofweb' ); ?></p>
                <nav class="pub-hero__chips" aria-label="<?php esc_attr_e( 'Sections', 'holyprofweb' ); ?>">
                    <?php foreach ( $sections as $chip_slug => $chip ) : ?>
                    <a href="<?php echo esc_url( holyprofweb_section_url( $chip_slug ) ); ?>"><?php echo esc_html( $chip['label'] ); ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
            <?php if ( $lead_id ) { holyprofweb_pub_card( $lead_id, array( 'variant' => 'lead', 'excerpt' => true, 'eager' => true, 'tag' => 'h2' ) ); } ?>
        </div>
    </section>

    <?php if ( $trending_ids ) : ?>
    <section class="pub-section" aria-labelledby="pub-trending">
        <div class="pub-wrap">
            <?php $head( 'pub-trending', __( 'Trending Now', 'holyprofweb' ) ); ?>
            <div class="pub-grid pub-grid--3">
                <?php foreach ( $trending_ids as $id ) { holyprofweb_pub_card( $id ); } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ( $apps_ids ) : ?>
    <section class="pub-section" aria-labelledby="pub-apps">
        <div class="pub-wrap">
            <?php $head( 'pub-apps', $sections['apps-websites']['title'], 'apps-websites' ); ?>
            <div class="pub-feature">
                <?php holyprofweb_pub_card( $apps_ids[0], array( 'variant' => 'lead', 'excerpt' => true ) ); ?>
                <div class="pub-feature__side">
                    <?php foreach ( array_slice( $apps_ids, 1 ) as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'compact' ) ); } ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ( $products_ids ) : ?>
    <section class="pub-section" aria-labelledby="pub-products">
        <div class="pub-wrap">
            <?php $head( 'pub-products', $sections['products-tech']['title'], 'products-tech' ); ?>
            <div class="pub-grid pub-grid--3">
                <?php foreach ( $products_ids as $id ) { holyprofweb_pub_card( $id ); } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ( $people_ids ) : ?>
    <section class="pub-section pub-people" aria-labelledby="pub-people">
        <div class="pub-wrap">
            <?php $head( 'pub-people', $sections['people']['title'], 'people' ); ?>
            <div class="pub-people__row">
                <?php foreach ( $people_ids as $id ) : ?>
                <a class="pub-person" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
                    <?php $portrait = holyprofweb_pub_media( $id, 'holyprofweb-thumb', '180px' ); ?>
                    <span class="pub-person__img<?php echo $portrait ? '' : ' pub-person__img--empty'; ?>"><?php echo $portrait ? $portrait : '<span>' . esc_html( mb_substr( wp_strip_all_tags( holyprofweb_get_decoded_post_title( $id ) ), 0, 1 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                    <span class="pub-person__name"><?php echo esc_html( holyprofweb_get_decoded_post_title( $id ) ); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ( $trends_ids ) : ?>
    <section class="pub-section" aria-labelledby="pub-trends">
        <div class="pub-wrap">
            <?php $head( 'pub-trends', __( 'What People Are Searching', 'holyprofweb' ), 'internet-trends' ); ?>
            <div class="pub-grid pub-grid--3">
                <?php foreach ( $trends_ids as $id ) { holyprofweb_pub_card( $id ); } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ( $latest_ids ) : ?>
    <section class="pub-section" aria-labelledby="pub-latest">
        <div class="pub-wrap pub-wrap--narrow">
            <?php $head( 'pub-latest', __( 'Latest', 'holyprofweb' ), 'blog', __( 'All stories', 'holyprofweb' ) ); ?>
            <div class="pub-list">
                <?php foreach ( $latest_ids as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'row', 'thumb' => false ) ); } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>
<?php
get_footer();
