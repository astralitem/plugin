<?php
if (!defined('ABSPATH')) exit;
get_header();

global $post;
$post_id = $post->ID;

/** helper */
function scx_meta($post_id, $key, $fallback = '') {
    $v = get_post_meta($post_id, $key, true);
    return $v !== '' ? $v : $fallback;
}

/* Steam / header */
$steam_avatar = scx_meta($post_id, 'scx_steam_avatar', '');
$steam_name = scx_meta($post_id, 'scx_steam_name', get_the_title($post_id));
$steam_level = scx_meta($post_id, 'scx_steam_level', '');
$steam_profile_url = scx_meta($post_id, 'scx_steam_profile_url', '');

/* Bans */
$communityBan = scx_meta($post_id, 'scx_ban_community', '') === '1';
$tradeBan = scx_meta($post_id, 'scx_ban_trade', '') === '1';
$gameBan = scx_meta($post_id, 'scx_ban_game', '') === '1';
$vacBan = scx_meta($post_id, 'scx_ban_vac', '') === '1';
$anyBan = $communityBan || $tradeBan || $gameBan || $vacBan;

/* Stats */
$games_owned = scx_meta($post_id, 'scx_games_owned', '');
$friends = scx_meta($post_id, 'scx_friends', '');
$account_created = scx_meta($post_id, 'scx_account_created', '');
$region = scx_meta($post_id, 'scx_region', '');

/* Games library (JSON) */
$games_json = scx_meta($post_id, 'scx_games_library', '[]');
$games = json_decode($games_json, true);
if (!is_array($games)) $games = [];

/* Dota */
$dota_medal = scx_meta($post_id, 'scx_dota_medal', '');
$dota_mmr = scx_meta($post_id, 'scx_dota_mmr', '');
$dota_behavior = scx_meta($post_id, 'scx_dota_behavior', '');
$dota_level = scx_meta($post_id, 'scx_dota_level', '');
$dota_shards = scx_meta($post_id, 'scx_dota_shards', '');

/* Market / price / email / description */
$account_price = scx_meta($post_id, 'scx_account_price', '');
$market_status = scx_meta($post_id, 'scx_market_status', '');
$main_email = scx_meta($post_id, 'scx_main_email', '');
$account_description = get_post_field('post_content', $post_id);

/* Gallery images (attachment IDs) */
$gallery = get_post_meta($post_id, 'scx_gallery', true);
if (!is_array($gallery)) $gallery = [];

/* Fallback avatar: post thumbnail */
if (empty($steam_avatar)) {
    $thumb = get_the_post_thumbnail_url($post_id, 'full');
    if ($thumb) $steam_avatar = $thumb;
}

/* medals list (used for building selected-medal filename) */
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

/* parse selected medal into name & star if present */
$selected_medal_name = '';
$selected_medal_star = '';
if (!empty($dota_medal) && strpos($dota_medal, '-') !== false) {
    list($selected_medal_name, $selected_medal_star) = explode('-', $dota_medal, 2);
}
$selected_medal_img = '';
if ($selected_medal_name && $selected_medal_star) {
    $medal_slug = lcfirst(str_replace(' ', '-', $selected_medal_name));

$selected_medal_img = SCX_URL . "assets/medals/" . $medal_slug . "-" . intval($selected_medal_star) . ".png";

}
?>
<div class="scx-wrapper">


	<?php if (!empty($gallery)): ?>
<div class="scx-pro-gallery">

    <!-- MAIN SLIDER -->
    <div class="scx-pro-main">

        <?php foreach ($gallery as $i => $att_id):
            $full = wp_get_attachment_image_url($att_id, 'full');
            $large = wp_get_attachment_image_url($att_id, 'large');
            if (!$full) continue;
        ?>
        <div class="scx-pro-slide <?= $i===0 ? 'active':'' ?>">
            <img 
                src="<?= esc_url($large ?: $full) ?>"
                data-full="<?= esc_url($full) ?>"
                loading="lazy"
            >
        </div>
        <?php endforeach; ?>

        <button class="scx-pro-prev">‹</button>
        <button class="scx-pro-next">›</button>

    </div>

    <!-- THUMBNAILS -->
    <div class="scx-pro-thumbs">
        <?php foreach ($gallery as $i => $att_id):
            $thumb = wp_get_attachment_image_url($att_id, 'thumbnail');
            if (!$thumb) continue;
        ?>
        <img 
            src="<?= esc_url($thumb) ?>"
            class="scx-thumb <?= $i===0?'active':'' ?>"
            data-index="<?= $i ?>"
        >
        <?php endforeach; ?>
    </div>

</div>

<!-- LIGHTBOX -->
<div class="scx-pro-lightbox">
    <span class="scx-lightbox-close">×</span>
    <img class="scx-lightbox-img">
</div>

<?php endif; ?>

    <!-- HEADER -->
    <div class="scx-header-card">

        <div class="scx-avatar-wrap">
            <img src="<?= esc_url($steam_avatar) ?>" alt="<?= esc_attr($steam_name) ?>">
        </div>

        <div class="scx-header-info">

            <h2><?= esc_html($steam_name) ?></h2>

            <div class="scx-badges">
                <span class="scx-badge pro">PRO</span>
                <span class="scx-steam-level">Lv <?= intval($steam_level) ?></span>
            </div>

            <?php if ($steam_profile_url): ?>
                <a href="<?= esc_url($steam_profile_url) ?>" target="_blank" class="scx-profile-link">
                    Steam Profile
                </a>
            <?php endif; ?>

        </div>

    </div>

    <!-- STATS -->
    <div class="scx-stats-grid">

        <!-- BAN MASTER BOX -->
        <div class="scx-ban-box <?= $anyBan ? 'banned' : 'clean' ?>">

            <div class="scx-ban-status">
                STATUS : <?= $anyBan ? 'BANNED' : 'CLEAN' ?>
            </div>

            <div class="scx-ban-grid">

                <div class="scx-ban-item <?= $communityBan ? 'bad' : 'good' ?>">
                    Community Ban
                </div>

                <div class="scx-ban-item <?= $tradeBan ? 'bad' : 'good' ?>">
                    Trade / Market Ban
                </div>

                <div class="scx-ban-item <?= $gameBan ? 'bad' : 'good' ?>">
                    Game Ban
                </div>

                <div class="scx-ban-item <?= $vacBan ? 'bad' : 'good' ?>">
                    VAC Ban
                </div>

            </div>

        </div>

        <!-- RIGHT STATS -->
        <div class="scx-stat blue">
            <span>Games Owned</span>
            <strong><?= esc_html($games_owned !== '' ? $games_owned : count($games)) ?></strong>
        </div>

        <div class="scx-stat purple">
            <span>Friends</span>
            <strong><?= esc_html($friends) ?></strong>
        </div>

        <div class="scx-stat teal">
            <span>Account Created</span>
            <strong><?= esc_html($account_created) ?></strong>
        </div>

        <div class="scx-stat gold">
            <span>Region</span>
            <strong><?= esc_html($region) ?></strong>
        </div>

    </div>

   
    <!-- GAMES -->
    <h3 class="scx-section-title">Game Library</h3>

    <div class="scx-games-grid">
        <?php foreach ($games as $game):
            // try to get numeric hours
            $playtime_hours = 0;
            if (!empty($game['hours_raw'])) {
                $playtime_hours = intval($game['hours_raw']);
            } elseif (!empty($game['hours'])) {
                preg_match_all('/\d+/', $game['hours'], $m);
                $playtime_hours = intval($m[0][0] ?? 0);
            } elseif (!empty($game['playtime_forever'])) {
                $playtime_hours = floor(intval($game['playtime_forever']) / 60);
            }
            $appid = $game['appid'] ?? 0;
            $img = !empty($game['image']) ? $game['image'] : ($appid ? "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appid}/header.jpg" : '');
            $price_text = $game['price'] ?? ($game['price_overview']['final_formatted'] ?? '');
            $is_free = stripos($price_text, 'free') !== false;
        ?>
            <div class="scx-game-card">

                <?php if ($img): ?>
                    <img src="<?= esc_url($img) ?>" alt="<?= esc_attr($game['name'] ?? '') ?>">
                <?php endif; ?>

                <div class="scx-game-info">
                    <div class="scx-game-title"><?= esc_html($game['name'] ?? '') ?></div>
                    <div class="scx-game-meta">
                        <span class="scx-game-hours"><?= esc_html($playtime_hours) ?> ساعت </span>
                        <?php if ($is_free): ?>
                            <span class="scx-game-free">Free</span>
                        <?php elseif (!empty($price_text)): ?>
                            <span class="scx-game-price"><?= esc_html($price_text) ?></span>
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
            <span>Dota 2 Info</span>
            <span class="scx-dota-toggle">+</span>
        </div>

        <div class="scx-dota-content" style="display:block;">

            <!-- Show only the selected medal (single) -->
            <div class="scx-medal-card">
                <div class="scx-card-content">
                    <div class="scx-medals">
                        <?php if ($selected_medal_name && $selected_medal_star && $selected_medal_img): 
                            $medal_class = strtolower(str_replace(' ', '-', $selected_medal_name));
                        ?>
                            <div class="scx-medal <?= esc_attr($medal_class) ?> active">
                                <img src="<?= esc_url($selected_medal_img) ?>" alt="<?= esc_attr($dota_medal) ?>">
                                <span class="scx-medal-title"><?= esc_html($selected_medal_name . ' ' . $selected_medal_star) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="scx-medal" style="opacity:.6">
                                <img src="<?= esc_url(SCX_URL . 'assets/medals/herald-1.png') ?>" alt="No medal">
                                <span class="scx-medal-title">No Medal</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="dota_medal" value="<?= esc_attr($dota_medal) ?>">
                </div>
            </div>

            <div class="scx-dota-grid">

     <!-- MMR -->
<div class="scx-dota-field blue">
    <label>MMR</label>
    <div class="scx-output">
        <?= esc_html(number_format($dota_mmr)) ?>
    </div>
</div>

<!-- BEHAVIOR -->
<div class="scx-dota-field teal">
    <label>Behavior Score</label>
    <div class="scx-output">
        <?= esc_html(number_format($dota_behavior)) ?>
    </div>
</div>

<!-- LEVEL -->
<div class="scx-dota-field gold">
    <label>Dota Level</label>
    <div class="scx-output">
        <?= esc_html(number_format($dota_level)) ?>
    </div>
</div>

<!-- SHARDS -->
<div class="scx-dota-field shards">
    <label>Shards</label>
    <div class="scx-output">
        <?= esc_html(number_format($dota_shards)) ?>
    </div>
</div>




                </div>

            </div>

        </div>

    </div>

    <!-- ================= ACCOUNT DETAILS (read-only form layout) ================= -->
    <div class="scx-account-form" style="margin-top:30px;">
        <div class="scx-form-row">

            <!-- PRICE -->
            <div class="scx-form-field price">
                <label>قیمت</label>
                <div class="scx-input">
                    <input type="text" readonly value="<?= esc_attr($account_price) ?>">
                </div>
            </div>

            <!-- MARKET STATUS -->
            <div class="scx-form-field market">
                <label>وضعیت مارکت استیم</label>
                <div class="scx-select scx-form-select">
                    <div class="scx-select-trigger">
                        <?= $market_status === 'active' ? 'فعال' : ($market_status === 'inactive' ? 'غیر فعال' : '—') ?>
                    </div>
                    <input type="hidden" name="market_status" value="<?= esc_attr($market_status) ?>">
                </div>
            </div>

            <!-- MAIN EMAIL -->
            <div class="scx-form-field email">
                <label>ایمیل اوریجینال</label>
                <div class="scx-select scx-form-select">
                    <div class="scx-select-trigger">
                        <?= $main_email === 'yes' ? 'بله' : ($main_email === 'no' ? 'خیر' : '—') ?>
                    </div>
                    <input type="hidden" name="main_email" value="<?= esc_attr($main_email) ?>">
                </div>
            </div>

        </div>

        <!-- DESCRIPTION -->
        <div class="scx-form-row full">
            <div class="scx-form-field">
                <label>توضیحات</label>
                <div class="scx-input">
                    <textarea readonly rows="6"><?= esc_textarea($account_description) ?></textarea>
                </div>
            </div>
        </div>

        <!-- NOTE: upload/submit removed on single product view -->

    </div>

</div>
<?php get_footer(); ?>