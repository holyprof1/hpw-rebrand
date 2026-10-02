<?php
/**
 * Holyprofweb content audit (CLI only).
 *
 *   php tools/content-audit.php --out=/path/to/dir [--traffic=logstats.tsv] [--prepend=file.php]
 *
 * Reads every published post, computes the signals listed in the Phase 2 brief, applies the decision
 * rules in holyprofweb_audit_decide(), and writes audit.csv and audit.json to --out. It never modifies
 * the database. The same tool is run again after cleanup to prove the end state.
 *
 * Decisions: KEEP, IMPROVE, MERGE, NOINDEX_TEMPORARILY, REMOVE, REDIRECT.
 * Every decision carries reason codes so it can be reviewed and overridden by hand.
 */

if ( 'cli' !== php_sapi_name() ) {
    exit( 'CLI only' );
}

$opts = getopt( '', array( 'out:', 'traffic::', 'prepend::' ) );
if ( empty( $opts['out'] ) ) {
    fwrite( STDERR, "Usage: php tools/content-audit.php --out=DIR [--traffic=logstats.tsv] [--prepend=file.php]\n" );
    exit( 1 );
}
if ( ! empty( $opts['prepend'] ) ) {
    require $opts['prepend'];
}
require dirname( __DIR__, 4 ) . '/wp-load.php';

$out_dir = rtrim( $opts['out'], '/\\' );
if ( ! is_dir( $out_dir ) ) {
    mkdir( $out_dir, 0775, true );
}

// ---- Traffic (optional) -------------------------------------------------------------------------

$traffic = array();
if ( ! empty( $opts['traffic'] ) && is_readable( $opts['traffic'] ) ) {
    $fh   = fopen( $opts['traffic'], 'r' );
    $head = fgetcsv( $fh, 0, "\t" );
    while ( ( $row = fgetcsv( $fh, 0, "\t" ) ) !== false ) {
        if ( count( $row ) === count( $head ) ) {
            $traffic[ $row[0] ] = array_combine( $head, $row );
        }
    }
    fclose( $fh );
}

// ---- Patterns -----------------------------------------------------------------------------------

const HPW_AUTO_START = '<!-- HPW-AUTO-CONTENT:START -->';

function hpw_audit_patterns() {
    return array(
        'review_farm_title' => '/\b(legit|legitimate|scam|scammer|fraud|real or fake|fake or real|trusted or|reviews?|complaints?|rated|rating|honest|worth it|side effects)\b/i',
        'bio_farm_title'    => '/\b(biography|net worth|wife|husband|girlfriend|boyfriend|age,|how old|scandal|before and after|height|religion|family|parents|house|cars?)\b/i',
        'scam_accusation'   => '/\b(scam|scammer|scammers|fraud|fraudulent|ponzi|fake|stole|stolen|deceiv\w+|rip[- ]?off|con artist|do not trust|avoid at all costs)\b/i',
        'net_worth'         => '/net worth[^.]{0,80}(\$|usd|₦|naira|million|billion|\d{2,})/i',
        'age_relationship'  => '/\b(is|was|are|were) (currently )?(married|dating|engaged|single)\b|\b(wife|husband|girlfriend|boyfriend|spouse|fianc[eé]e?)\b|\bborn (on|in)\b|\b\d{2} years old\b|\bage:?\s*\d{2}\b/i',
        'generic_advice'    => '/\b(be careful|do your own research|always verify|stay safe|protect yourself|read the terms|never share|use strong passwords?|trust your instincts|red flags?|proceed with caution|check reviews)\b/i',
        'medical'           => '/\b(side effects?|dosage|symptoms?|medication|overdose|contraindications?|seek medical)\b/i',
        'unverifiable'      => '/\b(reportedly|allegedly|some users (say|report|claim)|many users|people say|it is said|rumou?rs?|sources say|insiders?)\b/i',
        'direction_yes'     => '/\b(app|apps|website|platform|startup|founder|co-?founder|ceo|developer|creator|youtuber|streamer|ai|artificial intelligence|chatgpt|openai|software|saas|fintech|launch(es|ed)?|new feature|update|vpn|crypto exchange|marketplace|tiktok|instagram|x\.com|twitter|threads|telegram|whatsapp)\b/i',
        'direction_no'      => '/\b(side effects?|loan offer|casino|betting|bet9ja|sportybet|slot|jackpot|scholarship|salary|salaries|recipe|diet|weight loss|supplement|mattress|hotel|airline|perfume|shampoo|mouthwash|toothpaste)\b/i',
        'review_headings'   => array( 'our verdict', 'our verdict: caution', 'our verdict: legit', 'our verdict: scam', 'red flags and complaints', 'common complaints people usually watch for', 'common complaints', 'how to protect yourself before buying, registering, or signing up', 'what if you already paid or signed up?', 'what to check before you trust it', 'our review approach', 'is it legit or a scam?', 'user complaints and red flags', 'benefits, complaints, and common concerns', 'final thoughts', 'quick summary', 'what readers should check first', 'who this page is for', 'useful signals and practical context', 'alternatives, comparisons, and related searches', 'risks, gaps, and what to watch', 'key details to check', 'who this page helps', 'what to know', 'update notes', 'who should avoid it?', 'serious side effects (seek medical help)', 'common side effects', 'what to do if you\'ve already ordered', 'before and after comparison', 'what experts say' ),
    );
}

function hpw_audit_title_key( $title ) {
    $t = strtolower( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' ) );
    $t = preg_replace( '/\b(20\d\d|is|it|a|an|the|of|and|or|in|on|for|to|by|with|real|fake|legit|legitimate|scam|scammer|review|reviews|complaints?|complains|honest|worth|good|bad|rated|rating|negative|positive|full|breakdown|guide|explained|everything|you|need|know|should|what|how|does|do|this|that|app|site|website|platform|really|truth|about|we|found|my|our|your|any|are|can|will|safe|trusted|trust|scams|legitimate)\b/', ' ', $t );
    $t = preg_replace( '/[^a-z0-9]+/', ' ', $t );
    $parts = array_values( array_filter( explode( ' ', trim( $t ) ), function ( $w ) { return strlen( $w ) > 1; } ) );
    sort( $parts );
    return implode( ' ', array_slice( $parts, 0, 6 ) );
}

// ---- Gather -------------------------------------------------------------------------------------

global $wpdb;
$posts = $wpdb->get_results( "SELECT ID, post_title, post_name, post_date, post_modified, post_author, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' ORDER BY ID" );
$P     = hpw_audit_patterns();

// Redirect rules from Settings (source => target).
$rules       = function_exists( 'holyprofweb_parse_redirect_rules' ) ? holyprofweb_parse_redirect_rules() : array();
$rule_source = array();
$rule_target = array();
foreach ( $rules as $from => $to ) {
    $rule_source[ trim( $from, '/' ) ] = $to;
    $rule_target[ trim( (string) wp_parse_url( $to, PHP_URL_PATH ), '/' ) ] = true;
}

// Pass 1: heading frequency across the archive (to detect templates).
$heading_count = array();
$meta_cache    = array();
$parsed        = array();
foreach ( $posts as $p ) {
    $content = (string) $p->post_content;
    $auto_words = 0;
    $had_auto   = false !== strpos( $content, HPW_AUTO_START );
    if ( $had_auto ) {
        if ( preg_match_all( '/<!-- HPW-AUTO-CONTENT:START -->(.*?)<!-- HPW-AUTO-CONTENT:END -->/s', $content, $mm ) ) {
            $auto_words = str_word_count( wp_strip_all_tags( implode( ' ', $mm[1] ) ) );
        }
        $content = preg_replace( '/<!-- HPW-AUTO-CONTENT:START -->.*?<!-- HPW-AUTO-CONTENT:END -->/s', '', $content );
    }
    preg_match_all( '#<h[1-4][^>]*>(.*?)</h[1-4]>#si', $content, $hm );
    $headings = array_map( function ( $x ) { return trim( strtolower( html_entity_decode( wp_strip_all_tags( $x ), ENT_QUOTES, 'UTF-8' ) ) ); }, $hm[1] );
    foreach ( array_unique( $headings ) as $h ) {
        $heading_count[ $h ] = ( $heading_count[ $h ] ?? 0 ) + 1;
    }
    $parsed[ $p->ID ] = array( 'content' => $content, 'auto_words' => $auto_words, 'had_auto' => $had_auto, 'headings' => $headings );
}
$template_headings = array_flip( $P['review_headings'] );
foreach ( $heading_count as $h => $c ) {
    if ( $c >= 12 && strlen( $h ) > 8 ) {
        $template_headings[ $h ] = true; // appears in 12+ posts: part of a template
    }
}

$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
$rows      = array();
$keys      = array();

foreach ( $posts as $p ) {
    $id      = (int) $p->ID;
    $d       = $parsed[ $id ];
    $content = $d['content'];
    $text    = trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( preg_replace( '#</(p|h[1-6]|li|div|td|tr)>#i', ' ', $content ) ), ENT_QUOTES, 'UTF-8' ) ) );
    $words   = str_word_count( $text );
    $title   = html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' );
    $url     = get_permalink( $id );
    $path    = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );

    // Links
    preg_match_all( '#<a\s[^>]*href=["\']([^"\']+)["\']#i', $content, $lm );
    $internal = 0; $outbound = 0; $source_links = 0; $out_domains = array();
    foreach ( $lm[1] as $href ) {
        $host = wp_parse_url( $href, PHP_URL_HOST );
        if ( ! $host || $host === $home_host || 0 === strpos( $href, '/' ) ) {
            $internal++;
        } elseif ( ! preg_match( '/(facebook|twitter|x\.com|instagram|linkedin|youtube|tiktok|t\.me|wa\.me|whatsapp)\./i', $host ) ) {
            $outbound++;
            $out_domains[ preg_replace( '/^www\./', '', $host ) ] = 1;
        }
    }
    // "Source links": outbound links to distinct domains (evidence), capped so affiliate-style repeats do not inflate.
    $source_links = count( $out_domains );

    // Featured image
    $thumb_id = (int) get_post_thumbnail_id( $id );
    $file     = $thumb_id ? wp_basename( (string) get_post_meta( $thumb_id, '_wp_attached_file', true ) ) : '';
    if ( ! $thumb_id ) {
        $img = get_post_meta( $id, 'external_image', true ) ? 'external_only' : 'none';
    } elseif ( preg_match( '/placeholder/i', $file ) ) {
        $img = 'placeholder';
    } elseif ( preg_match( '/hpw-generated/i', $file ) ) {
        $img = 'generated';
    } else {
        $img = 'real';
    }

    // Comments
    $approved = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID=%d AND comment_approved='1'", $id ) );
    $legit_comments = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID=%d AND comment_approved='1' AND LOWER(comment_author) IN ('tobi','tobi arowosegbe')", $id ) );

    // Flags
    $is_seed    = (bool) get_post_meta( $id, '_hpw_seed_post', true ) || (bool) get_post_meta( $id, '_hpw_placeholder_post', true );
    // Sample posts created by the theme on activation (holyprofweb_create_sample_posts): all within one second on 2026-03-31 18:27.
    $is_sample  = 0 === strpos( (string) $p->post_date, '2026-03-31 18:27:1' ) && 1 === (int) $p->post_author;
    $headings   = $d['headings'];
    $tmpl_hits  = 0;
    foreach ( $headings as $h ) {
        if ( isset( $template_headings[ $h ] ) ) { $tmpl_hits++; }
    }
    $templated        = $tmpl_hits >= 3 || ( count( $headings ) > 0 && $tmpl_hits / max( 1, count( $headings ) ) >= 0.5 && $tmpl_hits >= 2 );
    $rating_data      = (bool) get_post_meta( $id, '_cached_rating', true ) || (bool) preg_match( '/\b\d(\.\d)?\s*(\/|out of)\s*5\b|★/u', $content . ' ' . $title );
    $rating_override  = '' !== (string) get_post_meta( $id, '_hpw_rating_override', true );
    $dup_sections     = count( $headings ) !== count( array_unique( $headings ) );
    $paras            = array_filter( array_map( 'trim', preg_split( '#</p>#i', $content ) ) );
    $norm_paras       = array_map( function ( $x ) { return md5( strtolower( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $x ) ) ) ); }, array_filter( $paras, function ( $x ) { return strlen( wp_strip_all_tags( $x ) ) > 60; } ) );
    $dup_sections     = $dup_sections || count( $norm_paras ) !== count( array_unique( $norm_paras ) );
    $title_review     = (bool) preg_match( $P['review_farm_title'], $title );
    $title_bio        = (bool) preg_match( $P['bio_farm_title'], $title );
    $cats             = wp_get_post_categories( $id, array( 'fields' => 'slugs' ) );
    $in_bio_cat       = (bool) array_intersect( $cats, array( 'biography', 'celeb', 'founders', 'founder', 'influencers' ) );
    $accusation_hits  = preg_match_all( $P['scam_accusation'], $text );
    $accusation_title = (bool) preg_match( $P['scam_accusation'], $title );
    $net_worth        = (bool) preg_match( $P['net_worth'], $text ) || (bool) preg_match( '/net worth/i', $title );
    $age_rel          = (bool) preg_match( $P['age_relationship'], $text );
    $generic_hits     = preg_match_all( $P['generic_advice'], $text );
    $generic_density  = $words > 0 ? $generic_hits / ( $words / 100 ) : 0;
    $unverifiable     = preg_match_all( $P['unverifiable'], $text );
    $medical          = preg_match_all( $P['medical'], $text ) >= 4 || preg_match( '/side effects?/i', $title );
    $number_tokens    = preg_match_all( '/\b\d[\d,\.]*\b/', $text );
    $images_inline    = preg_match_all( '#<img\b#i', $content );
    $has_table        = false !== stripos( $content, '<table' );
    $original         = ( $images_inline >= 2 ? 1 : 0 ) + ( $has_table ? 1 : 0 ) + ( $source_links >= 2 ? 1 : 0 ) + ( $number_tokens >= 25 && ! $templated ? 1 : 0 );
    $dir_yes          = preg_match( $P['direction_yes'], $title . ' ' . implode( ' ', $cats ) ) ? true : false;
    $dir_no           = preg_match( $P['direction_no'], $title ) ? true : false;
    $fit              = $dir_no ? 'no' : ( $dir_yes ? 'yes' : 'partial' );
    if ( 'yes' === $fit && ( $title_review || $title_bio ) ) { $fit = 'partial'; }
    $topic_clear      = (bool) preg_match( '/[A-Z][A-Za-z0-9]{2,}/', $title ) && str_word_count( $title ) >= 3;
    $encoding_issue   = (bool) preg_match( '/\?\s|[^\x00-\x7F]\?|\x{FFFD}/u', $title ) || false !== strpos( $title, "\xEF\xBF\xBD" );
    $stale_year       = (bool) preg_match( '/\b(20(1\d|2[0-3]))\b/', $title );
    $redirect_src     = isset( $rule_source[ $path ] );
    $redirect_tgt     = isset( $rule_target[ $path ] );

    // Traffic
    $t        = $traffic[ '/' . $path . '/' ] ?? array();
    $tv       = function ( $k ) use ( $t ) { return (int) ( $t[ $k ] ?? 0 ); };
    $google   = $tv( 'ref_google' );
    $bing     = $tv( 'ref_bing' );
    $human    = $tv( 'human' );

    $key  = hpw_audit_title_key( $title );
    $keys[ $key ][] = $id;

    $rows[ $id ] = array(
        'post_id'               => $id,
        'url'                   => $url,
        'title'                 => $title,
        'slug'                  => $p->post_name,
        'publish_date'          => substr( $p->post_date, 0, 10 ),
        'editorial_modified'    => function_exists( 'holyprofweb_content_modified_iso' ) ? substr( holyprofweb_content_modified_iso( $id ), 0, 10 ) : substr( $p->post_modified, 0, 10 ),
        'author'                => get_the_author_meta( 'user_login', (int) $p->post_author ),
        'categories'            => implode( '|', $cats ),
        'words'                 => $words,
        'featured_image'        => $img,
        'internal_links'        => $internal,
        'outbound_links'        => $outbound,
        'source_links'          => $source_links,
        'comments_approved'     => $approved,
        'comments_legit'        => $legit_comments,
        'redirect_source'       => (int) $redirect_src,
        'redirect_target'       => (int) $redirect_tgt,
        'expected_http'         => $redirect_src ? 301 : 200,
        'canonical'             => $url,
        'index_status'          => ( $is_seed || $is_sample ) ? 'noindex(demo)' : 'index',
        'auto_blocks'           => (int) $d['had_auto'],
        'auto_words'            => $d['auto_words'],
        'update_notes'          => (int) ( $d['had_auto'] || in_array( 'update notes', $headings, true ) || false !== strpos( $content, 'hpw-auto-update' ) ),
        'generated_review_blocks' => (int) $tmpl_hits,
        'duplicated_sections'   => (int) $dup_sections,
        'rating_data'           => (int) ( $rating_data || $rating_override ),
        'title_review_farm'     => (int) $title_review,
        'title_bio_farm'        => (int) ( $title_bio || $in_bio_cat ),
        'templated'             => (int) $templated,
        'scam_accusation'       => (int) ( $accusation_title || $accusation_hits >= 3 ),
        'unverifiable_phrases'  => (int) $unverifiable,
        'net_worth_claim'       => (int) $net_worth,
        'age_relationship_claim' => (int) $age_rel,
        'generic_advice_density' => round( $generic_density, 2 ),
        'medical_content'       => (int) $medical,
        'original_signals'      => (int) $original,
        'topic_clear'           => (int) $topic_clear,
        'direction_fit'         => $fit,
        'factual_flags'         => implode( '|', array_filter( array( $encoding_issue ? 'encoding' : '', $stale_year ? 'stale_year_title' : '', $is_seed ? 'seed_demo' : '' ) ) ),
        'google_clicks_33d'     => $google,
        'bing_clicks_33d'       => $bing,
        'human_hits_33d'        => $human,
        'googlebot_hits_33d'    => $tv( 'googlebot' ),
        'bingbot_hits_33d'      => $tv( 'bingbot' ),
        'ai_bot_hits_33d'       => $tv( 'openai' ) + $tv( 'claude' ) + $tv( 'perplexity' ),
        'title_key'             => $key,
        'rewritten'             => (int) (bool) get_post_meta( $id, '_hpw_rewritten', true ),
        'is_seed'               => (int) $is_seed,
        'is_sample'             => (int) $is_sample,
        'owner_marketing'       => (int) (bool) array_intersect( $cats, array( 'web-development-seo' ) ),
        'decision'              => '',
        'reasons'               => '',
        'target'                => '',
    );
}

// ---- Decision rules ------------------------------------------------------------------------------

/**
 * Returns array( decision, reasons[] ). Order matters: the first matching rule wins for REMOVE; the
 * remaining rules grade what is left.
 */
function holyprofweb_audit_decide( array $r ) {
    $traffic_signal = $r['google_clicks_33d'] >= 5 || $r['bing_clicks_33d'] >= 1;
    $why            = array();

    // 0. Hand-written, sourced rewrites (tools/publish-rewrites.php) are the standard to keep.
    if ( ! empty( $r['rewritten'] ) ) {
        return array( 'KEEP', array( 'REWRITTEN_SOURCED' ) );
    }
    // 1. Theme-generated demo content (seeded posts and the sample posts created on activation).
    if ( $r['is_seed'] || $r['is_sample'] ) {
        return array( 'REMOVE', array( 'DEMO_SEED' ) );
    }
    // 2. Empty / tiny / dominated by stored auto content.
    if ( $r['words'] < 120 ) {
        return array( 'REMOVE', array( 'EMPTY_OR_TINY' ) );
    }
    if ( $r['auto_words'] > 0 && $r['auto_words'] / max( 1, $r['words'] + $r['auto_words'] ) > 0.4 ) {
        return array( 'REMOVE', array( 'AUTO_CONTENT_DOMINATED' ) );
    }
    // 3. Unsourced medical advice (YMYL).
    if ( $r['medical_content'] && ! $r['source_links'] ) {
        return array( 'REMOVE', array( 'UNSOURCED_MEDICAL_YMYL' ) );
    }
    // 4. Biography farm: personal-detail pages need real sourcing; thin ones go.
    if ( $r['title_bio_farm'] ) {
        if ( $r['words'] < 500 || $r['net_worth_claim'] || $r['age_relationship_claim'] || $r['source_links'] < 2 ) {
            return array( 'REMOVE', array( 'BIO_FARM_THIN_OR_UNVERIFIABLE_PERSONAL_CLAIMS' ) );
        }
        return array( 'NOINDEX_TEMPORARILY', array( 'BIOGRAPHY_NEEDS_HUMAN_VERIFICATION' ) );
    }
    // 5. Review farm framing (Is X legit / reviews / complaints ...).
    if ( $r['title_review_farm'] ) {
        if ( $traffic_signal ) {
            return array( 'NOINDEX_TEMPORARILY', array( 'REWRITE_CANDIDATE_HAS_SEARCH_TRAFFIC', $r['scam_accusation'] ? 'ACCUSATION_NEEDS_EVIDENCE' : 'REVIEW_FARM_FRAMING' ) );
        }
        if ( $r['source_links'] >= 2 && ! $r['templated'] ) {
            return array( 'NOINDEX_TEMPORARILY', array( 'REVIEW_FARM_FRAMING_HAS_SOURCES' ) );
        }
        return array( 'REMOVE', array( $r['scam_accusation'] ? 'UNSUPPORTED_SCAM_ACCUSATION' : 'REVIEW_FARM_NO_EVIDENCE' ) );
    }
    // 6. Scam-themed explainers that are not review-framed.
    if ( $r['scam_accusation'] ) {
        if ( $traffic_signal ) {
            return array( 'NOINDEX_TEMPORARILY', array( 'REWRITE_CANDIDATE_HAS_SEARCH_TRAFFIC', 'SCAM_EXPLAINER_NEEDS_SOURCES' ) );
        }
        return array( 'REMOVE', array( 'SCAM_CONTENT_NO_EVIDENCE_NO_TRAFFIC' ) );
    }
    // 7. Owner marketing / service pages do not belong in a discovery publication's index.
    if ( $r['owner_marketing'] ) {
        return array( 'NOINDEX_TEMPORARILY', array( 'OWNER_SERVICE_MARKETING_OFF_DIRECTION' ) );
    }
    // 8. Direction fit.
    if ( 'no' === $r['direction_fit'] ) {
        return $traffic_signal ? array( 'NOINDEX_TEMPORARILY', array( 'OFF_DIRECTION', 'HAS_SEARCH_TRAFFIC' ) ) : array( 'REMOVE', array( 'OFF_DIRECTION_NO_TRAFFIC' ) );
    }
    if ( 'partial' === $r['direction_fit'] ) {
        if ( $r['words'] >= 500 && ! $r['templated'] ) {
            return array( 'NOINDEX_TEMPORARILY', array( 'GENERIC_EVERGREEN_NO_CLEAR_FIT' ) );
        }
        return array( 'REMOVE', array( 'OFF_DIRECTION_THIN' ) );
    }
    // 9. Fits the direction: grade.
    if ( $r['duplicated_sections'] ) { $why[] = 'DUPLICATED_SECTIONS'; }
    if ( $r['update_notes'] )        { $why[] = 'AUTO_BOILERPLATE'; }
    if ( $r['rating_data'] )         { $why[] = 'RATING_DATA'; }
    if ( $r['templated'] )           { $why[] = 'TEMPLATED'; }
    if ( ! $r['source_links'] && ! $r['original_signals'] ) { $why[] = 'NO_SOURCES_OR_ORIGINAL_EVIDENCE'; }
    if ( $r['words'] < 300 )         { $why[] = 'SHORT'; }
    if ( $r['templated'] && ! $r['source_links'] ) {
        return array( 'REMOVE', array( 'TEMPLATED_NO_EVIDENCE' ) );
    }
    // No sources and no original evidence: not index-worthy until someone adds them.
    if ( ! $r['source_links'] && ! $r['original_signals'] ) {
        return array( 'NOINDEX_TEMPORARILY', $why );
    }
    return $why ? array( 'IMPROVE', $why ) : array( 'KEEP', array( 'CLEAN' ) );
}

foreach ( $rows as $id => &$r ) {
    list( $dec, $why ) = holyprofweb_audit_decide( $r );
    $r['decision'] = $dec;
    $r['reasons']  = implode( '|', $why );
}
unset( $r );

// Duplicate clusters (same subject after stripping review-farm filler words).
foreach ( $keys as $key => $ids ) {
    if ( '' === $key || count( $ids ) < 2 ) {
        continue;
    }
    usort( $ids, function ( $a, $b ) use ( $rows ) {
        $score = function ( $r ) {
            $rank = array( 'KEEP' => 4, 'IMPROVE' => 3, 'NOINDEX_TEMPORARILY' => 2, 'REMOVE' => 0 );
            return array( $rank[ $r['decision'] ] ?? 0, $r['google_clicks_33d'], $r['comments_legit'], $r['words'], $r['post_id'] );
        };
        return $score( $rows[ $b ] ) <=> $score( $rows[ $a ] );
    } );
    $primary = $ids[0];
    foreach ( array_slice( $ids, 1 ) as $dup ) {
        $rows[ $dup ]['reasons'] .= ( $rows[ $dup ]['reasons'] ? '|' : '' ) . 'NEAR_DUPLICATE_OF_' . $primary;
        $rows[ $primary ]['reasons'] .= ( false === strpos( $rows[ $primary ]['reasons'], 'HAS_DUPLICATES' ) ? '|HAS_DUPLICATES' : '' );
        if ( 'REMOVE' === $rows[ $dup ]['decision'] ) {
            continue; // already removed for a stronger reason; no redirect needed
        }
        if ( in_array( $rows[ $primary ]['decision'], array( 'KEEP', 'IMPROVE' ), true ) ) {
            $rows[ $dup ]['decision'] = 'REDIRECT';
            $rows[ $dup ]['target']   = $rows[ $primary ]['url'];
        } else {
            $rows[ $dup ]['decision'] = 'REMOVE';
            $rows[ $dup ]['reasons'] .= '|DUPLICATE_OF_WEAK_PRIMARY';
        }
    }
}

// Posts that already have a redirect rule are decided by that rule.
foreach ( $rows as $id => &$r ) {
    if ( $r['redirect_source'] && 'REDIRECT' !== $r['decision'] ) {
        $r['reasons'] .= '|HAS_REDIRECT_RULE';
        $r['decision'] = 'REDIRECT';
        $r['target']   = (string) $rule_source[ trim( (string) wp_parse_url( $r['url'], PHP_URL_PATH ), '/' ) ];
    }
}
unset( $r );

// ---- Output --------------------------------------------------------------------------------------

$list = array_values( $rows );
$csv  = fopen( $out_dir . '/audit.csv', 'w' );
fwrite( $csv, "\xEF\xBB\xBF" );
fputcsv( $csv, array_keys( $list[0] ) );
foreach ( $list as $row ) {
    fputcsv( $csv, $row );
}
fclose( $csv );
file_put_contents( $out_dir . '/audit.json', wp_json_encode( $list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );

$summary = array();
foreach ( $list as $row ) {
    $summary[ $row['decision'] ] = ( $summary[ $row['decision'] ] ?? 0 ) + 1;
}
ksort( $summary );
$reasons = array();
foreach ( $list as $row ) {
    foreach ( explode( '|', $row['reasons'] ) as $rs ) {
        $rs = preg_replace( '/_\d+$/', '', $rs );
        if ( $rs ) { $reasons[ $rs ] = ( $reasons[ $rs ] ?? 0 ) + 1; }
    }
}
arsort( $reasons );
echo 'Audited ' . count( $list ) . " posts\n";
foreach ( $summary as $k => $v ) { echo str_pad( $k, 22 ) . $v . "\n"; }
echo "\nReason codes:\n";
foreach ( $reasons as $k => $v ) { echo str_pad( $k, 48 ) . $v . "\n"; }
echo "\nWritten to $out_dir/audit.csv and audit.json\n";
