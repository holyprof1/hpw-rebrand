<?php
/**
 * Fallback index and the "All stories" archive (/blog/).
 */

$hpw_title = get_query_var( 'hpw_blog_archive' ) ? __( 'All stories', 'holyprofweb' ) : get_bloginfo( 'name' );
$hpw_intro = get_query_var( 'hpw_blog_archive' ) ? __( 'Every published story, newest first.', 'holyprofweb' ) : '';
holyprofweb_render_archive_page( $hpw_title, $hpw_intro );
