<?php
/**
 * Mail guard.
 *
 * The production host has PHP mail() disabled and no SMTP configured at the time, so wp_mail() ended in
 * "Call to undefined function PHPMailer\PHPMailer\mail()", a fatal error on every contact-form
 * submission (visible in the server error_log). Until SMTP is configured, wp_mail() returns false
 * instead, which the contact form already reports to the visitor as "could not be sent".
 * Configuring SMTP (any plugin, or a phpmailer_init handler) re-enables normal sending automatically.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'pre_wp_mail', function ( $short_circuit ) {
    if ( null !== $short_circuit ) {
        return $short_circuit;
    }
    if ( function_exists( 'mail' ) || has_action( 'phpmailer_init' ) ) {
        return null; // let wp_mail() run
    }
    // An SMTP plugin (for example GoSMTP) may take over sending through its own pre_wp_mail handler.
    global $wp_filter;
    $handlers = 0;
    if ( isset( $wp_filter['pre_wp_mail'] ) ) {
        foreach ( $wp_filter['pre_wp_mail']->callbacks as $callbacks ) {
            $handlers += count( $callbacks );
        }
    }
    if ( $handlers > 1 ) {
        return null;
    }
    static $logged = false;
    if ( ! $logged ) {
        $logged = true;
        error_log( 'holyprofweb: wp_mail() skipped because PHP mail() is disabled and no SMTP is configured.' );
    }
    return false;
}, 5 );
