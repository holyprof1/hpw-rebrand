<?php
/**
 * Editorial tooling for the post editor.
 *
 * - Workflow box: stage (draft > research > fact check > source check > image check > SEO preview > ready),
 *   four confirmations, a live pre-publish checklist and a search-result preview.
 * - Entity facts ("At a glance"): optional structured facts that are printed as plain HTML on the article
 *   and described in JSON-LD only when the editor filled them in.
 * - Related stories: hand-picked URLs shown before any automatic suggestions.
 *
 * Nothing here blocks publishing and nothing publishes automatically. The checklist is advice, not a score.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function holyprofweb_workflow_stages() {
    return array(
        'draft'        => 'Draft',
        'research'     => 'Research',
        'fact_check'   => 'Fact check',
        'source_check' => 'Source check',
        'image_check'  => 'Image check',
        'seo_preview'  => 'SEO preview',
        'ready'        => 'Ready to publish',
    );
}

function holyprofweb_entity_types() {
    return array(
        ''        => 'No entity (general article)',
        'app'     => 'App',
        'website' => 'Website or online service',
        'product' => 'Product',
        'person'  => 'Person',
        'company' => 'Company',
        'trend'   => 'Trend or event',
    );
}

add_action( 'add_meta_boxes', function () {
    add_meta_box( 'hpw_workflow', __( 'Editorial workflow', 'holyprofweb' ), 'holyprofweb_workflow_box', 'post', 'side', 'high' );
    add_meta_box( 'hpw_facts', __( 'At a glance: facts and related stories', 'holyprofweb' ), 'holyprofweb_facts_box', 'post', 'normal', 'default' );
} );

function holyprofweb_workflow_box( $post ) {
    wp_nonce_field( 'hpw_editorial_save', 'hpw_editorial_nonce' );
    $stage = (string) get_post_meta( $post->ID, '_hpw_stage', true );
    $stage = $stage ? $stage : 'draft';
    ?>
    <p><label for="hpw_stage"><strong><?php esc_html_e( 'Stage', 'holyprofweb' ); ?></strong></label><br>
    <select name="hpw_stage" id="hpw_stage" style="width:100%">
        <?php foreach ( holyprofweb_workflow_stages() as $k => $label ) : ?>
        <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $stage, $k ); ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select></p>
    <p style="margin:0 0 6px"><strong><?php esc_html_e( 'Confirmed by a person', 'holyprofweb' ); ?></strong></p>
    <?php foreach ( array( 'researched' => 'Researched from primary sources', 'facts' => 'Facts checked', 'sources' => 'Sources linked and checked', 'images' => 'Images checked (own, licensed or credited)' ) as $k => $label ) : ?>
    <label style="display:block;margin-bottom:4px"><input type="checkbox" name="hpw_chk[<?php echo esc_attr( $k ); ?>]" value="1" <?php checked( 1, (int) get_post_meta( $post->ID, '_hpw_chk_' . $k, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
    <?php endforeach; ?>
    <hr>
    <p style="margin:0 0 6px"><strong><?php esc_html_e( 'Pre-publish checks', 'holyprofweb' ); ?></strong></p>
    <ul id="hpw-checklist" style="margin:0;padding-left:16px;list-style:disc"><li><?php esc_html_e( 'Checks appear as you write.', 'holyprofweb' ); ?></li></ul>
    <hr>
    <p style="margin:0 0 6px"><strong><?php esc_html_e( 'Search preview', 'holyprofweb' ); ?></strong></p>
    <div id="hpw-serp" style="border:1px solid #dcdcde;border-radius:6px;padding:8px 10px;background:#fff;font-family:Arial,sans-serif">
        <div id="hpw-serp-url" style="color:#188038;font-size:12px"></div>
        <div id="hpw-serp-title" style="color:#1a0dab;font-size:16px;line-height:1.3"></div>
        <div id="hpw-serp-desc" style="color:#4d5156;font-size:13px"></div>
    </div>
    <p class="description"><?php esc_html_e( 'The description is the excerpt. These are advice, not a score, and they never block publishing.', 'holyprofweb' ); ?></p>
    <?php
}

function holyprofweb_facts_box( $post ) {
    $f = (array) get_post_meta( $post->ID, '_hpw_facts', true );
    $g = function ( $k ) use ( $f ) { return isset( $f[ $k ] ) ? (string) $f[ $k ] : ''; };
    $rel = (string) get_post_meta( $post->ID, '_hpw_related', true );
    $fields = array(
        'name'         => 'Name (use it the same way everywhere)',
        'operator'     => 'Made or run by (company, founder)',
        'launched'     => 'Launched (date or year)',
        'availability' => 'Where it is available',
        'pricing'      => 'Pricing (only what you verified, with the date)',
        'url'          => 'Official website (https://)',
        'limits'       => 'Limitations or what we could not verify',
    );
    ?>
    <p class="description"><?php esc_html_e( 'Optional. Anything filled in is printed as plain text under the headline and described in structured data. Leave a field empty rather than guess.', 'holyprofweb' ); ?></p>
    <table class="form-table" role="presentation"><tbody>
        <tr><th><label for="hpw_ent_type"><?php esc_html_e( 'Entity type', 'holyprofweb' ); ?></label></th>
            <td><select name="hpw_ent[type]" id="hpw_ent_type"><?php foreach ( holyprofweb_entity_types() as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $g( 'type' ), $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
        <?php foreach ( $fields as $k => $label ) : ?>
        <tr><th><label for="hpw_ent_<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><input type="text" class="large-text" name="hpw_ent[<?php echo esc_attr( $k ); ?>]" id="hpw_ent_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $g( $k ) ); ?>"></td></tr>
        <?php endforeach; ?>
        <tr><th><label for="hpw_related"><?php esc_html_e( 'Related stories (one URL per line)', 'holyprofweb' ); ?></label></th>
            <td><textarea class="large-text" rows="3" name="hpw_related" id="hpw_related"><?php echo esc_textarea( $rel ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Link the company, founder, competitor or later update. These show before any automatic suggestions.', 'holyprofweb' ); ?></p></td></tr>
    </tbody></table>
    <?php
}

add_action( 'save_post_post', function ( $post_id ) {
    if ( ! isset( $_POST['hpw_editorial_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hpw_editorial_nonce'] ) ), 'hpw_editorial_save' ) ) {
        return;
    }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $stage = isset( $_POST['hpw_stage'] ) ? sanitize_key( wp_unslash( $_POST['hpw_stage'] ) ) : 'draft';
    update_post_meta( $post_id, '_hpw_stage', isset( holyprofweb_workflow_stages()[ $stage ] ) ? $stage : 'draft' );
    foreach ( array( 'researched', 'facts', 'sources', 'images' ) as $k ) {
        update_post_meta( $post_id, '_hpw_chk_' . $k, empty( $_POST['hpw_chk'][ $k ] ) ? 0 : 1 );
    }
    $ent   = isset( $_POST['hpw_ent'] ) && is_array( $_POST['hpw_ent'] ) ? wp_unslash( $_POST['hpw_ent'] ) : array();
    $clean = array();
    foreach ( array( 'type', 'name', 'operator', 'launched', 'availability', 'pricing', 'limits' ) as $k ) {
        $v = isset( $ent[ $k ] ) ? sanitize_text_field( $ent[ $k ] ) : '';
        if ( 'type' === $k && ! isset( holyprofweb_entity_types()[ $v ] ) ) { $v = ''; }
        $clean[ $k ] = $v;
    }
    $clean['url'] = isset( $ent['url'] ) ? esc_url_raw( $ent['url'] ) : '';
    update_post_meta( $post_id, '_hpw_facts', $clean );
    update_post_meta( $post_id, '_hpw_related', isset( $_POST['hpw_related'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hpw_related'] ) ) : '' );
} );

/** Facts the editor filled in, as label => value (empty values dropped). */
function holyprofweb_get_entity_facts( $post_id ) {
    $f = (array) get_post_meta( $post_id, '_hpw_facts', true );
    $map = array(
        'operator'     => 'Made or run by',
        'launched'     => 'Launched',
        'availability' => 'Available in',
        'pricing'      => 'Pricing',
        'url'          => 'Official site',
        'limits'       => 'Not verified',
    );
    $out = array();
    foreach ( $map as $k => $label ) {
        if ( ! empty( $f[ $k ] ) ) { $out[ $label ] = (string) $f[ $k ]; }
    }
    return $out;
}

/** Hand-picked related post IDs (from URLs), in order. */
function holyprofweb_get_related_picks( $post_id ) {
    $raw = (string) get_post_meta( $post_id, '_hpw_related', true );
    $ids = array();
    foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
        $line = trim( $line );
        if ( '' === $line ) { continue; }
        $pid = is_numeric( $line ) ? (int) $line : url_to_postid( $line );
        if ( $pid && $pid !== (int) $post_id && 'publish' === get_post_status( $pid ) && ! get_post_meta( $pid, '_hpw_noindex', true ) ) {
            $ids[] = $pid;
        }
    }
    return array_values( array_unique( $ids ) );
}

// ---- Live checklist in the block editor ------------------------------------------------------------------

add_action( 'enqueue_block_editor_assets', function () {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'post' !== $screen->post_type ) {
        return;
    }
    wp_add_inline_script( 'wp-editor', holyprofweb_editor_checklist_js() );
} );

function holyprofweb_editor_checklist_js() {
    $home = esc_js( home_url( '/' ) );
    return <<<JS
(function(){
  if (!window.wp || !wp.data) return;
  var last = '';
  function strip(h){ var d=document.createElement('div'); d.innerHTML=h||''; return (d.textContent||'').replace(/\\s+/g,' ').trim(); }
  function run(){
    var ed = wp.data.select('core/editor'); if(!ed) return;
    var list = document.getElementById('hpw-checklist'); if(!list) return;
    var title = ed.getEditedPostAttribute('title')||'';
    var excerpt = ed.getEditedPostAttribute('excerpt')||'';
    var content = ed.getEditedPostContent ? ed.getEditedPostContent() : '';
    var cats = ed.getEditedPostAttribute('categories')||[];
    var img = ed.getEditedPostAttribute('featured_media');
    var text = strip(content), words = text ? text.split(' ').length : 0;
    var hasLink = /<a\\s[^>]*href=/i.test(content);
    var internal = /<a\\s[^>]*href=["'](?:\\/|{$home})/i.test(content);
    var hasSources = /<h[23][^>]*>\\s*(sources?|references)\\b/i.test(content);
    var firstP = (content.match(/<p[^>]*>(.*?)<\\/p>/i)||[])[1]; firstP = strip(firstP||'');
    var msgs = [];
    if (excerpt.replace(/\\s/g,'').length < 60) msgs.push('Add a one or two sentence excerpt: it becomes the description in search and on cards.');
    if (!cats.length) msgs.push('Choose a section (Apps & Websites, Products & Tech, People or Internet Trends).');
    if (!img) msgs.push('No featured image. Add a real screenshot, product photo or original graphic if one would help.');
    if (words > 150 && !hasSources) msgs.push('No "Sources" heading. Factual research articles should link their sources.');
    if (words < 300) msgs.push('Short article (' + words + ' words). Fine if it is complete; add detail if it is not.');
    if (firstP.length < 80) msgs.push('The opening paragraph should say plainly what the thing is and answer the main question.');
    if (!internal) msgs.push('No link to another Holyprofweb story, company or person page.');
    if (/\\b(is it (legit|a scam)|scam or legit|legit or (a )?scam|reviews? and complaints|honest review|real or fake)\\b/i.test(title)) msgs.push('The title uses old review-farm wording. Describe what the article actually contains.');
    if (/\\b(we tested|our testing|hands-on|we used)\\b/i.test(text)) msgs.push('The text claims first-hand testing. Only keep that if someone really tested it.');
    var key = msgs.join('|') + title + excerpt;
    if (key !== last) {
      last = key;
      list.innerHTML = msgs.length ? msgs.map(function(m){return '<li>'+m.replace(/&/g,'&amp;').replace(/</g,'&lt;')+'</li>';}).join('') : '<li>Nothing to flag.</li>';
      var slug = ed.getEditedPostAttribute('slug') || 'your-slug';
      var u = document.getElementById('hpw-serp-url'), t = document.getElementById('hpw-serp-title'), d = document.getElementById('hpw-serp-desc');
      if (u) u.textContent = '{$home}'.replace(/^https?:\\/\\//,'') + slug + '/';
      if (t) t.textContent = (title || 'Title') + ' - ' + document.title.split(' - ').pop();
      if (d) d.textContent = (excerpt ? strip(excerpt) : text).slice(0, 155);
    }
  }
  wp.data.subscribe(function(){ clearTimeout(run._t); run._t = setTimeout(run, 400); });
})();
JS;
}
