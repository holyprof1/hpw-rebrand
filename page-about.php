<?php
/**
 * Template for the /about/ page: copy lives in inc/trust.php.
 */

while ( have_posts() ) {
    the_post();
    holyprofweb_render_trust_page( 'about' );
}
