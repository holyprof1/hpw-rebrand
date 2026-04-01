<?php
/**
 * Comments Template — HolyprofWeb
 * General discussion only. Star reviews are handled separately in single.php.
 */

if ( post_password_required() ) {
    return;
}

// Only show if there are existing comments or comments are open
if ( ! have_comments() && ! comments_open() ) {
    return;
}
?>

<section id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>

    <h2 class="comments-title">
        <?php
        $comment_count = get_comments_number();
        echo esc_html( sprintf(
            _n( '%s Comment', '%s Comments', $comment_count, 'holyprofweb' ),
            number_format_i18n( $comment_count )
        ) );
        ?>
    </h2>

    <ol class="comment-list">
        <?php
        wp_list_comments( array(
            'style'       => 'ol',
            'short_ping'  => true,
            'avatar_size' => 36,
            'callback'    => 'holyprofweb_comment',
        ) );
        ?>
    </ol>

    <?php the_comments_pagination( array(
        'prev_text' => '&larr; ' . esc_html__( 'Older comments', 'holyprofweb' ),
        'next_text' => esc_html__( 'Newer comments', 'holyprofweb' ) . ' &rarr;',
    ) ); ?>

    <?php endif; ?>

    <?php if ( comments_open() ) : ?>
    <?php
    comment_form( array(
        'title_reply'          => esc_html__( 'Leave a Comment', 'holyprofweb' ),
        'title_reply_before'   => '<h2 id="reply-title" class="comments-title">',
        'title_reply_after'    => '</h2>',
        'comment_notes_before' => '',
        'comment_notes_after'  => '',
        'label_submit'         => esc_html__( 'Post Comment', 'holyprofweb' ),
        'class_submit'         => 'submit',
        'comment_field'        => '<p class="comment-form-comment">
            <label for="comment">' . esc_html__( 'Comment', 'holyprofweb' ) . '</label>
            <textarea id="comment" name="comment" cols="45" rows="5" required></textarea>
        </p>',
    ) );
    ?>
    <?php endif; ?>

</section><!-- #comments -->

<?php
/**
 * Custom comment callback.
 */
function holyprofweb_comment( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment;
    ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'comment' ); ?>>
        <article>
            <header>
                <span class="comment-author-name"><?php comment_author(); ?></span>
                <p class="comment-meta">
                    <time datetime="<?php comment_time( 'c' ); ?>">
                        <?php comment_date(); ?> <?php esc_html_e( 'at', 'holyprofweb' ); ?> <?php comment_time(); ?>
                    </time>
                </p>
            </header>
            <div class="comment-body">
                <?php if ( '0' === $comment->comment_approved ) : ?>
                <p><em><?php esc_html_e( 'Your comment is awaiting moderation.', 'holyprofweb' ); ?></em></p>
                <?php endif; ?>
                <?php comment_text(); ?>
            </div>
            <footer>
                <?php
                comment_reply_link( array_merge( $args, array(
                    'add_below' => 'comment',
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'before'    => '<p class="reply" style="font-size:0.8rem;">',
                    'after'     => '</p>',
                ) ) );
                ?>
            </footer>
        </article>
    <?php
    // No closing </li> — WordPress adds it automatically.
}
