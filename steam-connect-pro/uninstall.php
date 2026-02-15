<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options
delete_option( 'scp_api_key' );
delete_option( 'scp_cache_profile_duration' );

// Note: We intentionally do not delete user meta steam_id automatically.
// If you want to remove them, uncomment the following block (CAUTION):
/*
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'steam_id'" );
*/