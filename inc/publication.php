<?php
/**
 * Publication front end: card/hero helpers and stylesheet.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', function () {
    $file = get_template_directory() . '/assets/css/publication.css';
    wp_enqueue_style(
        'holyprofweb-publication',
        get_template_directory_uri() . '/assets/css/publication.css',
        array(),
        file_exists( $file ) ? (string) filemtime( $file ) : null
    );
}, 30 );

/** Image URL for a post; large when a real featured image exists. */
function holyprofweb_pub_image_url( $post_id, $large = false ) {
    if ( $large && function_exists( 'holyprofweb_post_has_trusted_featured_image' ) && holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
        $url = get_the_post_thumbnail_url( $post_id, 'large' );
        if ( $url ) {
            return $url;
        }
    }
    return holyprofweb_get_post_card_image_url( $post_id );
}

/** "Updated" is shown only when the post was meaningfully modified (more than a day after publishing). */
function holyprofweb_pub_dates( $post_id ) {
    $pub = get_post_time( 'U', true, $post_id );
    $mod = get_post_modified_time( 'U', true, $post_id );
    $out = '<time datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( get_the_date( 'M j, Y', $post_id ) ) . '</time>';
    if ( $mod - $pub > DAY_IN_SECONDS ) {
        $out .= ' <span class="pub-updated">· Updated <time datetime="' . esc_attr( get_the_modified_date( 'c', $post_id ) ) . '">' . esc_html( get_the_modified_date( 'M j, Y', $post_id ) ) . '</time></span>';
    }
    return $out;
}

function holyprofweb_pub_excerpt( $post_id, $words = 22 ) {
    return wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), $words, '…' );
}

/**
 * Render a story card.
 * $o: variant (''|'lead'|'compact'|'row'), excerpt (bool), eager (bool).
 */
function holyprofweb_pub_card( $post_id, array $o = array() ) {
    $o       = wp_parse_args( $o, array( 'variant' => '', 'excerpt' => false, 'eager' => false ) );
    $kicker  = holyprofweb_post_kicker( $post_id );
    $lead    = 'lead' === $o['variant'];
    $img     = holyprofweb_pub_image_url( $post_id, $lead );
    $w       = $lead ? 1200 : 640;
    $h       = $lead ? 675 : 480;
    $title   = holyprofweb_get_decoded_post_title( $post_id );
    $classes = 'pub-card' . ( $o['variant'] ? ' pub-card--' . $o['variant'] : '' );
    ?>
    <article class="<?php echo esc_attr( $classes ); ?>">
        <?php if ( 'compact' !== $o['variant'] ) : ?>
        <a class="pub-card__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
            <img src="<?php echo esc_url( $img ); ?>" alt="" width="<?php echo (int) $w; ?>" height="<?php echo (int) $h; ?>"
                 <?php echo $o['eager'] ? 'fetchpriority="high" decoding="async"' : 'loading="lazy" decoding="async"'; ?> />
        </a>
        <?php endif; ?>
        <div class="pub-card__body">
            <?php if ( $kicker['label'] ) : ?>
            <a class="pub-kicker" href="<?php echo esc_url( $kicker['url'] ); ?>"><?php echo esc_html( $kicker['label'] ); ?></a>
            <?php endif; ?>
            <h3 class="pub-card__title"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( $title ); ?></a></h3>
            <?php if ( $o['excerpt'] ) : ?>
            <p class="pub-card__excerpt"><?php echo esc_html( holyprofweb_pub_excerpt( $post_id, $lead ? 34 : 20 ) ); ?></p>
            <?php endif; ?>
            <p class="pub-meta"><?php echo holyprofweb_pub_dates( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
        </div>
    </article>
    <?php
}

/** Up to $n recent post IDs for a section, skipping ids already used on the page. */
function holyprofweb_pub_section_ids( $slug, $n, array &$used ) {
    $q   = new WP_Query( holyprofweb_section_query_args( $slug, array(
        'posts_per_page' => $n,
        'post__not_in'   => $used,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) ) );
    $ids = $q->posts;
    $used = array_merge( $used, $ids );
    return $ids;
}
