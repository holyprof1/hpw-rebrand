<?php
/**
 * Template Name: Submit a Review
 * Template Post Type: page
 */

get_header();

$submitted   = false;
$error       = '';
$nonce_field = 'holyprofweb_submit_review';
$allowed_prefill_categories = array( 'reviews', 'companies', 'salaries', 'biography', 'reports' );
$allowed_prefill_modes      = array( 'report', 'listing', 'suggest-edit', 'add-information' );
$prefill_category = isset( $_GET['submit_category'] ) ? sanitize_key( wp_unslash( $_GET['submit_category'] ) ) : '';
$prefill_name     = isset( $_GET['submit_name'] ) ? sanitize_text_field( wp_unslash( $_GET['submit_name'] ) ) : '';
$prefill_mode     = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : '';

if ( ! in_array( $prefill_category, $allowed_prefill_categories, true ) ) {
    $prefill_category = '';
}

if ( ! in_array( $prefill_mode, $allowed_prefill_modes, true ) ) {
    $prefill_mode = '';
}

$prefill_name = mb_substr( trim( $prefill_name ), 0, 120 );

if ( isset( $_POST[ $nonce_field ] ) && wp_verify_nonce(
    sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ),
    'submit_review_action'
) ) {
    $name        = isset( $_POST['submit_name'] ) ? sanitize_text_field( wp_unslash( $_POST['submit_name'] ) ) : '';
    $site_url    = isset( $_POST['submit_url'] ) ? wp_unslash( $_POST['submit_url'] ) : '';
    $category    = isset( $_POST['submit_category'] ) ? sanitize_key( wp_unslash( $_POST['submit_category'] ) ) : 'reviews';
    $description = isset( $_POST['submit_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['submit_description'] ) ) : '';
    $report_issue= isset( $_POST['report_issue'] ) ? sanitize_textarea_field( wp_unslash( $_POST['report_issue'] ) ) : '';
    $submitter   = isset( $_POST['submit_your_name'] ) ? sanitize_text_field( wp_unslash( $_POST['submit_your_name'] ) ) : '';
    $email       = isset( $_POST['submit_email'] ) ? sanitize_email( wp_unslash( $_POST['submit_email'] ) ) : '';
    $body_plain  = strtolower( trim( wp_strip_all_tags( 'reports' === $category ? $report_issue : $description ) ) );
    $duplicate_key = 'hpw_submit_dup_' . md5( strtolower( $name ) . '|' . strtolower( $email ) . '|' . $category . '|' . $body_plain );
    $submission_fingerprint = function_exists( 'holyprofweb_get_request_fingerprint' ) ? holyprofweb_get_request_fingerprint() : '';

    if ( function_exists( 'holyprofweb_validate_public_form_guard' ) ) {
        $guard = holyprofweb_validate_public_form_guard( 'submit' );
        if ( is_wp_error( $guard ) ) {
            $error = $guard->get_error_message();
        }
    }

    if ( ! $error && function_exists( 'holyprofweb_enforce_rate_limit' ) ) {
        $limited = holyprofweb_enforce_rate_limit( 'submit_listing', 3, HOUR_IN_SECONDS, __( 'Too many submissions were sent from this browser. Please try again later.', 'holyprofweb' ) );
        if ( is_wp_error( $limited ) ) {
            $error = $limited->get_error_message();
        }
    }

    if ( ! $error && $body_plain && get_transient( $duplicate_key ) ) {
        $error = __( 'That submission was already received. Please wait before sending it again.', 'holyprofweb' );
    }

    if ( ! $error && function_exists( 'holyprofweb_enforce_public_cooldown' ) ) {
        $cooldown_suffix = $email ? strtolower( $email ) : strtolower( $name );
        $cooldown = holyprofweb_enforce_public_cooldown( 'submit_listing', 5 * MINUTE_IN_SECONDS, __( 'Please wait a few minutes before sending another submission.', 'holyprofweb' ), $cooldown_suffix );
        if ( is_wp_error( $cooldown ) ) {
            $error = $cooldown->get_error_message();
        }
    }

    if ( ! $error && ( $email || $submission_fingerprint ) ) {
        global $wpdb;

        $meta_clauses = array();
        $query_params = array(
            'post',
            'pending',
            'draft',
            'publish',
            'future',
            'private',
            '_hpw_submission_created_at',
            time() - ( 5 * MINUTE_IN_SECONDS ),
        );

        if ( $email ) {
            $meta_clauses[] = '(pm.meta_key = %s AND pm.meta_value = %s)';
            $query_params[] = '_hpw_submitter_email';
            $query_params[] = strtolower( $email );
        }

        if ( $submission_fingerprint ) {
            $meta_clauses[] = '(pm.meta_key = %s AND pm.meta_value = %s)';
            $query_params[] = '_hpw_submission_fingerprint';
            $query_params[] = $submission_fingerprint;
        }

        if ( ! empty( $meta_clauses ) ) {
            $recent_submission_sql = "
                SELECT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                INNER JOIN {$wpdb->postmeta} pm_created ON pm_created.post_id = p.ID
                WHERE p.post_type = %s
                  AND p.post_status IN (%s, %s, %s, %s, %s)
                  AND pm_created.meta_key = %s
                  AND CAST(pm_created.meta_value AS UNSIGNED) >= %d
                  AND (" . implode( ' OR ', $meta_clauses ) . ')
                LIMIT 1
            ';

            $recent_submission_id = $wpdb->get_var( $wpdb->prepare( $recent_submission_sql, $query_params ) );
            if ( $recent_submission_id ) {
                $error = __( 'A submission from this connection is already waiting for review. Please wait a few minutes before sending another one.', 'holyprofweb' );
            }
        }
    }

    if ( function_exists( 'holyprofweb_clean_public_url_submission' ) ) {
        $site_url = holyprofweb_clean_public_url_submission( $site_url );
    } else {
        $site_url = esc_url_raw( $site_url );
    }

    if ( ! $error && empty( $name ) ) {
        $error = 'reports' === $category ? __( 'Please enter the site name.', 'holyprofweb' ) : __( 'Please enter the name of the app or company.', 'holyprofweb' );
    } elseif ( ! $error && 'reports' === $category && mb_strlen( $report_issue ) < 20 ) {
        $error = __( 'Please provide at least 20 characters for the issue description.', 'holyprofweb' );
    } elseif ( ! $error && 'reports' !== $category && mb_strlen( $description ) < 20 ) {
        $error = __( 'Please provide at least 20 characters describing this company or site.', 'holyprofweb' );
    } elseif ( ! $error && ! empty( $email ) && ! is_email( $email ) ) {
        $error = __( 'Please enter a valid email address.', 'holyprofweb' );
    } elseif ( ! $error && ! empty( $_POST['submit_url'] ) && ! $site_url ) {
        $error = __( 'Please enter a valid public website URL.', 'holyprofweb' );
    } else {
        $term      = get_term_by( 'slug', $category, 'category' );
        $cat_ids   = $term ? array( (int) $term->term_id ) : array();
        $main_body = 'reports' === $category ? $report_issue : $description;

        $content = "<p><strong>Submitted via HolyprofWeb</strong></p>\n"
            . ( $site_url ? "<p><strong>Website:</strong> <a href='" . esc_url( $site_url ) . "'>" . esc_html( $site_url ) . "</a></p>\n" : '' )
            . ( $submitter ? "<p><strong>Submitted by:</strong> " . esc_html( $submitter ) . "</p>\n" : '' )
            . "<p>" . nl2br( esc_html( $main_body ) ) . "</p>";

        $submission_status = 'pending';

        $post_id = wp_insert_post( array(
            'post_title'     => sanitize_text_field( $name ),
            'post_content'   => $content,
            'post_status'    => $submission_status,
            'post_type'      => 'post',
            'post_category'  => $cat_ids,
            'comment_status' => 'open',
            'meta_input'     => array(
                '_hpw_submitted_url'          => $site_url,
                '_hpw_submitter_name'         => $submitter,
                '_hpw_submitter_email'        => strtolower( $email ),
                '_hpw_submission_fingerprint' => $submission_fingerprint,
                '_hpw_submission_created_at'  => time(),
                '_hpw_submission_type'        => 'reports' === $category ? 'report' : 'listing',
            ),
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            if ( $site_url && current_user_can( 'edit_post', $post_id ) ) {
                holyprofweb_fetch_og_image( $post_id, $site_url );
            }
            if ( ! get_post_thumbnail_id( $post_id ) ) {
                $post = get_post( $post_id );
                if ( $post ) {
                    holyprofweb_generate_post_image( $post_id, $post );
                }
            }
            if ( $body_plain ) {
                set_transient( $duplicate_key, 1, 12 * HOUR_IN_SECONDS );
            }
            holyprofweb_notify_submission( $post_id, $name, $site_url, $category, $main_body );
            $submitted = true;
        } else {
            $error = __( 'Something went wrong. Please try again.', 'holyprofweb' );
        }
    }
}

$available_cats = array(
    'reviews'   => array( 'icon' => '&#9733;', 'label' => 'Reviews', 'desc' => 'Apps, platforms, services' ),
    'companies' => array( 'icon' => '&#127970;', 'label' => 'Companies', 'desc' => 'Company profiles & overviews' ),
    'salaries'  => array( 'icon' => '&#128176;', 'label' => 'Salaries', 'desc' => 'Compensation & staff data' ),
    'biography' => array( 'icon' => '&#128100;', 'label' => 'Biography', 'desc' => 'People & founders' ),
    'reports'   => array( 'icon' => '&#128203;', 'label' => 'Reports', 'desc' => 'Submit a report or complaint' ),
);
$selected_cat = isset( $_POST['submit_category'] ) ? sanitize_key( wp_unslash( $_POST['submit_category'] ) ) : ( $prefill_category ?: 'reviews' );
$name_label   = 'biography' === $selected_cat
    ? __( 'Person, Founder, or Public Figure Name', 'holyprofweb' )
    : __( 'Company, App, or Site Name', 'holyprofweb' );
$page_title   = 'biography' === $selected_cat && 'suggest-edit' === $prefill_mode
    ? __( 'Suggest an Edit', 'holyprofweb' )
    : ( 'biography' === $selected_cat ? __( 'Add Information', 'holyprofweb' ) : __( 'Submit a Company, Site, or Report', 'holyprofweb' ) );
$page_subtitle = 'biography' === $selected_cat
    ? __( 'Send corrections, updates, or missing biography details. Your submission lands in admin review.', 'holyprofweb' )
    : __( 'Submit a listing or report an issue. Every submission lands in admin as a draft for moderation.', 'holyprofweb' );
?>

<div class="platform-wrap">
    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <div class="submit-page-wrap">
            <header class="submit-page-header">
                <div class="submit-page-badge">+ Submit</div>
                <h1 class="submit-page-title"><?php echo esc_html( $page_title ); ?></h1>
                <p class="submit-page-sub"><?php echo esc_html( $page_subtitle ); ?></p>
            </header>

            <?php if ( $submitted ) : ?>
            <div class="submit-success" role="alert">
                <div class="submit-success-icon" aria-hidden="true">&#10003;</div>
                <h2><?php esc_html_e( 'Submission received!', 'holyprofweb' ); ?></h2>
                <p><?php esc_html_e( 'Your submission is now waiting in admin review before anything goes live.', 'holyprofweb' ); ?></p>
            </div>
            <?php else : ?>
                <?php if ( $error ) : ?>
                <div class="submit-error" role="alert"><strong>&#9888;</strong> <?php echo esc_html( $error ); ?></div>
                <?php endif; ?>

                <form class="submit-form" method="post" novalidate>
                    <?php wp_nonce_field( 'submit_review_action', $nonce_field ); ?>
                    <?php if ( function_exists( 'holyprofweb_render_public_form_guard' ) ) { holyprofweb_render_public_form_guard( 'submit' ); } ?>

                    <div class="submit-form-field">
                        <label class="submit-label"><?php esc_html_e( 'Category', 'holyprofweb' ); ?> <span class="submit-required">*</span></label>
                        <div class="submit-cat-grid">
                            <?php foreach ( $available_cats as $slug => $cat ) : ?>
                            <label class="submit-cat-card<?php echo $selected_cat === $slug ? ' is-selected' : ''; ?>">
                                <input type="radio" name="submit_category" value="<?php echo esc_attr( $slug ); ?>" class="submit-cat-radio" <?php checked( $selected_cat, $slug ); ?> />
                                <span class="submit-cat-icon" aria-hidden="true"><?php echo wp_kses_post( $cat['icon'] ); ?></span>
                                <span class="submit-cat-label"><?php echo esc_html( $cat['label'] ); ?></span>
                                <span class="submit-cat-desc"><?php echo esc_html( $cat['desc'] ); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="submit-form-field">
                        <label for="submit_name" class="submit-label"><?php echo esc_html( $name_label ); ?> <span class="submit-required">*</span></label>
                        <input type="text" id="submit_name" name="submit_name" class="submit-input" value="<?php echo isset( $_POST['submit_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['submit_name'] ) ) ) : esc_attr( $prefill_name ); ?>" required />
                    </div>

                    <div class="submit-form-field">
                        <label for="submit_url" class="submit-label"><?php esc_html_e( 'Website URL', 'holyprofweb' ); ?></label>
                        <input type="url" id="submit_url" name="submit_url" class="submit-input" value="<?php echo isset( $_POST['submit_url'] ) ? esc_attr( esc_url_raw( wp_unslash( $_POST['submit_url'] ) ) ) : ''; ?>" />
                    </div>

                    <div class="submit-form-field">
                        <label for="submit_description" class="submit-label"><?php esc_html_e( 'Description / Notes', 'holyprofweb' ); ?></label>
                        <textarea id="submit_description" name="submit_description" class="submit-textarea" rows="6"><?php echo isset( $_POST['submit_description'] ) ? esc_textarea( sanitize_textarea_field( wp_unslash( $_POST['submit_description'] ) ) ) : ''; ?></textarea>
                    </div>

                    <div class="submit-form-field">
                        <label for="report_issue" class="submit-label"><?php esc_html_e( 'Issue Description', 'holyprofweb' ); ?></label>
                        <textarea id="report_issue" name="report_issue" class="submit-textarea" rows="5"><?php echo isset( $_POST['report_issue'] ) ? esc_textarea( sanitize_textarea_field( wp_unslash( $_POST['report_issue'] ) ) ) : ''; ?></textarea>
                    </div>

                    <div class="submit-form-row">
                        <div class="submit-form-field">
                            <label for="submit_your_name" class="submit-label"><?php esc_html_e( 'Your Name', 'holyprofweb' ); ?></label>
                            <input type="text" id="submit_your_name" name="submit_your_name" class="submit-input" value="<?php echo isset( $_POST['submit_your_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['submit_your_name'] ) ) ) : ''; ?>" />
                        </div>
                        <div class="submit-form-field">
                            <label for="submit_email" class="submit-label"><?php esc_html_e( 'Your Email', 'holyprofweb' ); ?></label>
                            <input type="email" id="submit_email" name="submit_email" class="submit-input" value="<?php echo isset( $_POST['submit_email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['submit_email'] ) ) ) : ''; ?>" />
                        </div>
                    </div>

                    <div class="submit-form-footer">
                        <button type="submit" class="submit-btn"><?php esc_html_e( 'Submit for Review', 'holyprofweb' ); ?></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
