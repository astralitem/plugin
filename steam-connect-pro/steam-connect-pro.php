<?php
/*
Plugin Name: Steam Connect Pro
Description: Connect Steam accounts and display connected users publicly.
Version: 1.1.0
Author: You
Text Domain: steam-connect-pro
Domain Path: /languages
*/

if (! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SCP_VERSION', '1.1.0' );
define( 'SCP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SCP_PLUGIN_PATH . 'includes/api.php';
require_once SCP_PLUGIN_PATH . 'includes/auth.php';
require_once SCP_PLUGIN_PATH . 'includes/users-list.php';
require_once SCP_PLUGIN_PATH . 'includes/ajax.php';
require_once SCP_PLUGIN_PATH . 'includes/admin.php';
require_once SCP_PLUGIN_PATH . 'includes/public-profile.php';


function scp_add_rewrite_rule() {
    add_rewrite_rule(
        '^steam-user/([0-9]+)/?$',
        'index.php?scp_steam_user=$matches[1]',
        'top'
    );
}
add_action('init', 'scp_add_rewrite_rule');

function scp_add_query_vars($vars) {
    $vars[] = 'scp_steam_user';
    return $vars;
}
add_filter('query_vars', 'scp_add_query_vars');

/**
 * Enqueue frontend assets
 */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'scp-style', SCP_PLUGIN_URL . 'assets/css/style.css', [], SCP_VERSION );
    wp_register_script( 'scp-online', SCP_PLUGIN_URL . 'assets/js/online-status.js', [ 'jquery' ], SCP_VERSION, true );

    $scp_ajax_data = [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'scp-ajax' ),
    ];

    wp_localize_script( 'scp-online', 'scpAjax', $scp_ajax_data );
    wp_enqueue_script( 'scp-online' );
} );





