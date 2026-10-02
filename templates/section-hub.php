<?php
/**
 * Section hub: /apps-websites/, /products-tech/, /people/, /internet-trends/.
 */

get_header();

$term     = get_queried_object();
$sections = holyprofweb_sections();
$section  = $sections[ $term->slug ];
$paged    = max( 1, (int) get_query_var( 'paged' ) );
$ids      = array();
while ( have_posts() ) {
    the_post();
    $ids[] = get_the_ID();
}
$featured = array();
if ( 1 === $paged && count( $ids ) > 3 ) {
    $featured = array_splice( $ids, 0, 3 );
}
?>
<main id="primary" class="site-main pub-hub">
    <header class="pub-hub__head">
        <div class="pub-wrap">
            <nav class="pub-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'holyprofweb' ); ?>">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'holyprofweb' ); ?></a>
                <span aria-hidden="true">/</span> <span><?php echo esc_html( $section['label'] ); ?></span>
            </nav>
            <h1 class="pub-hub__title"><?php echo esc_html( $section['title'] ); ?></h1>
            <p class="pub-hub__intro"><?php echo esc_html( $section['intro'] ); ?></p>
            <nav class="pub-hub__switch" aria-label="<?php esc_attr_e( 'Sections', 'holyprofweb' ); ?>">
                <?php foreach ( $sections as $slug => $s ) : ?>
                <a href="<?php echo esc_url( holyprofweb_section_url( $slug ) ); ?>"<?php echo $slug === $term->slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $s['label'] ); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <div class="pub-wrap">
        <?php if ( $featured ) : ?>
        <section class="pub-section" aria-labelledby="hub-featured">
            <header class="pub-section__head"><h2 id="hub-featured"><?php esc_html_e( 'Featured', 'holyprofweb' ); ?></h2></header>
            <div class="pub-feature">
                <?php holyprofweb_pub_card( $featured[0], array( 'variant' => 'lead', 'excerpt' => true, 'eager' => true ) ); ?>
                <div class="pub-feature__side">
                    <?php foreach ( array_slice( $featured, 1 ) as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'compact' ) ); } ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="pub-section" aria-labelledby="hub-newest">
            <header class="pub-section__head"><h2 id="hub-newest"><?php esc_html_e( 'Newest stories', 'holyprofweb' ); ?></h2></header>
            <?php if ( $ids ) : ?>
            <div class="pub-grid pub-grid--3">
                <?php foreach ( $ids as $id ) { holyprofweb_pub_card( $id ); } ?>
            </div>
            <?php else : ?>
            <p><?php esc_html_e( 'New stories in this section are on the way.', 'holyprofweb' ); ?></p>
            <?php endif; ?>
            <?php
            the_posts_pagination( array(
                'mid_size'  => 1,
                'prev_text' => __( 'Newer', 'holyprofweb' ),
                'next_text' => __( 'Older', 'holyprofweb' ),
            ) );
            ?>
        </section>
    </div>
</main>
<?php
get_footer();
