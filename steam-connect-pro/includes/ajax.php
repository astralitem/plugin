<?php
if (!defined('ABSPATH')) exit;

/**
 * AJAX: Check online status for a list of Steam IDs.
 * Expects POST: steamids (comma separated) and nonce.
 */
add_action( 'wp_ajax_scp_check_online_status', 'scp_check_online_status' );
add_action( 'wp_ajax_nopriv_scp_check_online_status', 'scp_check_online_status' );

function scp_check_online_status() {
    // Check nonce (returns false if invalid)
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'scp-ajax' ) ) {
        wp_send_json_error( [ 'message' => 'invalid_nonce' ], 400 );
    }

    if ( empty( $_POST['steamids'] ) ) {
        wp_send_json_error( [ 'message' => 'no_steamids' ], 400 );
    }

    $raw = sanitize_text_field( wp_unslash( $_POST['steamids'] ) );
    $steamids = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

    // Keep only numeric steamids (safety)
    $steamids = array_filter( $steamids, function ( $id ) {
        return preg_match( '/^\d+$/', $id );
    } );

    if ( empty( $steamids ) ) {
        wp_send_json_error( [ 'message' => 'invalid_steamids' ], 400 );
    }

    $api_key = scp_get_api_key();
    if ( empty( $api_key ) ) {
        wp_send_json_error( [ 'message' => 'missing_api_key' ], 500 );
    }

    $result = [];
    $to_query = [];

    // Try to serve from transient cache per steamid
    foreach ( $steamids as $sid ) {
        $cache_key = 'scp_online_status_' . $sid;
        $cached = get_transient( $cache_key );
        if ( $cached !== false ) {
            $result[ $sid ] = $cached;
        } else {
            $to_query[] = $sid;
        }
    }

    if ( ! empty( $to_query ) ) {
        $query_ids = implode( ',', array_map( 'sanitize_text_field', $to_query ) );

        $url = add_query_arg( [
            'key'      => $api_key,
            'steamids' => $query_ids,
        ], 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/' );

        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $response ) ) {
            // return what we have but indicate partial failure
            wp_send_json_error( [ 'message' => 'remote_error' ], 502 );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! empty( $data['response']['players'] ) && is_array( $data['response']['players'] ) ) {
            foreach ( $data['response']['players'] as $player ) {
                $sid = isset( $player['steamid'] ) ? $player['steamid'] : '';
                $state = ( isset( $player['personastate'] ) && intval( $player['personastate'] ) > 0 ) ? 'Online' : 'Offline';
                if ( $sid ) {
                    $result[ $sid ] = $state;
                    // Cache status for short time to reduce API calls
                    set_transient( 'scp_online_status_' . $sid, $state, 30 ); // 30 seconds
                }
            }
        }
    }

    wp_send_json_success( $result );
}





