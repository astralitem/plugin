<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arknight_EDD_Account_Form_Admin {
	const OPTION_CHARACTER_IMAGES = 'arkn_character_image_items';
	const OPTION_WEAPON_IMAGES    = 'arkn_weapon_image_items';

	public function register_settings_page() {
		add_options_page(
			__( 'Arknight Account Form', 'arknight-edd-account-form' ),
			__( 'Arknight Account Form', 'arknight-edd-account-form' ),
			'manage_options',
			'arkn-account-form-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'arkn_account_form_settings_group',
			self::OPTION_CHARACTER_IMAGES,
			array( $this, 'sanitize_image_items_option' )
		);

		register_setting(
			'arkn_account_form_settings_group',
			self::OPTION_WEAPON_IMAGES,
			array( $this, 'sanitize_image_items_option' )
		);
	}

	/**
	 * @param mixed $value Option value.
	 * @return array<int, array{name:string,file:string}>
	 */
	public function sanitize_image_items_option( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$cleaned = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$name = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
			$file = isset( $row['file'] ) ? sanitize_file_name( wp_unslash( $row['file'] ) ) : '';

			if ( '' === $name || '' === $file ) {
				continue;
			}

			$cleaned[] = array(
				'name' => $name,
				'file' => $file,
			);
		}

		return $cleaned;
	}

	/**
	 * @return array<int, array{name:string,file:string}>
	 */
	public function get_character_images() {
		$items = get_option( self::OPTION_CHARACTER_IMAGES, array() );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * @return array<int, array{name:string,file:string}>
	 */
	public function get_weapon_images() {
		$items = get_option( self::OPTION_WEAPON_IMAGES, array() );
		return is_array( $items ) ? $items : array();
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$character_images = $this->get_character_images();
		$weapon_images    = $this->get_weapon_images();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Arknight Account Form Settings', 'arknight-edd-account-form' ); ?></h1>
			<p>
				<?php esc_html_e( 'عکس‌ها را دستی در مسیر زیر آپلود کنید (نه از داشبورد وردپرس) و در جدول فقط نام فایل را بنویسید.', 'arknight-edd-account-form' ); ?>
			</p>
			<code><?php echo esc_html( ARKN_EDD_IMG_SERVER_PATH ); ?></code>
			<p><?php esc_html_e( 'مثال نام فایل: texalter.png', 'arknight-edd-account-form' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'arkn_account_form_settings_group' ); ?>

				<?php $this->render_image_table( self::OPTION_CHARACTER_IMAGES, $character_images, __( '6★ Characters', 'arknight-edd-account-form' ) ); ?>
				<?php $this->render_image_table( self::OPTION_WEAPON_IMAGES, $weapon_images, __( '6★ Weapons', 'arknight-edd-account-form' ) ); ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param string                                  $option_name Option name.
	 * @param array<int, array{name:string,file:string}> $items Items list.
	 * @param string                                  $title Section title.
	 */
	private function render_image_table( $option_name, $items, $title ) {
		?>
		<h2 style="margin-top:24px;"><?php echo esc_html( $title ); ?></h2>
		<table class="widefat striped">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'arknight-edd-account-form' ); ?></th>
				<th><?php esc_html_e( 'File Name (from assets/img)', 'arknight-edd-account-form' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php for ( $i = 0; $i < 30; $i++ ) : ?>
				<tr>
					<td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name ); ?>[<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( isset( $items[ $i ]['name'] ) ? $items[ $i ]['name'] : '' ); ?>" /></td>
					<td><input type="text" class="regular-text" name="<?php echo esc_attr( $option_name ); ?>[<?php echo esc_attr( $i ); ?>][file]" value="<?php echo esc_attr( isset( $items[ $i ]['file'] ) ? $items[ $i ]['file'] : '' ); ?>" placeholder="example.png" /></td>
				</tr>
			<?php endfor; ?>
			</tbody>
		</table>
		<?php
	}
}
