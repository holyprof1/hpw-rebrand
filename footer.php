    </div><!-- #page -->

    <footer id="colophon" class="site-footer" role="contentinfo">
        <div class="footer-grid pub-wrap">
            <div class="footer-col footer-col--brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand brand--footer" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
                    <svg class="brand__mark" width="26" height="26" viewBox="0 0 64 64" aria-hidden="true" focusable="false"><path d="M32 4 10 12v16c0 14 10 24 22 32 12-8 22-18 22-32V12L32 4Z" fill="currentColor"/><path d="m20 32 8 8 16-18" fill="none" stroke="#F0A500" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="brand__text">Holyprof<span>web</span></span>
                </a>
                <p class="footer-tagline"><?php esc_html_e( 'Discover what’s new online. New apps, websites, products, people and internet trends, researched and explained clearly.', 'holyprofweb' ); ?></p>
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

            <nav class="footer-col" aria-label="<?php esc_attr_e( 'Sections', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Sections', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php foreach ( holyprofweb_sections() as $sec_slug => $sec ) : ?>
                    <li><a href="<?php echo esc_url( holyprofweb_section_url( $sec_slug ) ); ?>"><?php echo esc_html( $sec['label'] ); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo esc_url( holyprofweb_get_blog_url() ); ?>"><?php esc_html_e( 'All stories', 'holyprofweb' ); ?></a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="<?php esc_attr_e( 'About', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Holyprofweb', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php
                    foreach ( array( 'about' => 'About Holyprofweb', 'editorial-policy' => 'Editorial Policy', 'corrections-updates-policy' => 'Corrections Policy', 'contact' => 'Contact' ) as $slug => $label ) :
                        if ( ! get_page_by_path( $slug ) ) { continue; }
                    ?>
                    <li><a href="<?php echo esc_url( home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="<?php esc_attr_e( 'Legal', 'holyprofweb' ); ?>">
                <h3 class="footer-col-title"><?php esc_html_e( 'Legal', 'holyprofweb' ); ?></h3>
                <ul>
                    <?php
                    foreach ( array( 'privacy-policy' => 'Privacy', 'terms' => 'Terms', 'disclaimer' => 'Disclaimer' ) as $slug => $label ) :
                        if ( ! get_page_by_path( $slug ) ) { continue; }
                    ?>
                    <li><a href="<?php echo esc_url( home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo esc_url( home_url( '/sitemap-index.xml' ) ); ?>"><?php esc_html_e( 'Sitemap', 'holyprofweb' ); ?></a></li>
                </ul>
            </nav>
        </div>

        <div class="footer-bottom">
            <div class="pub-wrap">
                <p class="footer-copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>. <?php esc_html_e( 'All rights reserved.', 'holyprofweb' ); ?></p>
            </div>
        </div>
    </footer>

<?php wp_footer(); ?>
</body>
</html>
