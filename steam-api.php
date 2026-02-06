<?php
if (!defined('ABSPATH')) exit;

function scx_get_player_summary($steam_id) {
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=" . SCX_API_KEY . "&steamids={$steam_id}";
    $data = scx_api_request($url);
    return $data['response']['players'][0] ?? false;
}

function scx_get_steam_level($steam_id) {
    $url = "https://api.steampowered.com/IPlayerService/GetSteamLevel/v1/?key=" . SCX_API_KEY . "&steamid={$steam_id}";
    $data = scx_api_request($url);
    return $data['response']['player_level'] ?? 0;
}

function scx_get_bans($steam_id) {
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerBans/v1/?key=" . SCX_API_KEY . "&steamids={$steam_id}";
    $data = scx_api_request($url);
    return $data['players'][0] ?? [];
}

function scx_get_friends_count($steam_id) {
    $url = "https://api.steampowered.com/ISteamUser/GetFriendList/v1/?key=" . SCX_API_KEY . "&steamid={$steam_id}&relationship=friend";
    $data = scx_api_request($url);
    return isset($data['friendslist']['friends']) ? count($data['friendslist']['friends']) : 0;
}

function scx_get_owned_games($steam_id) {
    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=" . SCX_API_KEY . "&steamid={$steam_id}&include_appinfo=1&include_played_free_games=1";
    $data = scx_api_request($url);
    return $data['response']['games'] ?? [];
}

function scx_get_game_store_info($app_id) {
    $url = "https://store.steampowered.com/api/appdetails?appids={$app_id}&cc=us&l=en";
    $data = scx_api_request($url);

    if (empty($data[$app_id]['success'])) return null;
    return $data[$app_id]['data'] ?? null;
}

function scx_get_account_creation_year($profile) {
    return empty($profile['timecreated']) ? null : date('Y', $profile['timecreated']);
}

function scx_get_region($profile) {
    return $profile['loccountrycode'] ?? null;
}
