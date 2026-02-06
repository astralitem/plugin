<?php
// name=steam-companion-extended/steam-companion-extended.php
/*
Plugin Name: Steam Companion Extended Info
Description: افزونه مکمل برای اتصال Steam که اطلاعات کامل اکانت را نمایش می‌دهد.
Version: 1.2.0
*/

if (!defined('ABSPATH')) exit;

define('SCX_PATH', plugin_dir_path(__FILE__));
define('SCX_URL', plugin_dir_url(__FILE__));

/* Load Core Files */
require_once SCX_PATH . 'includes/constants.php';
require_once SCX_PATH . 'includes/cache.php';
require_once SCX_PATH . 'includes/steam-api.php';
require_once SCX_PATH . 'includes/shortcode.php';
require_once SCX_PATH . 'includes/form-handler.php'; // جدید
require_once SCX_PATH . 'includes/edd-meta.php';     // جدید

// New additions
require_once SCX_PATH . 'includes/template-functions.php'; // product card renderer
require_once SCX_PATH . 'includes/dashboard-shortcode.php'; // user dashboard shortcode

/* Assets */
function scx_enqueue_assets() {
    // همیشه CSS و JS افزونه را روی صفحات لازم load کن
    wp_enqueue_style(
        'scx-style',
        SCX_URL . 'assets/steam-companion-extended.css',
        [],
        '1.2.0'
    );

    // Product card specific CSS (new)
    wp_enqueue_style(
        'scx-product-card',
        SCX_URL . 'assets/scx-product-card.css',
        ['scx-style'],
        '1.0.0'
    );

    wp_enqueue_script(
        'scx-dota',
        SCX_URL . 'assets/scx-dota.js',
        ['jquery'],
        '1.1',
        true
    );

    wp_localize_script('scx-dota', 'scx_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('scx_submit_account'),
    ]);
}
add_action('wp_enqueue_scripts', 'scx_enqueue_assets');

/* Single template for EDD download (allow theme override) */
function scx_single_download_template($single) {
    global $post;

    if (empty($post) || $post->post_type !== 'download') {
        return $single;
    }

    // If theme provided its own template named single-download-scx.php, use it.
    $theme_template = locate_template('single-download-scx.php');
    if ($theme_template) {
        return $theme_template;
    }

    // Else, use plugin template if exists
    $plugin_template = SCX_PATH . 'templates/single-download-scx.php';
    if (file_exists($plugin_template)) {
        return $plugin_template;
    }

    return $single;
}
add_filter('single_template', 'scx_single_download_template');