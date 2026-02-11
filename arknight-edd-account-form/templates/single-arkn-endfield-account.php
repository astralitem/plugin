<?php
/**
 * Single template for Arknight Endfield Account products.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'arkn_render_meta_value' ) ) {
	/**
	 * @param int    $product_id Product ID.
	 * @param string $meta_key   Meta key.
	 * @return string
	 */
	function arkn_render_meta_value( $product_id, $meta_key ) {
		$value = get_post_meta( $product_id, $meta_key, true );
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return '' !== $value ? $value : '—';
	}
}

if ( ! function_exists( 'arkn_parse_selected_names' ) ) {
	/**
	 * @param string $raw Raw comma-separated names.
	 * @return array<int, string>
	 */
	function arkn_parse_selected_names( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return array();
		}

		$parts = array_map( 'trim', explode( ',', (string) $raw ) );
		$parts = array_filter(
			$parts,
			static function ( $item ) {
				return '' !== $item;
			}
		);

		return array_values( array_unique( $parts ) );
	}
}

if ( ! function_exists( 'arkn_build_name_image_map' ) ) {
	/**
	 * @param array<int, array{name:string,file:string}> $items Configured items.
	 * @return array<string, string>
	 */
	function arkn_build_name_image_map( $items ) {
		$mapped = array();
		if ( ! is_array( $items ) ) {
			return $mapped;
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['name'] ) || empty( $item['file'] ) ) {
				continue;
			}

			$name = sanitize_text_field( $item['name'] );
			$file = sanitize_file_name( $item['file'] );
			if ( '' === $name || '' === $file ) {
				continue;
			}

			$mapped[ $name ] = ARKN_EDD_IMG_BASE_URL . ltrim( $file, '/' );
		}

		return $mapped;
	}
}

if ( ! function_exists( 'arkn_get_uploaded_gallery_items' ) ) {
	/**
	 * @param int $product_id Product ID.
	 * @return array<int, array{thumb:string,full:string,alt:string}>
	 */
	function arkn_get_uploaded_gallery_items( $product_id ) {
		$raw_ids = get_post_meta( $product_id, Arknight_EDD_Account_Form_Frontend::UPLOADED_IMAGE_META_KEY, true );
		if ( ! is_scalar( $raw_ids ) || '' === trim( (string) $raw_ids ) ) {
			return array();
		}

		$ids = array_map( 'absint', array_filter( array_map( 'trim', explode( ',', (string) $raw_ids ) ) ) );
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$items = array();
		foreach ( $ids as $id ) {
			$full  = wp_get_attachment_image_url( $id, 'large' );
			$thumb = wp_get_attachment_image_url( $id, 'medium' );
			if ( ! $full ) {
				continue;
			}

			$items[] = array(
				'thumb' => $thumb ? $thumb : $full,
				'full'  => $full,
				'alt'   => get_post_meta( $id, '_wp_attachment_image_alt', true ) ? get_post_meta( $id, '_wp_attachment_image_alt', true ) : get_the_title( $product_id ),
			);
		}

		return $items;
	}
}

get_header();

while ( have_posts() ) :
	the_post();

	$product_id = get_the_ID();
	$meta_map   = array(
		'arkn_authority_level'       => 'آتوریتی لول',
		'arkn_server'                => 'سرور',
		'arkn_character_banner_pity' => 'پیتی بنر کاراکتر',
		'arkn_weapon_banner_pity'    => 'پیتی بنر سلاح',
		'arkn_standard_banner_pity'  => 'پیتی بنر استاندارد',
		'arkn_remaining_wish'        => 'مقدار ویش مانده',
		'arkn_orundum'               => 'تعداد اروبریل',
		'arkn_originium'             => 'تعداد اریجئومتری',
		'arkn_arsenal_ticket'        => 'تعداد ارسنال تیکت',
		'arkn_character_potential'   => 'پوتنشال کاراکترها',
	);

	$character_names = arkn_parse_selected_names( get_post_meta( $product_id, 'arkn_selected_characters', true ) );
	$weapon_names    = arkn_parse_selected_names( get_post_meta( $product_id, 'arkn_selected_weapons', true ) );

	$character_image_map = arkn_build_name_image_map( get_option( Arknight_EDD_Account_Form_Admin::OPTION_CHARACTER_IMAGES, array() ) );
	$weapon_image_map    = arkn_build_name_image_map( get_option( Arknight_EDD_Account_Form_Admin::OPTION_WEAPON_IMAGES, array() ) );
	$gallery_items       = arkn_get_uploaded_gallery_items( $product_id );
	?>
	<main id="primary" class="arkn-single-account">
		<?php if ( ! empty( $gallery_items ) ) : ?>
			<section class="arkn-single-account__panel">
				<div class="arkn-gallery" data-arkn-gallery>
					<button type="button" class="arkn-gallery__nav arkn-gallery__nav--prev" data-gallery-prev aria-label="Previous image">‹</button>
					<div class="arkn-gallery__stage" data-gallery-stage>
						<?php foreach ( $gallery_items as $index => $image ) : ?>
							<button type="button" class="arkn-gallery__slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-gallery-slide data-index="<?php echo esc_attr( $index ); ?>" data-full="<?php echo esc_url( $image['full'] ); ?>" aria-label="Open image">
								<img src="<?php echo esc_url( $image['full'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy" />
							</button>
						<?php endforeach; ?>
					</div>
					<button type="button" class="arkn-gallery__nav arkn-gallery__nav--next" data-gallery-next aria-label="Next image">›</button>
					<div class="arkn-gallery__thumbs">
						<?php foreach ( $gallery_items as $index => $image ) : ?>
							<button type="button" class="arkn-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-gallery-thumb data-index="<?php echo esc_attr( $index ); ?>" aria-label="Select image">
								<img src="<?php echo esc_url( $image['thumb'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy" />
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="arkn-single-account__panel">
			<h1 class="arkn-single-account__title"><?php echo esc_html( get_the_title() ); ?></h1>
			<div class="arkn-single-account__overview">
				<div class="arkn-single-account__item">
					<span class="arkn-single-account__label">نام محصول</span>
					<span class="arkn-single-account__value"><?php echo esc_html( get_the_title() ); ?></span>
				</div>
				<div class="arkn-single-account__item">
					<span class="arkn-single-account__label">قیمت محصول</span>
					<span class="arkn-single-account__value"><?php echo esc_html( arkn_render_meta_value( $product_id, 'edd_price' ) ); ?></span>
				</div>
				<div class="arkn-single-account__item">
					<span class="arkn-single-account__label">تاریخ ثبت</span>
					<span class="arkn-single-account__value"><?php echo esc_html( get_the_date( 'Y/m/d - H:i', $product_id ) ); ?></span>
				</div>
			</div>
		</section>

		<section class="arkn-single-account__panel">
			<h2>دیتای کامل اکانت</h2>
			<div class="arkn-single-account__grid">
				<?php foreach ( $meta_map as $key => $label ) : ?>
					<div class="arkn-single-account__item">
						<span class="arkn-single-account__label"><?php echo esc_html( $label ); ?></span>
						<span class="arkn-single-account__value"><?php echo esc_html( arkn_render_meta_value( $product_id, $key ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="arkn-single-account__panel">
			<h2>کاراکترهای 6 ستاره</h2>
			<div class="arkn-image-grid">
				<?php if ( empty( $character_names ) ) : ?>
					<p class="arkn-empty">موردی انتخاب نشده است.</p>
				<?php else : ?>
					<?php foreach ( $character_names as $name ) : ?>
						<div class="arkn-card">
							<?php if ( isset( $character_image_map[ $name ] ) ) : ?>
								<img src="<?php echo esc_url( $character_image_map[ $name ] ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
							<?php endif; ?>
							<span class="arkn-card__name"><?php echo esc_html( $name ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="arkn-single-account__panel">
			<h2>سلاح‌های 6 ستاره</h2>
			<div class="arkn-image-grid">
				<?php if ( empty( $weapon_names ) ) : ?>
					<p class="arkn-empty">موردی انتخاب نشده است.</p>
				<?php else : ?>
					<?php foreach ( $weapon_names as $name ) : ?>
						<div class="arkn-card">
							<?php if ( isset( $weapon_image_map[ $name ] ) ) : ?>
								<img src="<?php echo esc_url( $weapon_image_map[ $name ] ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
							<?php endif; ?>
							<span class="arkn-card__name"><?php echo esc_html( $name ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="arkn-single-account__panel">
			<h2>توضیحات کامل</h2>
			<div class="arkn-single-account__content">
				<?php echo wp_kses_post( wpautop( get_post_field( 'post_content', $product_id ) ) ); ?>
			</div>
		</section>

		<div class="arkn-lightbox" data-arkn-lightbox hidden>
			<button type="button" class="arkn-lightbox__close" data-lightbox-close aria-label="Close">×</button>
			<img src="" alt="" data-lightbox-image />
		</div>
	</main>
	<?php
endwhile;

get_footer();
