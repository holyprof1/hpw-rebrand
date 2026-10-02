<?php
/**
 * Front page: a discovery publication for what is new online.
 */

get_header();

$sections = holyprofweb_sections();
$used     = array();
$no_ph    = array( array( 'key' => '_hpw_placeholder_post', 'compare' => 'NOT EXISTS' ) );

// The newest story across the four sections leads; each section also feeds its own block below.
$lead_candidates = array();
foreach ( array_keys( $sections ) as $slug ) {
    $q = new WP_Query( holyprofweb_section_query_args( $slug, array( 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true ) ) );
    if ( $q->posts ) {
        $lead_candidates[ $q->posts[0] ] = get_post_time( 'U', true, $q->posts[0] );
    }
}
arsort( $lead_candidates );
$lead_id = $lead_candidates ? (int) array_key_first( $lead_candidates ) : 0;
if ( $lead_id ) {
    $used[] = $lead_id;
}

$trending_ids = ( new WP_Query( array(
    'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 6, 'post__not_in' => $used,
    'ignore_sticky_posts' => true, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => $no_ph,
) ) )->posts;
$used = array_merge( $used, $trending_ids );

$apps_ids     = holyprofweb_pub_section_ids( 'apps-websites', 4, $used );
$products_ids = holyprofweb_pub_section_ids( 'products-tech', 3, $used );
$people_ids   = holyprofweb_pub_section_ids( 'people', 4, $used );
$trends_ids   = holyprofweb_pub_section_ids( 'internet-trends', 3, $used );

$latest_ids = ( new WP_Query( array(
    'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 10, 'post__not_in' => $used,
    'ignore_sticky_posts' => true, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => $no_ph,
) ) )->posts;

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
            </div>
            <?php if ( $lead_id ) { holyprofweb_pub_card( $lead_id, array( 'variant' => 'lead', 'excerpt' => true, 'eager' => true ) ); } ?>
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
                    <img src="<?php echo esc_url( holyprofweb_pub_image_url( $id ) ); ?>" alt="" width="240" height="240" loading="lazy" decoding="async" />
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
                <?php foreach ( $latest_ids as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'row' ) ); } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>
<?php
get_footer();
