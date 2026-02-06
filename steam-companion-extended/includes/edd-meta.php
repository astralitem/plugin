<?php
if (!defined('ABSPATH')) exit;

function scx_save_edd_meta($product_id, $data) {

    $meta_fields = [
        // Steam Profile
        'steam_avatar',
        'steam_name',
        'steam_level',
        'steam_profile_url',

        // Bans
        'ban_community',
        'ban_trade',
        'ban_game',
        'ban_vac',

        // Stats
        'games_owned',
        'friends',
        'account_created',
        'region',

        // Dota
        'dota_medal',
        'dota_mmr',
        'dota_behavior',
        'dota_level',
        'dota_shards',

        // Market
        'account_price',
        'market_status',
        'main_email',
        'account_description',

        // Games library (JSON)
        'games_library',
    ];

    foreach ($meta_fields as $field) {
        if (isset($data[$field])) {
            update_post_meta($product_id, 'scx_' . $field, sanitize_text_field($data[$field]));
        }
    }

    // Set EDD price
    if (!empty($data['account_price'])) {
        update_post_meta($product_id, 'edd_price', floatval($data['account_price']));
    }
}

function scx_handle_account_images($product_id, $files) {

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $image_ids = [];

    foreach ($files['name'] as $key => $value) {

        if ($files['name'][$key]) {
            $file = [
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error'    => $files['error'][$key],
                'size'     => $files['size'][$key],
            ];

            $_FILES = ['upload_file' => $file];

            $attach_id = media_handle_upload('upload_file', $product_id);

            if (!is_wp_error($attach_id)) {
                $image_ids[] = $attach_id;
            }
        }
    }

    if (!empty($image_ids)) {
        set_post_thumbnail($product_id, $image_ids[0]);
        update_post_meta($product_id, 'scx_gallery', $image_ids);
    }
}
