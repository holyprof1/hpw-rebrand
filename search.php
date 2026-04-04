<?php
/**
 * Search Results — HolyprofWeb
 */

get_header();

$search_term = get_search_query();
$found_posts = (int) $GLOBALS['wp_query']->found_posts;
$card_size   = holyprofweb_get_image_size_dimensions( 'holyprofweb-card' );
$latest      = new WP_Query( array(
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'no_found_rows'  => true,
) );

if ( 0 === $found_posts && ! empty( $search_term ) ) {
    $existing = holyprofweb_find_draft_by_title( $search_term );
    $search_log = holyprofweb_get_search_log();
    $search_key = md5( strtolower( trim( $search_term ) ) );
    $search_row = isset( $search_log[ $search_key ] ) ? $search_log[ $search_key ] : null;

    if ( get_option( 'hpw_search_auto_draft', 0 ) && ! $existing && $search_row && (int) $search_row['count'] >= holyprofweb_get_search_alert_threshold() ) {
        $reviews = get_term_by( 'slug', 'reviews', 'category' );
        $draft_id = wp_insert_post( array(
            'post_title'    => sanitize_text_field( $search_term ),
            'post_status'   => 'draft',
            'post_type'     => 'post',
            'post_category' => $reviews ? array( (int) $reviews->term_id ) : array(),
            'post_author'   => 1,
        ) );

        if ( $draft_id && ! is_wp_error( $draft_id ) ) {
            $search_log[ $search_key ]['auto_draft_id']    = (int) $draft_id;
            $search_log[ $search_key ]['draft_created_at'] = time();
            update_option( 'holyprofweb_search_log', $search_log, false );
        }
    }
}
?>

<div class="platform-wrap">
    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <header class="search-header search-header--cards">
            <h1 class="search-heading"><?php esc_html_e( 'Search Results', 'holyprofweb' ); ?><?php if ( $search_term ) : ?>: <span class="search-query">&ldquo;<?php echo esc_html( $search_term ); ?>&rdquo;</span><?php endif; ?></h1>
            <p class="search-count">
                <?php
                echo esc_html(
                    $found_posts > 0
                        ? sprintf( _n( '%s result found', '%s results found', $found_posts, 'holyprofweb' ), holyprofweb_format_display_count( $found_posts ) )
                        : __( 'No exact matches yet. Showing fallback paths so the page never feels empty.', 'holyprofweb' )
                );
                ?>
            </p>
        </header>

        <?php if ( have_posts() ) : ?>
        <div class="tp-list tp-list--cards">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php
                $cats    = get_the_category();
                $rating  = holyprofweb_get_post_rating( get_the_ID() );
                $r_count = holyprofweb_get_review_count( get_the_ID() );
                $thumb   = holyprofweb_get_post_image_url( get_the_ID(), 'holyprofweb-card' );
                ?>
            <article class="tp-card">
                <a href="<?php the_permalink(); ?>" class="tp-logo-wrap">
                    <img src="<?php echo esc_attr( $thumb ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" class="tp-logo" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" />
                </a>
                <div class="tp-body">
                    <div class="tp-rating-row">
                        <?php if ( ! empty( $cats ) ) : ?><span class="tp-cat-badge"><?php echo esc_html( $cats[0]->name ); ?></span><?php endif; ?>
                        <?php if ( $rating > 0 ) : ?><span class="tp-rating-score"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?> ★ (<?php echo esc_html( $r_count ); ?>)</span><?php endif; ?>
                    </div>
                    <h2 class="tp-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h2>
                    <p class="tp-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                </div>
            </article>
            <?php endwhile; ?>
        </div>

        <?php holyprofweb_pagination(); ?>
        <?php else : ?>
        <section class="search-fallback-section">
            <div class="search-no-results">
                <div class="search-no-results-inner">
                    <h2><?php esc_html_e( 'Nothing published for this search yet', 'holyprofweb' ); ?></h2>
                    <p class="search-no-results-note"><?php esc_html_e( 'We logged this search for editorial review. In the meantime, here are recent posts you can check next.', 'holyprofweb' ); ?></p>
                    <div class="search-no-results-search"><?php get_search_form(); ?></div>
                </div>
            </div>

            <div class="post-grid post-grid--compact">
                <?php if ( $latest->have_posts() ) : while ( $latest->have_posts() ) : $latest->the_post(); ?>
                <article class="post-card">
                    <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                        <img src="<?php echo esc_attr( holyprofweb_get_post_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" />
                    </a>
                    <div class="post-card-body">
                        <h3 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h3>
                        <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                    </div>
                </article>
                <?php endwhile; wp_reset_postdata(); endif; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>
</div>

<?php get_footer(); ?>
