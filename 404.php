<?php
/**
 * 404 template.
 */

get_header();
$hpw_gone = (bool) get_query_var( 'hpw_gone' );
?>
<main id="primary" class="site-main pub-hub pub-404">
    <header class="pub-hub__head">
        <div class="pub-wrap pub-wrap--narrow">
            <p class="pub-kicker"><?php echo $hpw_gone ? esc_html__( 'Removed', 'holyprofweb' ) : esc_html__( 'Error 404', 'holyprofweb' ); ?></p>
            <h1 class="pub-hub__title"><?php echo $hpw_gone ? esc_html__( 'This page has been removed.', 'holyprofweb' ) : esc_html__( 'That page isn’t here.', 'holyprofweb' ); ?></h1>
            <p class="pub-hub__intro"><?php echo $hpw_gone ? esc_html__( 'We removed it deliberately because it did not meet our editorial standards. Try a search, or start from one of the sections.', 'holyprofweb' ) : esc_html__( 'It may have moved or been removed. Try a search, or start from one of the sections.', 'holyprofweb' ); ?></p>
            <?php get_search_form(); ?>
            <nav class="pub-hub__switch" aria-label="<?php esc_attr_e( 'Sections', 'holyprofweb' ); ?>">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'holyprofweb' ); ?></a>
                <?php foreach ( holyprofweb_sections() as $slug => $s ) : ?>
                <a href="<?php echo esc_url( holyprofweb_section_url( $slug ) ); ?>"><?php echo esc_html( $s['label'] ); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>
</main>
<?php
get_footer();
