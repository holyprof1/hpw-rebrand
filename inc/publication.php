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

/**
 * <img> markup for a post, or '' when it has no usable image (callers render a typographic
 * placeholder instead of a broken image). Real attachments get srcset/sizes; remote or generated
 * images fall back to a plain URL with explicit dimensions.
 */
function holyprofweb_pub_media( $post_id, $size = 'holyprofweb-card', $sizes = '(max-width: 600px) 100vw, 380px', $eager = false ) {
    $attrs = array(
        'alt'      => '',
        'sizes'    => $sizes,
        'decoding' => 'async',
    );
    if ( $eager ) {
        $attrs['fetchpriority'] = 'high';
        $attrs['loading']       = 'eager';
    } else {
        $attrs['loading'] = 'lazy';
    }
    if ( function_exists( 'holyprofweb_post_has_trusted_featured_image' ) && holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
        return wp_get_attachment_image( get_post_thumbnail_id( $post_id ), $size, false, $attrs );
    }
    $url = holyprofweb_get_post_card_image_url( $post_id );
    // The legacy auto-generated title cards (data: URIs) are not editorial images; show the neutral placeholder instead.
    if ( ! $url || 0 === strpos( $url, 'data:' ) ) {
        return '';
    }
    $dim = holyprofweb_get_image_size_dimensions( 'holyprofweb-card' );
    return sprintf(
        '<img src="%s" alt="" width="%d" height="%d" %s decoding="async" />',
        esc_url( $url ),
        (int) $dim['width'],
        (int) $dim['height'],
        $eager ? 'fetchpriority="high"' : 'loading="lazy"'
    );
}

/** True when a post has a real (non-generated) featured image. */
function holyprofweb_pub_has_real_image( $post_id ) {
    return function_exists( 'holyprofweb_post_has_trusted_featured_image' ) && holyprofweb_post_has_trusted_featured_image( $post_id );
}

/** "Updated" shows only when a meaningful edit was recorded (see inc/dates.php) and it is more than a day after publishing. */
function holyprofweb_pub_dates( $post_id ) {
    $pub = (int) get_post_time( 'U', true, $post_id );
    $mod = holyprofweb_content_modified_ts( $post_id );
    $out = '<time datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( get_the_date( 'M j, Y', $post_id ) ) . '</time>';
    if ( $mod - $pub > DAY_IN_SECONDS ) {
        $out .= ' <span class="pub-updated">· Updated <time datetime="' . esc_attr( holyprofweb_content_modified_iso( $post_id ) ) . '">' . esc_html( wp_date( 'M j, Y', $mod ) ) . '</time></span>';
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
    $o       = wp_parse_args( $o, array( 'variant' => '', 'excerpt' => false, 'eager' => false, 'tag' => 'h3', 'thumb' => true ) );
    $tag     = in_array( $o['tag'], array( 'h2', 'h3' ), true ) ? $o['tag'] : 'h3';
    $kicker  = holyprofweb_post_kicker( $post_id );
    $lead    = 'lead' === $o['variant'];
    $size    = $lead ? 'large' : 'holyprofweb-card';
    $sizes   = $lead ? '(max-width: 900px) 100vw, 1200px' : '(max-width: 600px) 100vw, 380px';
    $img     = holyprofweb_pub_media( $post_id, $size, $sizes, $o['eager'] );
    $title   = holyprofweb_get_decoded_post_title( $post_id );
    $classes = 'pub-card' . ( $o['variant'] ? ' pub-card--' . $o['variant'] : '' );
    ?>
    <article class="<?php echo esc_attr( $classes ); ?>">
        <?php if ( 'compact' !== $o['variant'] && $o['thumb'] ) : ?>
        <a class="pub-card__media<?php echo $img ? '' : ' pub-card__media--empty'; ?>" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
            <?php echo $img ? $img : '<span class="pub-card__mark">' . esc_html( $kicker['label'] ? $kicker['label'] : get_bloginfo( 'name' ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
        </a>
        <?php endif; ?>
        <div class="pub-card__body">
            <?php if ( $kicker['label'] ) : ?>
            <a class="pub-kicker" href="<?php echo esc_url( $kicker['url'] ); ?>"><?php echo esc_html( $kicker['label'] ); ?></a>
            <?php endif; ?>
            <<?php echo $tag; ?> class="pub-card__title"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( $title ); ?></a></<?php echo $tag; ?>>
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

// Automated body injections from the legacy theme are off: "Also read" blocks and title auto-links
// produced irrelevant links. Related links are editorial (hand-written) or in the related block.
remove_filter( 'the_content', 'holyprofweb_inject_inline_also_read', 19 );
remove_filter( 'the_content', 'holyprofweb_auto_interlink', 15 );
