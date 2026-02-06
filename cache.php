<?php
if (!defined('ABSPATH')) exit;

function scx_get_cached($key) {
    return get_transient('scx_' . $key);
}

function scx_set_cached($key, $value) {
    set_transient('scx_' . $key, $value, SCX_CACHE_TIME);
}

function scx_api_request($url) {

    $cache_key = md5($url);
    $cached = scx_get_cached($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $response = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($response)) return false;

    $body = json_decode(wp_remote_retrieve_body($response), true);

    scx_set_cached($cache_key, $body);
    return $body;
}
