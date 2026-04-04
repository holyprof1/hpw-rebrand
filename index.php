<?php
/**
 * Main Index Template — HolyprofWeb
 * Used for virtual blog/reports archives and general fallback listings.
 */

get_header();

$is_blog_archive    = (bool) get_query_var( 'hpw_blog_archive' );
$is_reports_archive = (bool) get_query_var( 'hpw_reports_archive' );
$is_virtual_archive = $is_blog_archive || $is_reports_archive;
$found_posts        = (int) $GLOBALS['wp_query']->found_posts;
$card_size          = holyprofweb_get_image_size_dimensions( 'holyprofweb-card' );

if ( $is_blog_archive ) {
    $archive_title       = __( 'Blog', 'holyprofweb' );
    $archive_description = __( 'All published posts across reviews, companies, biographies, salaries, reports, and site updates in one clean archive.', 'holyprofweb' );
    $archive_icon        = '✦';
    $archive_section     = __( 'Latest from the blog', 'holyprofweb' );
} elseif ( $is_reports_archive ) {
    $archive_title       = __( 'Reports', 'holyprofweb' );
    $archive_description = __( 'Complaint trends, scam alerts, user warnings, and report-driven posts gathered in one place.', 'holyprofweb' );
    $archive_icon        = '▣';
    $archive_section     = __( 'Latest reports', 'holyprofweb' );
} else {
    $archive_title       = '';
    $archive_description = '';
    $archive_icon        = '';
    $archive_section     = '';
}
?>

<?php if ( $is_virtual_archive ) : ?>
<div class="platform-wrap">

    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <header class="archive-header archive-header--branded">
            <div class="archive-header-eyebrow">
                <span class="archive-header-icon" aria-hidden="true"><?php echo esc_html( $archive_icon ); ?></span>
            </div>
            <h1 class="archive-title"><?php echo esc_html( $archive_title ); ?></h1>
            <div class="archive-description"><?php echo esc_html( $archive_description ); ?></div>
            <?php if ( $found_posts > 0 ) : ?>
            <div class="archive-header-meta">
                <span class="archive-header-stat">
                    <strong><?php echo esc_html( holyprofweb_format_display_count( $found_posts ) ); ?></strong>
                    <?php echo esc_html( _n( 'result', 'results', $found_posts, 'holyprofweb' ) ); ?>
                </span>
            </div>
            <?php endif; ?>
        </header>

        <?php holyprofweb_render_ad_format( 'leaderboard', 'archive_inline', 'ad-archive-inline' ); ?>

        <?php if ( have_posts() ) : ?>
        <div class="section-header">
            <h2 class="section-title"><?php echo esc_html( $archive_section ); ?></h2>
        </div>

        <div class="tp-list">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php
                $cats    = get_the_category();
                $rating  = holyprofweb_get_post_rating( get_the_ID() );
                $r_count = holyprofweb_get_review_count( get_the_ID() );
                $thumb   = holyprofweb_get_post_card_image_url( get_the_ID() );
                ?>
            <a href="<?php the_permalink(); ?>" class="tp-card">
                <img src="<?php echo esc_attr( $thumb ); ?>"
                    alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>"
                    class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID(), 'tp-logo' ) ); ?>"
                    loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" />

                <div class="tp-body">
                    <p class="tp-title"><?php holyprofweb_the_decoded_title(); ?></p>
                    <p class="tp-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

                    <div class="tp-rating-row">
                        <?php if ( $rating > 0 ) : ?>
                            <?php echo holyprofweb_render_stars( $rating ); ?>
                            <span class="tp-rating-score"><?php echo esc_html( $rating ); ?></span>
                            <span style="font-size:.72rem;color:var(--color-text-muted);">(<?php echo esc_html( $r_count ); ?>)</span>
                        <?php else : ?>
                            <span style="font-size:.78rem;color:var(--color-text-muted);"><?php esc_html_e( 'No reviews yet', 'holyprofweb' ); ?></span>
                        <?php endif; ?>

                        <?php if ( ! empty( $cats ) ) : ?>
                        <span class="tp-cat-badge"><?php echo esc_html( $cats[0]->name ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>

        <?php holyprofweb_pagination(); ?>
        <?php else : ?>
        <div class="no-results-message">
            <h2><?php esc_html_e( 'Nothing found yet', 'holyprofweb' ); ?></h2>
            <p><?php esc_html_e( 'There are no published posts here yet. Try a search or check another section.', 'holyprofweb' ); ?></p>
            <br>
            <?php get_search_form(); ?>
        </div>
        <?php endif; ?>
    </main>

</div>
<?php else : ?>
<main id="primary" class="site-main">
    <div class="container">

        <?php if ( is_home() && ! is_front_page() ) : ?>
        <header class="archive-header">
            <h1 class="archive-title"><?php echo esc_html( single_post_title( '', false ) ); ?></h1>
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
                    <?php $categories = get_the_category(); ?>
                    <?php if ( ! empty( $categories ) ) : ?>
                        <span class="post-card-category">
                            <a href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>">
                                <?php echo esc_html( $categories[0]->name ); ?>
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
            <p><?php esc_html_e( 'It seems we can’t find what you’re looking for. Try a search below.', 'holyprofweb' ); ?></p>
            <br>
            <?php get_search_form(); ?>
        </div>
        <?php endif; ?>

    </div>
</main>
<?php endif; ?>

<?php get_footer(); ?>
