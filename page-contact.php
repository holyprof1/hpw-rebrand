<?php
/**
 * Template Name: Contact Page
 * Contact form with server-side validation and wp_mail delivery.
 */

get_header();

$sent    = false;
$errors  = array();
$success = false;

if ( isset( $_POST['hpw_contact_nonce'] ) ) {
    $nonce = sanitize_text_field( wp_unslash( $_POST['hpw_contact_nonce'] ) );

    if ( ! wp_verify_nonce( $nonce, 'hpw_contact_submit' ) ) {
        $errors[] = __( 'Security check failed. Please refresh the page and try again.', 'holyprofweb' );
    } else {
        $name    = isset( $_POST['contact_name'] )    ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) )    : '';
        $email   = isset( $_POST['contact_email'] )   ? sanitize_email( wp_unslash( $_POST['contact_email'] ) )         : '';
        $subject = isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) )  : '';
        $message = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';

        if ( strlen( $name ) < 2 )           $errors[] = __( 'Please enter your name.',          'holyprofweb' );
        if ( ! is_email( $email ) )           $errors[] = __( 'Please enter a valid email.',      'holyprofweb' );
        if ( strlen( $subject ) < 3 )         $errors[] = __( 'Please enter a subject.',          'holyprofweb' );
        if ( strlen( $message ) < 10 )        $errors[] = __( 'Message must be at least 10 characters.', 'holyprofweb' );

        if ( empty( $errors ) ) {
            $to      = get_option( 'hpw_contact_email', get_option( 'admin_email' ) );
            $subject_line = '[HPW Contact] ' . $subject;
            $body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'Reply-To: ' . $name . ' <' . $email . '>',
            );

            $success = wp_mail( $to, $subject_line, $body, $headers );
            if ( ! $success ) {
                $errors[] = __( 'Message could not be sent right now. Please email us directly.', 'holyprofweb' );
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
                            <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About HolyprofWeb', 'holyprofweb' ); ?></a></li>
                            <li><a href="<?php echo esc_url( home_url( '/advertise/' ) ); ?>"><?php esc_html_e( 'Advertise With Us', 'holyprofweb' ); ?></a></li>
                            <li><a href="<?php echo esc_url( home_url( '/work-with-us/' ) ); ?>"><?php esc_html_e( 'Work With Us', 'holyprofweb' ); ?></a></li>
                        </ul>
                    </div>

                </aside>

            </div><!-- .contact-layout -->

        </div><!-- .contact-page-wrap -->
    </main>

</div><!-- .platform-wrap -->

<?php get_footer(); ?>
