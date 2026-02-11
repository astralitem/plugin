<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [steam_connected_users]
 * Displays a grid of users who connected their Steam accounts.
 */
function scp_connected_users_shortcode() {
    global $wpdb;

    // Get users who have steam_id meta
    $users = $wpdb->get_results( $wpdb->prepare(
        "SELECT user_id, meta_value AS steam_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
        'steam_id'
    ) );

    if ( ! $users ) {
        return '<p>' . esc_html__( 'No users have connected their Steam account yet.', 'steam-connect-pro' ) . '</p>';
    }

    ob_start();
    ?>
    <div class="scp-users-grid">
        <?php
        foreach ( $users as $user ) :
            $steam_id = sanitize_text_field( $user->steam_id );
            $steam_data = scp_get_steam_user_info( $steam_id );
            if ( ! $steam_data ) {
                continue;
            }

            $steam_level = intval( $steam_data['level'] );
            $level_class = 'scp-level-basic';

            if ( $steam_level >= 11 && $steam_level <= 30 ) {
                $level_class = 'scp-level-bronze';
            } elseif ( $steam_level >= 31 && $steam_level <= 60 ) {
                $level_class = 'scp-level-silver';
            } elseif ( $steam_level >= 61 && $steam_level <= 100 ) {
                $level_class = 'scp-level-gold';
            } elseif ( $steam_level >= 101 ) {
                $level_class = 'scp-level-platinum';
            }
            ?>
            <div class="scp-user-card" data-steamid="<?php echo esc_attr( $steam_data['steamid'] ); ?>">
                <img src="<?php echo esc_url( $steam_data['avatar'] ); ?>" class="scp-avatar" alt="<?php echo esc_attr( $steam_data['name'] ); ?>">
                <div class="scp-user-info">
                    <div class="scp-name"><?php echo esc_html( $steam_data['name'] ); ?></div>

                    <span class="scp-level-badge <?php echo esc_attr( $level_class ); ?>" data-tooltip="<?php esc_attr_e( 'Steam Level', 'steam-connect-pro' ); ?>">
                        <?php printf( esc_html__( 'Lv. %d', 'steam-connect-pro' ), $steam_level ); ?>
                    </span>

                    <div class="scp-status">
                        <span class="scp-online-status <?php echo ( $steam_data['online'] > 0 ? 'online' : 'offline' ); ?>">
                            <?php echo ( $steam_data['online'] > 0 ? esc_html__( 'Online', 'steam-connect-pro' ) : esc_html__( 'Offline', 'steam-connect-pro' ) ); ?>
                        </span>
                    </div>

                    <a href="<?php echo esc_url( $steam_data['profile_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="scp-profile-link">
                        <?php esc_html_e( 'Steam Profile', 'steam-connect-pro' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/steam-user/' . $steam_data['steamid'] ) ); ?>" class="scp-profile-link">
        <?php esc_html_e( 'پروفایل کاربر', 'steam-connect-pro' ); ?>
    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'steam_connected_users', 'scp_connected_users_shortcode' );