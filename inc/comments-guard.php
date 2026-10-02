<?php
/**
 * Comments are never auto-published.
 *
 * Production had 479 approved comments that were almost all spam (casino and phishing links, gibberish)
 * because the legacy "salvage" logic auto-approved public comments. From now on every public comment is held
 * for moderation; only users who can moderate comments bypass that.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'pre_comment_approved', function ( $approved, $commentdata ) {
    if ( 'spam' === $approved || is_wp_error( $approved ) ) {
        return $approved;
    }
    if ( is_user_logged_in() && current_user_can( 'moderate_comments' ) ) {
        return $approved;
    }
    return 0; // hold for moderation
}, 999, 2 );

// Public comments carry no clickable author links.
add_filter( 'get_comment_author_link', function ( $link, $author, $comment_id ) {
    return esc_html( $author );
}, 10, 3 );
add_filter( 'comment_form_default_fields', function ( $fields ) {
    unset( $fields['url'] );
    return $fields;
}, 99 );
