<?php
/**
 * Generic page template.
 */

get_header();
?>
<main id="primary" class="site-main pub-article">
    <?php while ( have_posts() ) : the_post(); ?>
    <article id="page-<?php the_ID(); ?>" <?php post_class( 'pub-article__inner' ); ?>>
        <header class="pub-article__head">
            <h1 class="pub-article__title"><?php the_title(); ?></h1>
        </header>
        <div class="pub-article__body entry-content">
            <?php the_content(); ?>
        </div>
    </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
