<?php
/**
 * Template Name: Contact Page
 * Contact form with server-side validation and wp_mail delivery.
 */

get_header();

$sent    = false;
$errors  = array();
$success = false;
$allowed_subjects = array(
    'General Enquiry',
    'Content Correction',
    'Advertising',
    'Partnership',
    'Report a Problem',
    'Other',
);
$spam_phrases = array(
    'whatsapp',
    'telegram',
    'investment opportunity',
    'guaranteed profit',
    'earn money fast',
    'work from home',
    'crypto signal',
    'click here',
    'seo service',
    'guest post',
    'casino',
    'loan offer',
);

if ( isset( $_POST['hpw_contact_nonce'] ) ) {
    $nonce = sanitize_text_field( wp_unslash( $_POST['hpw_contact_nonce'] ) );

    if ( ! wp_verify_nonce( $nonce, 'hpw_contact_submit' ) ) {
        $errors[] = __( 'Security check failed. Please refresh the page and try again.', 'holyprofweb' );
    } else {
        $honeypot   = isset( $_POST['contact_website'] ) ? trim( wp_unslash( $_POST['contact_website'] ) ) : '';
        $form_time  = isset( $_POST['hpw_contact_time'] ) ? (int) $_POST['hpw_contact_time'] : 0;
        $name    = isset( $_POST['contact_name'] )    ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) )    : '';
        $email   = isset( $_POST['contact_email'] )   ? sanitize_email( wp_unslash( $_POST['contact_email'] ) )         : '';
        $subject = isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) )  : '';
        $message = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';
        $remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $message_plain = strtolower( wp_strip_all_tags( $message ) );
        $request_host  = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
        $origin_header = '';
        $origin_host   = '';

        if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
            $origin_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $origin_header = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
        }

        if ( $origin_header ) {
            $origin_host = wp_parse_url( $origin_header, PHP_URL_HOST );
        }

        if ( '' !== $honeypot ) {
            $errors[] = __( 'Security check failed. Please try again.', 'holyprofweb' );
        }
        if ( $form_time < ( time() - DAY_IN_SECONDS ) || $form_time > time() || ( time() - $form_time ) < 4 ) {
            $errors[] = __( 'Please wait a few seconds and submit the form again.', 'holyprofweb' );
        }
        if ( $origin_host && $request_host && strtolower( $origin_host ) !== strtolower( $request_host ) ) {
            $errors[] = __( 'Security check failed. Please refresh the page and try again.', 'holyprofweb' );
        }

        if ( strlen( $name ) < 2 )           $errors[] = __( 'Please enter your name.',          'holyprofweb' );
        if ( ! is_email( $email ) )           $errors[] = __( 'Please enter a valid email.',      'holyprofweb' );
        if ( ! in_array( $subject, $allowed_subjects, true ) ) $errors[] = __( 'Please choose a valid subject.', 'holyprofweb' );
        if ( strlen( $message ) < 10 )        $errors[] = __( 'Message must be at least 10 characters.', 'holyprofweb' );
        if ( strlen( $message ) > 3000 )      $errors[] = __( 'Message is too long. Please keep it under 3000 characters.', 'holyprofweb' );
        if ( preg_match_all( '#https?://#i', $message ) > 2 ) $errors[] = __( 'Please remove extra links from your message.', 'holyprofweb' );

        foreach ( $spam_phrases as $spam_phrase ) {
            if ( false !== strpos( $message_plain, $spam_phrase ) ) {
                $errors[] = __( 'Your message looks like automated spam. Please rewrite it and try again.', 'holyprofweb' );
                break;
            }
        }

        if ( preg_match( '/(.)\\1{7,}/', $message_plain ) || preg_match( '/\\b(\\w+)\\b(?:\\s+\\1\\b){4,}/i', $message_plain ) ) {
            $errors[] = __( 'Please rewrite the message with a bit more detail.', 'holyprofweb' );
        }

        $rate_limit_key = 'hpw_contact_rate_' . md5( strtolower( $email ) . '|' . $remote_ip );
        $burst_limit_key = 'hpw_contact_burst_' . md5( $remote_ip );
        $duplicate_key   = 'hpw_contact_dup_' . md5( strtolower( $email ) . '|' . strtolower( $subject ) . '|' . $message_plain );

        if ( empty( $errors ) && get_transient( $rate_limit_key ) ) {
            $errors[] = __( 'Please wait a minute before sending another message.', 'holyprofweb' );
        }
        if ( empty( $errors ) && get_transient( $duplicate_key ) ) {
            $errors[] = __( 'That message was already received. Please wait before sending it again.', 'holyprofweb' );
        }
        if ( empty( $errors ) ) {
            $burst_count = (int) get_transient( $burst_limit_key );
            if ( $burst_count >= 3 ) {
                $errors[] = __( 'Too many messages were sent from this connection recently. Please try again later.', 'holyprofweb' );
            }
        }

        if ( empty( $errors ) ) {
            $to      = get_option( 'hpw_contact_email', get_option( 'admin_email' ) );
            $safe_name = trim( preg_replace( '/[\r\n]+/', ' ', $name ) );
            $safe_email = sanitize_email( $email );
            $subject_line = '[HPW Contact] ' . trim( preg_replace( '/[\r\n]+/', ' ', $subject ) );
            $body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
            );
            if ( $safe_name && is_email( $safe_email ) ) {
                $headers[] = sprintf( 'Reply-To: %s <%s>', $safe_name, $safe_email );
            }

            $success = wp_mail( $to, $subject_line, $body, $headers );
            if ( ! $success ) {
                $errors[] = __( 'Message could not be sent right now. Please email us directly.', 'holyprofweb' );
            } else {
                set_transient( $rate_limit_key, 1, MINUTE_IN_SECONDS );
                set_transient( $duplicate_key, 1, 12 * HOUR_IN_SECONDS );
                set_transient( $burst_limit_key, (int) get_transient( $burst_limit_key ) + 1, HOUR_IN_SECONDS );
            }
        }
    }
}
?>

<div class="platform-wrap">

    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <div class="contact-page-wrap">

            <!-- Header -->
            <div class="contact-header">
                <h1 class="contact-title"><?php esc_html_e( 'Contact Us', 'holyprofweb' ); ?></h1>
                <p class="contact-subtitle">
                    <?php esc_html_e( 'Have a tip, correction, advertising question, or just want to say hi? We read every message.', 'holyprofweb' ); ?>
                </p>
            </div>

            <div class="contact-layout">

                <!-- Left: Form -->
                <div class="contact-form-col">

                    <?php if ( $success ) : ?>
                    <div class="contact-success" role="alert">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <p><?php esc_html_e( 'Thanks! Your message has been sent. We usually reply within 1–2 business days.', 'holyprofweb' ); ?></p>
                    </div>
                    <?php else : ?>

                    <?php if ( ! empty( $errors ) ) : ?>
                    <div class="contact-errors" role="alert">
                        <ul>
                            <?php foreach ( $errors as $err ) : ?>
                            <li><?php echo esc_html( $err ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="post" class="contact-form" novalidate>
                        <?php wp_nonce_field( 'hpw_contact_submit', 'hpw_contact_nonce' ); ?>
                        <input type="hidden" name="hpw_contact_time" value="<?php echo esc_attr( time() ); ?>" />
                        <div class="contact-field" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
                            <label for="contact_website"><?php esc_html_e( 'Website', 'holyprofweb' ); ?></label>
                            <input type="text" id="contact_website" name="contact_website" value="" tabindex="-1" autocomplete="off" />
                        </div>

                        <div class="contact-row contact-row--2col">
                            <div class="contact-field">
                                <label for="contact_name"><?php esc_html_e( 'Your Name', 'holyprofweb' ); ?> <span aria-hidden="true">*</span></label>
                                <input type="text" id="contact_name" name="contact_name"
                                       value="<?php echo esc_attr( isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '' ); ?>"
                                       placeholder="<?php esc_attr_e( 'John Doe', 'holyprofweb' ); ?>"
                                       required autocomplete="name" />
                            </div>
                            <div class="contact-field">
                                <label for="contact_email"><?php esc_html_e( 'Email Address', 'holyprofweb' ); ?> <span aria-hidden="true">*</span></label>
                                <input type="email" id="contact_email" name="contact_email"
                                       value="<?php echo esc_attr( isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '' ); ?>"
                                       placeholder="<?php esc_attr_e( 'you@example.com', 'holyprofweb' ); ?>"
                                       required autocomplete="email" />
                            </div>
                        </div>

                        <div class="contact-field">
                            <label for="contact_subject"><?php esc_html_e( 'Subject', 'holyprofweb' ); ?> <span aria-hidden="true">*</span></label>
                            <select id="contact_subject" name="contact_subject" required>
                                <option value=""><?php esc_html_e( '— Select a topic —', 'holyprofweb' ); ?></option>
                                <option value="General Enquiry"<?php selected( isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '', 'General Enquiry' ); ?>><?php esc_html_e( 'General Enquiry', 'holyprofweb' ); ?></option>
                                <option value="Content Correction"<?php selected( isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '', 'Content Correction' ); ?>><?php esc_html_e( 'Content Correction', 'holyprofweb' ); ?></option>
                                <option value="Advertising"<?php selected( isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '', 'Advertising' ); ?>><?php esc_html_e( 'Advertising', 'holyprofweb' ); ?></option>
                                <option value="Partnership"<?php selected( isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '', 'Partnership' ); ?>><?php esc_html_e( 'Partnership', 'holyprofweb' ); ?></option>
                                <option value="Report a Problem"<?php selected( isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '', 'Report a Problem' ); ?>><?php esc_html_e( 'Report a Problem', 'holyprofweb' ); ?></option>
                                <option value="Other"<?php selected( isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '', 'Other' ); ?>><?php esc_html_e( 'Other', 'holyprofweb' ); ?></option>
                            </select>
                        </div>

                        <div class="contact-field">
                            <label for="contact_message"><?php esc_html_e( 'Message', 'holyprofweb' ); ?> <span aria-hidden="true">*</span></label>
                            <textarea id="contact_message" name="contact_message" rows="6"
                                      placeholder="<?php esc_attr_e( 'Tell us what\'s on your mind…', 'holyprofweb' ); ?>"
                                      required><?php echo esc_textarea( isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '' ); ?></textarea>
                        </div>

                        <button type="submit" class="contact-submit">
                            <?php esc_html_e( 'Send Message', 'holyprofweb' ); ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        </button>
                    </form>
                    <?php endif; ?>

                </div>

                <!-- Right: Info sidebar -->
                <aside class="contact-info-col" aria-label="<?php esc_attr_e( 'Contact information', 'holyprofweb' ); ?>">

                    <div class="contact-info-card">
                        <h3 class="contact-info-heading"><?php esc_html_e( 'Direct Email', 'holyprofweb' ); ?></h3>
                        <a href="mailto:admin@holyprofweb.com" class="contact-email-link">admin@holyprofweb.com</a>
                        <p class="contact-info-note"><?php esc_html_e( 'We reply within 1–2 business days.', 'holyprofweb' ); ?></p>
                    </div>

                    <div class="contact-info-card">
                        <h3 class="contact-info-heading"><?php esc_html_e( 'Response Time', 'holyprofweb' ); ?></h3>
                        <ul class="contact-info-list">
                            <li><strong><?php esc_html_e( 'General enquiries:', 'holyprofweb' ); ?></strong> 1–2 days</li>
                            <li><strong><?php esc_html_e( 'Content corrections:', 'holyprofweb' ); ?></strong> 24 hours</li>
                            <li><strong><?php esc_html_e( 'Advertising:', 'holyprofweb' ); ?></strong> Same business day</li>
                        </ul>
                    </div>

                    <div class="contact-info-card">
                        <h3 class="contact-info-heading"><?php esc_html_e( 'Other Pages', 'holyprofweb' ); ?></h3>
                        <ul class="contact-info-list">
                            <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'About HolyprofWeb', 'holyprofweb' ); ?></a></li>
                            <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Advertise With Us', 'holyprofweb' ); ?></a></li>
                            <li><a href="<?php echo esc_url( home_url( '/work-with-us/' ) ); ?>"><?php esc_html_e( 'Work With Us', 'holyprofweb' ); ?></a></li>
                        </ul>
                    </div>

                </aside>

            </div><!-- .contact-layout -->

        </div><!-- .contact-page-wrap -->
    </main>

</div><!-- .platform-wrap -->

<?php get_footer(); ?>
