<?php
if (!defined('ABSPATH')) exit;

/**
 * Find a WP user ID by steam_id user meta.
 */
function scp_get_user_id_by_steam_id($steam_id) {
    $query = new WP_User_Query([
        'number'     => 1,
        'fields'     => 'ID',
        'meta_key'   => 'steam_id',
        'meta_value' => $steam_id,
    ]);

    $results = $query->get_results();
    return !empty($results) ? (int) $results[0] : 0;
}

/**
 * Build a unique username.
 */
function scp_generate_unique_username($base_username) {
    $username = sanitize_user($base_username, true);

    if ($username === '') {
        $username = 'steam_user';
    }

    $candidate = $username;
    $suffix    = 1;

    while (username_exists($candidate)) {
        $candidate = $username . '_' . $suffix;
        $suffix++;
    }

    return $candidate;
}

/**
 * Create a secure WP user from steam id.
 */
function scp_register_user_from_steam($steam_id) {
    $steam_user = scp_get_steam_user_info($steam_id);
    $persona    = is_array($steam_user) && !empty($steam_user['name']) ? $steam_user['name'] : 'Steam User';

    $username_base = !empty($steam_user['name']) ? $steam_user['name'] : 'steam_' . $steam_id;
    $user_login    = scp_generate_unique_username($username_base);

    $email_candidate = sprintf('steam_%s@users.local', $steam_id);
    if (email_exists($email_candidate)) {
        $email_candidate = sprintf('steam_%s_%s@users.local', $steam_id, wp_generate_password(6, false, false));
    }

    $user_id = wp_insert_user([
        'user_login'   => $user_login,
        'user_pass'    => wp_generate_password(32, true, true),
        'user_email'   => sanitize_email($email_candidate),
        'display_name' => sanitize_text_field($persona),
        'nickname'     => sanitize_text_field($persona),
        'role'         => 'subscriber',
    ]);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    update_user_meta($user_id, 'steam_id', sanitize_text_field($steam_id));

    return (int) $user_id;
}

/**
 * Shortcode: [steam_connect_button]
 * Steam connect / profile / disconnect
 */
function scp_steam_connect_shortcode() {

    $is_logged_in = is_user_logged_in();
    $user_id  = get_current_user_id();
    $steam_id = $user_id ? get_user_meta($user_id, 'steam_id', true) : '';

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $current_url = esc_url(home_url(add_query_arg([], $request_uri)));

    $openid_params = [
        'openid.ns'         => 'http://specs.openid.net/auth/2.0',
        'openid.mode'       => 'checkid_setup',
        'openid.return_to'  => home_url('/steam-auth-callback') . '?redirect=' . urlencode($current_url),
        'openid.realm'      => home_url(),
        'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
        'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
    ];

    $openid_url = 'https://steamcommunity.com/openid/login?' . http_build_query($openid_params);
    $notice     = isset($_GET['scp_notice']) ? sanitize_key(wp_unslash($_GET['scp_notice'])) : '';

    ob_start();
    ?>
    <div class="scp-box">

        <?php if ($notice === 'steam_exists') : ?>
            <div class="scp-alert scp-alert-error" role="alert">
                این آکانت از قبل در سایت ثبت شده برای راهنمایی لطفا به پشتیبانی پیام بدهید.
            </div>
        <?php endif; ?>

        <?php if ($is_logged_in && $steam_id) :
            $steam_user = scp_get_steam_user_info($steam_id);
            if ($steam_user) : ?>

                <!-- TOP BAR -->
                <div class="scp-top-bar">
                    <div class="scp-success">
                        <span class="scp-check">✔</span>
                        <span>Steam Connected</span>
                    </div>

                    <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="scp_disconnect_steam">
                        <?php wp_nonce_field('scp_disconnect_action', 'scp_disconnect_nonce'); ?>
                        <button type="submit" class="scp-btn scp-btn-danger scp-btn-sm">
                            Disconnect
                        </button>
                    </form>
                </div>

                <!-- PROFILE -->
                <div class="scp-profile">
                    <img src="<?php echo esc_url($steam_user['avatar']); ?>" class="scp-avatar" alt="">
                    <div class="scp-info">
                        <strong><?php echo esc_html($steam_user['name']); ?></strong>
                        <span>Steam Level: <?php echo intval($steam_user['level']); ?></span>
                        <a href="<?php echo esc_url($steam_user['profile_url']); ?>" target="_blank" rel="noopener noreferrer">
                            View Steam Profile →
                        </a>
                    </div>
                </div>

            <?php endif; ?>

        <?php else : ?>

            <a href="<?php echo esc_url($openid_url); ?>" class="scp-btn scp-btn-steam">
                <?php echo $is_logged_in ? 'Connect Steam Account' : 'ورود / ثبت نام با استیم'; ?>
            </a>

        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('steam_connect_button', 'scp_steam_connect_shortcode');

/**
 * OpenID callback handling with verification.
 */
function scp_handle_steam_openid_callback() {
    if (
        !isset($_GET['openid_mode']) ||
        $_GET['openid_mode'] !== 'id_res' ||
        !isset($_SERVER['REQUEST_URI']) ||
        strpos(wp_unslash($_SERVER['REQUEST_URI']), '/steam-auth-callback') === false ||
        !isset($_GET['openid_claimed_id'])
    ) {
        return;
    }

    $verify_params = [];

    foreach ($_GET as $key => $value) {
        if (strpos($key, 'openid_') === 0) {
            $new_key = str_replace('openid_', 'openid.', $key);
            $verify_params[$new_key] = sanitize_text_field(wp_unslash($value));
        }
    }

    if (
        empty($verify_params['openid.claimed_id']) ||
        !preg_match('#^https?://steamcommunity.com/openid/id/(\d+)$#', $verify_params['openid.claimed_id'], $matches)
    ) {
        wp_safe_redirect(home_url('/?steam_error=1'));
        exit;
    }

    $verify_params['openid.mode'] = 'check_authentication';

    $response = wp_remote_post(
        'https://steamcommunity.com/openid/login',
        [
            'body'    => $verify_params,
            'timeout' => 10,
        ]
    );

    if (is_wp_error($response)) {
        wp_safe_redirect(home_url('/?steam_error=1'));
        exit;
    }

    $body = wp_remote_retrieve_body($response);

    if (strpos($body, 'is_valid:true') === false) {
        wp_safe_redirect(home_url('/?steam_error=1'));
        exit;
    }

    $steam_id        = $matches[1];
    $current_user_id = get_current_user_id();
    $existing_user   = scp_get_user_id_by_steam_id($steam_id);

    $raw_redirect = isset($_GET['redirect']) ? esc_url_raw(wp_unslash($_GET['redirect'])) : home_url('/');
    $redirect_url = wp_validate_redirect($raw_redirect, home_url('/'));

    if ($current_user_id) {
        if ($existing_user && $existing_user !== $current_user_id) {
            wp_safe_redirect(add_query_arg('scp_notice', 'steam_exists', $redirect_url));
            exit;
        }

        update_user_meta($current_user_id, 'steam_id', sanitize_text_field($steam_id));
        wp_safe_redirect(add_query_arg('steam_connected', '1', $redirect_url));
        exit;
    }

    if ($existing_user) {
        wp_set_current_user($existing_user);
        wp_set_auth_cookie($existing_user, true);

        $existing = get_user_by('id', $existing_user);
        if ($existing instanceof WP_User) {
            do_action('wp_login', $existing->user_login, $existing);
        }

        wp_safe_redirect(add_query_arg('steam_logged_in', '1', $redirect_url));
        exit;
    }

    $new_user_id = scp_register_user_from_steam($steam_id);

    if (is_wp_error($new_user_id)) {
        wp_safe_redirect(home_url('/?steam_error=register'));
        exit;
    }

    wp_set_current_user($new_user_id);
    wp_set_auth_cookie($new_user_id, true);

    $new_user = get_user_by('id', $new_user_id);
    if ($new_user instanceof WP_User) {
        do_action('wp_login', $new_user->user_login, $new_user);
    }

    wp_safe_redirect(add_query_arg('steam_registered', '1', $redirect_url));
    exit;
}
add_action('init', 'scp_handle_steam_openid_callback');

/**
 * Disconnect handler
 */
add_action('admin_post_scp_disconnect_steam', 'scp_handle_disconnect');
add_action('admin_post_nopriv_scp_disconnect_steam', 'scp_handle_disconnect');

function scp_handle_disconnect() {
    if (!is_user_logged_in() || !check_admin_referer('scp_disconnect_action', 'scp_disconnect_nonce')) {
        wp_safe_redirect(home_url('/?steam_disconnect_error=1'));
        exit;
    }

    $user_id = get_current_user_id();
    delete_user_meta($user_id, 'steam_id');

    wp_safe_redirect(home_url('/?steam_disconnected=1'));
    exit;
}
