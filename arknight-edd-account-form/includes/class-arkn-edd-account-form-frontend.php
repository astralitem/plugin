<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arknight_EDD_Account_Form_Frontend {
	const NONCE_ACTION = 'arkn_account_submit';
	const DOWNLOAD_CATEGORY_TAXONOMY = 'download_category';
	const DOWNLOAD_CATEGORY_SLUG     = 'arknight-endfield-account';
	const DOWNLOAD_CATEGORY_NAME     = 'Arknight Endfield Account';
	const MAX_UPLOAD_IMAGES         = 10;
	const UPLOADED_IMAGE_META_KEY   = 'arkn_uploaded_image_ids';
	/**
	 * @var Arknight_EDD_Account_Form_Admin
	 */
	private $admin;

	/**
	 * @param Arknight_EDD_Account_Form_Admin $admin Admin dependency.
	 */
	public function __construct( Arknight_EDD_Account_Form_Admin $admin ) {
		$this->admin = $admin;
	}

	public function register_shortcode() {
		add_shortcode( 'arknight_account_form', array( $this, 'render_form_shortcode' ) );
	}

	public function register_assets() {
		wp_register_style( 'arkn-account-form-style', ARKN_EDD_FORM_URL . 'assets/css/style.css', array(), ARKN_EDD_FORM_VERSION );
		wp_register_script( 'arkn-account-form-script', ARKN_EDD_FORM_URL . 'assets/js/form.js', array(), ARKN_EDD_FORM_VERSION, true );
	}

	/**
	 * @return string
	 */
	public function render_form_shortcode() {
		wp_enqueue_style( 'arkn-account-form-style' );
		wp_enqueue_script( 'arkn-account-form-script' );

		if ( ! post_type_exists( 'download' ) ) {
			return '<p class="arkn-message arkn-error">' . esc_html__( 'Easy Digital Downloads فعال نیست. لطفاً EDD را نصب و فعال کنید.', 'arknight-edd-account-form' ) . '</p>';
		}

		$success = isset( $_GET['arkn_submitted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['arkn_submitted'] ) );
		$server_options = array(
			'global' => 'Global',
			'Japan'     => 'Japan',
			'China'     => 'China',
			'Korea'     => 'Korea',
			'Taiwan'     => 'Taiwan',
		);

		ob_start();
		?>
		<div class="arkn-wrap">
			<?php if ( $success ) : ?>
				<div class="arkn-message arkn-success" role="alert">
					<?php esc_html_e( '✅ آگهی با موفقیت ارسال شد و پس از بررسی منتشر می‌شود.', 'arknight-edd-account-form' ); ?>
				</div>
			<?php endif; ?>
			<form class="arkn-account-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" novalidate>
				<input type="hidden" name="action" value="arkn_submit_account" />
				<?php wp_nonce_field( self::NONCE_ACTION, 'arkn_nonce' ); ?>

				<div class="arkn-grid">
					<?php $this->render_number_field( 'authority_level', 'آتوریتی لول', 1, 60 ); ?>
					<?php $this->render_custom_select_field( 'server', 'سرور', $server_options ); ?>
					<?php $this->render_number_field( 'character_banner_pity', 'پیتی بنر کاراکتر', 1, 90 ); ?>
					<?php $this->render_number_field( 'weapon_banner_pity', 'پیتی بنر سلاح', 1, 80 ); ?>
					<?php $this->render_number_field( 'standard_banner_pity', 'پیتی بنر استاندارد', 1, 80 ); ?>
					<?php $this->render_number_field( 'remaining_wish', 'مقدار ویش مانده', 1, 300, '/300' ); ?>
					<?php $this->render_number_field( 'orundum', 'تعداد اروبریل', 1, 90 ); ?>
					<?php $this->render_number_field( 'originium', 'تعداد اریجئومتری', 1, 100 ); ?>
					<?php $this->render_number_field( 'arsenal_ticket', 'تعداد ارسنال تیکت', 1, 100 ); ?>

					<div class="arkn-field">
						<label for="character_potential"><?php esc_html_e( 'پوتنشال کاراکترها', 'arknight-edd-account-form' ); ?></label>
						<input id="character_potential" name="character_potential" type="text" placeholder="P5" required data-error="پوتنشال کاراکتر را وارد کنید." />
					</div>

					<?php $this->render_number_field( 'price', 'قیمت', 1, 99999999 ); ?>
				</div>

				<div class="arkn-selector-group">
					<h3><?php esc_html_e( 'کاراکتر 6 ستاره', 'arknight-edd-account-form' ); ?></h3>
					<?php echo $this->render_image_checkbox_list( 'selected_characters', $this->admin->get_character_images() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="arkn-selector-group">
					<h3><?php esc_html_e( 'سلاح 6 ستاره', 'arknight-edd-account-form' ); ?></h3>
					<?php echo $this->render_image_checkbox_list( 'selected_weapons', $this->admin->get_weapon_images() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="arkn-field arkn-field-full">
					<label for="description"><?php esc_html_e( 'توضیحات', 'arknight-edd-account-form' ); ?></label>
					<textarea id="description" name="description" rows="5" required data-error="لطفاً توضیحات را کامل کنید."></textarea>
				</div>
								<div class="arkn-field arkn-field-full">
					<input id="arkn_images" class="arkn-image-input" name="arkn_images[]" type="file" accept="image/*" multiple data-max-images="<?php echo esc_attr( self::MAX_UPLOAD_IMAGES ); ?>" />
					<div class="arkn-upload-preview" data-upload-preview></div>
				</div>

				<div class="arkn-form-feedback" role="alert" aria-live="polite"></div>
								<div class="arkn-form-actions">
					<label for="arkn_images" class="arkn-upload-button"><?php esc_html_e( 'آپلود تصاویر (حداکثر 10)', 'arknight-edd-account-form' ); ?></label>
					<button type="submit" class="arkn-submit"><?php esc_html_e( 'ارسال آکانت', 'arknight-edd-account-form' ); ?></button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_custom_select_field( $name, $label, $options ) {
		?>
		<div class="arkn-field">
			<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="arkn-custom-select" data-select-wrap>
				<button type="button" class="arkn-custom-select__trigger" data-select-trigger aria-expanded="false">
					<span data-select-label><?php esc_html_e( 'انتخاب کنید', 'arknight-edd-account-form' ); ?></span>
				</button>
				<ul class="arkn-custom-select__menu" data-select-menu hidden>
					<?php foreach ( $options as $value => $option_label ) : ?>
						<li>
							<button type="button" data-option="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $option_label ); ?></button>
						</li>
					<?php endforeach; ?>
				</ul>
				<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" required data-error="سرور را انتخاب کنید.">
					<option value=""><?php esc_html_e( 'انتخاب کنید', 'arknight-edd-account-form' ); ?></option>
					<?php foreach ( $options as $value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php
	}

	private function render_number_field( $name, $label, $min, $max, $suffix = '' ) {
		$error = sprintf( 'فیلد %s باید بین %d تا %d باشد.', $label, $min, $max );
		?>
		<div class="arkn-field">
			<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="arkn-number-wrap">
				<input id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" type="number" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" required data-error="<?php echo esc_attr( $error ); ?>" />
				<?php if ( '' !== $suffix ) : ?>
					<span><?php echo esc_html( $suffix ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function render_image_checkbox_list( $name, $items ) {
		if ( empty( $items ) ) {
			return '<p class="arkn-empty">' . esc_html__( 'هنوز تصویری ثبت نشده است. نام فایل‌ها را در تنظیمات وارد کنید.', 'arknight-edd-account-form' ) . '</p>';
		}

		ob_start();
		echo '<div class="arkn-image-grid">';

		foreach ( $items as $index => $item ) {
			if ( empty( $item['name'] ) || empty( $item['file'] ) ) {
				continue;
			}

			$image_url = esc_url( ARKN_EDD_IMG_BASE_URL . ltrim( $item['file'], '/' ) );
			$input_id  = sanitize_html_class( $name . '-' . $index . '-' . $item['name'] );
			?>
			<label class="arkn-card" for="<?php echo esc_attr( $input_id ); ?>">
				<input id="<?php echo esc_attr( $input_id ); ?>" class="arkn-card__input" type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $item['name'] ); ?>" />
				<span class="arkn-card__tick" aria-hidden="true">✓</span>
				<img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" loading="lazy" />
				<span class="arkn-card__name"><?php echo esc_html( $item['name'] ); ?></span>
			</label>
			<?php
		}

		echo '</div>';
		return ob_get_clean();
	}

	public function handle_submission() {
		if ( ! isset( $_POST['arkn_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arkn_nonce'] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'درخواست نامعتبر است.', 'arknight-edd-account-form' ) );
		}

		if ( ! post_type_exists( 'download' ) ) {
			wp_die( esc_html__( 'Easy Digital Downloads فعال نیست.', 'arknight-edd-account-form' ) );
		}

		$server_whitelist = array( 'global', 'Japan', 'China', 'Korea', 'Taiwan' );
		$server           = isset( $_POST['server'] ) ? sanitize_text_field( wp_unslash( $_POST['server'] ) ) : '';
		if ( ! in_array( $server, $server_whitelist, true ) ) {
			wp_die( esc_html__( 'سرور انتخابی معتبر نیست.', 'arknight-edd-account-form' ) );
		}

		$allowed_character_names = $this->extract_allowed_item_names( $this->admin->get_character_images() );
		$allowed_weapon_names    = $this->extract_allowed_item_names( $this->admin->get_weapon_images() );

		$data = array(
			'authority_level'       => $this->sanitize_bounded_number( 'authority_level', 1, 60 ),
			'server'                => $server,
			'character_banner_pity' => $this->sanitize_bounded_number( 'character_banner_pity', 1, 90 ),
			'weapon_banner_pity'    => $this->sanitize_bounded_number( 'weapon_banner_pity', 1, 80 ),
			'standard_banner_pity'  => $this->sanitize_bounded_number( 'standard_banner_pity', 1, 80 ),
			'remaining_wish'        => $this->sanitize_bounded_number( 'remaining_wish', 1, 300 ) . '/300',
			'orundum'               => $this->sanitize_bounded_number( 'orundum', 1, 90 ),
			'originium'             => $this->sanitize_bounded_number( 'originium', 1, 100 ),
			'arsenal_ticket'        => $this->sanitize_bounded_number( 'arsenal_ticket', 1, 100 ),
			'character_potential'   => isset( $_POST['character_potential'] ) ? sanitize_text_field( wp_unslash( $_POST['character_potential'] ) ) : '',
			'description'           => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'price'                 => $this->sanitize_bounded_number( 'price', 1, 99999999 ),
			'selected_characters'   => $this->sanitize_text_list( isset( $_POST['selected_characters'] ) ? wp_unslash( $_POST['selected_characters'] ) : array(), $allowed_character_names ),
			'selected_weapons'      => $this->sanitize_text_list( isset( $_POST['selected_weapons'] ) ? wp_unslash( $_POST['selected_weapons'] ) : array(), $allowed_weapon_names ),
		);

		if ( '' === $data['character_potential'] || '' === $data['description'] ) {
			wp_die( esc_html__( 'لطفاً تمام فیلدهای الزامی را تکمیل کنید.', 'arknight-edd-account-form' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => sprintf( 'Arknight Account - %s - Lv %d', strtoupper( $data['server'] ), (int) $data['authority_level'] ),
				'post_type'    => 'download',
				'post_status'  => 'pending',
				'post_content' => $data['description'],
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html__( 'خطا در ثبت آگهی. دوباره تلاش کنید.', 'arknight-edd-account-form' ) );
		}

		$this->assign_download_category( $post_id );

		update_post_meta( $post_id, 'edd_price', $data['price'] );
		foreach ( $data as $meta_key => $meta_value ) {
			if ( 'price' === $meta_key ) {
				continue;
			}

			if ( is_array( $meta_value ) && empty( $meta_value ) ) {
				continue;
			}

			update_post_meta( $post_id, 'arkn_' . $meta_key, is_array( $meta_value ) ? implode( ', ', $meta_value ) : $meta_value );
		}

		$this->handle_uploaded_images( $post_id );

		wp_safe_redirect( add_query_arg( 'arkn_submitted', '1', wp_get_referer() ? wp_get_referer() : home_url() ) );
		exit;
	}

			private function assign_download_category( $post_id ) {
		if ( ! taxonomy_exists( self::DOWNLOAD_CATEGORY_TAXONOMY ) ) {
			return;
		}

		$term = get_term_by( 'slug', self::DOWNLOAD_CATEGORY_SLUG, self::DOWNLOAD_CATEGORY_TAXONOMY );

		if ( ! $term || is_wp_error( $term ) ) {
			$term_result = wp_insert_term(
				self::DOWNLOAD_CATEGORY_NAME,
				self::DOWNLOAD_CATEGORY_TAXONOMY,
				array(
					'slug' => self::DOWNLOAD_CATEGORY_SLUG,
				)
			);

			if ( is_wp_error( $term_result ) || empty( $term_result['term_id'] ) ) {
				return;
			}

			$term_id = (int) $term_result['term_id'];
		} else {
			$term_id = (int) $term->term_id;
		}

		if ( $term_id > 0 ) {
			wp_set_object_terms( $post_id, array( $term_id ), self::DOWNLOAD_CATEGORY_TAXONOMY, false );
		}
	}


	private function handle_uploaded_images( $post_id ) {
		if ( empty( $_FILES['arkn_images'] ) || empty( $_FILES['arkn_images']['name'] ) ) {
			return;
		}

		$names = isset( $_FILES['arkn_images']['name'] ) && is_array( $_FILES['arkn_images']['name'] ) ? $_FILES['arkn_images']['name'] : array();
		if ( count( $names ) > self::MAX_UPLOAD_IMAGES ) {
			wp_die( esc_html__( 'حداکثر 10 تصویر قابل آپلود است.', 'arknight-edd-account-form' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$uploaded_image_ids = array();
		$files              = $_FILES['arkn_images'];

		foreach ( $names as $index => $name ) {
			if ( empty( $name ) ) {
				continue;
			}

			if ( empty( $files['type'][ $index ] ) || 0 !== strpos( (string) $files['type'][ $index ], 'image/' ) ) {
				continue;
			}

			$_FILES['arkn_single_upload'] = array(
				'name'     => $files['name'][ $index ],
				'type'     => $files['type'][ $index ],
				'tmp_name' => $files['tmp_name'][ $index ],
				'error'    => $files['error'][ $index ],
				'size'     => $files['size'][ $index ],
			);

			$attachment_id = media_handle_upload( 'arkn_single_upload', $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				$uploaded_image_ids[] = (int) $attachment_id;
			}
		}

		unset( $_FILES['arkn_single_upload'] );

		if ( ! empty( $uploaded_image_ids ) ) {
			update_post_meta( $post_id, self::UPLOADED_IMAGE_META_KEY, implode( ',', $uploaded_image_ids ) );
		}
	}

	private function sanitize_bounded_number( $field_name, $min, $max ) {
		$value = isset( $_POST[ $field_name ] ) ? absint( wp_unslash( $_POST[ $field_name ] ) ) : 0;
		if ( $value < $min || $value > $max ) {
			wp_die( esc_html__( 'مقدار عددی خارج از بازه مجاز است.', 'arknight-edd-account-form' ) );
		}
		return $value;
	}

	private function sanitize_text_list( $values, $allowed = array() ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$clean = array();
		foreach ( $values as $value ) {
			$text = sanitize_text_field( $value );
			if ( '' === $text ) {
				continue;
			}

			if ( ! empty( $allowed ) && ! in_array( $text, $allowed, true ) ) {
				continue;
			}

			$clean[] = $text;
		}

		return array_values( array_unique( $clean ) );
	}

	private function extract_allowed_item_names( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$names = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['name'] ) ) {
				continue;
			}
			$name = sanitize_text_field( $item['name'] );
			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		return array_values( array_unique( $names ) );
	}

}
