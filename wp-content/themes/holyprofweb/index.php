<?php
/**
 * Main Index Template — HolyprofWeb
 * Used as the blog/posts page and fallback for all other views.
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">

        <?php if ( is_home() && ! is_front_page() ) : ?>
        <header class="archive-header">
            <h1 class="archive-title"><?php single_post_title(); ?></h1>
        </header>
        <?php endif; ?>

        <?php if ( have_posts() ) : ?>

        <div class="section-header">
            <h2 class="section-title">
                <?php
                if ( is_home() ) {
                    esc_html_e( 'All Posts', 'holyprofweb' );
                } else {
                    the_archive_title();
                }
                ?>
            </h2>
        </div>

        <div class="post-list">
            <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class( 'post-list-item' ); ?>>

                <div class="post-card-meta">
                    <?php
                    $categories = get_the_category();
                    if ( ! empty( $categories ) ) :
                        $cat = $categories[0];
                    ?>
                    <span class="post-card-category">
                        <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </a>
                    </span>
                    <?php endif; ?>
                    <span class="post-card-date">
                        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                            <?php echo esc_html( get_the_date() ); ?>
                        </time>
                    </span>
                </div>

                <h2 class="post-card-title">
                    <a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a>
                </h2>

                <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

            </article>

            <?php endwhile; ?>
        </div>

        <?php holyprofweb_pagination(); ?>

        <?php else : ?>

        <div class="no-results-message">
            <h2><?php esc_html_e( 'Nothing found', 'holyprofweb' ); ?></h2>
            <p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Try a search below.', 'holyprofweb' ); ?></p>
            <br>
            <?php get_search_form(); ?>
        </div>

        <?php endif; ?>

    </div><!-- .container -->
</main><!-- #primary -->

<?php get_footer(); ?>
