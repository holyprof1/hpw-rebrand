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
    <a class="skip-link" href="#primary"><?php esc_html_e( 'Skip to content', 'holyprofweb' ); ?></a>

    <header id="masthead" class="site-header" role="banner">
        <div class="header-inner">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="brand" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
                <svg class="brand__mark" width="26" height="26" viewBox="0 0 64 64" aria-hidden="true" focusable="false"><path d="M32 4 10 12v16c0 14 10 24 22 32 12-8 22-18 22-32V12L32 4Z" fill="currentColor"/><path d="m20 32 8 8 16-18" fill="none" stroke="#F0A500" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="brand__text">Holyprof<span>web</span></span>
            </a>

            <nav id="site-navigation" class="primary-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'holyprofweb' ); ?>">
                <ul id="primary-menu" class="menu">
                    <?php foreach ( holyprofweb_sections() as $sec_slug => $sec ) : ?>
                    <li class="menu-item"><a href="<?php echo esc_url( holyprofweb_section_url( $sec_slug ) ); ?>"<?php echo ( is_category( $sec_slug ) || ( is_singular( 'post' ) && holyprofweb_post_section( get_queried_object_id() ) === $sec_slug ) ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $sec['label'] ); ?></a></li>
                    <?php endforeach; ?>
                    <li class="menu-item menu-item--about"><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'holyprofweb' ); ?></a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <button class="icon-btn header-search-trigger" id="header-search-trigger" type="button"
                        aria-label="<?php esc_attr_e( 'Open search', 'holyprofweb' ); ?>"
                        aria-expanded="false" aria-controls="live-search-overlay">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
                <button class="icon-btn theme-toggle" id="theme-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'holyprofweb' ); ?>" aria-pressed="false">
                    <svg class="theme-toggle__sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
                    <svg class="theme-toggle__moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.3 14.8A8.5 8.5 0 0 1 9.2 3.7a8.5 8.5 0 1 0 11.1 11.1Z"></path></svg>
                </button>
                <button class="icon-btn menu-toggle" id="menu-toggle" type="button" aria-controls="site-navigation" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle menu', 'holyprofweb' ); ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="3" y1="7" x2="21" y2="7"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="17" x2="21" y2="17"></line></svg>
                </button>
            </div>
        </div>
    </header>

    <div id="live-search-overlay" class="live-search-overlay" role="dialog"
         aria-label="<?php esc_attr_e( 'Search', 'holyprofweb' ); ?>"
         aria-hidden="true" inert>
        <div class="live-search-inner">
            <div class="live-search-bar">
                <svg class="live-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" id="live-search-input" class="live-search-input"
                       placeholder="<?php esc_attr_e( 'Search apps, websites, products, people…', 'holyprofweb' ); ?>"
                       autocomplete="off" aria-controls="live-search-results" spellcheck="false" />
                <button class="live-search-close" id="live-search-close" type="button" aria-label="<?php esc_attr_e( 'Close search', 'holyprofweb' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div id="live-search-results" class="live-search-results" aria-label="<?php esc_attr_e( 'Search suggestions', 'holyprofweb' ); ?>"></div>
            <div class="live-search-default" id="live-search-default">
                <p class="live-search-label"><?php esc_html_e( 'Browse sections', 'holyprofweb' ); ?></p>
                <div class="live-search-cats">
                    <?php foreach ( holyprofweb_sections() as $sec_slug => $sec ) : ?>
                    <a href="<?php echo esc_url( holyprofweb_section_url( $sec_slug ) ); ?>" class="live-search-cat-chip"><?php echo esc_html( $sec['label'] ); ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="live-search-recent-wrap" id="live-search-recent-wrap" hidden>
                    <p class="live-search-label"><?php esc_html_e( 'Recent searches', 'holyprofweb' ); ?></p>
                    <div class="live-search-trending" id="live-search-recent"></div>
                </div>
            </div>
        </div>
        <div class="live-search-backdrop" id="live-search-backdrop"></div>
    </div>
