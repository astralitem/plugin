<?php
if (!defined('ABSPATH')) exit;

/**
 * Get the Steam Web API key from options.
 *
 * @return string|null
 */
function scp_get_api_key() {
    $key = get_option('scp_api_key', '');
    return $key ? trim($key) : null;
}

/**
 * Fetch Steam user data (GetPlayerSummaries) with caching.
 *
 * @param string $steam_id
 * @return array|false
 */
function scp_get_steam_user_info( $steam_id ) {
    if ( empty( $steam_id ) ) {
        return false;
    }

    $steam_id = sanitize_text_field( $steam_id );
    $cache_key = 'scp_user_info_' . $steam_id;
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return $cached;
    }

    $api_key = scp_get_api_key();
    if ( empty( $api_key ) ) {
        return false;
    }

    $profile_url = add_query_arg( [
        'key'      => $api_key,
        'steamids' => $steam_id,
    ], 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/' );

    $response = wp_remote_get( $profile_url, [
        'timeout' => 10,
    ] );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( empty( $data['response']['players'][0] ) ) {
        return false;
    }

    $player = $data['response']['players'][0];

    $level = 0;
    // Optionally fetch level (cached in separate transient)
    $level_cache = get_transient( 'scp_user_level_' . $steam_id );
    if ( $level_cache !== false ) {
        $level = intval( $level_cache );
    } else {
        $api_key = scp_get_api_key();
        if ( $api_key ) {
            $level_url = add_query_arg( [
                'key'     => $api_key,
                'steamid' => $steam_id,
            ], 'https://api.steampowered.com/IPlayerService/GetSteamLevel/v1/' );

            $level_res = wp_remote_get( $level_url, [
                'timeout' => 10,
            ] );

            if ( ! is_wp_error( $level_res ) ) {
                $level_data = json_decode( wp_remote_retrieve_body( $level_res ), true );
                $level = isset( $level_data['response']['player_level'] ) ? intval( $level_data['response']['player_level'] ) : 0;
                set_transient( 'scp_user_level_' . $steam_id, $level, 12 * HOUR_IN_SECONDS );
            }
        }
    }

    $user_info = [
        'steamid'     => isset( $player['steamid'] ) ? $player['steamid'] : '',
        'name'        => isset( $player['personaname'] ) ? $player['personaname'] : '',
        'profile_url' => isset( $player['profileurl'] ) ? $player['profileurl'] : '',
        'avatar'      => isset( $player['avatarfull'] ) ? $player['avatarfull'] : '',
        'online'      => isset( $player['personastate'] ) ? intval( $player['personastate'] ) : 0,
        'level'       => $level,
    ];

    // Cache profile for 6 hours
    set_transient( $cache_key, $user_info, 6 * HOUR_IN_SECONDS );

    return $user_info;
}
function scp_get_steam_friends($steam_id){

    if(empty($steam_id)) return [];

    $cache_key = 'scp_friends_'.$steam_id;
    $cached = get_transient($cache_key);

    if($cached !== false){
        return $cached;
    }

    $api_key = scp_get_api_key();

    $url = "https://api.steampowered.com/ISteamUser/GetFriendList/v1/?key={$api_key}&steamid={$steam_id}";

    $response = wp_remote_get($url, ['timeout'=>20]);

    if(is_wp_error($response)) return [];

    $body = json_decode(wp_remote_retrieve_body($response), true);

    $friends = $body['friendslist']['friends'] ?? [];

    set_transient($cache_key, $friends, 6 * HOUR_IN_SECONDS);

    return $friends;
}
function scp_get_recent_games($steam_id){

    if(empty($steam_id)) return [];

    $cache_key = 'scp_recent_'.$steam_id;
    $cached = get_transient($cache_key);

    if($cached !== false){
        return $cached;
    }

    $api_key = scp_get_api_key();

    $url = "https://api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/v1/?key={$api_key}&steamid={$steam_id}";

    $response = wp_remote_get($url);

    if(is_wp_error($response)) return [];

    $body = json_decode(wp_remote_retrieve_body($response), true);

    $games = $body['response']['games'] ?? [];

    set_transient($cache_key, $games, 3 * HOUR_IN_SECONDS);

    return $games;
}
function scp_get_owned_games($steam_id){

    if(empty($steam_id)) return [];

    $cache_key = 'scp_owned_'.$steam_id;
    $cached = get_transient($cache_key);

    if($cached !== false){
        return $cached;
    }

    $api_key = scp_get_api_key();

    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key={$api_key}&steamid={$steam_id}&include_appinfo=true&include_played_free_games=true";

    $response = wp_remote_get($url);

    if(is_wp_error($response)) return [];

    $body = json_decode(wp_remote_retrieve_body($response), true);

    $games = $body['response']['games'] ?? [];

    set_transient($cache_key, $games, 12 * HOUR_IN_SECONDS);

    return $games;
}
