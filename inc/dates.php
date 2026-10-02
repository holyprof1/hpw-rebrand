<?php
/**
 * "Meaningful" modified dates.
 *
 * post_modified is touched by bulk jobs (imports, image upgrades, auto content passes), which on
 * production left hundreds of posts, some published in 2023, with modified dates in April 2026.
 * Sitemap lastmod, schema dateModified and the visible "Updated" label use a separate value that is
 * only set when a signed-in person actually changes the title, body or excerpt. Posts that have no
 * such record fall back to their publish date. No date is invented or altered in the database.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HPW_CONTENT_MODIFIED_META = '_hpw_content_modified_gmt';

add_action( 'post_updated', function ( $post_id, $after, $before ) {
    if ( 'post' !== $after->post_type || 'publish' !== $after->post_status || 'publish' !== $before->post_status ) {
        return;
    }
    if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || wp_is_post_revision( $post_id ) || ! is_user_logged_in() || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( $after->post_title === $before->post_title && $after->post_content === $before->post_content && $after->post_excerpt === $before->post_excerpt ) {
        return;
    }
    update_post_meta( $post_id, HPW_CONTENT_MODIFIED_META, gmdate( 'Y-m-d H:i:s' ) );
}, 10, 3 );

/** Unix timestamp (UTC) of the last meaningful change, never earlier than publication. */
function holyprofweb_content_modified_ts( $post_id ) {
    $pub  = (int) get_post_time( 'U', true, $post_id );
    $meta = get_post_meta( $post_id, HPW_CONTENT_MODIFIED_META, true );
    $ts   = $meta ? strtotime( $meta . ' UTC' ) : 0;
    return max( $pub, (int) $ts );
}

function holyprofweb_content_modified_iso( $post_id ) {
    return wp_date( 'c', holyprofweb_content_modified_ts( $post_id ) );
}

add_filter( 'wp_sitemaps_posts_entry', function ( $entry, $post ) {
    if ( $post instanceof WP_Post && 'post' === $post->post_type ) {
        $entry['lastmod'] = gmdate( 'c', holyprofweb_content_modified_ts( $post->ID ) );
    }
    return $entry;
}, 10, 2 );
