<?php
/**
 * Search results. Always noindex (see inc/crawl-seo.php / wp_robots); shows real result counts only.
 */

get_header();

$search_term = get_search_query();
$found       = (int) $GLOBALS['wp_query']->found_posts;
$ids         = array();
while ( have_posts() ) {
    the_post();
    $ids[] = get_the_ID();
}
?>
<main id="primary" class="site-main pub-hub pub-search">
    <header class="pub-hub__head">
        <div class="pub-wrap pub-wrap--narrow">
            <h1 class="pub-hub__title">
                <?php echo $search_term ? esc_html( sprintf( __( 'Results for “%s”', 'holyprofweb' ), $search_term ) ) : esc_html__( 'Search', 'holyprofweb' ); ?>
            </h1>
            <?php get_search_form(); ?>
            <p class="pub-hub__intro">
                <?php
                echo esc_html(
                    $found > 0
                        ? sprintf( _n( '%s result', '%s results', $found, 'holyprofweb' ), number_format_i18n( $found ) )
                        : __( 'Nothing published matches this search yet.', 'holyprofweb' )
                );
                ?>
            </p>
        </div>
    </header>

    <div class="pub-wrap pub-wrap--narrow">
        <?php if ( $ids ) : ?>
        <div class="pub-list">
            <?php foreach ( $ids as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'row', 'excerpt' => true ) ); } ?>
        </div>
        <?php the_posts_pagination( array( 'prev_text' => __( 'Previous', 'holyprofweb' ), 'next_text' => __( 'Next', 'holyprofweb' ) ) ); ?>
        <?php else : ?>
        <section class="pub-section">
            <header class="pub-section__head"><h2><?php esc_html_e( 'Browse a section instead', 'holyprofweb' ); ?></h2></header>
            <div class="pub-hub__switch">
                <?php foreach ( holyprofweb_sections() as $slug => $s ) : ?>
                <a href="<?php echo esc_url( holyprofweb_section_url( $slug ) ); ?>"><?php echo esc_html( $s['label'] ); ?></a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
