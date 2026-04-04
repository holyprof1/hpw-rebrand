<?php
/**
 * Archive Template — HolyprofWeb
 * Trustpilot-style listing with logo + rating + excerpt.
 */

get_header();

$card_size = holyprofweb_get_image_size_dimensions( 'holyprofweb-card' );
?>

<div class="platform-wrap">

    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">

        <?php
        $arc_term  = get_queried_object();
        $arc_icons = array( 'reviews' => '★', 'companies' => '🏢', 'salaries' => '💰', 'biography' => '👤', 'reports' => '📋' );
        $arc_icon  = ( $arc_term instanceof WP_Term ) ? ( $arc_icons[ $arc_term->slug ] ?? '' ) : '';
        $arc_found = (int) $GLOBALS['wp_query']->found_posts;
        ?>
        <header class="archive-header<?php echo $arc_icon ? ' archive-header--branded' : ''; ?>">
            <?php if ( $arc_icon ) : ?>
            <div class="archive-header-eyebrow">
                <span class="archive-header-icon" aria-hidden="true"><?php echo $arc_icon; // phpcs:ignore -- safe literal ?></span>
            </div>
            <?php endif; ?>
            <?php the_archive_title( '<h1 class="archive-title">', '</h1>' ); ?>
            <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
            <?php if ( $arc_found > 0 ) : ?>
            <div class="archive-header-meta">
                <span class="archive-header-stat">
                    <strong><?php echo esc_html( holyprofweb_format_display_count( $arc_found ) ); ?></strong>
                    <?php echo esc_html( _n( 'result', 'results', $arc_found, 'holyprofweb' ) ); ?>
                </span>
            </div>
            <?php endif; ?>
        </header>

        <?php holyprofweb_render_ad_format( 'leaderboard', 'archive_inline', 'ad-archive-inline' ); ?>

        <?php if ( have_posts() ) : ?>

        <div class="section-header">
            <span class="section-title">
                <?php
                $found = $GLOBALS['wp_query']->found_posts;
                echo esc_html( sprintf(
                    _n( '%s result', '%s results', $found, 'holyprofweb' ),
                    holyprofweb_format_display_count( $found )
                ) );
                ?>
            </span>
        </div>

        <!-- Trustpilot-style listing -->
        <div class="tp-list">
            <?php while ( have_posts() ) : the_post();
                $cats    = get_the_category();
                $rating  = holyprofweb_get_post_rating( get_the_ID() );
                $r_count = holyprofweb_get_review_count( get_the_ID() );
                $thumb   = holyprofweb_get_post_card_image_url( get_the_ID() );
            ?>
            <a href="<?php the_permalink(); ?>" class="tp-card">

                <!-- Logo / thumbnail — always shown -->
                <img src="<?php echo esc_attr( $thumb ); ?>"
                     alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>"
                     class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID(), 'tp-logo' ) ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" />

                <!-- Body -->
                <div class="tp-body">
                    <p class="tp-title"><?php holyprofweb_the_decoded_title(); ?></p>
                    <p class="tp-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

                    <div class="tp-rating-row">
                        <?php if ( $rating > 0 ) : ?>
                            <?php echo holyprofweb_render_stars( $rating ); ?>
                            <span class="tp-rating-score"><?php echo esc_html( $rating ); ?></span>
                            <span style="font-size:.72rem;color:var(--color-text-muted);">(<?php echo esc_html( $r_count ); ?>)</span>
                        <?php else : ?>
                            <span style="font-size:.78rem;color:var(--color-text-muted);">No reviews yet</span>
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
            <p><?php esc_html_e( 'There are no posts in this category yet. Check back soon.', 'holyprofweb' ); ?></p>
            <br>
            <?php get_search_form(); ?>
        </div>

        <?php endif; ?>

    </main>

</div><!-- .platform-wrap -->

<?php get_footer(); ?>
