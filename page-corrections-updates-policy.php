<?php
/**
 * Template for the /corrections-updates-policy/ page: copy lives in inc/trust.php.
 */

while ( have_posts() ) {
    the_post();
    holyprofweb_render_trust_page( 'corrections-updates-policy' );
}
