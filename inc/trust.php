<?php
/**
 * Trust pages: About, Editorial Policy, Corrections Policy.
 *
 * The copy lives in the theme (version controlled, easy to revise) and is rendered by
 * page-about.php, page-editorial-policy.php and page-corrections-updates-policy.php.
 * It states standards the publication commits to; the owner should read and approve it.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Last meaningful revision of the trust copy. Update only when the wording really changes. */
const HPW_TRUST_COPY_UPDATED = '2026-10-02';

function holyprofweb_trust_copy( $key ) {
    $pages = array(

        'about' => array(
            'lead'     => 'Holyprofweb finds new apps, websites, products, people and internet trends early, researches them properly and explains them clearly.',
            'sections' => array(
                array(
                    'h' => 'What we cover',
                    'p' => array(
                        'We cover what is new and gaining attention online: new apps and websites, technology products, the founders and creators behind them, and the trends people are only starting to search for.',
                    ),
                    'ul' => array(
                        '<a href="' . esc_url( holyprofweb_section_url( 'apps-websites' ) ) . '">Apps &amp; Websites</a>: new apps, AI tools, platforms and online services.',
                        '<a href="' . esc_url( holyprofweb_section_url( 'products-tech' ) ) . '">Products &amp; Tech</a>: gadgets, phones and technology products.',
                        '<a href="' . esc_url( holyprofweb_section_url( 'people' ) ) . '">People</a>: founders, creators and developers, with verified background only.',
                        '<a href="' . esc_url( holyprofweb_section_url( 'internet-trends' ) ) . '">Internet Trends</a>: what is trending, platform changes and the questions behind them.',
                    ),
                ),
                array(
                    'h' => 'How we choose topics',
                    'p' => array(
                        'We write about something when we can add what existing search results do not explain well: a clear definition, verified facts, original screenshots or testing, a timeline, or a comparison. If we cannot add that, we do not publish.',
                    ),
                ),
                array(
                    'h' => 'How we label what we know',
                    'p' => array(
                        'Every claim in an article is one of five things, and we say which.',
                    ),
                    'ul' => array(
                        '<strong>Tested by us:</strong> we used the product or service ourselves and say what we did. We only call something hands-on when it was.',
                        '<strong>Researched:</strong> drawn from primary sources (the company, official documentation, filings, the person) and named secondary sources.',
                        '<strong>Official statements:</strong> what a company, regulator or institution says. We attribute it to them and link it. It is their claim, not our finding.',
                        '<strong>User reports:</strong> what people say they experienced. We describe them as reports, not as facts, and we do not repeat anonymous accusations about named people or companies.',
                        '<strong>Unverified:</strong> claims we could not confirm. We say so, using wording such as “We could not independently verify this.”',
                    ),
                ),
                array(
                    'h' => 'Older articles',
                    'p' => array(
                        Holyprofweb has changed direction. In October 2026 we removed most of the older articles, which did not meet these standards, and then restored the topics that could be rewritten properly: each restored page was researched again, rewritten from sources and given a new headline. Pages that could not be sourced stay removed. A story shows an “Updated” date only when someone has made a meaningful change to it.',
                    ),
                ),
                array(
                    'h' => 'When we get it wrong',
                    'p' => array(
                        'Mistakes are corrected openly. See our <a href="' . esc_url( home_url( '/corrections-updates-policy/' ) ) . '">Corrections Policy</a> and <a href="' . esc_url( home_url( '/editorial-policy/' ) ) . '">Editorial Policy</a>.',
                    ),
                ),
            ),
            'authors' => true,
        ),

        'editorial-policy' => array(
            'lead'     => 'The standards every Holyprofweb article is held to.',
            'sections' => array(
                array(
                    'h' => 'Topic selection',
                    'p' => array( 'We choose topics that are new or newly relevant, and where we can add something useful. We do not publish a page only because a keyword exists.' ),
                ),
                array(
                    'h' => 'Research and sources',
                    'ul' => array(
                        'Key facts (what something is, who made it, when it launched, price, availability) are checked against a primary source where one exists.',
                        'Sources are named in the article, with links where possible. Dates are real: the published date never changes and the updated date changes only when we add or correct information.',
                        'Facts we cannot verify are labelled as unverified. For people we do not publish age, wealth, family, education or income unless it is confirmed by a reliable source.',
                    ),
                ),
                array(
                    'h' => 'Claims about named people and companies',
                    'p' => array( 'We do not call a named person or company a scam, fraud or liar unless there is documented evidence we can link, such as a court record, regulator notice or official statement. Otherwise we describe what is reported, say who reported it, and say what we could not verify.' ),
                ),
                array(
                    'h' => 'Hands-on testing',
                    'p' => array( 'An article is described as hands-on only if we actually used the product. Where we only researched it, the article says so.' ),
                ),
                array(
                    'h' => 'Images and screenshots',
                    'p' => array( 'We prefer real screenshots, product photos and original graphics. We do not fabricate interfaces or screenshots, and we credit images we did not create.' ),
                ),
                array(
                    'h' => 'Use of AI tools',
                    'p' => array( 'Going forward, we may use AI tools to help with research, outlining or editing. AI is not used to invent facts, sources, quotes, test results or screenshots, and a person is responsible for checking every published claim before it goes live. Nothing is published automatically.' ),
                ),
                array(
                    'h' => 'Sponsored content and affiliate links',
                    'p' => array( 'Advertising on the site is separate from editorial decisions, and payment does not buy coverage or change what an article says. If we ever publish sponsored content or use affiliate links, it will be labelled clearly on the page.' ),
                ),
                array(
                    'h' => 'Updates and corrections',
                    'p' => array( 'When there is meaningful new information we update the existing article rather than publishing duplicates. Factual errors are handled under our <a href="' . esc_url( home_url( '/corrections-updates-policy/' ) ) . '">Corrections Policy</a>.' ),
                ),
            ),
        ),

        'corrections-updates-policy' => array(
            'lead'     => 'If we publish something wrong, we fix it and say so.',
            'sections' => array(
                array(
                    'h' => 'Reporting a mistake',
                    'p' => array( 'Use the <a href="' . esc_url( home_url( '/contact/' ) ) . '">contact page</a>. Include the article link, what is wrong and, if you can, a source that shows the correct information.' ),
                ),
                array(
                    'h' => 'What we do',
                    'ul' => array(
                        'We check the claim against sources and respond to reasonable requests promptly.',
                        'Factual errors are corrected in the article. Significant corrections carry a visible note describing what changed and when.',
                        'Minor fixes such as typos are made without a note.',
                        'We update the “Updated” date when information changes, and not otherwise.',
                    ),
                ),
                array(
                    'h' => 'People and companies mentioned',
                    'p' => array( 'If you are named in an article and something about you is inaccurate, tell us. We will verify and correct it. We may decline to remove accurate, sourced information.' ),
                ),
            ),
        ),
    );

    return isset( $pages[ $key ] ) ? $pages[ $key ] : null;
}

/** Render a trust page using the publication layout. */
function holyprofweb_render_trust_page( $key ) {
    $copy = holyprofweb_trust_copy( $key );
    get_header();
    ?>
    <main id="primary" class="site-main pub-article pub-trust">
        <article class="pub-article__inner">
            <header class="pub-article__head">
                <nav class="pub-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'holyprofweb' ); ?>">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'holyprofweb' ); ?></a>
                </nav>
                <h1 class="pub-article__title"><?php the_title(); ?></h1>
                <?php if ( $copy ) : ?><p class="pub-article__summary"><?php echo esc_html( $copy['lead'] ); ?></p><?php endif; ?>
            </header>
            <div class="pub-article__body entry-content">
                <?php if ( $copy ) : foreach ( $copy['sections'] as $sec ) : ?>
                    <h2><?php echo esc_html( $sec['h'] ); ?></h2>
                    <?php foreach ( isset( $sec['p'] ) ? $sec['p'] : array() as $para ) : ?>
                        <p><?php echo wp_kses_post( $para ); ?></p>
                    <?php endforeach; ?>
                    <?php if ( ! empty( $sec['ul'] ) ) : ?>
                        <ul><?php foreach ( $sec['ul'] as $li ) : ?><li><?php echo wp_kses_post( $li ); ?></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                <?php endforeach; endif; ?>

                <?php if ( $copy && ! empty( $copy['authors'] ) ) : ?>
                    <?php
                    $authors = get_users( array( 'has_published_posts' => array( 'post' ), 'orderby' => 'display_name' ) );
                    $authors = array_filter( $authors, function ( $u ) { return '' !== trim( (string) $u->description ); } );
                    ?>
                    <?php if ( $authors ) : ?>
                    <h2><?php esc_html_e( 'Who writes it', 'holyprofweb' ); ?></h2>
                    <ul>
                        <?php foreach ( $authors as $u ) : ?>
                        <li><a href="<?php echo esc_url( get_author_posts_url( $u->ID ) ); ?>"><?php echo esc_html( $u->display_name ); ?></a>: <?php echo esc_html( $u->description ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                <?php endif; ?>

                <p class="pub-meta"><?php printf( esc_html__( 'Last reviewed %s.', 'holyprofweb' ), esc_html( date_i18n( 'F j, Y', strtotime( HPW_TRUST_COPY_UPDATED ) ) ) ); ?></p>
            </div>
        </article>
    </main>
    <?php
    get_footer();
}
