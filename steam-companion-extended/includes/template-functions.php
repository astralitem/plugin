<?php
// name=steam-companion-extended/includes/template-functions.php
if (!defined('ABSPATH')) exit;

if (!function_exists('scx_get_listing_status')) {
    /**
     * Get listing status for a post
     * Possible values: 'under_review', 'approved', 'rejected'
     * If meta not set, default to 'under_review'
     */
    function scx_get_listing_status($post_id) {
        $status = get_post_meta($post_id, 'scx_listing_status', true);
        if (empty($status)) return 'under_review';
        return in_array($status, ['under_review','approved','rejected']) ? $status : 'under_review';
    }
}

if (!function_exists('scx_render_product_card')) {
    /**
     * Render a product card for a given post_id.
     *
     * $args:
     *  - show_status (bool) show the listing status badge (for dashboard)
     *  - classes (string) additional classes
     */
    function scx_render_product_card($post_id, $args = []) {
        $args = wp_parse_args($args, [
            'show_status' => false,
            'classes'     => '',
        ]);

        // Basic post / permalink
        $post = get_post($post_id);
        if (!$post) return;

        $permalink = get_permalink($post_id);

        // Steam / metadata stored by plugin
        $steam_avatar = get_post_meta($post_id, 'scx_steam_avatar', true);
        $steam_name = get_post_meta($post_id, 'scx_steam_name', true) ?: get_the_title($post_id);
        $steam_level = get_post_meta($post_id, 'scx_steam_level', true);
		// Steam level class (1 - 200)
$level_class = 'scx-level-basic';

if (is_numeric($steam_level)) {
    $steam_level = intval($steam_level);

    if ($steam_level >= 11 && $steam_level <= 30) {
        $level_class = 'scx-level-bronze';
    } elseif ($steam_level >= 31 && $steam_level <= 60) {
        $level_class = 'scx-level-silver';
    } elseif ($steam_level >= 61 && $steam_level <= 100) {
        $level_class = 'scx-level-gold';
    } elseif ($steam_level >= 101) {
        $level_class = 'scx-level-platinum';
    }
}

        $steam_profile_url = get_post_meta($post_id, 'scx_steam_profile_url', true);

        // Bans
        $communityBan = get_post_meta($post_id, 'scx_ban_community', true) === '1';
        $tradeBan = get_post_meta($post_id, 'scx_ban_trade', true) === '1';
        $gameBan = get_post_meta($post_id, 'scx_ban_game', true) === '1';
        $vacBan = get_post_meta($post_id, 'scx_ban_vac', true) === '1';

        $ban_texts = [];
        if ($gameBan) $ban_texts[] = 'Game Ban';
        if ($vacBan) $ban_texts[] = 'VAC Ban';
        if ($communityBan) $ban_texts[] = 'Community Ban';
        if ($tradeBan) $ban_texts[] = 'Trade/Market Ban';
        if (empty($ban_texts)) $ban_texts[] = 'Clean';
        $ban_display = implode(' · ', $ban_texts);

        // Stats
        $games_owned = get_post_meta($post_id, 'scx_games_owned', true);
        $games_json = get_post_meta($post_id, 'scx_games_library', true);
        $games = json_decode($games_json, true);
        if (!is_array($games)) $games = [];
        if ($games_owned === '') {
            $games_owned_display = count($games);
        } else {
            $games_owned_display = intval($games_owned);
        }

        $friends = intval(get_post_meta($post_id, 'scx_friends', true));
        $account_created = get_post_meta($post_id, 'scx_account_created', true);
        if (empty($account_created)) {
            $account_created = get_the_date('', $post_id);
        }

        $region = get_post_meta($post_id, 'scx_region', true);

        $account_price = get_post_meta($post_id, 'scx_account_price', true);
        $market_status = get_post_meta($post_id, 'scx_market_status', true);
        $main_email = get_post_meta($post_id, 'scx_main_email', true);

        // Fallback avatar: post thumbnail or plugin asset
        if (empty($steam_avatar)) {
            $thumb = get_the_post_thumbnail_url($post_id, 'full');
            if ($thumb) $steam_avatar = $thumb;
            else $steam_avatar = SCX_URL . 'assets/default-avatar.png'; // optional default (may be absent)
        }

        // Listing status (for dashboard)
        $listing_status = $args['show_status'] ? scx_get_listing_status($post_id) : '';

        // Classes
        $outer_classes = trim('scx-product-card ' . $args['classes']);

        // Begin output
        ?>
   <div class="<?php echo esc_attr($outer_classes); ?>" data-post-id="<?php echo intval($post_id); ?>">

    <?php if ($args['show_status']): 
        $status_label = 'درحال بررسی';
        $status_class = 'scx-status-review';
        if ($listing_status === 'approved') {
            $status_label = 'تایید شده';
            $status_class = 'scx-status-approved';
        } elseif ($listing_status === 'rejected') {
            $status_label = 'رد شده';
            $status_class = 'scx-status-rejected';
        }
    ?>
    <div class="scx-listing-status <?php echo esc_attr($status_class); ?>">
        <?php echo esc_html($status_label); ?>
    </div>
    <?php endif; ?>

    <div class="scx-product-card-inner">

        <div class="scx-product-left">
            <div class="scx-card-main">

                <!-- Avatar + Name + Profile -->
                <div class="scx-card-title-row">
                    <div class="scx-avatar-card-wrap">
    <div class="scx-card-avatar-card">
        <img src="<?php echo esc_url($steam_avatar); ?>" alt="<?php echo esc_attr($steam_name); ?>">
    </div>

    <?php if (!empty($steam_level)): ?>
        <div class="scx-steam-level <?php echo esc_attr($level_class); ?>">
    Lv. <?php echo esc_html($steam_level); ?>
</div>
    <?php endif; ?>
</div>
                    <div class="scx-card-title-info">
    <a href="<?php echo esc_url($permalink); ?>" class="scx-card-title" title="<?php echo esc_attr($steam_name); ?>">
        <?php echo esc_html($steam_name); ?>
    </a>
    <?php if (!empty($steam_profile_url)): ?>
        <a class="scx-card-profile-link" href="<?php echo esc_url($steam_profile_url); ?>" target="_blank" rel="noopener noreferrer">Steam profile</a>
    <?php endif; ?>
</div>

                </div>

                <!-- Bans -->
                <div class="scx-card-bans">
                    <?php 
                    $ban_display_list = [];
                    if ($gameBan) $ban_display_list[] = '<span class="scx-ban-item-card scx-ban-red">Game Ban</span>';
                    if ($vacBan) $ban_display_list[] = '<span class="scx-ban-item-card scx-ban-red">VAC Ban</span>';
                    if ($communityBan) $ban_display_list[] = '<span class="scx-ban-item-card scx-ban-red">Community Ban</span>';
                    if ($tradeBan) $ban_display_list[] = '<span class="scx-ban-item-card scx-ban-red">Trade/Market Ban</span>';
                    if (empty($ban_display_list)) $ban_display_list[] = '<span class="scx-ban-item-card scx-ban-green">بدون بن</span>';
                    echo implode(' · ', $ban_display_list);
                    ?>
                </div>

                <!-- Stats -->
                <div class="scx-card-stats">
                    <span>🎮 <?php echo esc_html($games_owned_display); ?> games</span>
                    <span>👥 <?php echo esc_html($friends); ?> friends</span>
                    <span>📅 <?php echo esc_html($account_created); ?></span>
                    <?php if (!empty($region)): ?>
                        <span>🌍 <?php echo esc_html($region); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Price + Meta + Actions -->
        <div class="scx-product-right">
            <div class="scx-product-price">
                <?php if ($account_price !== ''): ?>
                    <?php echo esc_html(number_format(floatval(str_replace([',',' '], '', $account_price)))); ?> تومان
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>

            <!-- Market & Original Email -->
            <div class="scx-product-meta">
                <div>
                    <strong>مارکت استیم</strong>
                    <?php if ($market_status === 'active'): ?>
                        <span class="scx-meta-badge scx-meta-green">فعال</span>
                    <?php elseif ($market_status === 'inactive'): ?>
                        <span class="scx-meta-badge scx-meta-red">غیرفعال</span>
                    <?php else: ?>
                        <span class="scx-meta-badge">—</span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong>ایمیل اوریجینال</strong>
                    <?php if ($main_email === 'yes'): ?>
                        <span class="scx-meta-badge scx-meta-green">هست</span>
                    <?php elseif ($main_email === 'no'): ?>
                        <span class="scx-meta-badge scx-meta-red">نیست</span>
                    <?php else: ?>
                        <span class="scx-meta-badge">—</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="scx-product-actions">
                <a class="scx-btn-view" href="<?php echo esc_url($permalink); ?>">View Account</a>
            </div>
        </div>

    </div>
</div>

        <?php
    }
}

/* ===========================
   Shortcode: scx_listings
   Usage: [scx_listings posts_per_page="12" show_status="1" author="current" market_status="active"]
   =========================== */

if (!function_exists('scx_listings_shortcode')) {
    function scx_listings_shortcode($atts = []) {
        $atts = shortcode_atts([
            'posts_per_page' => 12,
            'author'         => '',      // numeric user ID or "current"
            'show_status'    => 0,
            'market_status'  => '',      // active / inactive
            'orderby'        => 'date',
            'order'          => 'DESC',
            'category'       => '',      // optional download_category term slug
        ], $atts, 'scx_listings');

        $args = [
            'post_type' => 'download',
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        ];

        // Author handling
        if (!empty($atts['author'])) {
            if ($atts['author'] === 'current' && is_user_logged_in()) {
                $args['author'] = get_current_user_id();
            } elseif (is_numeric($atts['author'])) {
                $args['author'] = intval($atts['author']);
            }
        }

        // Taxonomy filter (download_category)
        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'download_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($atts['category']),
                ]
            ];
        }

        // Meta queries
        $meta_query = [];
        if (!empty($atts['market_status'])) {
            $meta_query[] = [
                'key' => 'scx_market_status',
                'value' => sanitize_text_field($atts['market_status']),
                'compare' => '=',
            ];
        }
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        $q = new WP_Query($args);

        if (!$q->have_posts()) {
            return '<div class="scx-listings-empty"><p>هیچ آگهی‌ای یافت نشد.</p></div>';
        }

        ob_start();
        echo '<div class="scx-listings-grid">';
        while ($q->have_posts()) {
            $q->the_post();
            $post_id = get_the_ID();
            scx_render_product_card($post_id, [
                'show_status' => boolval(intval($atts['show_status'])),
                'classes' => 'scx-listing-card',
            ]);
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }
    add_shortcode('scx_listings', 'scx_listings_shortcode');
}