<?php
/**
 * Author profiles: real bylines, a short "areas of knowledge" field, and ProfilePage markup.
 * Nothing is invented: a profile shows only what the author filled in under Users > Profile.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function holyprofweb_author_url( $author_id ) {
    return get_author_posts_url( $author_id );
}

// ---- Profile field -----------------------------------------------------------------------------

function holyprofweb_author_profile_fields( $user ) {
    ?>
    <h2><?php esc_html_e( 'Holyprofweb author profile', 'holyprofweb' ); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="hpw_expertise"><?php esc_html_e( 'Areas of knowledge', 'holyprofweb' ); ?></label></th>
            <td>
                <input type="text" name="hpw_expertise" id="hpw_expertise" class="regular-text"
                       value="<?php echo esc_attr( get_user_meta( $user->ID, 'hpw_expertise', true ) ); ?>" />
                <p class="description"><?php esc_html_e( 'Comma separated, e.g. fintech apps, AI tools. The biography above and this field are shown publicly. A profile without a biography stays out of search results.', 'holyprofweb' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'holyprofweb_author_profile_fields' );
add_action( 'edit_user_profile', 'holyprofweb_author_profile_fields' );

function holyprofweb_save_author_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }
    if ( isset( $_POST['hpw_expertise'] ) ) {
        update_user_meta( $user_id, 'hpw_expertise', sanitize_text_field( wp_unslash( $_POST['hpw_expertise'] ) ) );
    }
}
add_action( 'personal_options_update', 'holyprofweb_save_author_profile_fields' );
add_action( 'edit_user_profile_update', 'holyprofweb_save_author_profile_fields' );

// ---- Indexing: a thin profile (no bio) is not indexable ----------------------------------------

add_filter( 'wp_robots', function ( $robots ) {
    if ( is_author() ) {
        $author = get_queried_object();
        if ( ! $author || '' === trim( (string) $author->description ) ) {
            $robots['noindex'] = true;
            $robots['follow']  = true;
        }
    }
    return $robots;
}, 14 );

// ---- ProfilePage + Person ----------------------------------------------------------------------

add_action( 'wp_head', function () {
    if ( ! is_author() ) {
        return;
    }
    $author = get_queried_object();
    if ( ! $author || '' === trim( (string) $author->description ) ) {
        return;
    }
    $url    = get_author_posts_url( $author->ID );
    $person = array(
        '@type'       => 'Person',
        '@id'         => $url . '#person',
        'name'        => $author->display_name,
        'url'         => $url,
        'description' => wp_strip_all_tags( $author->description ),
        'worksFor'    => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
    );
    if ( $author->user_url ) {
        $person['sameAs'] = array( esc_url_raw( $author->user_url ) );
    }
    $expertise = array_filter( array_map( 'trim', explode( ',', (string) get_user_meta( $author->ID, 'hpw_expertise', true ) ) ) );
    if ( $expertise ) {
        $person['knowsAbout'] = array_values( $expertise );
    }
    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'ProfilePage',
        'url'        => $url,
        'mainEntity' => $person,
    );
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}, 20 );
