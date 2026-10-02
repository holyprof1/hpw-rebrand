<?php
/**
 * Editorial safety: nothing is published, rewritten or padded automatically.
 *
 * The legacy theme had background jobs that (a) auto-published drafts after a short delay,
 * (b) appended generated boilerplate to "thin" posts, (c) created drafts from search terms, and
 * (d) replaced featured images from cron. These are switched off here. Existing stored content is
 * not touched; boilerplate blocks already saved in old posts stay hidden by the display filter in
 * functions.php and are a Phase 2 clean-up item.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Options that drive the jobs always read as "off", whatever the stored value or the admin checkbox says.
add_filter( 'pre_option_hpw_enable_draft_autopublish', '__return_zero' );
add_filter( 'pre_option_hpw_search_auto_draft', '__return_zero' );

// Detach the hooks outright so they cannot run even if an option is re-enabled elsewhere.
add_action( 'init', function () {
    remove_action( 'save_post_post', 'holyprofweb_maybe_publish_ready_draft_on_save', 40 );
    remove_action( 'holyprofweb_draft_publish_audit', 'holyprofweb_process_draft_queue' );
    remove_action( 'holyprofweb_draft_publish_audit', 'holyprofweb_retry_generated_image_upgrades', 20 );
    remove_action( 'holyprofweb_daily_content_audit', 'holyprofweb_run_content_audit' );
    remove_action( 'init', 'holyprofweb_expand_existing_thin_posts_once', 60 );
}, 0 );

// remove_action on 'init' only works for hooks registered before it runs; the init-priority-60 hook is
// registered at file load, so the call above (priority 0) is early enough.

// Stop the stale schedules from firing no-op events.
add_action( 'init', function () {
    foreach ( array( 'holyprofweb_draft_publish_audit', 'holyprofweb_daily_content_audit' ) as $hook ) {
        $next = wp_next_scheduled( $hook );
        if ( $next ) {
            wp_unschedule_event( $next, $hook );
        }
    }
}, 50 );
add_action( 'init', function () {
    remove_action( 'init', 'holyprofweb_schedule_content_audit', 40 );
}, 0 );

// Nothing may pad a post: the generator is never allowed to run.
add_filter( 'holyprofweb_allow_thin_content_expansion', '__return_false' );
