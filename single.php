<?php
/**
 * Single Post Template — HolyprofWeb
 * Platform layout: left sidebar + article + right sidebar.
 * Reviews are completely separate from WP comments.
 */

get_header();
?>

<div class="platform-wrap platform-wrap--single">

    <?php holyprofweb_left_sidebar(); ?>

    <main id="primary" class="site-main platform-main">
        <div class="single-wrap">

            <?php while ( have_posts() ) : the_post();
                $post_id     = get_the_ID();
                $decoded_title = holyprofweb_get_decoded_post_title( $post_id );
                $categories  = get_the_category();
                $primary_cat = ! empty( $categories ) ? $categories[0] : null;
                $rating      = holyprofweb_get_post_rating( $post_id );
                $review_count = holyprofweb_get_review_count( $post_id );
                $reactions   = holyprofweb_get_reactions( $post_id );
                $tags        = get_the_tags();
                $img_url     = holyprofweb_post_uses_generated_image_fallback( $post_id )
                    ? holyprofweb_get_generated_svg_image_url( $post_id, 'hero' )
                    : holyprofweb_get_post_image_url( $post_id, 'full' );
                $is_salary_post  = holyprofweb_post_in_category_tree( $post_id, 'salaries' );
                $is_company_post = holyprofweb_post_in_category_tree( $post_id, 'companies' );
                $salary_count    = holyprofweb_get_comment_count_by_type( $post_id, 'salary_submission' );
                $salary_min      = get_post_meta( $post_id, '_hpw_salary_min', true );
                $salary_max      = get_post_meta( $post_id, '_hpw_salary_max', true );
                $salary_currency = get_post_meta( $post_id, '_hpw_salary_currency', true ) ?: '₦';
                $salary_period   = get_post_meta( $post_id, '_hpw_salary_period', true );
                $salary_role     = get_post_meta( $post_id, '_hpw_salary_role', true );
                $work_score      = get_post_meta( $post_id, '_hpw_work_score', true );
                $best_review_ids = holyprofweb_get_best_review_ids( $post_id, 2 );
                $is_biography_post = holyprofweb_post_in_category_tree( $post_id, 'biography' );
                $show_company_review_mode = $is_company_post;
                $show_salary_module = $is_salary_post;
                $source_url   = holyprofweb_get_post_source_url( $post_id );
                $verdict      = holyprofweb_get_review_verdict( $post_id );
                $reaction_prompt = sprintf( __( 'What do you think about %s?', 'holyprofweb' ), $decoded_title );
            ?>

            <!-- =========================================
                 ARTICLE
            ========================================= -->
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'single-article' ); ?>>

                <!-- Breadcrumb -->
                <nav class="single-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
                    <?php if ( $primary_cat ) : ?>
                    <span aria-hidden="true">/</span>
                    <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>">
                        <?php echo esc_html( $primary_cat->name ); ?>
                    </a>
                    <?php endif; ?>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page"><?php echo esc_html( $decoded_title ); ?></span>
                </nav>

                <header class="single-header single-header--refined">
                    <div class="single-featured-image single-featured-image--inline">
                        <img src="<?php echo esc_attr( $img_url ); ?>"
                             alt="<?php echo esc_attr( $decoded_title ); ?>"
                             loading="eager"
                             class="<?php echo esc_attr( holyprofweb_get_post_image_class( $post_id ) ); ?>" />
                    </div>
                    <div class="single-header-copy">
                        <?php if ( $primary_cat ) : ?>
                        <span class="single-category">
                            <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>">
                                <?php echo esc_html( $primary_cat->name ); ?>
                            </a>
                        </span>
                        <?php endif; ?>
                        <?php if ( ! $is_biography_post ) : ?>
                        <span class="verdict-badge <?php echo esc_attr( $verdict['class'] ); ?>"><?php echo esc_html( $verdict['label'] ); ?></span>
                        <?php endif; ?>
                        <h1 class="single-title"><?php echo esc_html( $decoded_title ); ?></h1>
                        <p class="single-rating-summary">
                            <?php if ( $is_biography_post ) : ?>
                                <?php esc_html_e( 'Biography profile and background overview.', 'holyprofweb' ); ?>
                            <?php elseif ( $rating > 0 ) : ?>
                                <?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?> &#9733; (<?php echo esc_html( $review_count ); ?> <?php echo esc_html( _n( 'review', 'reviews', $review_count, 'holyprofweb' ) ); ?>)
                            <?php else : ?>
                                <?php esc_html_e( 'No reviews yet. Be the first to contribute.', 'holyprofweb' ); ?>
                            <?php endif; ?>
                        </p>
                        <div class="single-meta">
                            <span><a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a></span>
                            <span><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
                            <?php if ( $is_salary_post ) : ?>
                            <span><?php echo esc_html( sprintf( _n( '%s salary submission', '%s salary submissions', $salary_count, 'holyprofweb' ), number_format_i18n( $salary_count ) ) ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="single-header-actions">
                            <?php if ( $is_biography_post ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array( 'submit_category' => 'biography', 'submit_name' => $decoded_title, 'mode' => 'suggest-edit' ), home_url( '/submit/' ) ) ); ?>" class="single-action-btn single-action-btn--alt"><?php esc_html_e( 'Suggest Edit', 'holyprofweb' ); ?></a>
                            <a href="#write-review" class="single-action-btn"><?php esc_html_e( 'Have Information About This Person?', 'holyprofweb' ); ?></a>
                            <?php else : ?>
                            <?php if ( $source_url ) : ?>
                            <a href="<?php echo esc_url( $source_url ); ?>" class="single-action-btn single-action-btn--alt" target="_blank" rel="nofollow sponsored noopener"><?php esc_html_e( 'Visit Website', 'holyprofweb' ); ?></a>
                            <?php endif; ?>
                            <a href="#write-review" class="single-action-btn"><?php echo $show_salary_module ? esc_html__( 'Submit Salary', 'holyprofweb' ) : ( $show_company_review_mode ? esc_html__( 'Share Company Review', 'holyprofweb' ) : esc_html__( 'Have You Used This?', 'holyprofweb' ) ); ?></a>
                            <a href="#reviews" class="single-action-btn single-action-btn--alt"><?php esc_html_e( 'See Reviews', 'holyprofweb' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </header>

                <!-- Quick Facts -->
                <aside class="key-info-box<?php echo $is_biography_post ? ' key-info-box--wiki' : ''; ?>" aria-label="Key Information">
                    <p class="key-info-box-title">Quick Facts</p>
                    <dl class="key-info-list">
                        <?php if ( $primary_cat ) : ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Category</dt>
                            <dd class="key-info-value"><a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>"><?php echo esc_html( $primary_cat->name ); ?></a></dd>
                        </div>
                        <?php endif; ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Published</dt>
                            <dd class="key-info-value"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></dd>
                        </div>
                        <?php if ( get_the_modified_date() !== get_the_date() ) : ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Updated</dt>
                            <dd class="key-info-value"><time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time></dd>
                        </div>
                        <?php endif; ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Author</dt>
                            <dd class="key-info-value"><a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a></dd>
                        </div>
                        <?php if ( $rating > 0 ) : ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Rating</dt>
                            <dd class="key-info-value">
                                <?php echo holyprofweb_render_stars( $rating ); ?>
                                <span style="font-size:.78rem;color:var(--color-text-muted);margin-left:4px;">(<?php echo esc_html( $review_count ); ?>)</span>
                            </dd>
                        </div>
                        <?php endif; ?>
                        <?php if ( $salary_role ) : ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Role</dt>
                            <dd class="key-info-value"><?php echo esc_html( $salary_role ); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if ( $salary_min || $salary_max ) : ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Salary</dt>
                            <dd class="key-info-value">
                                <?php
                                if ( $salary_min && $salary_max ) {
                                    echo esc_html( $salary_currency . number_format_i18n( $salary_min ) . ' - ' . $salary_currency . number_format_i18n( $salary_max ) . $salary_period );
                                } elseif ( $salary_min ) {
                                    echo esc_html( $salary_currency . number_format_i18n( $salary_min ) . $salary_period );
                                } elseif ( $salary_max ) {
                                    echo esc_html( $salary_currency . number_format_i18n( $salary_max ) . $salary_period );
                                }
                                ?>
                            </dd>
                        </div>
                        <?php endif; ?>
                        <?php if ( $work_score ) : ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Work-Life</dt>
                            <dd class="key-info-value"><?php echo esc_html( number_format_i18n( (float) $work_score, 1 ) ); ?>/5</dd>
                        </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $tags ) ) :
                            $tag_names = implode( ', ', array_map( fn($t) => $t->name, array_slice( $tags, 0, 4 ) ) ); ?>
                        <div class="key-info-row">
                            <dt class="key-info-label">Topics</dt>
                            <dd class="key-info-value"><?php echo esc_html( $tag_names ); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </aside>

                <!-- Post content — accordion on h2 -->
                <div class="entry-content single-content">
                    <?php the_content(); ?>
                </div>

                <?php if ( ! $is_biography_post && ! $is_company_post ) : ?>
                <!-- Reactions bar -->
                <div class="reactions-bar" data-post-id="<?php the_ID(); ?>">
                    <p class="reactions-label"><?php echo esc_html( $reaction_prompt ); ?></p>
                    <div class="reactions-buttons">
                        <?php
                        $reaction_defs = array(
                            'legit'  => array( 'label' => __( 'Legit', 'holyprofweb' ),      'class' => 'reaction-legit' ),
                            'unsure' => array( 'label' => __( 'Dunno', 'holyprofweb' ),      'class' => 'reaction-unsure' ),
                            'scam'   => array( 'label' => __( 'Scam Alert', 'holyprofweb' ), 'class' => 'reaction-scam' ),
                        );
                        foreach ( $reaction_defs as $key => $def ) :
                            $count = $reactions[ $key ];
                        ?>
                        <button class="reaction-btn <?php echo esc_attr( $def['class'] ); ?>"
                                data-reaction="<?php echo esc_attr( $key ); ?>"
                                type="button">
                            <?php echo $def['label']; ?>
                            <span class="reaction-count"><?php echo $count > 0 ? esc_html( $count ) : ''; ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <p class="reactions-note"><?php esc_html_e( 'One quick pick per browser. You can change it later, but it only counts once.', 'holyprofweb' ); ?></p>
                </div>
                <?php endif; ?>

                <!-- Email capture -->
                <div class="email-capture-box">
                    <p class="email-capture-title"><?php echo $is_company_post ? esc_html__( 'Preparing for an interview or checking this company properly?', 'holyprofweb' ) : ( $is_biography_post ? esc_html__( 'Want updates about this person?', 'holyprofweb' ) : esc_html__( 'Evaluating this platform properly?', 'holyprofweb' ) ); ?></p>
                    <p class="email-capture-sub"><?php echo $is_company_post ? esc_html__( 'Get company reviews, interview signals, salary context and reports straight to your inbox.', 'holyprofweb' ) : ( $is_biography_post ? esc_html__( 'Get profile updates, biography corrections and related stories straight to your inbox.', 'holyprofweb' ) : esc_html__( 'Get reviews, salary signals and reports straight to your inbox.', 'holyprofweb' ) ); ?></p>
                    <form class="email-capture-form" novalidate>
                        <input type="email" class="email-capture-input"
                               placeholder="you@example.com" autocomplete="email" required />
                        <button type="submit" class="email-capture-btn">Subscribe</button>
                    </form>
                    <p class="email-capture-note">No spam. Unsubscribe any time.</p>
                </div>

                <!-- Tags -->
                <?php if ( ! empty( $tags ) ) : ?>
                <div class="post-tags" aria-label="Post tags">
                    <span class="tag-label">Tags:</span>
                    <?php foreach ( $tags as $tag ) : ?>
                    <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>"><?php echo esc_html( $tag->name ); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ============================================
                     REVIEWS SECTION (separate from WP comments)
                ============================================ -->
                <?php if ( ! $is_biography_post ) : ?>
                <section id="reviews" class="reviews-section">
                    <div class="reviews-header">
                        <h2 class="reviews-section-title">User Reviews</h2>
                        <p class="reviews-summary-line"><?php echo $rating > 0 ? esc_html( number_format_i18n( $rating, 1 ) ) . ' ★ (' . esc_html( $review_count ) . ' ' . esc_html( _n( 'review', 'reviews', $review_count, 'holyprofweb' ) ) . ')' : esc_html__( 'No reviews yet', 'holyprofweb' ); ?></p>
                    </div>

                    <?php $reviews = holyprofweb_get_post_reviews( $post_id ); ?>
                    <?php if ( ! empty( $reviews ) ) : ?>
                    <div class="review-list" id="review-list">
                        <?php foreach ( $reviews as $review ) :
                            $r_rating   = (int) get_comment_meta( $review->comment_ID, 'rating', true );
                            $r_url      = current_user_can( 'manage_options' ) ? get_comment_meta( $review->comment_ID, 'site_url', true ) : '';
                            $r_initial  = strtoupper( mb_substr( $review->comment_author, 0, 1 ) );
                            $verified   = holyprofweb_is_comment_verified( $review->comment_ID );
                            $is_best    = in_array( (int) $review->comment_ID, $best_review_ids, true );
                            $reviewer_type = (string) get_comment_meta( $review->comment_ID, 'reviewer_type', true );
                            $company_role_meta = (string) get_comment_meta( $review->comment_ID, 'company_role', true );
                            $company_location_meta = (string) get_comment_meta( $review->comment_ID, 'company_location', true );
                            $salary_range_meta = (string) get_comment_meta( $review->comment_ID, 'salary_range', true );
                            $interview_stage_meta = (string) get_comment_meta( $review->comment_ID, 'interview_stage', true );
                            $experience_issue_meta = (string) get_comment_meta( $review->comment_ID, 'experience_issue', true );
                            $reviewer_type_labels = array(
                                'staff'               => __( 'Current staff', 'holyprofweb' ),
                                'former-staff'        => __( 'Former staff', 'holyprofweb' ),
                                'interview-candidate' => __( 'Interview candidate', 'holyprofweb' ),
                                'partner-vendor'      => __( 'Partner / vendor', 'holyprofweb' ),
                                'customer-client'     => __( 'Customer / client', 'holyprofweb' ),
                                'affected-user'       => __( 'Affected user', 'holyprofweb' ),
                                'scam-reporter'       => __( 'Scam victim / reporter', 'holyprofweb' ),
                                'job-seeker'          => __( 'Job seeker', 'holyprofweb' ),
                                'other'               => __( 'Other', 'holyprofweb' ),
                            );
                            $experience_issue_labels = array(
                                'pay'        => __( 'Pay / benefits issue', 'holyprofweb' ),
                                'management' => __( 'Management problem', 'holyprofweb' ),
                                'culture'    => __( 'Culture problem', 'holyprofweb' ),
                                'workload'   => __( 'Workload / burnout', 'holyprofweb' ),
                                'interview'  => __( 'Interview problem', 'holyprofweb' ),
                                'support'    => __( 'Support / service issue', 'holyprofweb' ),
                                'product'    => __( 'Product / delivery issue', 'holyprofweb' ),
                                'billing'    => __( 'Billing / payment issue', 'holyprofweb' ),
                                'fraud'      => __( 'Fraud / scam concern', 'holyprofweb' ),
                                'communication' => __( 'Communication problem', 'holyprofweb' ),
                                'other'      => __( 'Other issue', 'holyprofweb' ),
                            );
                        ?>
                        <div class="review-card<?php echo $is_best ? ' review-card--featured' : ''; ?>">
                            <div class="review-card-header">
                                <div class="review-avatar" aria-hidden="true"><?php echo esc_html( $r_initial ); ?></div>
                                <div class="review-meta">
                                    <span class="review-author"><?php echo esc_html( $review->comment_author ); ?></span>
                                    <?php if ( $verified ) : ?><span class="review-verified-badge"><?php esc_html_e( 'Verified', 'holyprofweb' ); ?></span><?php endif; ?>
                                    <?php if ( $show_company_review_mode && ( $reviewer_type || $company_role_meta || $company_location_meta || $salary_range_meta || $interview_stage_meta || $experience_issue_meta ) ) : ?>
                                    <div class="review-context-meta">
                                        <?php if ( $reviewer_type && isset( $reviewer_type_labels[ $reviewer_type ] ) ) : ?><span class="review-context-chip"><?php echo esc_html( $reviewer_type_labels[ $reviewer_type ] ); ?></span><?php endif; ?>
                                        <?php if ( $company_role_meta ) : ?><span class="review-context-chip"><?php echo esc_html( $company_role_meta ); ?></span><?php endif; ?>
                                        <?php if ( $company_location_meta ) : ?><span class="review-context-chip"><?php echo esc_html( $company_location_meta ); ?></span><?php endif; ?>
                                        <?php if ( $salary_range_meta ) : ?><span class="review-context-chip review-context-chip--accent"><?php echo esc_html( $salary_range_meta ); ?></span><?php endif; ?>
                                        <?php if ( $interview_stage_meta ) : ?><span class="review-context-chip"><?php echo esc_html( $interview_stage_meta ); ?></span><?php endif; ?>
                                        <?php if ( $experience_issue_meta && isset( $experience_issue_labels[ $experience_issue_meta ] ) ) : ?><span class="review-context-chip"><?php echo esc_html( $experience_issue_labels[ $experience_issue_meta ] ); ?></span><?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ( $r_url ) : ?>
                                    <a href="<?php echo esc_url( $r_url ); ?>" class="review-site-url" target="_blank" rel="nofollow noopener">
                                        <?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $r_url, '/' ) ) ); ?>
                                    </a>
                                    <?php endif; ?>
                                    <time class="review-date" datetime="<?php echo esc_attr( get_comment_date( 'c', $review ) ); ?>">
                                        <?php echo esc_html( get_comment_date( 'M j, Y', $review ) ); ?>
                                    </time>
                                </div>
                                <?php if ( $r_rating > 0 ) : ?>
                                <div class="review-stars"><?php echo holyprofweb_render_stars( $r_rating ); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="review-card-body">
                                <p><?php echo esc_html( $review->comment_content ); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else : ?>
                    <p class="reviews-empty"><?php echo $show_company_review_mode ? esc_html__( 'No company reviews yet. Staff, interview candidates, partners, and customers can share what the experience is really like.', 'holyprofweb' ) : esc_html__( 'No reviews yet. Be the first to share your experience.', 'holyprofweb' ); ?></p>
                    <?php endif; ?>

                    <?php if ( $show_company_review_mode && ( $salary_min || $salary_max || $salary_role || $work_score ) ) : ?>
                    <div class="company-signals-strip" aria-label="<?php esc_attr_e( 'Company signals', 'holyprofweb' ); ?>">
                        <?php if ( $salary_min || $salary_max ) : ?>
                        <div class="company-signal-card">
                            <span class="company-signal-label"><?php esc_html_e( 'Salary Range', 'holyprofweb' ); ?></span>
                            <strong class="company-signal-value">
                                <?php
                                if ( $salary_min && $salary_max ) {
                                    echo esc_html( $salary_currency . number_format_i18n( $salary_min ) . ' - ' . $salary_currency . number_format_i18n( $salary_max ) . $salary_period );
                                } elseif ( $salary_min ) {
                                    echo esc_html( $salary_currency . number_format_i18n( $salary_min ) . $salary_period );
                                } elseif ( $salary_max ) {
                                    echo esc_html( $salary_currency . number_format_i18n( $salary_max ) . $salary_period );
                                }
                                ?>
                            </strong>
                        </div>
                        <?php endif; ?>
                        <?php if ( $salary_role ) : ?>
                        <div class="company-signal-card">
                            <span class="company-signal-label"><?php esc_html_e( 'Hiring Focus', 'holyprofweb' ); ?></span>
                            <strong class="company-signal-value"><?php echo esc_html( $salary_role ); ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ( $work_score ) : ?>
                        <div class="company-signal-card">
                            <span class="company-signal-label"><?php esc_html_e( 'Work-Life Score', 'holyprofweb' ); ?></span>
                            <strong class="company-signal-value"><?php echo esc_html( number_format_i18n( (float) $work_score, 1 ) ); ?>/5</strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="review-form-wrap review-form-wrap--switch" id="write-review">
                        <?php if ( $show_salary_module ) : ?>
                        <h3 class="review-form-title"><?php esc_html_e( 'Submit your salary', 'holyprofweb' ); ?></h3>
                        <p class="review-form-sub"><?php esc_html_e( 'Share your salary data privately. It will appear in admin immediately for moderation.', 'holyprofweb' ); ?></p>
                        <form class="review-form salary-form" id="salary-form" novalidate data-post-id="<?php echo esc_attr( $post_id ); ?>">
                            <div class="review-form-row">
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-name">Your Name <span class="review-required">*</span></label>
                                    <input type="text" id="salary-name" name="submitter_name" class="review-form-input" autocomplete="name" required />
                                </div>
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-email">Email <span class="review-required">*</span></label>
                                    <input type="email" id="salary-email" name="submitter_email" class="review-form-input" autocomplete="email" required />
                                </div>
                            </div>
                            <div class="review-form-row">
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-company">Company <span class="review-required">*</span></label>
                                    <input type="text" id="salary-company" name="salary_company" class="review-form-input" value="<?php echo esc_attr( $decoded_title ); ?>" required />
                                </div>
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-role">Role <span class="review-required">*</span></label>
                                    <input type="text" id="salary-role" name="salary_role" class="review-form-input" value="<?php echo esc_attr( $salary_role ); ?>" required />
                                </div>
                            </div>
                            <div class="review-form-row">
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-amount">Salary <span class="review-required">*</span></label>
                                    <input type="text" id="salary-amount" name="salary_amount" class="review-form-input" placeholder="e.g. 850000 / month" required />
                                </div>
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-location">Location</label>
                                    <input type="text" id="salary-location" name="salary_location" class="review-form-input" placeholder="e.g. Lagos, Nigeria" />
                                </div>
                            </div>
                            <div class="review-form-row">
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-currency">Currency</label>
                                    <input type="text" id="salary-currency" name="salary_currency" class="review-form-input" value="<?php echo esc_attr( $salary_currency ); ?>" />
                                </div>
                                <div class="review-form-field">
                                    <label class="review-form-label" for="salary-work-life">Work-Life Score</label>
                                    <input type="number" id="salary-work-life" name="salary_work_life" class="review-form-input" min="1" max="5" step="0.1" />
                                </div>
                            </div>
                            <div class="review-form-error" id="salary-error" aria-live="polite" hidden></div>
                            <button type="submit" class="review-form-submit" id="salary-submit">Submit Salary</button>
                        </form>
                        <?php else : ?>
                        <h3 class="review-form-title"><?php echo $show_company_review_mode ? esc_html__( 'Share your company experience', 'holyprofweb' ) : esc_html__( 'Have you used this?', 'holyprofweb' ); ?></h3>
                        <p class="review-form-sub"><?php echo $show_company_review_mode ? esc_html__( 'Current staff, former staff, interview candidates, partners, and customers can all share useful company context here.', 'holyprofweb' ) : esc_html__( 'Share your experience — your review helps others make better decisions.', 'holyprofweb' ); ?></p>
                        <form class="review-form" id="review-form" novalidate data-post-id="<?php echo esc_attr( $post_id ); ?>">
                            <div class="review-form-field">
                                <label class="review-form-label">Your Rating <span class="review-required">*</span></label>
                                <div class="review-star-picker" role="radiogroup" aria-label="Star rating">
                                    <?php for ( $s = 5; $s >= 1; $s-- ) : ?>
                                    <input type="radio" name="rating" id="rev-star-<?php echo $s; ?>" value="<?php echo $s; ?>" class="review-star-radio" />
                                    <label for="rev-star-<?php echo $s; ?>" class="review-star-label" title="<?php echo esc_attr( $s ); ?> stars">&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-star-hint">Click a star to rate</span>
                            </div>
                            <div class="review-form-row">
                                <div class="review-form-field">
                                    <label class="review-form-label" for="reviewer-name">Your Name <span class="review-required">*</span></label>
                                    <input type="text" id="reviewer-name" name="reviewer_name" class="review-form-input" placeholder="David Mark" autocomplete="name" required />
                                </div>
                                <div class="review-form-field">
                                    <label class="review-form-label" for="reviewer-email">Email <span class="review-required">*</span></label>
                                    <input type="email" id="reviewer-email" name="reviewer_email" class="review-form-input" placeholder="you@example.com" autocomplete="email" required />
                                    <span class="review-form-note">Not published publicly.</span>
                                </div>
                            </div>
                            <?php if ( $show_company_review_mode ) : ?>
                            <div class="review-form-field review-form-field--compact">
                                <label class="review-form-label" for="reviewer-type"><?php esc_html_e( 'You are writing as', 'holyprofweb' ); ?> <span class="review-required">*</span></label>
                                <select id="reviewer-type" name="reviewer_type" class="review-form-input" required>
                                    <option value=""><?php esc_html_e( 'Pick your relationship with this company', 'holyprofweb' ); ?></option>
                                    <option value="staff"><?php esc_html_e( 'Current staff', 'holyprofweb' ); ?></option>
                                    <option value="former-staff"><?php esc_html_e( 'Former staff', 'holyprofweb' ); ?></option>
                                    <option value="interview-candidate"><?php esc_html_e( 'Interview candidate', 'holyprofweb' ); ?></option>
                                    <option value="partner-vendor"><?php esc_html_e( 'Partner / vendor', 'holyprofweb' ); ?></option>
                                    <option value="customer-client"><?php esc_html_e( 'Customer / client', 'holyprofweb' ); ?></option>
                                    <option value="affected-user"><?php esc_html_e( 'Affected user', 'holyprofweb' ); ?></option>
                                    <option value="scam-reporter"><?php esc_html_e( 'Scam victim / reporter', 'holyprofweb' ); ?></option>
                                    <option value="job-seeker"><?php esc_html_e( 'Job seeker', 'holyprofweb' ); ?></option>
                                    <option value="other"><?php esc_html_e( 'Other', 'holyprofweb' ); ?></option>
                                </select>
                                <span class="review-form-note"><?php esc_html_e( 'Extra company fields stay hidden until you choose the path that fits.', 'holyprofweb' ); ?></span>
                            </div>
                            <div class="review-form-adaptive-panel" data-company-review-field="context" hidden>
                                <div class="review-form-row">
                                    <div class="review-form-field">
                                        <label class="review-form-label" for="company-role"><?php esc_html_e( 'Role or relationship', 'holyprofweb' ); ?></label>
                                        <input type="text" id="company-role" name="company_role" class="review-form-input" placeholder="<?php esc_attr_e( 'e.g. Product Designer, candidate, agency partner', 'holyprofweb' ); ?>" />
                                    </div>
                                    <div class="review-form-field">
                                        <label class="review-form-label" for="company-location"><?php esc_html_e( 'Location', 'holyprofweb' ); ?></label>
                                        <input type="text" id="company-location" name="company_location" class="review-form-input" placeholder="<?php esc_attr_e( 'e.g. Lagos, Nigeria', 'holyprofweb' ); ?>" />
                                    </div>
                                </div>
                                <div class="review-form-field" data-company-review-field="salary" hidden>
                                    <label class="review-form-label" for="salary-range"><?php esc_html_e( 'Salary range', 'holyprofweb' ); ?></label>
                                    <input type="text" id="salary-range" name="salary_range" class="review-form-input" placeholder="<?php esc_attr_e( 'Optional: e.g. 850k - 1.2m / month', 'holyprofweb' ); ?>" />
                                </div>
                                <div class="review-form-field" data-company-review-field="interview" hidden>
                                    <label class="review-form-label" for="interview-stage"><?php esc_html_e( 'Salary they asked or pay expectation mentioned', 'holyprofweb' ); ?></label>
                                    <input type="text" id="interview-stage" name="interview_stage" class="review-form-input" placeholder="<?php esc_attr_e( 'Optional: e.g. 700k fixed, salary expectation form, no range shared', 'holyprofweb' ); ?>" />
                                </div>
                                <div class="review-form-field" data-company-review-field="issue" hidden>
                                    <label class="review-form-label" for="experience-issue"><?php esc_html_e( 'What happened?', 'holyprofweb' ); ?></label>
                                    <select id="experience-issue" name="experience_issue" class="review-form-input">
                                        <option value=""><?php esc_html_e( 'Select what best fits', 'holyprofweb' ); ?></option>
                                        <option value="pay"><?php esc_html_e( 'Pay / benefits issue', 'holyprofweb' ); ?></option>
                                        <option value="management"><?php esc_html_e( 'Management problem', 'holyprofweb' ); ?></option>
                                        <option value="culture"><?php esc_html_e( 'Culture / toxic environment', 'holyprofweb' ); ?></option>
                                        <option value="workload"><?php esc_html_e( 'Workload / burnout', 'holyprofweb' ); ?></option>
                                        <option value="interview"><?php esc_html_e( 'Interview process issue', 'holyprofweb' ); ?></option>
                                        <option value="support"><?php esc_html_e( 'Support / service issue', 'holyprofweb' ); ?></option>
                                        <option value="product"><?php esc_html_e( 'Product / delivery issue', 'holyprofweb' ); ?></option>
                                        <option value="billing"><?php esc_html_e( 'Billing / payment issue', 'holyprofweb' ); ?></option>
                                        <option value="fraud"><?php esc_html_e( 'Fraud / scam concern', 'holyprofweb' ); ?></option>
                                        <option value="communication"><?php esc_html_e( 'Communication problem', 'holyprofweb' ); ?></option>
                                        <option value="other"><?php esc_html_e( 'Other', 'holyprofweb' ); ?></option>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="review-form-field">
                                <label class="review-form-label" for="review-content">Short Review <span class="review-required">*</span></label>
                                <textarea id="review-content" name="review_content" class="review-form-textarea" rows="5" placeholder="<?php echo esc_attr( $show_company_review_mode ? __( 'Share what it feels like to work with this company, interview here, partner with them, or buy from them...', 'holyprofweb' ) : __( 'Share what worked, what didn\'t, and who should use this platform...', 'holyprofweb' ) ); ?>" required></textarea>
                            </div>
                            <div class="review-form-error" id="review-error" aria-live="polite" hidden></div>
                            <button type="submit" class="review-form-submit" id="review-submit">Post Review</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </section>
                <?php else : ?>
                <section class="review-form-wrap review-form-wrap--switch biography-contribution-box" id="write-review">
                    <h3 class="review-form-title"><?php esc_html_e( 'Have Information About This Person?', 'holyprofweb' ); ?></h3>
                    <p class="review-form-sub"><?php esc_html_e( 'Share corrections, missing background, achievements, timeline updates, or any useful context for editorial review.', 'holyprofweb' ); ?></p>
                    <div class="single-header-actions">
                        <a href="<?php echo esc_url( add_query_arg( array( 'submit_category' => 'biography', 'submit_name' => $decoded_title, 'mode' => 'suggest-edit' ), home_url( '/submit/' ) ) ); ?>" class="single-action-btn single-action-btn--alt"><?php esc_html_e( 'Suggest Edit', 'holyprofweb' ); ?></a>
                        <a href="<?php echo esc_url( add_query_arg( array( 'submit_category' => 'biography', 'submit_name' => $decoded_title, 'mode' => 'add-information' ), home_url( '/submit/' ) ) ); ?>" class="single-action-btn"><?php esc_html_e( 'Add Information', 'holyprofweb' ); ?></a>
                    </div>
                </section>
                <?php endif; ?>

                <?php
                $related       = holyprofweb_get_related_posts( $post_id, 3 );
                $similar_posts = new WP_Query( array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'post__not_in'   => array( $post_id ),
                    'category__in'   => $primary_cat ? array( (int) $primary_cat->term_id ) : array(),
                    'no_found_rows'  => true,
                ) );
                $compare_posts = new WP_Query( array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'post__not_in'   => array( $post_id ),
                    'tag__in'        => $tags ? wp_list_pluck( $tags, 'term_id' ) : array(),
                    'no_found_rows'  => true,
                ) );
                $search_terms   = holyprofweb_get_trending_searches( 5 );
                $salary_links   = new WP_Query( array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'post__not_in'   => array( $post_id ),
                    'category_name'  => 'salaries',
                    's'              => wp_trim_words( get_the_title(), 3, '' ),
                    'no_found_rows'  => true,
                ) );
                $tips_links     = new WP_Query( array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'post__not_in'   => array( $post_id ),
                    'category_name'  => 'reports',
                    'no_found_rows'  => true,
                ) );
                ?>
                <?php if ( $related instanceof WP_Query && $related->have_posts() ) : ?>
                <p class="also-read-inline">
                    <strong><?php esc_html_e( 'Also read:', 'holyprofweb' ); ?></strong>
                    <?php
                    $also_links = array();
                    while ( $related->have_posts() ) :
                        $related->the_post();
                        $also_links[] = '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( holyprofweb_get_decoded_post_title() ) . '</a>';
                    endwhile;
                    wp_reset_postdata();
                    echo wp_kses_post( implode( ' / ', $also_links ) );
                    ?>
                </p>
                <?php endif; ?>
                <section class="related-posts related-posts--stack" aria-labelledby="related-title">
                    <h2 id="related-title" class="related-posts-title">Keep Exploring</h2>
                    <?php if ( ! empty( $search_terms ) ) : ?>
                    <div class="search-also-searched">
                        <p class="search-also-label">People also searched</p>
                        <div class="search-also-pills">
                            <?php foreach ( $search_terms as $term ) : ?>
                            <a href="<?php echo esc_url( home_url( '/?s=' . urlencode( $term['term'] ) ) ); ?>" class="search-cat-pill"><?php echo esc_html( $term['term'] ); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="related-link-grid">
                        <?php if ( $similar_posts->have_posts() ) : ?>
                        <div>
                            <h3 class="related-posts-title">Similar Platforms</h3>
                            <div class="related-grid">
                                <?php while ( $similar_posts->have_posts() ) : $similar_posts->the_post(); ?>
                                <article class="related-card">
                                    <a href="<?php the_permalink(); ?>" class="related-card-thumb"><img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" /></a>
                                    <h4 class="related-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h4>
                                </article>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( $compare_posts->have_posts() ) : ?>
                        <div>
                            <h3 class="related-posts-title">Compare With</h3>
                            <div class="related-grid">
                                <?php while ( $compare_posts->have_posts() ) : $compare_posts->the_post(); ?>
                                <article class="related-card">
                                    <a href="<?php the_permalink(); ?>" class="related-card-thumb"><img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" /></a>
                                    <h4 class="related-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h4>
                                </article>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( $is_company_post && $salary_links->have_posts() ) : ?>
                        <div>
                            <h3 class="related-posts-title">Salary Insights</h3>
                            <div class="related-grid">
                                <?php while ( $salary_links->have_posts() ) : $salary_links->the_post(); ?>
                                <article class="related-card">
                                    <a href="<?php the_permalink(); ?>" class="related-card-thumb"><img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" /></a>
                                    <h4 class="related-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h4>
                                </article>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( $is_company_post && $tips_links->have_posts() ) : ?>
                        <div>
                            <h3 class="related-posts-title">Preparing For Interview</h3>
                            <div class="related-grid">
                                <?php while ( $tips_links->have_posts() ) : $tips_links->the_post(); ?>
                                <article class="related-card">
                                    <a href="<?php the_permalink(); ?>" class="related-card-thumb"><img src="<?php echo esc_attr( holyprofweb_get_post_card_image_url( get_the_ID() ) ); ?>" alt="<?php echo esc_attr( holyprofweb_get_decoded_post_title() ); ?>" loading="lazy" class="<?php echo esc_attr( holyprofweb_get_post_image_class( get_the_ID() ) ); ?>" /></a>
                                    <h4 class="related-card-title"><a href="<?php the_permalink(); ?>"><?php holyprofweb_the_decoded_title(); ?></a></h4>
                                </article>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Standard WP comments (for general discussion, separate from reviews) -->
                <?php if ( holyprofweb_post_allows_wp_comments( $post_id ) && comments_open() ) {
                    comments_template();
                } ?>

            </article>

            <!-- =========================================
                 RIGHT SIDEBAR
            ========================================= -->
            <aside class="sidebar single-sidebar" role="complementary" aria-label="Sidebar">

                <?php holyprofweb_render_ad( 'sidebar' ); ?>

                <?php get_sidebar(); ?>

                <?php holyprofweb_render_ad( 'sidebar_2' ); ?>

            </aside>

        </div><!-- .single-wrap -->
        <?php endwhile; ?>
    </main>

</div><!-- .platform-wrap -->

<?php get_footer(); ?>
