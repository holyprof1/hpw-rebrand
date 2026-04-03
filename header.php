<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">

    <?php holyprofweb_render_ad( 'header', 'ad-header-banner' ); ?>

    <header id="masthead" class="site-header" role="banner">
        <div class="header-inner">
            <div class="site-logo">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="site-logo-link">
                        <?php
                        $logo_png = get_template_directory() . '/assets/images/logo.png';
                        $logo_svg = get_template_directory() . '/assets/images/logo.svg';
                        if ( file_exists( $logo_png ) ) :
                        ?>
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>"
                             alt="<?php bloginfo( 'name' ); ?>" width="360" height="96" loading="eager" />
                        <?php elseif ( file_exists( $logo_svg ) ) : ?>
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.svg' ); ?>"
                             alt="<?php bloginfo( 'name' ); ?>" width="360" height="96" loading="eager" />
                        <?php else : ?>
                        <span class="site-logo-text"><?php bloginfo( 'name' ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>

            <nav id="site-navigation" class="primary-navigation" role="navigation"
                 aria-label="<?php esc_attr_e( 'Primary Navigation', 'holyprofweb' ); ?>">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'fallback_cb'    => 'holyprofweb_fallback_menu',
                ) );
                ?>
            </nav>

            <div class="header-actions">
                <button class="theme-toggle" id="theme-toggle"
                        type="button"
                        aria-label="<?php esc_attr_e( 'Toggle dark mode', 'holyprofweb' ); ?>"
                        aria-pressed="false">
                    <span class="theme-toggle-icon theme-toggle-icon--sun" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4.2"></circle>
                            <path d="M12 2.5v2.2M12 19.3v2.2M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.9 19.1l1.6-1.6M17.5 6.5l1.6-1.6"></path>
                        </svg>
                    </span>
                    <span class="theme-toggle-icon theme-toggle-icon--moon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.3 14.8A8.5 8.5 0 0 1 9.2 3.7a8.5 8.5 0 1 0 11.1 11.1Z"></path>
                        </svg>
                    </span>
                    <span class="theme-toggle-label"><?php esc_html_e( 'Theme', 'holyprofweb' ); ?></span>
                </button>

                <button class="header-search-trigger" id="header-search-trigger"
                        aria-label="<?php esc_attr_e( 'Open search', 'holyprofweb' ); ?>"
                        aria-expanded="false" aria-controls="live-search-overlay">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.25"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span><?php esc_html_e( 'Search', 'holyprofweb' ); ?></span>
                </button>

                <a href="<?php echo esc_url( home_url( '/submit/' ) ); ?>" class="header-cta">
                    <?php esc_html_e( 'Review Now', 'holyprofweb' ); ?>
                </a>

                <button class="menu-toggle" id="menu-toggle"
                        aria-controls="site-navigation"
                        aria-expanded="false"
                        aria-label="<?php esc_attr_e( 'Toggle menu', 'holyprofweb' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.25"
                         stroke-linecap="round" aria-hidden="true">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div id="live-search-overlay" class="live-search-overlay" role="dialog"
         aria-label="<?php esc_attr_e( 'Search', 'holyprofweb' ); ?>"
         aria-hidden="true">
        <div class="live-search-inner">
            <div class="live-search-bar">
                <svg class="live-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.25" stroke-linecap="round"
                     stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="search" id="live-search-input" class="live-search-input"
                       placeholder="<?php esc_attr_e( 'Search reviews, companies, salaries...', 'holyprofweb' ); ?>"
                       autocomplete="off" aria-autocomplete="list"
                       aria-controls="live-search-results"
                       spellcheck="false" />
                <button class="live-search-close" id="live-search-close"
                        aria-label="<?php esc_attr_e( 'Close search', 'holyprofweb' ); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.25" stroke-linecap="round"
                         aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div id="live-search-results" class="live-search-results" role="listbox"
                 aria-label="<?php esc_attr_e( 'Search suggestions', 'holyprofweb' ); ?>"></div>

            <div class="live-search-default" id="live-search-default">
                <?php $trending = holyprofweb_get_trending_searches( 6 ); ?>
                <?php if ( ! empty( $trending ) ) : ?>
                <p class="live-search-label"><?php esc_html_e( 'Trending searches', 'holyprofweb' ); ?></p>
                <div class="live-search-trending">
                    <?php foreach ( $trending as $item ) : ?>
                    <a href="<?php echo esc_url( home_url( '/?s=' . urlencode( $item['term'] ) ) ); ?>"
                       class="live-search-trending-pill"><?php echo esc_html( $item['term'] ); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <p class="live-search-label live-search-label--cats"><?php esc_html_e( 'Browse categories', 'holyprofweb' ); ?></p>
                <div class="live-search-cats">
                    <?php
                    $cats = holyprofweb_get_visible_categories( array(
                        'parent' => 0,
                        'number' => 6,
                    ) );
                    if ( ! is_wp_error( $cats ) ) :
                        foreach ( $cats as $cat ) :
                    ?>
                    <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"
                       class="live-search-cat-chip"><?php echo esc_html( $cat->name ); ?></a>
                    <?php endforeach; endif; ?>
                </div>

                <div class="live-search-recent-wrap" id="live-search-recent-wrap" hidden>
                    <p class="live-search-label"><?php esc_html_e( 'Recent searches', 'holyprofweb' ); ?></p>
                    <div class="live-search-trending" id="live-search-recent"></div>
                </div>
            </div>
        </div>
        <div class="live-search-backdrop" id="live-search-backdrop"></div>
    </div>

<?php
function holyprofweb_fallback_menu() {
    $items = array(
        'Home'      => home_url( '/' ),
        'Reviews'   => home_url( '/category/reviews/' ),
        'Companies' => home_url( '/category/companies/' ),
        'Biography' => home_url( '/category/biography/' ),
        'Blog'      => holyprofweb_get_blog_url(),
        'Contact'   => home_url( '/contact/' ),
    );
    echo '<ul id="primary-menu">';
    foreach ( $items as $label => $url ) {
        printf(
            '<li><a href="%s">%s</a></li>',
            esc_url( $url ),
            esc_html( $label )
        );
    }
    echo '</ul>';
}
