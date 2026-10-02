<?php
/**
 * Author archive: a short profile followed by that author's stories.
 */

get_header();

$author    = get_queried_object();
$expertise = array_filter( array_map( 'trim', explode( ',', (string) get_user_meta( $author->ID, 'hpw_expertise', true ) ) ) );
$ids       = array();
while ( have_posts() ) {
    the_post();
    $ids[] = get_the_ID();
}
?>
<main id="primary" class="site-main pub-hub">
    <header class="pub-hub__head">
        <div class="pub-wrap pub-authorhead">
            <?php echo get_avatar( $author->ID, 96, '', '', array( 'class' => 'pub-authorbox__avatar' ) ); ?>
            <div>
                <h1 class="pub-hub__title"><?php echo esc_html( $author->display_name ); ?></h1>
                <?php if ( trim( (string) $author->description ) ) : ?>
                <p class="pub-hub__intro"><?php echo esc_html( $author->description ); ?></p>
                <?php endif; ?>
                <?php if ( $expertise ) : ?>
                <p class="pub-meta"><?php esc_html_e( 'Covers:', 'holyprofweb' ); ?> <?php echo esc_html( implode( ', ', $expertise ) ); ?></p>
                <?php endif; ?>
                <?php if ( $author->user_url ) : ?>
                <p class="pub-meta"><a href="<?php echo esc_url( $author->user_url ); ?>" rel="me noopener" target="_blank"><?php echo esc_html( wp_parse_url( $author->user_url, PHP_URL_HOST ) ); ?></a></p>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="pub-wrap">
        <section class="pub-section">
            <header class="pub-section__head"><h2><?php esc_html_e( 'Stories', 'holyprofweb' ); ?></h2></header>
            <div class="pub-grid pub-grid--3">
                <?php foreach ( $ids as $id ) { holyprofweb_pub_card( $id ); } ?>
            </div>
            <?php the_posts_pagination( array( 'prev_text' => __( 'Newer', 'holyprofweb' ), 'next_text' => __( 'Older', 'holyprofweb' ) ) ); ?>
        </section>
    </div>
</main>
<?php
get_footer();
