<?php
if (!defined('ABSPATH')) exit;

/**
 * Improved caching helpers for SCX plugin
 *
 * - Uses wp_cache (object cache) + transients for persistence
 * - Validates URLs before requesting
 * - Caches only successful JSON responses
 * - Tracks cache keys so we can flush plugin cache
 * - Honors WP_DEBUG and optional SCX_DISABLE_CACHE to bypass cache
 *
 * Compatible with previous usage which supplied an md5($url) key to scx_get_cached / scx_set_cached.
 */

/**
 * Prefix used for transients / key registry
 */
if (!defined('SCX_CACHE_TRANSIENT_PREFIX')) {
    define('SCX_CACHE_TRANSIENT_PREFIX', 'scx_api_cache_');
}

/**
 * Object cache group
 */
if (!defined('SCX_CACHE_GROUP')) {
    define('SCX_CACHE_GROUP', 'scx');
}

/**
 * Registry transient key that stores all active cache keys created by plugin.
 * This allows flushing all plugin cache entries on demand.
 */
if (!defined('SCX_CACHE_KEYS_TRANSIENT')) {
    define('SCX_CACHE_KEYS_TRANSIENT', 'scx_api_cache_keys_registry');
}

/**
 * Determine whether caching should be bypassed.
 * Useful during development or when explicitly disabled.
 */
function scx_is_cache_disabled() {
    if (defined('SCX_DISABLE_CACHE') && SCX_DISABLE_CACHE) return true;
    if (defined('WP_DEBUG') && WP_DEBUG) return true;
    return false;
}

/**
 * Normalize a cache key. Accepts either an already hashed key or any string.
 * Keeps compatibility: if md5-like hex string passed, use as-is; else hash it.
 *
 * @param string $key
 * @return string normalized key
 */
function scx_normalize_key($key) {
    $key = (string) $key;
    // If already 32 hex chars (md5), accept it; else md5 it.
    if (preg_match('/^[a-f0-9]{32}$/i', $key)) {
        return strtolower($key);
    }
    return md5($key);
}

/**
 * Register a new cache key into the registry for later flushing.
 *
 * @param string $key normal (already hashed) key
 * @return void
 */
function scx_register_cache_key($key) {
    $key = scx_normalize_key($key);
    $registry = get_transient(SCX_CACHE_KEYS_TRANSIENT);
    if (!is_array($registry)) $registry = [];
    if (!in_array($key, $registry, true)) {
        $registry[] = $key;
        // Keep registry TTL reasonably long (e.g. same as SCX_CACHE_TIME)
        set_transient(SCX_CACHE_KEYS_TRANSIENT, $registry, defined('SCX_CACHE_TIME') ? SCX_CACHE_TIME : HOUR_IN_SECONDS);
    }
}

/**
 * Remove a cache key from registry (internal use)
 *
 * @param string $key
 * @return void
 */
function scx_unregister_cache_key($key) {
    $key = scx_normalize_key($key);
    $registry = get_transient(SCX_CACHE_KEYS_TRANSIENT);
    if (!is_array($registry)) return;
    $idx = array_search($key, $registry, true);
    if ($idx !== false) {
        unset($registry[$idx]);
        set_transient(SCX_CACHE_KEYS_TRANSIENT, $registry, defined('SCX_CACHE_TIME') ? SCX_CACHE_TIME : HOUR_IN_SECONDS);
    }
}

/**
 * Get cached value for a key.
 * Tries object cache first then transient.
 *
 * @param string $key raw or hashed key
 * @return mixed|false cached value or false if not found
 */
function scx_get_cached($key) {
    if (scx_is_cache_disabled()) {
        return false;
    }

    $hkey = scx_normalize_key($key);

    // Try object cache
    $cached = wp_cache_get($hkey, SCX_CACHE_GROUP);
    if ($cached !== false) {
        return $cached;
    }

    // Fallback to transient
    $transient_key = SCX_CACHE_TRANSIENT_PREFIX . $hkey;
    $value = get_transient($transient_key);
    if ($value !== false) {
        // populate object cache for the rest of the request
        wp_cache_set($hkey, $value, SCX_CACHE_GROUP);
        return $value;
    }

    return false;
}

/**
 * Set cached value for a key.
 * Writes to both object cache and transient.
 *
 * @param string $key raw or hashed key
 * @param mixed $value Serializable value to cache
 * @param int|null $expire seconds. If null, uses SCX_CACHE_TIME if defined, otherwise 6 hours.
 * @return void
 */
function scx_set_cached($key, $value, $expire = null) {
    if (scx_is_cache_disabled()) {
        return;
    }

    $hkey = scx_normalize_key($key);
    $expire = is_int($expire) ? $expire : (defined('SCX_CACHE_TIME') ? SCX_CACHE_TIME : 6 * HOUR_IN_SECONDS);

    // Store in object cache
    wp_cache_set($hkey, $value, SCX_CACHE_GROUP, $expire);

    // Store persistent transient
    $transient_key = SCX_CACHE_TRANSIENT_PREFIX . $hkey;
    set_transient($transient_key, $value, $expire);

    // Register key for future flush
    scx_register_cache_key($hkey);
}

/**
 * Delete a cache entry by key (both object cache and transient)
 *
 * @param string $key
 * @return void
 */
function scx_delete_cached($key) {
    $hkey = scx_normalize_key($key);

    // object cache
    wp_cache_delete($hkey, SCX_CACHE_GROUP);

    // transient
    $transient_key = SCX_CACHE_TRANSIENT_PREFIX . $hkey;
    delete_transient($transient_key);

    // unregister
    scx_unregister_cache_key($hkey);
}

/**
 * Flush all plugin cache entries that were created via scx_set_cached.
 * Iterates registry and deletes entries.
 *
 * @return void
 */
function scx_flush_cache() {
    $registry = get_transient(SCX_CACHE_KEYS_TRANSIENT);
    if (!is_array($registry) || empty($registry)) {
        // nothing to do
        return;
    }

    foreach ($registry as $hkey) {
        // Ensure normalization
        $hkey = scx_normalize_key($hkey);
        wp_cache_delete($hkey, SCX_CACHE_GROUP);
        delete_transient(SCX_CACHE_TRANSIENT_PREFIX . $hkey);
    }

    // remove registry
    delete_transient(SCX_CACHE_KEYS_TRANSIENT);
}

/**
 * Perform an API request with caching applied.
 *
 * @param string $url
 * @param bool $force_refresh Bypass cache and fetch fresh
 * @return mixed|false Decoded JSON array/object on success, false on error
 */
function scx_api_request($url, $force_refresh = false) {

    // Validate URL
    if (!is_string($url) || !wp_http_validate_url($url)) {
        return false;
    }

    // if caching disabled globally or forced refresh, skip retrieving cached value
    $cache_key = md5($url);

    if (!$force_refresh && !scx_is_cache_disabled()) {
        $cached = scx_get_cached($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    // Perform remote request
    $args = [
        'timeout'   => 15,
        'headers'   => [
            'Accept' => 'application/json',
        ],
        // leave sslverify default (true) — do not force-disable for security
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        // do not cache WP_Error result as a valid response
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    if (intval($code) !== 200) {
        return false;
    }

    $body_raw = wp_remote_retrieve_body($response);
    if (empty($body_raw)) {
        return false;
    }

    // Decode JSON safely
    $body = json_decode($body_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    // Cache successful response
    scx_set_cached($cache_key, $body);

    return $body;
}