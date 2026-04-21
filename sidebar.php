<?php
/**
 * Sidebar Template — HolyprofWeb
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
    // No widgets registered — show default sidebar content
    holyprofweb_default_sidebar();
    return;
}

dynamic_sidebar( 'sidebar-1' );

/**
 * Default sidebar shown when no widgets are active.
 */
function holyprofweb_default_sidebar() {
    global $post;

    // Recent Posts
    $recent = holyprofweb_get_personalized_query(
        array(
            'posts_per_page' => 5,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            'post__not_in'   => $post ? array( $post->ID ) : array(),
        ),
        5,
        array(
            'scope'  => $post ? 'single-' . (int) $post->ID : 'sidebar',
            'module' => 'sidebar_recent_posts',
        )
    );

    if ( $recent->have_posts() ) :
    ?>
    <div class="widget" data-hpw-rec-module="sidebar_recent_posts">
        <h3 class="widget-title"><?php esc_html_e( 'Recent Posts', 'holyprofweb' ); ?></h3>
        <ul>
            <?php while ( $recent->have_posts() ) : $recent->the_post(); ?>
            <li data-post-id="<?php the_ID(); ?>" data-hpw-rec-pos="<?php echo esc_attr( $recent->current_post + 1 ); ?>">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    </div>
    <?php endif;

    // Categories
    $categories = holyprofweb_get_visible_categories( array(
        'orderby' => 'count',
        'order'   => 'DESC',
        'number'  => 8,
    ) );

    if ( ! empty( $categories ) ) :
    ?>
    <div class="widget">
        <h3 class="widget-title"><?php esc_html_e( 'Categories', 'holyprofweb' ); ?></h3>
        <ul>
            <?php foreach ( $categories as $cat ) : ?>
            <li>
                <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
                    <?php echo esc_html( $cat->name ); ?>
                    <span style="color:#999; font-size:0.75rem; float:right;">
                        <?php echo esc_html( $cat->count ); ?>
                    </span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif;

    // Search
    ?>
    <div class="widget">
        <h3 class="widget-title"><?php esc_html_e( 'Search', 'holyprofweb' ); ?></h3>
        <?php get_search_form(); ?>
    </div>
    <?php
}
