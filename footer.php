    </div><!-- #page -->

    <?php
    /**
     * Footer banner ad — fires before the <footer> element.
     * Hooked via holyprofweb_output_footer_banner() in functions.php.
     */
    do_action( 'holyprofweb_before_footer' );
    ?>

    <footer id="colophon" class="site-footer" role="contentinfo">
        <div class="footer-grid container">

            <!-- Column 1: Brand -->
            <div class="footer-col footer-col--brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo" aria-label="<?php bloginfo( 'name' ); ?>">
                    <?php
                    $logo_png = get_template_directory() . '/assets/images/logo.png';
                    $logo_svg = get_template_directory() . '/assets/images/logo.svg';

                    if ( file_exists( $logo_png ) ) :
                    ?>
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>"
                             alt="<?php bloginfo( 'name' ); ?>"
                             width="140" height="32" loading="lazy" />
                    <?php elseif ( file_exists( $logo_svg ) ) : ?>
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.svg' ); ?>"
                             alt="<?php bloginfo( 'name' ); ?>"
                             width="140" height="32" loading="lazy" />
                    <?php else : ?>
                        <span class="footer-logo-text"><?php bloginfo( 'name' ); ?></span>
                    <?php endif; ?>
                </a>
                <p class="footer-tagline">
                    <?php esc_html_e( 'Discover what’s new online. New apps, websites, products, people and internet trends explained clearly.', 'holyprofweb' ); ?>
                </p>
                <?php
                $socials = array_filter( array(
                    'X'         => get_option( 'hpw_social_x', '' ),
                    'Facebook'  => get_option( 'hpw_social_facebook', '' ),
                    'Instagram' => get_option( 'hpw_social_instagram', '' ),
                    'YouTube'   => get_option( 'hpw_social_youtube', '' ),
                    'TikTok'    => get_option( 'hpw_social_tiktok', '' ),
                ) );
                if ( $socials ) : ?>
                <p class="footer-social">
                    <?php foreach ( $socials as $label => $url ) : ?>
                    <a href="<?php echo esc_url( $url ); ?>" rel="me noopener" target="_blank"><?php echo esc_html( $label ); ?></a>
                    <?php endforeach; ?>
                </p>
                <?php endif; ?>
            </div>

            <nav class="footer-col footer-col--nav" aria-label="<?php esc_attr_e( 'Sections', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Sections', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php foreach ( holyprofweb_sections() as $sec_slug => $sec ) : ?>
                    <li><a href="<?php echo esc_url( holyprofweb_section_url( $sec_slug ) ); ?>"><?php echo esc_html( $sec['label'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <nav class="footer-col footer-col--nav" aria-label="<?php esc_attr_e( 'About', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Holyprofweb', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php
                    $trust_links = array(
                        'about'                      => 'About Holyprofweb',
                        'editorial-policy'           => 'Editorial Policy',
                        'corrections-updates-policy' => 'Corrections Policy',
                        'authors'                    => 'Authors',
                        'contact'                    => 'Contact',
                    );
                    foreach ( $trust_links as $slug => $label ) :
                        if ( ! get_page_by_path( $slug ) ) { continue; }
                    ?>
                    <li><a href="<?php echo esc_url( home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <nav class="footer-col footer-col--nav" aria-label="<?php esc_attr_e( 'Legal', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Legal', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php
                    $legal_links = array(
                        'privacy-policy' => 'Privacy',
                        'terms'          => 'Terms',
                        'disclaimer'     => 'Disclaimer',
                        'advertise'      => 'Advertise',
                    );
                    foreach ( $legal_links as $slug => $label ) :
                        if ( ! get_page_by_path( $slug ) ) { continue; }
                    ?>
                    <li><a href="<?php echo esc_url( home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo esc_url( home_url( '/sitemap-index.xml' ) ); ?>"><?php esc_html_e( 'Sitemap', 'holyprofweb' ); ?></a></li>
                </ul>
            </nav>

        </div><!-- .footer-grid -->

        <!-- Footer bottom bar -->
        <div class="footer-bottom">
            <div class="container">
                <p class="footer-copy">
                    &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>.
                    <?php esc_html_e( 'All rights reserved.', 'holyprofweb' ); ?>
                </p>
            </div>
        </div><!-- .footer-bottom -->

    </footer><!-- #colophon -->

<?php wp_footer(); ?>
</body>
</html>
