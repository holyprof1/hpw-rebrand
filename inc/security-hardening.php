<?php
/**
 * Public write-path hardening helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function holyprofweb_get_public_request_ip() {
    if ( function_exists( 'holyprofweb_get_trusted_client_ip' ) ) {
        $trusted_ip = holyprofweb_get_trusted_client_ip();
        if ( $trusted_ip ) {
            return $trusted_ip;
        }
    }

    $remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    return filter_var( $remote_addr, FILTER_VALIDATE_IP ) ? $remote_addr : '';
}

function holyprofweb_get_request_fingerprint() {
    $ip = holyprofweb_get_public_request_ip();
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    return hash_hmac( 'sha256', strtolower( $ip . '|' . $ua ), wp_salt( 'auth' ) );
}

function holyprofweb_get_rate_limit_key( $action, $suffix = '' ) {
    $action = sanitize_key( (string) $action );
    $suffix = (string) $suffix;
    return 'hpw_rl_' . md5( $action . '|' . holyprofweb_get_request_fingerprint() . '|' . $suffix );
}

function holyprofweb_enforce_rate_limit( $action, $limit, $window, $message, $suffix = '' ) {
    $limit   = max( 1, (int) $limit );
    $window  = max( MINUTE_IN_SECONDS, (int) $window );
    $key     = holyprofweb_get_rate_limit_key( $action, $suffix );
    $current = (int) get_transient( $key );

    if ( $current >= $limit ) {
        return new WP_Error( 'rate_limited', $message );
    }

    set_transient( $key, $current + 1, $window );
    return true;
}

function holyprofweb_enforce_public_cooldown( $action, $cooldown, $message, $suffix = '' ) {
    $cooldown = max( 10, (int) $cooldown );
    $key      = holyprofweb_get_rate_limit_key( $action . '_cooldown', $suffix );

    if ( get_transient( $key ) ) {
        return new WP_Error( 'cooldown', $message );
    }

    set_transient( $key, 1, $cooldown );
    return true;
}

function holyprofweb_has_recent_public_submission( $args = array() ) {
    global $wpdb;

    $defaults = array(
        'post_type'    => 'post',
        'post_status'  => array( 'pending', 'draft', 'publish', 'future', 'private' ),
        'created_after'=> time() - ( 5 * MINUTE_IN_SECONDS ),
        'meta_queries' => array(),
    );

    $args = wp_parse_args( $args, $defaults );

    if ( empty( $args['meta_queries'] ) || ! is_array( $args['meta_queries'] ) ) {
        return false;
    }

    $meta_clauses = array();
    $meta_params  = array();
    foreach ( $args['meta_queries'] as $meta_clause ) {
        if ( empty( $meta_clause['key'] ) || ! array_key_exists( 'value', $meta_clause ) ) {
            continue;
        }

        $meta_clauses[] = '(pm.meta_key = %s AND pm.meta_value = %s)';
        $meta_params[]  = sanitize_key( $meta_clause['key'] );
        $meta_params[]  = is_scalar( $meta_clause['value'] ) ? (string) $meta_clause['value'] : '';
    }

    if ( empty( $meta_clauses ) ) {
        return false;
    }

    $statuses     = array_values( array_filter( array_map( 'sanitize_key', (array) $args['post_status'] ) ) );
    if ( empty( $statuses ) ) {
        return false;
    }

    $status_sql   = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
    $created_after = max( 1, (int) $args['created_after'] );
    $query_params = array_merge(
        array(
            sanitize_key( $args['post_type'] ),
        ),
        $statuses,
        array(
            '_hpw_submission_created_at',
            $created_after,
        ),
        $meta_params
    );

    $sql = "
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
        INNER JOIN {$wpdb->postmeta} pm_created ON pm_created.post_id = p.ID
        WHERE p.post_type = %s
          AND p.post_status IN ({$status_sql})
          AND pm_created.meta_key = %s
          AND CAST(pm_created.meta_value AS UNSIGNED) >= %d
          AND (" . implode( ' OR ', $meta_clauses ) . ')
        LIMIT 1
    ';

    $recent_id = $wpdb->get_var( $wpdb->prepare( $sql, $query_params ) );
    return ! empty( $recent_id );
}

function holyprofweb_get_form_guard_prefix( $context ) {
    $context = sanitize_key( (string) $context );
    return $context ? 'hpw_' . $context : 'hpw_public';
}

function holyprofweb_render_public_form_guard( $context ) {
    $prefix = holyprofweb_get_form_guard_prefix( $context );
    ?>
    <input type="hidden" name="<?php echo esc_attr( $prefix . '_time' ); ?>" value="<?php echo esc_attr( time() ); ?>" />
    <div class="hpw-honeypot" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
        <label for="<?php echo esc_attr( $prefix . '_website' ); ?>"><?php esc_html_e( 'Website', 'holyprofweb' ); ?></label>
        <input type="text" id="<?php echo esc_attr( $prefix . '_website' ); ?>" name="<?php echo esc_attr( $prefix . '_website' ); ?>" value="" tabindex="-1" autocomplete="off" />
    </div>
    <?php
    holyprofweb_render_human_challenge( $context );
}

function holyprofweb_get_recaptcha_provider() {
    $provider = sanitize_key( (string) get_option( 'hpw_spam_challenge_provider', '' ) );
    if ( in_array( $provider, array( 'turnstile', 'recaptcha' ), true ) ) {
        return $provider;
    }
    return '';
}

function holyprofweb_is_human_challenge_enabled() {
    $provider = holyprofweb_get_recaptcha_provider();
    if ( 'turnstile' === $provider ) {
        return (bool) get_option( 'hpw_turnstile_site_key', '' ) && (bool) get_option( 'hpw_turnstile_secret_key', '' );
    }
    if ( 'recaptcha' === $provider ) {
        return (bool) get_option( 'hpw_recaptcha_site_key', '' ) && (bool) get_option( 'hpw_recaptcha_secret_key', '' );
    }
    return false;
}

function holyprofweb_render_human_challenge( $context ) {
    if ( ! holyprofweb_is_human_challenge_enabled() ) {
        return;
    }

    $provider = holyprofweb_get_recaptcha_provider();
    if ( 'turnstile' === $provider ) {
        $site_key = trim( (string) get_option( 'hpw_turnstile_site_key', '' ) );
        if ( $site_key ) {
            echo '<div class="hpw-human-challenge hpw-human-challenge--turnstile" data-context="' . esc_attr( sanitize_key( $context ) ) . '">';
            echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '" data-theme="auto"></div>';
            echo '</div>';
        }
        return;
    }

    if ( 'recaptcha' === $provider ) {
        $site_key = trim( (string) get_option( 'hpw_recaptcha_site_key', '' ) );
        if ( $site_key ) {
            echo '<div class="hpw-human-challenge hpw-human-challenge--recaptcha" data-context="' . esc_attr( sanitize_key( $context ) ) . '">';
            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
            echo '</div>';
        }
    }
}

function holyprofweb_enqueue_human_challenge_assets() {
    if ( is_admin() || ! holyprofweb_is_human_challenge_enabled() ) {
        return;
    }

    $provider = holyprofweb_get_recaptcha_provider();
    if ( 'turnstile' === $provider ) {
        wp_enqueue_script(
            'holyprofweb-turnstile',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            array(),
            null,
            true
        );
        return;
    }

    if ( 'recaptcha' === $provider ) {
        wp_enqueue_script(
            'holyprofweb-recaptcha',
            'https://www.google.com/recaptcha/api.js',
            array(),
            null,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'holyprofweb_enqueue_human_challenge_assets', 25 );

function holyprofweb_get_human_challenge_token() {
    if ( ! empty( $_POST['cf-turnstile-response'] ) ) {
        return sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) );
    }
    if ( ! empty( $_POST['g-recaptcha-response'] ) ) {
        return sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) );
    }
    return '';
}

function holyprofweb_verify_human_challenge() {
    if ( ! holyprofweb_is_human_challenge_enabled() ) {
        return true;
    }

    $provider = holyprofweb_get_recaptcha_provider();
    $token    = holyprofweb_get_human_challenge_token();
    if ( '' === $token ) {
        return new WP_Error( 'challenge_required', __( 'Please complete the human verification challenge.', 'holyprofweb' ) );
    }

    $endpoint = '';
    $secret   = '';
    if ( 'turnstile' === $provider ) {
        $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $secret   = (string) get_option( 'hpw_turnstile_secret_key', '' );
    } elseif ( 'recaptcha' === $provider ) {
        $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
        $secret   = (string) get_option( 'hpw_recaptcha_secret_key', '' );
    }

    if ( '' === $endpoint || '' === $secret ) {
        return new WP_Error( 'challenge_config', __( 'Human verification is not configured correctly.', 'holyprofweb' ) );
    }

    $response = wp_remote_post(
        $endpoint,
        array(
            'timeout' => 10,
            'body'    => array(
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => holyprofweb_get_public_request_ip(),
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'challenge_unavailable', __( 'Verification could not be completed. Please try again.', 'holyprofweb' ) );
    }

    $body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['success'] ) ) {
        return new WP_Error( 'challenge_failed', __( 'Human verification failed. Please try again.', 'holyprofweb' ) );
    }

    return true;
}

function holyprofweb_validate_public_form_guard( $context ) {
    $prefix       = holyprofweb_get_form_guard_prefix( $context );
    $honeypot_key = $prefix . '_website';
    $time_key     = $prefix . '_time';
    $honeypot     = isset( $_POST[ $honeypot_key ] ) ? trim( (string) wp_unslash( $_POST[ $honeypot_key ] ) ) : '';
    $form_time    = isset( $_POST[ $time_key ] ) ? (int) $_POST[ $time_key ] : 0;
    $request_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
    $origin_raw   = '';

    if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
        $origin_raw = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
    } elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        $origin_raw = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
    }

    if ( '' !== $honeypot ) {
        return new WP_Error( 'spam_honeypot', __( 'Security check failed. Please try again.', 'holyprofweb' ) );
    }

    if ( $form_time < ( time() - DAY_IN_SECONDS ) || $form_time > time() || ( time() - $form_time ) < 4 ) {
        return new WP_Error( 'spam_speed', __( 'Please wait a few seconds and submit again.', 'holyprofweb' ) );
    }

    if ( $origin_raw && $request_host ) {
        $origin_host = wp_parse_url( $origin_raw, PHP_URL_HOST );
        if ( $origin_host && strtolower( $origin_host ) !== strtolower( $request_host ) ) {
            return new WP_Error( 'bad_origin', __( 'Security check failed. Please refresh the page and try again.', 'holyprofweb' ) );
        }
    }

    return holyprofweb_verify_human_challenge();
}

function holyprofweb_blocked_internal_hostname( $host ) {
    $host = strtolower( trim( (string) $host ) );
    if ( '' === $host ) {
        return true;
    }

    if ( in_array( $host, array( 'localhost', 'metadata.google.internal' ), true ) ) {
        return true;
    }

    if ( false === strpos( $host, '.' ) ) {
        return true;
    }

    foreach ( array( '.local', '.internal', '.localhost', '.home', '.lan' ) as $suffix ) {
        if ( str_ends_with( $host, $suffix ) ) {
            return true;
        }
    }

    return false;
}

function holyprofweb_is_public_ip_address( $ip_address ) {
    return (bool) filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
}

function holyprofweb_is_safe_public_url( $url ) {
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return false;
    }

    $parts = wp_parse_url( $url );
    if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
        return false;
    }

    $scheme = strtolower( (string) $parts['scheme'] );
    $host   = strtolower( (string) $parts['host'] );

    if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
        return false;
    }

    if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
        return false;
    }

    if ( holyprofweb_blocked_internal_hostname( $host ) ) {
        return false;
    }

    if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
        return holyprofweb_is_public_ip_address( $host );
    }

    $ips = gethostbynamel( $host );
    if ( empty( $ips ) || ! is_array( $ips ) ) {
        return false;
    }

    foreach ( $ips as $ip ) {
        if ( ! holyprofweb_is_public_ip_address( $ip ) ) {
            return false;
        }
    }

    return true;
}

function holyprofweb_clean_public_url_submission( $url ) {
    $url = esc_url_raw( (string) $url );
    return holyprofweb_is_safe_public_url( $url ) ? $url : '';
}

function holyprofweb_can_send_throttled_notification( $type, $limit = 10, $window = HOUR_IN_SECONDS ) {
    $type    = sanitize_key( (string) $type );
    $limit   = max( 1, (int) $limit );
    $window  = max( MINUTE_IN_SECONDS, (int) $window );
    $key     = 'hpw_notice_' . $type;
    $current = (int) get_transient( $key );

    if ( $current >= $limit ) {
        return false;
    }

    set_transient( $key, $current + 1, $window );
    return true;
}

function holyprofweb_get_reaction_identity_key( $post_id ) {
    return 'hpw_react_' . md5( (int) $post_id . '|' . holyprofweb_get_request_fingerprint() );
}

function holyprofweb_get_saved_reaction_for_request( $post_id ) {
    $saved = get_transient( holyprofweb_get_reaction_identity_key( $post_id ) );
    return is_string( $saved ) ? sanitize_key( $saved ) : '';
}

function holyprofweb_store_reaction_for_request( $post_id, $reaction ) {
    set_transient( holyprofweb_get_reaction_identity_key( $post_id ), sanitize_key( $reaction ), 30 * DAY_IN_SECONDS );
}

function holyprofweb_pre_comment_approved( $approved, $commentdata ) {
    if ( is_admin() || ! empty( $commentdata['user_ID'] ) || ! empty( $commentdata['comment_type'] ) ) {
        return $approved;
    }

    $email   = isset( $commentdata['comment_author_email'] ) ? sanitize_email( $commentdata['comment_author_email'] ) : '';
    $content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';

    if ( ! is_email( $email ) ) {
        return 0;
    }

    if ( preg_match( '#https?://|www\.#i', $content ) ) {
        return 0;
    }

    $has_prior_approved = get_comments(
        array(
            'author_email' => $email,
            'status'       => 'approve',
            'count'        => true,
        )
    );

    return $has_prior_approved ? $approved : 0;
}
add_filter( 'pre_comment_approved', 'holyprofweb_pre_comment_approved', 20, 2 );

function holyprofweb_encrypt_private_value( $value ) {
    $value = (string) $value;
    if ( '' === $value || ! function_exists( 'openssl_encrypt' ) ) {
        return $value;
    }

    $key    = hash( 'sha256', wp_salt( 'auth' ), true );
    $iv     = random_bytes( 16 );
    $cipher = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
    if ( false === $cipher ) {
        return $value;
    }

    return base64_encode( $iv . $cipher );
}

function holyprofweb_decrypt_private_value( $value ) {
    $value = (string) $value;
    if ( '' === $value || ! function_exists( 'openssl_decrypt' ) ) {
        return $value;
    }

    $raw = base64_decode( $value, true );
    if ( false === $raw || strlen( $raw ) < 17 ) {
        return $value;
    }

    $key    = hash( 'sha256', wp_salt( 'auth' ), true );
    $iv     = substr( $raw, 0, 16 );
    $cipher = substr( $raw, 16 );
    $plain  = openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

    return false === $plain ? $value : $plain;
}

function holyprofweb_get_subscriber_store() {
    $list = get_option( 'holyprofweb_email_list', array() );
    if ( ! is_array( $list ) ) {
        return array();
    }

    $changed = false;
    foreach ( $list as $key => $entry ) {
        if ( ! is_array( $entry ) ) {
            unset( $list[ $key ] );
            $changed = true;
            continue;
        }
        if ( ! empty( $entry['email'] ) && empty( $entry['encrypted_email'] ) ) {
            $list[ $key ]['encrypted_email'] = holyprofweb_encrypt_private_value( sanitize_email( $entry['email'] ) );
            unset( $list[ $key ]['email'] );
            $changed = true;
        }
    }

    if ( $changed ) {
        update_option( 'holyprofweb_email_list', $list, false );
    }

    return $list;
}

function holyprofweb_get_subscriber_email( $entry ) {
    if ( ! is_array( $entry ) ) {
        return '';
    }
    if ( ! empty( $entry['encrypted_email'] ) ) {
        return sanitize_email( holyprofweb_decrypt_private_value( $entry['encrypted_email'] ) );
    }
    if ( ! empty( $entry['email'] ) ) {
        return sanitize_email( $entry['email'] );
    }
    return '';
}

function holyprofweb_sanitize_ad_code( $code ) {
    $code = (string) $code;
    if ( '' === $code ) {
        return '';
    }

    $code = str_replace( "\0", '', $code );
    $code = preg_replace( '/<\?(?:php|=)?/i', '', $code );
    return trim( $code );
}

function holyprofweb_ad_slot_registry() {
    return array(
        'social',
        'native',
        'banner_728x90',
        'banner_468x60',
        'banner_320x50',
        'banner_160x300',
        'banner_160x600',
        'banner_300x250',
    );
}
