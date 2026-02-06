<?php
// name=steam-companion-extended/includes/dashboard-shortcode.php
if (!defined('ABSPATH')) exit;

add_shortcode('scx_user_listings', function($atts) {

    if (!is_user_logged_in()) {
        return '<p>لطفا وارد شوید</p>';
    }

    $user_id = get_current_user_id();

    $args = [
        'post_type'      => 'download',
        'author'         => $user_id,
        'posts_per_page' => 20,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $posts = get_posts($args);

    if (empty($posts)) {
        return '<p>هیچ آگهی‌ای ثبت نکرده‌اید.</p>';
    }

    ob_start();
    echo '<div class="scx-user-listings">';

    foreach ($posts as $p) {
        // Only display posts that appear to be plugin-created (optional)
        // You can add meta filtering here if required.
        scx_render_product_card($p->ID, ['show_status' => true, 'classes' => 'scx-dashboard-card']);
    }

    echo '</div>';
    return ob_get_clean();

});