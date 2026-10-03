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

usort( $pool, function ( $x, $y ) { return holyprofweb_content_modified_ts( $y ) <=> holyprofweb_content_modified_ts( $x ); } );

// Lead: the freshest story with a real image; the two supporting stories come from other sections.
$lead_id = 0;
foreach ( $pool as $pid ) {
    if ( holyprofweb_pub_has_real_image( $pid ) ) { $lead_id = $pid; break; }
}
if ( ! $lead_id && $pool ) {
    $lead_id = $pool[0];
}
if ( $lead_id ) { $used[] = $lead_id; }
$lead_section = $lead_id ? holyprofweb_post_section( $lead_id ) : '';

$side_ids = array();
$seen_sec = array( $lead_section );
foreach ( $pool as $pid ) {
    if ( count( $side_ids ) >= 2 || in_array( $pid, $used, true ) ) { continue; }
    $sec = holyprofweb_post_section( $pid );
    if ( in_array( $sec, $seen_sec, true ) ) { continue; }
    $side_ids[] = $pid; $used[] = $pid; $seen_sec[] = $sec;
}
foreach ( $pool as $pid ) {
    if ( count( $side_ids ) >= 2 ) { break; }
    if ( ! in_array( $pid, $used, true ) ) { $side_ids[] = $pid; $used[] = $pid; }
}

// Latest discoveries: the next freshest stories, any section.
$latest = array();
foreach ( $pool as $pid ) {
    if ( count( $latest ) >= 6 ) { break; }
    if ( ! in_array( $pid, $used, true ) ) { $latest[] = $pid; $used[] = $pid; }
}

// One block per section, only when it has stories not already shown above.
$blocks = array();
foreach ( array_keys( $sections ) as $slug ) {
    $ids = holyprofweb_pub_pick( $slug, 'apps-websites' === $slug ? 6 : 3, $used, true );
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
$updated = array_slice( $updated, 0, 6 );
?>
<main id="primary" class="site-main pub-home">

    <section class="pub-intro" aria-labelledby="hero-title">
        <div class="pub-wrap">
            <h1 id="hero-title" class="pub-intro__title"><?php esc_html_e( 'Look up any app, website, product or person before you use it, buy it or trust it.', 'holyprofweb' ); ?></h1>
            <p class="pub-intro__lede"><?php esc_html_e( 'Holyprofweb researches what is new online and explains it in plain language: what it is, who owns it, how it works, what it costs and what to check first. Every article names its sources and says what we could not verify.', 'holyprofweb' ); ?></p>
            <form role="search" method="get" class="search-form pub-intro__search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label for="intro-search" class="screen-reader-text"><?php esc_html_e( 'Search Holyprofweb', 'holyprofweb' ); ?></label>
                <input id="intro-search" class="search-field" type="search" name="s" placeholder="<?php esc_attr_e( 'Search an app, website, product or person, for example Booking.com or Chime', 'holyprofweb' ); ?>" autocomplete="off" />
                <button type="submit" class="search-submit"><?php esc_html_e( 'Search', 'holyprofweb' ); ?></button>
            </form>
            <ul class="pub-intro__tiles">
                <?php
                $tile_text = array(
                    'apps-websites'   => __( 'Who owns it, how it works, what it costs', 'holyprofweb' ),
                    'products-tech'   => __( 'Price, specs and who it is for', 'holyprofweb' ),
                    'people'          => __( 'Founders and creators, verified facts only', 'holyprofweb' ),
                    'internet-trends' => __( 'What is trending and where it started', 'holyprofweb' ),
                );
                foreach ( $sections as $slug => $sec ) : ?>
                <li><a href="<?php echo esc_url( holyprofweb_section_url( $slug ) ); ?>"><strong><?php echo esc_html( $sec['label'] ); ?></strong><span><?php echo esc_html( $tile_text[ $slug ] ?? '' ); ?></span></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="pub-hero" aria-label="Top stories">
        <div class="pub-wrap">
            <h2 class="pub-hero__label"><?php esc_html_e( 'Top stories', 'holyprofweb' ); ?></h2>
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

    <?php if ( $latest ) : ?>
    <section class="pub-section pub-section--tight" aria-labelledby="blk-latest">
        <div class="pub-wrap">
            <header class="pub-section__head"><h2 id="blk-latest"><?php esc_html_e( 'Latest discoveries', 'holyprofweb' ); ?></h2></header>
            <div class="pub-grid pub-grid--3 pub-latest">
                <?php foreach ( $latest as $id ) { holyprofweb_pub_card( $id, array( 'variant' => 'text' ) ); } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php foreach ( $blocks as $slug => $ids ) : ?>
    <section class="pub-section<?php echo 'people' === $slug ? ' pub-people' : ''; ?>" aria-labelledby="blk-<?php echo esc_attr( $slug ); ?>">
        <div class="pub-wrap">
            <header class="pub-section__head">
                <h2 id="blk-<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $sections[ $slug ]['title'] ); ?></h2>
                <a href="<?php echo esc_url( holyprofweb_section_url( $slug ) ); ?>"><?php esc_html_e( 'See all', 'holyprofweb' ); ?></a>
            </header>
            <div class="pub-grid pub-grid--3">
                <?php foreach ( $ids as $id ) { holyprofweb_pub_card( $id, array( 'excerpt' => true ) ); } ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if ( $updated ) : ?>
    <section class="pub-section" aria-labelledby="blk-updated">
        <div class="pub-wrap pub-wrap--narrow">
            <header class="pub-section__head"><h2 id="blk-updated"><?php esc_html_e( 'Latest updates', 'holyprofweb' ); ?></h2></header>
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
