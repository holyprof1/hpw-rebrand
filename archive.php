<?php
/**
 * Generic archives (tags, dates, legacy categories).
 */

$hpw_title = get_the_archive_title();
$hpw_intro = wp_strip_all_tags( get_the_archive_description() );
holyprofweb_render_archive_page( $hpw_title, $hpw_intro );
