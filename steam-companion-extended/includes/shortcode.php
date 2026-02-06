<?php
if (!defined('ABSPATH')) exit;


add_shortcode('steam_extended_profile',function(){

    if(!is_user_logged_in()) return '<p>لطفا وارد شوید</p>';

    $user_id=get_current_user_id();
    $steam_id=get_user_meta($user_id,'steam_id',true);

    if(!$steam_id) return '<p>اکانت Steam متصل نیست</p>';

    $profile=scx_get_player_summary($steam_id);
    if(!$profile) return '<p>خطا در دریافت اطلاعات Steam</p>';

    // Fetch visibility live (no caching) so status is always current
    $visibility = 'Unknown';
    if (defined('SCX_API_KEY') && SCX_API_KEY) {
        $vis_url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=" . SCX_API_KEY . "&steamids={$steam_id}";
        $vis_resp = wp_remote_get($vis_url, ['timeout' => 15]);
        if (!is_wp_error($vis_resp)) {
            $vis_body = json_decode(wp_remote_retrieve_body($vis_resp), true);
            $vis_player = $vis_body['response']['players'][0] ?? null;
            if ($vis_player && isset($vis_player['communityvisibilitystate'])) {
                // Steam communityvisibilitystate values:
                // 1 = Private, 2 = Friends Only, 3 = Public (treat 3 as Public, others as Private)
                $state = intval($vis_player['communityvisibilitystate']);
                $visibility = ($state === 3) ? 'آکانت شما Public است' : 'آکانت شما Private است';
            }
        }
    }

    $level=scx_get_steam_level($steam_id);
    $bans=scx_get_bans($steam_id);
    $friends=scx_get_friends_count($steam_id);
    $games=scx_get_owned_games($steam_id);
	$creation_year = scx_get_account_creation_year($profile);
	$region = scx_get_region($profile);

    /* BAN LOGIC */

    $communityBan=!empty($bans['CommunityBanned']);
    $vacBan=!empty($bans['VACBanned']);
    $gameBan=!empty($bans['NumberOfGameBans']);
    $tradeBan=(($bans['EconomyBan'] ?? 'none') !== 'none');

    $anyBan=$communityBan || $vacBan || $gameBan || $tradeBan;

    ob_start();
?>

<div class="scx-wrapper">

  <!-- HEADER -->
<div class="scx-header-card">

    <!-- LEFT SIDE: AVATAR + NAME + LEVEL -->
    <div class="scx-header-left">
        <div class="scx-avatar-wrap">
            <img src="<?=esc_url($profile['avatarfull'])?>">
        </div>

        <div class="scx-header-info">
            <h2><?=esc_html($profile['personaname'])?></h2>

            <div class="scx-badges">
                <span class="scx-badge pro">PRO</span>
                <span class="scx-steam-level">Lv <?=intval($level)?></span>
            </div>

            <a href="<?=esc_url($profile['profileurl'])?>" target="_blank" class="scx-profile-link">
                Steam Profile
            </a>
        </div>
    </div>

    <!-- RIGHT SIDE: VISIBILITY -->
    <?php
// لینک تنظیمات پروفایل Steam کاربر
$visibility_url = "https://steamcommunity.com/profiles/{$steam_id}/edit/settings";
?>
<div class="scx-header-right">
    <a href="<?= esc_url($visibility_url) ?>" target="_blank" class="scx-visibility-badge <?= ($visibility === 'آکانت شما Public است') ? 'public' : 'private' ?>">
        <?= esc_html($visibility) ?>
    </a>
</div>


</div>



    <!-- STATS -->
<div class="scx-stats-grid">

    <!-- BAN MASTER BOX -->
    <div class="scx-ban-box <?=$anyBan?'banned':'clean'?>">

        <div class="scx-ban-status">
            STATUS : <?=$anyBan?'BANNED':'CLEAN'?>
        </div>

        <div class="scx-ban-grid">

            <div class="scx-ban-item <?=$communityBan?'bad':'good'?>">
                Community Ban
            </div>

            <div class="scx-ban-item <?=$tradeBan?'bad':'good'?>">
                Trade / Market Ban
            </div>

            <div class="scx-ban-item <?=$gameBan?'bad':'good'?>">
                Game Ban
            </div>

            <div class="scx-ban-item <?=$vacBan?'bad':'good'?>">
                VAC Ban
            </div>

        </div>

    </div>

    <!-- RIGHT STATS -->
    <div class="scx-stat blue">
        <span>Games Owned</span>
        <strong><?=count($games)?></strong>
    </div>

    <div class="scx-stat purple">
        <span>Friends</span>
        <strong><?=intval($friends)?></strong>
    </div>

    <div class="scx-stat teal">
        <span>Account Created</span>
        <strong><?=esc_html($creation_year ?? 'N/A')?></strong>
    </div>

    <div class="scx-stat gold">
        <span>Region</span>
        <strong><?=esc_html($region ?? 'N/A')?></strong>
    </div>

</div>


    <!-- GAMES -->
    <h3 class="scx-section-title">Game Library</h3>

<div class="scx-games-grid">
<?php foreach($games as $game):

    $playtime_hours = floor(($game['playtime_forever'] ?? 0) / 60);
    $store = scx_get_game_store_info($game['appid']);

    $is_free = !empty($store['is_free']);
    $price = null;

    if(!$is_free && !empty($store['price_overview'])){
        $price = $store['price_overview']['final_formatted'];
    }
?>
    <div class="scx-game-card">

        <img src="https://cdn.cloudflare.steamstatic.com/steam/apps/<?=$game['appid']?>/header.jpg">

        <div class="scx-game-info">
            <div class="scx-game-title"><?=esc_html($game['name'])?></div>
            <div class="scx-game-meta">
                <span class="scx-game-hours"><?=$playtime_hours?> ساعت </span>
                <?php if($is_free): ?>
                    <span class="scx-game-free">Free</span>
                <?php elseif($price): ?>
                    <span class="scx-game-price"><?=$price?></span>
                <?php else: ?>
                    <span class="scx-game-price">Paid</span>
                <?php endif; ?>
            </div>
        </div>

    </div>
<?php endforeach; ?>
</div>
<!-- ================= DOTA 2 PROFESSIONAL CARD ================= -->

<div class="scx-dota-card">

    <div class="scx-dota-head">
        <span>Dota 2 Form</span>
        <span class="scx-dota-toggle">+</span>
    </div>

    <div class="scx-dota-content">
<?php
$medals = [
    'Herald'    => [1,2,3,4,5],
    'Guardian'  => [1,2,3,4,5],
    'Crusader'  => [1,2,3,4,5],
    'Archon'    => [1,2,3,4,5],
    'Legend'    => [1,2,3,4,5],
    'Ancient'   => [1,2,3,4,5],
    'Divine'    => [1,2,3,4,5],
    'Immortal'  => [1],
    'Immortal Top'  => [100, 10],
];
?>

<div class="scx-medal-card">
    <div class="scx-card-content">
        <div class="scx-medals">
            <?php foreach ($medals as $name => $stars): ?>
                <?php foreach ($stars as $star): ?>
                    <div class="scx-medal <?php 
echo strtolower(str_replace(' ', '-', $name)); 
echo (stripos($name, 'immortal') !== false) ? ' immortal' : ''; 
?>" data-value="<?php echo esc_attr("$name-$star"); ?>">

                        <img src="<?php echo esc_url( SCX_URL . "assets/medals/". lcfirst(str_replace(' ', '-', $name)) . "-$star.png" ); ?>" alt="<?php echo esc_attr("$name $star"); ?>">
                        <!-- اسم مدال زیر عکس -->
                       <span class="scx-medal-title">
    <?=esc_html($name . ' ' . $star)?>
</span>

                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="dota_medal" value="">
    </div>
</div>


        <div class="scx-dota-grid">

         
            <!-- MMR -->
            <div class="scx-dota-field blue">
                <label>MMR</label>
                <div class="scx-input">
                    <input type="number" name="dota_mmr" placeholder="4320">
                </div>
            </div>

            <!-- BEHAVIOR -->
            <div class="scx-dota-field teal">
                <label>Behavior Score</label>
                <div class="scx-input">
                    <input type="number" name="dota_behavior" placeholder="9400">
                </div>
            </div>

            <!-- LEVEL -->
            <div class="scx-dota-field gold">
                <label>Dota Level</label>
                <div class="scx-input">
                    <input type="number" name="dota_level" placeholder="57">
                </div>
            </div>

			 <!-- SHARDS -->
  			  <div class="scx-dota-field shards">
    		    <label>Shards</label>
    		    <div class="scx-input">
            <input type="number" name="dota_shards" placeholder="12500">
        </div>
    </div>

</div>

        </div>

    </div>
</div>


</div>


<!-- ================= ACCOUNT SUBMISSION FORM ================= -->

<div class="scx-account-form">

    <div class="scx-form-row">

        <!-- PRICE -->
        <div class="scx-form-field price">
            <label>قیمت</label>
            <div class="scx-input">
                <input type="number" name="account_price" placeholder="مثلا :2,500,000">
            </div>
        </div>

        <!-- MARKET STATUS -->
        <div class="scx-form-field market">
            <label>وضعیت مارکت استیم</label>
            <div class="scx-select scx-form-select">
                <div class="scx-select-trigger">انتخاب</div>
                <div class="scx-select-options">
                    <div class="scx-option" data-value="active">فعال</div>
                    <div class="scx-option" data-value="inactive">غیر فعال</div>
                </div>
                <input type="hidden" name="market_status" value="">
            </div>
        </div>

        <!-- MAIN EMAIL -->
        <div class="scx-form-field email">
            <label>ایمیل اوریجینال</label>
            <div class="scx-select scx-form-select">
                <div class="scx-select-trigger">انتخاب</div>
                <div class="scx-select-options">
                    <div class="scx-option" data-value="yes">بله</div>
                    <div class="scx-option" data-value="no">خیر</div>
                </div>
                <input type="hidden" name="main_email" value="">
            </div>
        </div>

    </div>

    <!-- DESCRIPTION -->
    <div class="scx-form-row full">
        <div class="scx-form-field">
            <label>توضیحات</label>
            <div class="scx-input">
                <textarea name="account_description" rows="4" placeholder="توضیحات کامل آکانت..."></textarea>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <div class="scx-form-actions">

        <div class="scx-upload-wrap">
            <input type="file" id="scx-upload-input" accept="image/*" multiple hidden>
            <button type="button" class="scx-upload-btn">آپلود تصاویر</button>
            <div class="scx-upload-previews"></div>
        </div>

        <button type="button" class="scx-submit-btn">ارسال آکانت</button>

    </div>

</div>

<?php
return ob_get_clean();

});