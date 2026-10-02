<?php
/**
 * Single article template.
 *
 * Plain editorial layout: kicker, headline, summary, byline with real dates, lead image, body,
 * sources, author, related stories, comments. No star ratings, verdict badges or review forms.
 * (The previous review/salary template is in git history.)
 */

get_header();
?>
<main id="primary" class="site-main pub-article">
<?php
while ( have_posts() ) :
    the_post();
    $post_id = get_the_ID();
    $kicker  = holyprofweb_post_kicker( $post_id );
    $summary = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '';
    $author  = (int) get_the_author_meta( 'ID' );
    $bio     = get_the_author_meta( 'description', $author );
    $img_id  = get_post_thumbnail_id( $post_id );
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'pub-article__inner' ); ?>>
        <header class="pub-article__head">
            <nav class="pub-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'holyprofweb' ); ?>">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'holyprofweb' ); ?></a>
                <?php if ( $kicker['label'] ) : ?>
                <span aria-hidden="true">/</span> <a href="<?php echo esc_url( $kicker['url'] ); ?>"><?php echo esc_html( $kicker['label'] ); ?></a>
                <?php endif; ?>
            </nav>
            <h1 class="pub-article__title"><?php holyprofweb_the_decoded_title(); ?></h1>
            <?php if ( $summary ) : ?>
            <p class="pub-article__summary"><?php echo esc_html( $summary ); ?></p>
            <?php endif; ?>
            <p class="pub-byline">
                <?php esc_html_e( 'By', 'holyprofweb' ); ?>
                <a href="<?php echo esc_url( get_author_posts_url( $author ) ); ?>" rel="author"><?php the_author(); ?></a>
                <span aria-hidden="true">·</span>
                <?php echo holyprofweb_pub_dates( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </p>
        </header>

        <?php if ( $img_id && holyprofweb_pub_has_real_image( $post_id ) ) : ?>
        <figure class="pub-article__figure">
            <?php
            echo wp_get_attachment_image( $img_id, 'large', false, array(
                'fetchpriority' => 'high',
                'decoding'      => 'async',
                'loading'       => 'eager',
                'sizes'         => '(max-width: 900px) 100vw, 860px',
            ) );
            $caption = wp_get_attachment_caption( $img_id );
            if ( $caption ) {
                echo '<figcaption>' . esc_html( $caption ) . '</figcaption>';
            }
            ?>
        </figure>
        <?php endif; ?>

        <div class="pub-article__body entry-content">
            <?php the_content(); ?>
        </div>


        <aside class="pub-authorbox" aria-label="<?php esc_attr_e( 'About the author', 'holyprofweb' ); ?>">
            <?php echo get_avatar( $author, 64, '', '', array( 'class' => 'pub-authorbox__avatar' ) ); ?>
            <div>
                <p class="pub-authorbox__name"><a href="<?php echo esc_url( get_author_posts_url( $author ) ); ?>"><?php the_author(); ?></a></p>
                <?php if ( $bio ) : ?><p class="pub-authorbox__bio"><?php echo esc_html( $bio ); ?></p><?php endif; ?>
            </div>
        </aside>
    </article>

    <?php
    $related_ids = holyprofweb_pub_related( $post_id, 3 );
    if ( $related_ids ) :
        ?>
        <section class="pub-section" aria-labelledby="related-h">
            <div class="pub-wrap">
                <header class="pub-section__head"><h2 id="related-h"><?php esc_html_e( 'Keep reading', 'holyprofweb' ); ?></h2></header>
                <div class="pub-grid pub-grid--3">
                    <?php foreach ( $related_ids as $rid ) { holyprofweb_pub_card( $rid ); } ?>
                </div>
            </div>
        </section>
        <?php
    endif;

    if ( comments_open() || get_comments_number() ) :
        echo '<div class="pub-wrap pub-wrap--narrow">';
        comments_template();
        echo '</div>';
    endif;
endwhile;
?>
</main>
<?php
get_footer();
