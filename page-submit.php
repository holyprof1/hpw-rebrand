<?php
/**
 * Template Name: Submit a Review
 * Template Post Type: page
 */

get_header();

$submitted   = false;
$error       = '';
$nonce_field = 'holyprofweb_submit_review';
$prefill_category = isset( $_GET['submit_category'] ) ? sanitize_key( wp_unslash( $_GET['submit_category'] ) ) : '';
$prefill_name     = isset( $_GET['submit_name'] ) ? sanitize_text_field( wp_unslash( $_GET['submit_name'] ) ) : '';
$prefill_mode     = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : '';

if ( isset( $_POST[ $nonce_field ] ) && wp_verify_nonce(
    sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ),
    'submit_review_action'
) ) {
    $name        = isset( $_POST['submit_name'] ) ? sanitize_text_field( wp_unslash( $_POST['submit_name'] ) ) : '';
    $site_url    = isset( $_POST['submit_url'] ) ? esc_url_raw( wp_unslash( $_POST['submit_url'] ) ) : '';
    $category    = isset( $_POST['submit_category'] ) ? sanitize_key( wp_unslash( $_POST['submit_category'] ) ) : 'reviews';
    $description = isset( $_POST['submit_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['submit_description'] ) ) : '';
    $report_issue= isset( $_POST['report_issue'] ) ? sanitize_textarea_field( wp_unslash( $_POST['report_issue'] ) ) : '';
    $submitter   = isset( $_POST['submit_your_name'] ) ? sanitize_text_field( wp_unslash( $_POST['submit_your_name'] ) ) : '';
    $email       = isset( $_POST['submit_email'] ) ? sanitize_email( wp_unslash( $_POST['submit_email'] ) ) : '';

    if ( empty( $name ) ) {
        $error = 'reports' === $category ? __( 'Please enter the site name.', 'holyprofweb' ) : __( 'Please enter the name of the app or company.', 'holyprofweb' );
    } elseif ( 'reports' === $category && mb_strlen( $report_issue ) < 20 ) {
        $error = __( 'Please provide at least 20 characters for the issue description.', 'holyprofweb' );
    } elseif ( 'reports' !== $category && mb_strlen( $description ) < 20 ) {
        $error = __( 'Please provide at least 20 characters describing this company or site.', 'holyprofweb' );
    } elseif ( ! empty( $email ) && ! is_email( $email ) ) {
        $error = __( 'Please enter a valid email address.', 'holyprofweb' );
    } else {
        $term    = get_term_by( 'slug', $category, 'category' );
        $cat_ids = $term ? array( (int) $term->term_id ) : array();
        $main_body = 'reports' === $category ? $report_issue : $description;

        $content = "<p><strong>Submitted via HolyprofWeb</strong></p>\n"
            . ( $site_url ? "<p><strong>Website:</strong> <a href='" . esc_url( $site_url ) . "'>" . esc_html( $site_url ) . "</a></p>\n" : '' )
            . ( $submitter ? "<p><strong>Submitted by:</strong> " . esc_html( $submitter ) . "</p>\n" : '' )
            . "<p>" . nl2br( esc_html( $main_body ) ) . "</p>";

        $should_publish = ( 'reports' === $category );

        $post_id = wp_insert_post( array(
            'post_title'     => sanitize_text_field( $name ),
            'post_content'   => $content,
            'post_status'    => $should_publish ? 'publish' : 'draft',
            'post_type'      => 'post',
            'post_category'  => $cat_ids,
            'comment_status' => 'open',
            'meta_input'     => array(
                '_hpw_submitted_url'   => $site_url,
                '_hpw_submitter_name'  => $submitter,
                '_hpw_submitter_email' => $email,
                '_hpw_submission_type' => 'reports' === $category ? 'report' : 'listing',
            ),
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            if ( $site_url ) {
                holyprofweb_fetch_og_image( $post_id, $site_url );
            }
            if ( ! get_post_thumbnail_id( $post_id ) ) {
                $post = get_post( $post_id );
                if ( $post ) {
                    holyprofweb_generate_post_image( $post_id, $post );
                }
            }
            holyprofweb_notify_submission( $post_id, $name, $site_url, $category, $main_body );
            $submitted = true;
        } else {
            $error = __( 'Something went wrong. Please try again.', 'holyprofweb' );
        }
    }
}

$available_cats = array(
    'reviews'   => array( 'icon' => '★', 'label' => 'Reviews',   'desc' => 'Apps, platforms, services' ),
    'companies' => array( 'icon' => '🏢', 'label' => 'Companies', 'desc' => 'Company profiles & overviews' ),
    'salaries'  => array( 'icon' => '💰', 'label' => 'Salaries',  'desc' => 'Compensation & staff data' ),
    'biography' => array( 'icon' => '👤', 'label' => 'Biography', 'desc' => 'People & founders' ),
    'reports'   => array( 'icon' => '📋', 'label' => 'Reports',   'desc' => 'Submit a report or complaint' ),
);
$selected_cat = isset( $_POST['submit_category'] ) ? sanitize_key( wp_unslash( $_POST['submit_category'] ) ) : ( $prefill_category ?: 'reviews' );
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
                <p><?php echo 'reports' === $selected_cat ? esc_html__( 'Your report is now live and searchable on the site.', 'holyprofweb' ) : esc_html__( 'Your submission is now waiting in admin review.', 'holyprofweb' ); ?></p>
                <?php if ( ! empty( $post_id ) && 'reports' === $selected_cat ) : ?>
                <p><a class="submit-btn" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php esc_html_e( 'View published report', 'holyprofweb' ); ?></a></p>
                <?php endif; ?>
            </div>
            <?php else : ?>
                <?php if ( $error ) : ?>
                <div class="submit-error" role="alert"><strong>&#9888;</strong> <?php echo esc_html( $error ); ?></div>
                <?php endif; ?>

                <form class="submit-form" method="post" novalidate>
                    <?php wp_nonce_field( 'submit_review_action', $nonce_field ); ?>

                    <div class="submit-form-field">
                        <label class="submit-label"><?php esc_html_e( 'Category', 'holyprofweb' ); ?> <span class="submit-required">*</span></label>
                        <div class="submit-cat-grid">
                            <?php foreach ( $available_cats as $slug => $cat ) : ?>
                            <label class="submit-cat-card<?php echo $selected_cat === $slug ? ' is-selected' : ''; ?>">
                                <input type="radio" name="submit_category" value="<?php echo esc_attr( $slug ); ?>" class="submit-cat-radio" <?php checked( $selected_cat, $slug ); ?> />
                                <span class="submit-cat-icon" aria-hidden="true"><?php echo $cat['icon']; ?></span>
                                <span class="submit-cat-label"><?php echo esc_html( $cat['label'] ); ?></span>
                                <span class="submit-cat-desc"><?php echo esc_html( $cat['desc'] ); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="submit-form-field">
                        <label for="submit_name" class="submit-label"><?php esc_html_e( 'Company, App, or Site Name', 'holyprofweb' ); ?> <span class="submit-required">*</span></label>
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
