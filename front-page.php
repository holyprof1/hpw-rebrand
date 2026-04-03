<?php
/**
 * Front Page Template — HolyprofWeb
 */

get_header();

$total_posts      = (int) wp_count_posts()->publish;
$total_reviews    = (int) get_comments( array( 'type' => 'review', 'status' => 'approve', 'count' => true ) );
$companies_term   = get_term_by( 'slug', 'companies', 'category' );
$total_companies  = $companies_term && ! is_wp_error( $companies_term ) ? (int) $companies_term->count : 0;
$trending_searches = holyprofweb_get_trending_searches( 8 );
$card_size        = holyprofweb_get_image_size_dimensions( 'holyprofweb-card' );

$latest_query = new WP_Query( array(
    'posts_per_page' => 8,
    'post_status'    => 'publish',
    'no_found_rows'  => true,
) );

$just_added_query = new WP_Query( array(
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
) );

$reports_term      = get_term_by( 'slug', 'reports', 'category' );
$blog_opinion_term = get_term_by( 'slug', 'blog-opinion', 'category' );
$blog_term_ids     = array_values( array_filter( array(
    $reports_term ? (int) $reports_term->term_id : 0,
    $blog_opinion_term ? (int) $blog_opinion_term->term_id : 0,
) ) );
$guides_query = new WP_Query( array(
    'posts_per_page' => 4,
    'post_status'    => 'publish',
    'no_found_rows'  => true,
    'category__in'   => $blog_term_ids,
) );

$live_reviews = get_comments( array(
    'type'   => 'review',
    'status' => 'approve',
    'number' => 6,
) );
if ( ! empty( $live_reviews ) ) {
    usort(
        $live_reviews,
        static function( $a, $b ) {
            $a_verified = holyprofweb_is_comment_verified( $a->comment_ID ) ? 1 : 0;
            $b_verified = holyprofweb_is_comment_verified( $b->comment_ID ) ? 1 : 0;

            if ( $a_verified !== $b_verified ) {
                return $b_verified <=> $a_verified;
            }

            $a_rating = (int) get_comment_meta( $a->comment_ID, 'rating', true );
            $b_rating = (int) get_comment_meta( $b->comment_ID, 'rating', true );
            if ( $a_rating !== $b_rating ) {
                return $b_rating <=> $a_rating;
            }

            return strtotime( $b->comment_date_gmt ) <=> strtotime( $a->comment_date_gmt );
        }
    );
}
$featured_topics = holyprofweb_get_frontpage_topic_categories( 6 );
$topic_descriptions = array(
    'companies'       => __( 'Profiles, reviews, salary gist, and what people say before you apply or buy.', 'holyprofweb' ),
    'fintech'         => __( 'Payment apps, money tools, transfer services, and fintech trust checks.', 'holyprofweb' ),
    'banks'           => __( 'Bank reviews, account experience, charges, and customer support signals.', 'holyprofweb' ),
    'startups'        => __( 'Fast-growing teams, work culture clues, and early-stage company context.', 'holyprofweb' ),
    'scam-legit'      => __( 'Quick red flags, legitimacy checks, and what to verify before you commit.', 'holyprofweb' ),
    'app-reviews'     => __( 'App quality, user experience, complaints, and overall usefulness in one place.', 'holyprofweb' ),
    'website-reviews' => __( 'Website trust, service quality, fees, and warning signs worth checking.', 'holyprofweb' ),
    'loan-finance'    => __( 'Loan apps, finance platforms, interest concerns, and borrowing reality checks.', 'holyprofweb' ),
    'shopping'        => __( 'Online store reputation, delivery stories, and whether the deals make sense.', 'holyprofweb' ),
    'scholarship'     => __( 'Scholarship opportunities, deadlines, and practical guidance for applicants.', 'holyprofweb' ),
    'tech'            => __( 'Tech companies, tools, products, and industry topics people keep researching.', 'holyprofweb' ),
    'reports'         => __( 'Broader explainers, rankings, and context-driven posts from the blog side.', 'holyprofweb' ),
    'blog-opinion'    => __( 'Analysis, commentary, and deeper takes on trends shaping decisions.', 'holyprofweb' ),
);
?>

<section class="hero-section hero-section--home" aria-labelledby="hero-title">
    <div class="hero-inner">
        <div class="hero-eyebrow"><?php esc_html_e( 'Research first. Decide with confidence.', 'holyprofweb' ); ?></div>
        <h1 id="hero-title" class="hero-title"><?php esc_html_e( 'Discover what people are saying before you decide', 'holyprofweb' ); ?></h1>
        <p class="hero-subtitle"><?php esc_html_e( 'Search trusted reviews, salary stories, company profiles, and practical guides in one place.', 'holyprofweb' ); ?></p>
        <div class="hero-search-wrap">
            <?php get_search_form(); ?>
        </div>
        <div class="hero-quick-links" aria-label="<?php esc_attr_e( 'Homepage quick actions', 'holyprofweb' ); ?>">
            <a href="<?php echo esc_url( home_url( '/category/reviews/' ) ); ?>" class="hero-quick-link">
                <span class="hero-quick-label"><?php esc_html_e( 'Browse Reviews', 'holyprofweb' ); ?></span>
                <span class="hero-quick-copy"><?php esc_html_e( 'Real experiences from apps, platforms, and services.', 'holyprofweb' ); ?></span>
            </a>
            <a href="<?php echo esc_url( home_url( '/category/companies/' ) ); ?>" class="hero-quick-link">
                <span class="hero-quick-label"><?php esc_html_e( 'Browse Companies', 'holyprofweb' ); ?></span>
                <span class="hero-quick-copy"><?php esc_html_e( 'Check salary gist, company profile, and reputation fast.', 'holyprofweb' ); ?></span>
            </a>
            <a href="<?php echo esc_url( home_url( '/submit/' ) ); ?>" class="hero-quick-link">
                <span class="hero-quick-label"><?php esc_html_e( 'Submit a Report', 'holyprofweb' ); ?></span>
                <span class="hero-quick-copy"><?php esc_html_e( 'Add a review, correction, complaint, or warning signal.', 'holyprofweb' ); ?></span>
            </a>
        </div>
    </div>
</section>

<?php holyprofweb_render_ad_format( 'leaderboard', 'front_inline', 'ad-front-inline' ); ?>

<div class="stats-bar" aria-label="Platform statistics">
    <div class="stats-bar-inner">
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html( holyprofweb_format_display_count( max( 1, $total_posts ) ) ); ?></span>
            <span class="stat-label"><?php esc_html_e( 'Posts', 'holyprofweb' ); ?></span>
        </div>
        <div class="stat-divider" aria-hidden="true"></div>
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html( holyprofweb_format_display_count( max( 1, $total_reviews ) ) ); ?></span>
            <span class="stat-label"><?php esc_html_e( 'Reviews', 'holyprofweb' ); ?></span>
        </div>
        <div class="stat-divider" aria-hidden="true"></div>
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html( holyprofweb_format_display_count( max( 1, $total_companies ) ) ); ?></span>
            <span class="stat-label"><?php esc_html_e( 'Companies', 'holyprofweb' ); ?></span>
        </div>
    </div>
</div>

<div class="platform-wrap">
    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <section class="front-section front-section--carousel" aria-labelledby="trending-now-heading">
            <div class="section-header">
                <h2 id="trending-now-heading" class="section-title"><?php esc_html_e( 'Trending Now', 'holyprofweb' ); ?></h2>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="section-link"><?php esc_html_e( 'Check now', 'holyprofweb' ); ?></a>
            </div>

            <div class="post-carousel">
                <?php
                if ( $latest_query->have_posts() ) :
                    while ( $latest_query->have_posts() ) :
                        $latest_query->the_post();
                        $cats = get_the_category();
                ?>
                <article class="post-card post-card--carousel">
                    <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                        <img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" />
                    </a>
                    <div class="post-card-body">
                        <?php if ( ! empty( $cats ) ) : ?>
                        <span class="post-card-category"><a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a></span>
                        <?php endif; ?>
                        <h3 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h3>
                    </div>
                </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    foreach ( array(
                        array( 'title' => 'PalmPay Review', 'category' => 'Reviews' ),
                        array( 'title' => 'Flutterwave Company Profile', 'category' => 'Companies' ),
                        array( 'title' => 'Best Loan Apps', 'category' => 'Reports' ),
                    ) as $fallback ) :
                ?>
                <article class="post-card post-card--carousel post-card--demo">
                    <div class="post-card-thumb"><img src="<?php echo esc_url( holyprofweb_placeholder_url() ); ?>" alt="" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" /></div>
                    <div class="post-card-body">
                        <span class="post-card-category"><?php echo esc_html( $fallback['category'] ); ?></span>
                        <h3 class="post-card-title"><?php echo esc_html( $fallback['title'] ); ?></h3>
                    </div>
                </article>
                <?php endforeach; endif; ?>
            </div>
        </section>

        <section class="front-section front-section--topics" aria-labelledby="category-strip-heading">
            <div class="section-header">
                <h2 id="category-strip-heading" class="section-title"><?php esc_html_e( 'Explore Companies and Topics', 'holyprofweb' ); ?></h2>
                <?php if ( $companies_term && ! is_wp_error( $companies_term ) ) : ?>
                <a href="<?php echo esc_url( get_category_link( $companies_term->term_id ) ); ?>" class="section-link"><?php esc_html_e( 'See all topics', 'holyprofweb' ); ?></a>
                <?php endif; ?>
            </div>
            <div class="topic-hub-grid">
                <?php
                if ( ! empty( $featured_topics ) ) :
                    foreach ( $featured_topics as $cat ) :
                        $topic_description = isset( $topic_descriptions[ $cat->slug ] ) ? $topic_descriptions[ $cat->slug ] : __( 'Browse the latest posts, guides, and discussions in this topic.', 'holyprofweb' );
                ?>
                <article class="topic-hub-card">
                    <span class="topic-hub-count"><?php echo esc_html( holyprofweb_format_display_count( $cat->count ) ); ?> <?php esc_html_e( 'posts', 'holyprofweb' ); ?></span>
                    <h3 class="topic-hub-title"><a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php echo esc_html( $cat->name ); ?></a></h3>
                    <p class="topic-hub-description"><?php echo esc_html( $topic_description ); ?></p>
                    <a class="topic-hub-link" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php esc_html_e( 'Open topic', 'holyprofweb' ); ?></a>
                </article>
                <?php endforeach; else : ?>
                <article class="topic-hub-card topic-hub-card--empty">
                    <span class="topic-hub-count"><?php esc_html_e( 'Topics', 'holyprofweb' ); ?></span>
                    <h3 class="topic-hub-title"><?php esc_html_e( 'Topic categories will appear here', 'holyprofweb' ); ?></h3>
                    <p class="topic-hub-description"><?php esc_html_e( 'Add or publish categories with posts and this section will update automatically.', 'holyprofweb' ); ?></p>
                </article>
                <?php endif; ?>
            </div>
        </section>

        <section class="front-section front-section--carousel" aria-labelledby="just-added-heading">
            <div class="section-header">
                <h2 id="just-added-heading" class="section-title"><?php esc_html_e( 'Just Added', 'holyprofweb' ); ?></h2>
                <a href="<?php echo esc_url( holyprofweb_get_blog_url() ); ?>" class="section-link"><?php esc_html_e( 'Browse all', 'holyprofweb' ); ?></a>
            </div>

            <div class="post-carousel">
                <?php
                if ( $just_added_query->have_posts() ) :
                    while ( $just_added_query->have_posts() ) :
                        $just_added_query->the_post();
                        $cats    = get_the_category();
                        $rating  = holyprofweb_get_post_rating( get_the_ID() );
                        $reviews = holyprofweb_get_review_count( get_the_ID() );
                ?>
                <article class="post-card">
                    <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                        <img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" />
                    </a>
                    <div class="post-card-body">
                        <div class="post-card-meta">
                            <?php if ( ! empty( $cats ) ) : ?>
                            <span class="post-card-category"><a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a></span>
                            <?php endif; ?>
                            <span class="post-card-date"><?php echo esc_html( get_the_date() ); ?></span>
                        </div>
                        <h3 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h3>
                        <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                        <div class="post-card-footer">
                            <span class="post-card-rating-inline"><?php echo $rating > 0 ? wp_kses_post( holyprofweb_render_stars( $rating ) ) : esc_html__( 'No ratings yet', 'holyprofweb' ); ?></span>
                            <a href="<?php the_permalink(); ?>" class="post-card-readmore"><?php echo $reviews > 0 ? esc_html__( 'Check now', 'holyprofweb' ) : esc_html__( 'Review now', 'holyprofweb' ); ?></a>
                        </div>
                    </div>
                </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </section>

        <section class="front-section" aria-labelledby="live-reviews-heading">
            <div class="section-header">
                <h2 id="live-reviews-heading" class="section-title"><?php esc_html_e( 'Live Reviews', 'holyprofweb' ); ?></h2>
                <a href="<?php echo esc_url( home_url( '/category/reviews/' ) ); ?>" class="section-link"><?php esc_html_e( 'All reviews', 'holyprofweb' ); ?></a>
            </div>

            <div class="live-reviews-grid">
                <?php if ( ! empty( $live_reviews ) ) : ?>
                    <?php foreach ( $live_reviews as $review ) : ?>
                        <?php
                        $post_id    = (int) $review->comment_post_ID;
                        $rating     = (int) get_comment_meta( $review->comment_ID, 'rating', true );
                        $is_verified = holyprofweb_is_comment_verified( $review->comment_ID );
                        $review_cats = get_the_category( $post_id );
                        ?>
                    <article class="live-review-card">
                        <div class="live-review-header">
                            <div class="live-review-copy">
                                <div class="live-review-meta">
                                    <strong class="live-review-author"><?php echo esc_html( $review->comment_author ); ?></strong>
                                    <?php if ( $is_verified ) : ?><span class="review-verified-badge"><?php esc_html_e( 'Verified', 'holyprofweb' ); ?></span><?php endif; ?>
                                    <?php if ( ! empty( $review_cats ) ) : ?><span class="live-review-chip"><?php echo esc_html( $review_cats[0]->name ); ?></span><?php endif; ?>
                                </div>
                                <a class="live-review-post" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( holyprofweb_get_decoded_post_title( $post_id ) ); ?></a>
                            </div>
                            <div class="live-review-stars"><?php echo wp_kses_post( holyprofweb_render_stars( $rating ) ); ?></div>
                        </div>
                        <p class="live-review-text"><?php echo esc_html( wp_trim_words( $review->comment_content, 18, '...' ) ); ?></p>
                        <time class="live-review-time" datetime="<?php echo esc_attr( get_comment_date( 'c', $review ) ); ?>"><?php echo esc_html( get_comment_date( 'M j, Y', $review ) ); ?></time>
                    </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <?php foreach ( array_slice( $trending_searches, 0, 3 ) as $item ) : ?>
                    <article class="live-review-card live-review-card--placeholder">
                        <div class="live-review-header">
                            <div class="live-review-copy">
                                <div class="live-review-meta">
                                    <strong class="live-review-author"><?php esc_html_e( 'HolyprofWeb', 'holyprofweb' ); ?></strong>
                                </div>
                                <a class="live-review-post" href="<?php echo esc_url( home_url( '/?s=' . urlencode( $item['term'] ) ) ); ?>"><?php echo esc_html( $item['term'] ); ?></a>
                            </div>
                        </div>
                        <p class="live-review-text"><?php esc_html_e( 'Fresh community reviews will appear here as soon as users submit them.', 'holyprofweb' ); ?></p>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php holyprofweb_render_ad_format( 'rectangle', 'front_inline', 'ad-front-rectangle' ); ?>

        <section class="front-section" aria-labelledby="guides-heading">
            <div class="section-header">
                <h2 id="guides-heading" class="section-title"><?php esc_html_e( 'From the Blog', 'holyprofweb' ); ?></h2>
                <a href="<?php echo esc_url( holyprofweb_get_blog_url() ); ?>" class="section-link"><?php esc_html_e( 'Browse all', 'holyprofweb' ); ?></a>
            </div>

            <div class="post-grid post-grid--compact">
                <?php
                if ( $guides_query->have_posts() ) :
                    while ( $guides_query->have_posts() ) :
                        $guides_query->the_post();
                ?>
                <article class="post-card">
                    <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                        <img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" />
                    </a>
                    <div class="post-card-body">
                        <span class="post-card-category"><?php esc_html_e( 'Reports', 'holyprofweb' ); ?></span>
                        <h3 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h3>
                        <p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                    </div>
                </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    $fallback_posts = get_posts( array(
                        'posts_per_page' => 4,
                        'post_status'    => 'publish',
                        'no_found_rows'  => true,
                    ) );
                    foreach ( $fallback_posts as $post ) :
                        setup_postdata( $post );
                ?>
                <article class="post-card">
                    <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                        <img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" width="<?php echo esc_attr( $card_size['width'] ); ?>" height="<?php echo esc_attr( $card_size['height'] ); ?>" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" />
                    </a>
                    <div class="post-card-body">
                        <span class="post-card-category"><?php esc_html_e( 'Latest', 'holyprofweb' ); ?></span>
                        <h3 class="post-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h3>
                    </div>
                </article>
                <?php endforeach; wp_reset_postdata(); endif; ?>
            </div>
        </section>

    </main>
</div>

<?php
wp_reset_postdata();
get_footer();
