<?php
/**
 * Front page: curated, not "latest rows". One lead story, supporting stories, then each section.
 */

get_header();

$sections = holyprofweb_sections();
$used     = array();
$meta     = holyprofweb_real_posts_meta_query();

// Candidate pool: recent real posts without old review-farm titles.
$pool_q = new WP_Query( array(
    'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 60, 'ignore_sticky_posts' => true,
    'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => $meta,
) );
$pool = array();
foreach ( $pool_q->posts as $pid ) {
    if ( ! holyprofweb_pub_is_farm_title( $pid ) ) {
        $pool[] = (int) $pid;
    }
}

// Lead: the newest story that has a real image, else simply the newest.
$lead_id = 0;
foreach ( $pool as $pid ) {
    if ( holyprofweb_pub_has_real_image( $pid ) ) { $lead_id = $pid; break; }
}
if ( ! $lead_id && $pool ) {
    $lead_id = $pool[0];
}
if ( $lead_id ) { $used[] = $lead_id; }

$side_ids = array();
foreach ( $pool as $pid ) {
    if ( ! in_array( $pid, $used, true ) && count( $side_ids ) < 3 ) {
        $side_ids[] = $pid;
        $used[]     = $pid;
    }
}

// One block per section, only when it has stories not already shown above.
$blocks = array();
foreach ( array_keys( $sections ) as $slug ) {
    $ids = holyprofweb_pub_pick( $slug, 3, $used, true );
    if ( $ids ) { $blocks[ $slug ] = $ids; }
}

// Recently updated: stories with a recorded meaningful update.
$updated = array();
foreach ( $pool as $pid ) {
    if ( ! in_array( $pid, $used, true ) && holyprofweb_content_modified_ts( $pid ) - (int) get_post_time( 'U', true, $pid ) > DAY_IN_SECONDS ) {
        $updated[] = $pid;
    }
}
usort( $updated, function ( $a, $b ) { return holyprofweb_content_modified_ts( $b ) <=> holyprofweb_content_modified_ts( $a ); } );
$updated = array_slice( $updated, 0, 5 );
?>
<main id="primary" class="site-main pub-home">

    <div class="pub-masthead">
        <div class="pub-wrap">
            <p><?php esc_html_e( 'New apps, websites, products, people and internet trends, researched and explained clearly.', 'holyprofweb' ); ?></p>
            <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'How we work', 'holyprofweb' ); ?> &rarr;</a>
        </div>
    </div>

    <section class="pub-hero" aria-labelledby="hero-title">
        <div class="pub-wrap">
            <h1 id="hero-title" class="pub-hero__title"><?php esc_html_e( 'Discover what’s new online.', 'holyprofweb' ); ?></h1>
            <?php if ( $lead_id ) : ?>
            <div class="pub-hero__grid">
                <?php holyprofweb_pub_card( $lead_id, array( 'variant' => 'lead', 'excerpt' => true, 'eager' => true, 'tag' => 'h2' ) ); ?>
                <?php if ( $side_ids ) : ?>
                <div class="pub-hero__side">
                    <?php foreach ( $side_ids as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'compact', 'excerpt' => true ) ); } ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else : ?>
            <p class="pub-hub__intro"><?php esc_html_e( 'Fresh stories are on the way. Meanwhile, explore the four sections below.', 'holyprofweb' ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ( $blocks as $slug => $ids ) : ?>
    <section class="pub-section<?php echo 'people' === $slug ? ' pub-people' : ''; ?>" aria-labelledby="blk-<?php echo esc_attr( $slug ); ?>">
        <div class="pub-wrap">
            <header class="pub-section__head">
                <h2 id="blk-<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $sections[ $slug ]['title'] ); ?></h2>
                <a href="<?php echo esc_url( holyprofweb_section_url( $slug ) ); ?>"><?php esc_html_e( 'See all', 'holyprofweb' ); ?></a>
            </header>
            <?php if ( 'people' === $slug ) : ?>
            <div class="pub-people__row">
                <?php foreach ( $ids as $id ) : $portrait = holyprofweb_pub_media( $id, 'holyprofweb-thumb', '168px' ); ?>
                <a class="pub-person" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
                    <span class="pub-person__img<?php echo $portrait ? '' : ' pub-person__img--empty'; ?>"><?php echo $portrait ? $portrait : '<span>' . esc_html( mb_substr( wp_strip_all_tags( holyprofweb_get_decoded_post_title( $id ) ), 0, 1 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                    <span class="pub-person__name"><?php echo esc_html( holyprofweb_get_decoded_post_title( $id ) ); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="pub-grid pub-grid--3">
                <?php foreach ( $ids as $id ) { holyprofweb_pub_card( $id, array( 'excerpt' => true ) ); } ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if ( $updated ) : ?>
    <section class="pub-section" aria-labelledby="blk-updated">
        <div class="pub-wrap pub-wrap--narrow">
            <header class="pub-section__head"><h2 id="blk-updated"><?php esc_html_e( 'Recently updated', 'holyprofweb' ); ?></h2></header>
            <div class="pub-list">
                <?php foreach ( $updated as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'row', 'thumb' => false ) ); } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="pub-section" aria-labelledby="blk-explore">
        <div class="pub-wrap">
            <header class="pub-section__head">
                <h2 id="blk-explore"><?php esc_html_e( 'Explore by section', 'holyprofweb' ); ?></h2>
                <a href="<?php echo esc_url( holyprofweb_get_blog_url() ); ?>"><?php esc_html_e( 'All stories', 'holyprofweb' ); ?></a>
            </header>
            <div class="pub-grid pub-grid--2">
                <?php foreach ( $sections as $slug => $s ) : ?>
                <article class="pub-card pub-card--noimg">
                    <h3 class="pub-card__title"><a href="<?php echo esc_url( holyprofweb_section_url( $slug ) ); ?>"><?php echo esc_html( $s['label'] ); ?></a></h3>
                    <p class="pub-card__excerpt"><?php echo esc_html( $s['intro'] ); ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>
<?php
get_footer();
