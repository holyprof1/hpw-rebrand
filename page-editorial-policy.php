<?php
/**
 * Template for the /editorial-policy/ page: copy lives in inc/trust.php.
 */

while ( have_posts() ) {
    the_post();
    holyprofweb_render_trust_page( 'editorial-policy' );
}
