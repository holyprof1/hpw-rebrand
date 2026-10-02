<?php
/**
 * Outgoing mail through the site's own mailbox (SMTP).
 *
 * The host disables PHP mail(), so wp_mail() needs SMTP. The owner's earlier SMTP settings (the cPanel
 * mailbox for this domain) are still stored in the database by the previously installed GoSMTP plugin,
 * which is no longer on disk. This reads those stored values and nothing else: no host, user or password is
 * kept in the theme or the repository. Define HPW_SMTP_DISABLED to turn it off, or use any SMTP plugin
 * (this handler stands down when another plugin already configured the mailer).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function holyprofweb_smtp_settings() {
    $o = get_option( 'gosmtp_options' );
    $m = is_array( $o ) && ! empty( $o['mailer'][0] ) ? $o['mailer'][0] : array();
    if ( empty( $m['mail_type'] ) || 'smtp' !== $m['mail_type'] || empty( $m['smtp_host'] ) || empty( $m['smtp_username'] ) || empty( $m['smtp_password'] ) ) {
        return null;
    }
    return array(
        'host'   => (string) $m['smtp_host'],
        'port'   => (int) ( $m['smtp_port'] ?? 465 ),
        'secure' => in_array( $m['encryption'] ?? '', array( 'ssl', 'tls' ), true ) ? $m['encryption'] : '',
        'user'   => (string) $m['smtp_username'],
        'pass'   => (string) $m['smtp_password'],
        'from'   => ! empty( $o['from_email'] ) ? (string) $o['from_email'] : (string) $m['smtp_username'],
        'name'   => ! empty( $o['from_name'] ) ? (string) $o['from_name'] : get_bloginfo( 'name' ),
    );
}

add_action( 'phpmailer_init', function ( $phpmailer ) {
    if ( defined( 'HPW_SMTP_DISABLED' ) || ! empty( $phpmailer->Mailer ) && 'smtp' === $phpmailer->Mailer ) {
        return;
    }
    $s = holyprofweb_smtp_settings();
    if ( ! $s ) {
        return;
    }
    $phpmailer->isSMTP();
    $phpmailer->Host       = $s['host'];
    $phpmailer->Port       = $s['port'];
    $phpmailer->SMTPSecure = $s['secure'];
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $s['user'];
    $phpmailer->Password   = $s['pass'];
    $phpmailer->Timeout    = 15;
    // The mailbox only accepts its own address as the sender; the visitor's address goes in Reply-To.
    $phpmailer->setFrom( $s['from'], $s['name'], false );
} );
