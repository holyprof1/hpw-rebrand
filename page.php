<?php
/**
 * Generic Page Template — HolyprofWeb
 */

get_header();
?>

<div class="platform-wrap">

    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">

        <?php while ( have_posts() ) : the_post(); ?>

        <article id="page-<?php the_ID(); ?>" <?php post_class( 'page-content-wrap' ); ?>>
            <h1 class="page-title"><?php the_title(); ?></h1>
            <div class="page-body">
                <?php the_content(); ?>
            </div>
        </article>

        <?php endwhile; ?>

    </main>

</div><!-- .platform-wrap -->

<?php get_footer(); ?>
