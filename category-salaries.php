<?php
/**
 * Category Template: Salaries
 */

get_header();

$term      = get_queried_object();
$term_name = $term ? $term->name : __( 'Salaries', 'holyprofweb' );
$found     = (int) $GLOBALS['wp_query']->found_posts;
$salary_focus_label = $term instanceof WP_Term && $term->description
    ? wp_strip_all_tags( $term->description )
    : __( 'Local, remote, and role-specific pay signals', 'holyprofweb' );
$card_size = holyprofweb_get_image_size_dimensions( 'holyprofweb-card' );
$fallback  = new WP_Query(
    array(
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'category_name'  => 'salaries',
        'no_found_rows'  => true,
    )
);
?>

<div class="platform-wrap">
    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <header class="archive-header archive-header--salary">
            <div class="archive-header-eyebrow">
                <span class="archive-header-icon" aria-hidden="true">&#128176;</span>
                <span class="archive-header-category-label"><?php esc_html_e( 'Salary Intelligence', 'holyprofweb' ); ?></span>
            </div>
            <h1 class="archive-title"><?php echo esc_html( $term_name ); ?></h1>
            <p class="archive-description-default"><?php esc_html_e( 'Browse salary ranges, work-life scores, and company-specific compensation pages built for quick comparison.', 'holyprofweb' ); ?></p>
            <div class="archive-header-meta">
                <?php if ( $found > 0 ) : ?>
                <span class="archive-header-stat">
                    <strong><?php echo esc_html( holyprofweb_format_display_count( $found ) ); ?></strong>
                    <?php echo esc_html( _n( 'salary page', 'salary pages', $found, 'holyprofweb' ) ); ?>
                </span>
                <?php endif; ?>
                <span class="archive-header-stat">
                    <strong><?php esc_html_e( 'Updated regularly', 'holyprofweb' ); ?></strong>
                </span>
                <span class="archive-header-stat archive-header-stat--tag"><?php echo esc_html( $salary_focus_label ); ?></span>
            </div>
        </header>

        <?php if ( have_posts() ) : ?>
        <div class="section-header">
            <span class="section-title"><?php echo esc_html( sprintf( _n( '%s salary page', '%s salary pages', $found, 'holyprofweb' ), holyprofweb_format_display_count( $found ) ) ); ?></span>
            <span class="section-meta"><?php esc_html_e( 'Updated automatically', 'holyprofweb' ); ?></span>
        </div>

        <div class="salary-list">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php
                $pid        = get_the_ID();
                $thumb      = holyprofweb_get_post_image_url( $pid, 'holyprofweb-card' );
                $rating     = holyprofweb_get_post_rating( $pid );
                $r_count    = holyprofweb_get_review_count( $pid );
                $company    = get_the_title();
                $excerpt    = get_the_excerpt();
                $role       = get_post_meta( $pid, '_hpw_salary_role', true ) ?: trim( preg_replace( '/\s+salary.*$/i', '', $company ) );
                $sal_min    = get_post_meta( $pid, '_hpw_salary_min', true );
                $sal_max    = get_post_meta( $pid, '_hpw_salary_max', true );
                $sal_curr   = get_post_meta( $pid, '_hpw_salary_currency', true ) ?: 'NGN';
                $sal_period = get_post_meta( $pid, '_hpw_salary_period', true );
                $work_score = get_post_meta( $pid, '_hpw_work_score', true );
                ?>
            <article class="salary-card">
                <a href="<?php the_permalink(); ?>" class="salary-card-logo-wrap">
                    <img src="<?php echo esc_attr( $thumb ); ?>" alt="<?php echo esc_attr( $company ); ?>" class="<?php echo esc_attr( holyprofweb_get_post_image_class( $pid, 'salary-card-logo' ) ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" />
                </a>
                <div class="salary-card-body">
                    <div class="salary-card-top">
                        <div>
                            <p class="salary-card-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( $company ); ?></a></p>
                            <?php if ( $role ) : ?><span class="salary-card-role-label"><?php echo esc_html( $role ); ?></span><?php endif; ?>
                        </div>
                        <div class="salary-card-score-wrap">
                            <?php if ( $rating > 0 ) : ?>
                            <span class="salary-card-score"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></span>
                            <span class="salary-card-score-label"><?php echo holyprofweb_render_stars( $rating ); ?><span class="salary-card-count"><?php echo esc_html( holyprofweb_format_display_count( $r_count ) ); ?> <?php esc_html_e( 'reviews', 'holyprofweb' ); ?></span></span>
                            <?php else : ?>
                            <span class="salary-card-no-reviews"><?php esc_html_e( 'No reviews yet', 'holyprofweb' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="salary-card-range-row">
                        <span class="salary-card-range">
                            <?php
                            if ( '' !== (string) $sal_min && '' !== (string) $sal_max ) {
                                echo esc_html( $sal_curr . number_format_i18n( $sal_min ) . ' - ' . $sal_curr . number_format_i18n( $sal_max ) . $sal_period );
                            } elseif ( '' !== (string) $sal_min ) {
                                echo esc_html( $sal_curr . number_format_i18n( $sal_min ) . $sal_period );
                            } elseif ( '' !== (string) $sal_max ) {
                                echo esc_html( $sal_curr . number_format_i18n( $sal_max ) . $sal_period );
                            } else {
                                esc_html_e( 'Salary range pending update', 'holyprofweb' );
                            }
                            ?>
                        </span>
                    </div>

                    <div class="salary-card-badges">
                        <span class="salary-badge"><?php esc_html_e( 'Currency', 'holyprofweb' ); ?>: <?php echo esc_html( $sal_curr ); ?></span>
                        <?php if ( $work_score ) : ?><span class="salary-badge"><?php esc_html_e( 'Work-Life', 'holyprofweb' ); ?>: <?php echo esc_html( number_format_i18n( (float) $work_score, 1 ) ); ?>/5</span><?php endif; ?>
                    </div>
                    <?php if ( $excerpt ) : ?><p class="salary-card-excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 16, '...' ) ); ?></p><?php endif; ?>
                </div>
                <div class="salary-card-cta">
                    <a href="<?php the_permalink(); ?>" class="salary-card-view"><?php esc_html_e( 'View Page', 'holyprofweb' ); ?></a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>

        <?php holyprofweb_pagination(); ?>
        <?php else : ?>
        <div class="no-results-message no-results-message--salary">
            <h2><?php esc_html_e( 'No salary pages yet', 'holyprofweb' ); ?></h2>
            <p><?php esc_html_e( 'We are still building this salary section. Here are other salary pages visitors can check right now.', 'holyprofweb' ); ?></p>
            <div class="post-grid post-grid--compact">
                <?php if ( $fallback->have_posts() ) : while ( $fallback->have_posts() ) : $fallback->the_post(); ?>
                <article class="post-card">
                    <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                        <img src="<?php echo esc_attr( holyprofweb_get_post_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" />
                    </a>
                    <div class="post-card-body">
                        <h3 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h3>
                        <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                    </div>
                </article>
                <?php endwhile; wp_reset_postdata(); else : ?>
                <div class="post-card post-card--demo">
                    <div class="post-card-thumb"><img src="<?php echo esc_url( holyprofweb_placeholder_url() ); ?>" alt="" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" /></div>
                    <div class="post-card-body">
                        <h3 class="post-card-title"><?php esc_html_e( 'Salary pages will appear here once content is published.', 'holyprofweb' ); ?></h3>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>
