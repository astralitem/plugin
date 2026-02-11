<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin settings for the plugin: Settings -> Steam Connect Pro
 */
add_action( 'admin_menu', function () {
    add_options_page(
        __( 'Steam Connect Pro', 'steam-connect-pro' ),
        __( 'Steam Connect Pro', 'steam-connect-pro' ),
        'manage_options',
        'scp-settings',
        'scp_settings_page'
    );
} );

add_action( 'admin_init', function () {
    register_setting( 'scp_settings_group', 'scp_api_key', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ] );

    register_setting( 'scp_settings_group', 'scp_cache_profile_duration', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 6,
    ] );
} );

function scp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['scp_settings_submitted'] ) && check_admin_referer( 'scp_settings_nonce', 'scp_settings_nonce_field' ) ) {
        // Settings are registered via register_setting - WordPress handles saving
        echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'steam-connect-pro' ) . '</p></div>';
    }

    $api_key = get_option( 'scp_api_key', '' );
    $profile_cache_hours = get_option( 'scp_cache_profile_duration', 6 );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Steam Connect Pro Settings', 'steam-connect-pro' ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'scp_settings_group' );
            do_settings_sections( 'scp_settings_group' );
            wp_nonce_field( 'scp_settings_nonce', 'scp_settings_nonce_field' );
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="scp_api_key"><?php esc_html_e( 'Steam Web API Key', 'steam-connect-pro' ); ?></label></th>
                    <td>
                        <input name="scp_api_key" type="password" id="scp_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Your Steam Web API key (kept secret). Required for fetching Steam data.', 'steam-connect-pro' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="scp_cache_profile_duration"><?php esc_html_e( 'Profile Cache (hours)', 'steam-connect-pro' ); ?></label></th>
                    <td>
                        <input name="scp_cache_profile_duration" type="number" id="scp_cache_profile_duration" value="<?php echo esc_attr( $profile_cache_hours ); ?>" min="1" max="168">
                        <p class="description"><?php esc_html_e( 'How many hours to cache Steam profile data. Default 6 hours.', 'steam-connect-pro' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}