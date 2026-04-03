<?php
/**
 * 404 Template — HolyprofWeb
 */

get_header();
?>

<div class="platform-wrap">

    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <div class="error-404-wrap">

            <div class="error-404-icon" aria-hidden="true">404</div>

            <h1 class="error-404-title">
                <?php esc_html_e( 'Page not found', 'holyprofweb' ); ?>
            </h1>

            <p class="error-404-message">
                <?php esc_html_e( "The page you're looking for doesn't exist, was moved, or may have been removed.", 'holyprofweb' ); ?>
            </p>

            <div class="error-404-actions">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-primary">
                    <?php esc_html_e( 'Go to Homepage', 'holyprofweb' ); ?>
                </a>
                <button class="btn-secondary header-search-trigger" id="header-search-trigger-404" type="button"
                        onclick="document.getElementById('header-search-trigger').click();">
                    <?php esc_html_e( 'Search the Site', 'holyprofweb' ); ?>
                </button>
            </div>

            <!-- Quick category links -->
            <div class="error-404-cats">
                <p class="error-404-cats-label"><?php esc_html_e( 'Browse our topics:', 'holyprofweb' ); ?></p>
                <div class="error-404-cats-row">
                    <?php
                    $nav_cats = array(
                        'reviews'   => array( 'label' => 'Reviews',   'icon' => '★' ),
                        'companies' => array( 'label' => 'Companies', 'icon' => '🏢' ),
                        'salaries'  => array( 'label' => 'Salaries',  'icon' => '💰' ),
                        'biography' => array( 'label' => 'Biography', 'icon' => '👤' ),
                        'reports'   => array( 'label' => 'Reports',   'icon' => '📋' ),
                    );
                    foreach ( $nav_cats as $slug => $data ) :
                        $term = get_term_by( 'slug', $slug, 'category' );
                        if ( ! $term || is_wp_error( $term ) ) continue;
                    ?>
                    <a href="<?php echo esc_url( get_category_link( $term->term_id ) ); ?>" class="error-404-cat-chip">
                        <span aria-hidden="true"><?php echo $data['icon']; // phpcs:ignore -- safe literal ?></span>
                        <?php echo esc_html( $data['label'] ); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>

</div><!-- .platform-wrap -->

<?php get_footer(); ?>
