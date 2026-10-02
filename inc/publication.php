<?php
/**
 * Publication front end: card/hero helpers and stylesheet.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Styles live in assets/css/site.css (enqueued from functions.php).

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
    if ( holyprofweb_pub_has_real_image( $post_id ) && has_post_thumbnail( $post_id ) ) {
        return wp_get_attachment_image( get_post_thumbnail_id( $post_id ), $size, false, $attrs );
    }
    // Only editorial images: an attached real featured image (above) or an explicitly set external image.
    // Legacy auto-generated cards and scraped site logos are not shown; the placeholder is used instead.
    $url = trim( (string) get_post_meta( $post_id, 'external_image', true ) );
    if ( ! $url || 0 !== strpos( $url, 'https://' ) ) {
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
    if ( function_exists( 'holyprofweb_post_has_trusted_featured_image' ) && holyprofweb_post_has_trusted_featured_image( $post_id ) ) {
        // The generic brand/placeholder artwork is not an editorial image.
        $file = (string) get_post_meta( get_post_thumbnail_id( $post_id ), '_wp_attached_file', true );
        return ! preg_match( '/placeholder|hpw-generated|logo/i', wp_basename( $file ) );
    }
    return 0 === strpos( trim( (string) get_post_meta( $post_id, 'external_image', true ) ), 'https://' );
}

/**
 * Related stories chosen for relevance, not just "same broad category":
 * Other posts that mention the subject named at the start of the title. Tags and broad categories are
 * deliberately not used: on this archive they were auto-assigned and produced unrelated links.
 * Returns up to $n IDs; an empty array means "show nothing".
 */
function holyprofweb_pub_related( $post_id, $n = 3 ) {
    $found = array();
    $base  = array( 'post_type' => 'post', 'post_status' => 'publish', 'post__not_in' => array( $post_id ), 'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true, 'meta_query' => holyprofweb_real_posts_meta_query() );

    {
        $title = wp_strip_all_tags( html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ) );
        $subject = trim( preg_split( '/\s+(?:review|reviews|—|–|-|:|is|how|what|who|why)\b|[:—–?]/i', $title, 2 )[0] );
        if ( mb_strlen( $subject ) >= 4 ) {
            $found = array_merge( $found, get_posts( array_merge( $base, array( 'posts_per_page' => $n, 's' => $subject, 'post__not_in' => array_merge( array( $post_id ), $found ) ) ) ) );
        }
    }

    return array_slice( array_values( array_unique( array_map( 'intval', $found ) ) ), 0, $n );
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
    if ( 'text' === $o['variant'] ) { $img = ''; }
    $title   = holyprofweb_get_decoded_post_title( $post_id );
    $classes = 'pub-card' . ( $o['variant'] ? ' pub-card--' . $o['variant'] : '' ) . ( $img ? '' : ' pub-card--noimg' );
    ?>
    <article class="<?php echo esc_attr( $classes ); ?>">
        <?php if ( $img && 'compact' !== $o['variant'] && $o['thumb'] ) : ?>
        <a class="pub-card__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
        <?php endif; ?>
        <div class="pub-card__body">
            <?php if ( $kicker['label'] ) : ?>
            <?php if ( $kicker['url'] ) : ?><a class="pub-kicker" href="<?php echo esc_url( $kicker['url'] ); ?>"><?php echo esc_html( $kicker['label'] ); ?></a><?php else : ?><span class="pub-kicker"><?php echo esc_html( $kicker['label'] ); ?></span><?php endif; ?>
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

/**
 * Homepage-only quality gate. Old review-farm and biography-farm titles stay on the site and in their
 * hubs, but do not lead the front page. Pure title heuristics; no post is modified.
 */
function holyprofweb_pub_is_farm_title( $post_id, $strict = false ) {
    $t = wp_strip_all_tags( html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ) );
    if ( preg_match( '/\b(legit|scam)\b|complaints|side effects|honest overview|net worth|\bwife\b|scandal|before and after|\bage,|biography:/i', $t ) ) {
        return true;
    }
    // The excerpt gives away the template used by the old scam-check posts.
    $x = wp_strip_all_tags( get_the_excerpt( $post_id ) );
    if ( preg_match( '/legit or a scam|trust signals|honest verdict|red flags|net worth/i', $x ) ) {
        return true;
    }
    // Strict mode (used for the lead story) also skips plain "... Review" titles.
    return $strict && (bool) preg_match( '/\breviews?\b/i', $t );
}

/**
 * Pick up to $n IDs from a section pool for the homepage: newest first, farm titles skipped,
 * posts with a real image preferred when $prefer_images is true. Records picks in $used.
 */
function holyprofweb_pub_pick( $slug, $n, array &$used, $prefer_images = false ) {
    $q = new WP_Query( holyprofweb_section_query_args( $slug, array(
        'posts_per_page' => 80,
        'post__not_in'   => $used,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) ) );
    $good = array();
    foreach ( $q->posts as $id ) {
        if ( ! holyprofweb_pub_is_farm_title( $id ) ) {
            $good[] = $id;
        }
    }
    if ( $prefer_images ) {
        usort( $good, function ( $a, $b ) {
            return (int) holyprofweb_pub_has_real_image( $b ) <=> (int) holyprofweb_pub_has_real_image( $a );
        } );
    }
    $picked = array_slice( $good, 0, $n );
    $used   = array_merge( $used, $picked );
    return $picked;
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

/**
 * Generic archive page (all stories, tags, dates, legacy categories) in the publication layout.
 * No ratings, scores or review language: titles, kicker, date.
 */
function holyprofweb_render_archive_page( $title, $intro = '' ) {
    get_header();
    $ids = array();
    while ( have_posts() ) {
        the_post();
        $ids[] = get_the_ID();
    }
    ?>
    <main id="primary" class="site-main pub-hub">
        <header class="pub-hub__head">
            <div class="pub-wrap">
                <nav class="pub-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'holyprofweb' ); ?>">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'holyprofweb' ); ?></a>
                </nav>
                <h1 class="pub-hub__title"><?php echo esc_html( $title ); ?></h1>
                <?php if ( $intro ) : ?><p class="pub-hub__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
            </div>
        </header>
        <div class="pub-wrap pub-wrap--narrow">
            <?php if ( $ids ) : ?>
            <div class="pub-list">
                <?php foreach ( $ids as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'row', 'excerpt' => true, 'thumb' => false ) ); } ?>
            </div>
            <?php the_posts_pagination( array( 'prev_text' => __( 'Newer', 'holyprofweb' ), 'next_text' => __( 'Older', 'holyprofweb' ) ) ); ?>
            <?php else : ?>
            <p><?php esc_html_e( 'Nothing published here yet.', 'holyprofweb' ); ?></p>
            <?php endif; ?>
        </div>
    </main>
    <?php
    get_footer();
}
