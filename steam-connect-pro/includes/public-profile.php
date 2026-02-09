<?php
if (!defined('ABSPATH')) exit;

/*
 * Public Steam User Profile Page
 */

function scp_public_profile_template() {

    if (!get_query_var('scp_steam_user')) return;

    $steam_id = sanitize_text_field(get_query_var('scp_steam_user'));

    if (!$steam_id) return;

    $steam_data = scp_get_steam_user_info($steam_id);
    $friends = scp_get_steam_friends($steam_id);
    $recent_games = scp_get_recent_games($steam_id);
    $owned_games = scp_get_owned_games($steam_id);

/* Sort by playtime (DESC) */
if(!empty($recent_games)){
    usort($recent_games, function($a,$b){
        return $b['playtime_forever'] <=> $a['playtime_forever'];
    });
}

if(!empty($owned_games)){
    usort($owned_games, function($a,$b){
        return $b['playtime_forever'] <=> $a['playtime_forever'];
    });
}





    if (!$steam_data) {
        wp_die('Steam user not found');
    }

    get_header();

    ?>

    <div class="scp-public-profile">

        <div class="scp-public-card">

            <img src="<?php echo esc_url($steam_data['avatar']); ?>" class="scp-avatar-large">

            <h2><?php echo esc_html($steam_data['name']); ?></h2>

            <div class="scp-level">
                Steam Level: <?php echo intval($steam_data['level']); ?>
            </div>
<div class="scp-friends-count">
    Friends: <?php echo count($friends); ?>
</div>

            <div class="scp-status">
                <span class="scp-online-status-profile <?php echo ($steam_data['online'] > 0 ? 'online' : 'offline'); ?>">
                    <?php echo ($steam_data['online'] > 0 ? 'Online' : 'Offline'); ?>
                </span>
            </div>

            <a href="<?php echo esc_url($steam_data['profile_url']); ?>" target="_blank" class="scp-profile-link-steami">
                View Steam Profile
            </a>

        </div>

    <div class="scp-section-header">
    <h3 class="scp-section-title">Recently Played</h3>
</div>


<div class="scp-games-grid">

<?php if(!empty($recent_games)): ?>

<?php foreach($recent_games as $game): ?>

<div class="scp-game-card">

<img src="https://cdn.cloudflare.steamstatic.com/steam/apps/<?php echo $game['appid']; ?>/header.jpg">

<div class="scp-game-info">
<strong><?php echo esc_html($game['name']); ?></strong>

<div class="scp-playtime-badge">
     <?php echo round($game['playtime_forever']/60,1); ?> ساعت بازی
</div>


</div>
</div>

<?php endforeach; ?>

<?php else: ?>
<div class="scp-empty-state">
    هیچ بازی اخیری پیدا نشد
</div>

<?php endif; ?>

</div>
<div class="scp-section-header">
    <h3 class="scp-section-title">بازی‌هایی که این کاربر تجربه کرده</h3>
</div>


<div class="scp-games-grid">

<?php if(!empty($owned_games)): ?>

<?php foreach(array_slice($owned_games,0,24) as $game): ?>

<div class="scp-game-card">

<img src="https://cdn.cloudflare.steamstatic.com/steam/apps/<?php echo $game['appid']; ?>/header.jpg">

<div class="scp-game-info">

<strong><?php echo esc_html($game['name']); ?></strong>

<div class="scp-playtime-badge">
     <?php echo round($game['playtime_forever']/60,1); ?> ساعت بازی
</div>

</div>
</div>

<?php endforeach; ?>

<?php else: ?>
<div class="scp-empty-state">
    هیچ بازی‌ای یافت نشد
</div>

<?php endif; ?>

</div>

</div> <!-- close scp-public-profile -->

    <?php

    get_footer();
    exit;
}

add_action('template_redirect', 'scp_public_profile_template');
