<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Build Steam OpenID URL.
 */
function scp_get_steam_openid_url($current_url = '') {
    $current_url = $current_url ? esc_url_raw($current_url) : home_url('/');

    $openid_params = [
        'openid.ns'         => 'http://specs.openid.net/auth/2.0',
        'openid.mode'       => 'checkid_setup',
        'openid.return_to'  => home_url('/steam-auth-callback') . '?redirect=' . urlencode($current_url),
        'openid.realm'      => home_url(),
        'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
        'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
    ];

    return 'https://steamcommunity.com/openid/login?' . http_build_query($openid_params);
}

/**
 * Find a user by steam_id meta.
 */
function scp_find_user_by_steam_id($steam_id) {
    $users = get_users([
        'meta_key'   => 'steam_id',
        'meta_value' => sanitize_text_field($steam_id),
        'number'     => 1,
        'fields'     => ['ID'],
    ]);

    if (!empty($users) && isset($users[0]->ID)) {
        return (int) $users[0]->ID;
    }

    return 0;
}

/**
 * Create a new WP user for a Steam account.
 */
function scp_create_user_from_steam($steam_id) {
    $steam_user = scp_get_steam_user_info($steam_id);
    $base_login = 'steam_' . sanitize_user($steam_id);
    $user_login = $base_login;
    $suffix = 1;

    while (username_exists($user_login)) {
        $user_login = $base_login . '_' . $suffix;
        $suffix++;
    }

    // WordPress expects a valid email format for user creation on most setups.
    // We use a placeholder email on a reserved domain so registration always works.
    $email = scp_generate_unique_placeholder_email($user_login);

    $display_name = !empty($steam_user['name']) ? sanitize_text_field($steam_user['name']) : 'Steam User ' . $steam_id;

    $user_id = wp_insert_user([
        'user_login'   => $user_login,
        'user_pass'    => wp_generate_password(24, true, true),
        'user_email'   => $email,
        'display_name' => $display_name,
        'nickname'     => $display_name,
        'role'         => get_option('default_role', 'subscriber'),
    ]);

    if (is_wp_error($user_id)) {
        return 0;
    }

    update_user_meta($user_id, 'steam_id', sanitize_text_field($steam_id));

    return (int) $user_id;
}

/**
 * Generate a unique placeholder email for Steam-created accounts.
 * Uses the reserved .invalid TLD to avoid real email collisions.
 */
function scp_generate_unique_placeholder_email($user_login) {
    $base = sanitize_user($user_login, true);
    if (!$base) {
        $base = 'steam_user';
    }

    $email = $base . '@steam.invalid';

    while (email_exists($email)) {
        $email = $base . '_' . wp_generate_password(6, false, false) . '@steam.invalid';
    }

    return $email;
}

/**
 * Find existing user by steam id, or create user if not found.
 */
function scp_get_or_create_user_by_steam($steam_id) {
    $user_id = scp_find_user_by_steam_id($steam_id);

    if ($user_id) {
        return $user_id;
    }

    return scp_create_user_from_steam($steam_id);
}

/**
 * Shortcode: [steam_connect_button]
 * Steam connect / profile / disconnect
 */
function scp_steam_connect_shortcode() {
    $is_logged_in = is_user_logged_in();
    $user_id = get_current_user_id();
    $steam_id = $user_id ? get_user_meta($user_id, 'steam_id', true) : '';

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $current_url = esc_url(home_url(add_query_arg([], $request_uri)));
    $openid_url = scp_get_steam_openid_url($current_url);

    ob_start();
    ?>
    <div class="scp-box">
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
                <?php echo $is_logged_in ? 'Connect Steam Account' : 'Login with Steam'; ?>
            </a>

        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('steam_connect_button', 'scp_steam_connect_shortcode');

/**
 * OpenID callback handling with verification.
 * Verifies the OpenID response with Steam before trusting the steamid.
 * If Steam account is already linked to a user, logs that user in.
 * If not linked, creates a new user and logs them in.
 */
add_action('init', function () {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';

    if (
        isset($_GET['openid_mode']) &&
        $_GET['openid_mode'] === 'id_res' &&
        strpos($request_uri, '/steam-auth-callback') !== false &&
        isset($_GET['openid_claimed_id'])
    ) {
        $verify_params = [];

        foreach ($_GET as $key => $value) {
            if (strpos($key, 'openid_') === 0) {
                $new_key = str_replace('openid_', 'openid.', $key);
                $verify_params[$new_key] = wp_unslash($value);
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

        $steam_id = sanitize_text_field($matches[1]);
        $current_user_id = get_current_user_id();

        if ($current_user_id) {
            update_user_meta($current_user_id, 'steam_id', $steam_id);
            $redirect_url = isset($_GET['redirect']) ? esc_url_raw(wp_unslash($_GET['redirect'])) : home_url('/?steam_connected=1');
            wp_safe_redirect($redirect_url);
            exit;
        }

        $user_id = scp_get_or_create_user_by_steam($steam_id);

        if (!$user_id) {
            wp_safe_redirect(home_url('/?steam_error=1'));
            exit;
        }

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        $user = get_userdata($user_id);
        if ($user) {
            do_action('wp_login', $user->user_login, $user);
        }

        $redirect_url = isset($_GET['redirect']) ? esc_url_raw(wp_unslash($_GET['redirect'])) : home_url('/?steam_connected=1');
        wp_safe_redirect($redirect_url);
        exit;
    }
});

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
