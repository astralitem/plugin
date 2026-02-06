<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_scx_submit_account', 'scx_handle_account_submission');
add_action('wp_ajax_nopriv_scx_submit_account', 'scx_handle_account_submission');

function scx_handle_account_submission() {

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'scx_submit_account')) {
        wp_send_json_error('درخواست نامعتبر است.');
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('برای ارسال باید وارد شوید.');
    }

    // Create EDD product
    $product_id = wp_insert_post([
    'post_type'    => 'download',
    'post_title'   => sanitize_text_field($_POST['steam_name'] ?? 'Steam Account'),
    'post_content' => sanitize_textarea_field($_POST['account_description'] ?? ''),
    'post_status'  => 'publish',
    'post_author'  => get_current_user_id(),
]);


    if (!$product_id || is_wp_error($product_id)) {
        wp_send_json_error('خطا در ایجاد محصول.');
    }

    // Set category "steam account"
    wp_set_object_terms($product_id, 'steam account', 'download_category');

    // Save meta fields
    scx_save_edd_meta($product_id, $_POST);


	$steam_id = get_user_meta(get_current_user_id(), 'steam_id', true);
if ($steam_id) {
    $level = scx_get_steam_level($steam_id);
    update_post_meta($product_id, 'scx_steam_level', intval($level));
}

    // Handle image uploads
    if (!empty($_FILES['account_images'])) {
        scx_handle_account_images($product_id, $_FILES['account_images']);
    }

    wp_send_json_success(['product_id' => $product_id]);
}
