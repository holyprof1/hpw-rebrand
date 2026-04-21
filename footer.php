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
                    <?php esc_html_e( 'Global web intelligence platform for reviews, companies, salaries and user insights.', 'holyprofweb' ); ?>
                </p>
            </div>

            <!-- Column 2: Platform -->
            <nav class="footer-col footer-col--nav" aria-label="<?php esc_attr_e( 'Platform links', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Platform', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php
                    $platform_links = array(
                        'Reviews'   => '/category/reviews/',
                        'Companies' => '/category/companies/',
                        'Salaries'  => '/category/salaries/',
                        'Biography' => '/category/biography/',
                        'Blog'      => holyprofweb_get_blog_url(),
                        'Reports'   => holyprofweb_get_reports_url(),
                    );
                    foreach ( $platform_links as $label => $path ) :
                    ?>
                    <li>
                        <a href="<?php echo esc_url( 0 === strpos( $path, 'http' ) ? $path : home_url( $path ) ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Column 3: Company -->
            <nav class="footer-col footer-col--nav" aria-label="<?php esc_attr_e( 'Company links', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Company', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php
                    $company_links = array(
                        'Work with us' => '/work-with-us/',
                        'Advertise'    => '/advertise/',
                        'About'        => '/about/',
                    );
                    foreach ( $company_links as $label => $path ) :
                    ?>
                    <li>
                        <a href="<?php echo esc_url( home_url( $path ) ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Column 4: Legal -->
            <nav class="footer-col footer-col--nav" aria-label="<?php esc_attr_e( 'Legal links', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Legal', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php
                    $legal_links = array(
                        'Contact' => array( 'href' => '/contact/', 'external' => false ),
                        'Privacy' => array( 'href' => '/privacy-policy/', 'external' => false ),
                    );
                    foreach ( $legal_links as $label => $link ) :
                        $href = $link['external'] ? esc_url( $link['href'] ) : esc_url( home_url( $link['href'] ) );
                    ?>
                    <li>
                        <a href="<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above ?>">
                            <?php echo esc_html( $label ); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
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
