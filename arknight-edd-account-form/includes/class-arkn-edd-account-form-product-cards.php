<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arknight_EDD_Account_Form_Product_Cards {
	const SHORTCODE = 'arknight_account_cards';

	/**
	 * @var Arknight_EDD_Account_Form_Admin
	 */
	private $admin;

	/**
	 * @var string
	 */
	private $download_category_taxonomy;

	/**
	 * @var string
	 */
	private $download_category_slug;

	/**
	 * @param Arknight_EDD_Account_Form_Admin $admin Admin dependency.
	 */
	public function __construct( Arknight_EDD_Account_Form_Admin $admin ) {
		$this->admin                      = $admin;
		$this->download_category_taxonomy = Arknight_EDD_Account_Form_Frontend::DOWNLOAD_CATEGORY_TAXONOMY;
		$this->download_category_slug     = Arknight_EDD_Account_Form_Frontend::DOWNLOAD_CATEGORY_SLUG;
	}

	public function register_shortcode() {
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
	}

	public function register_assets() {
		wp_register_style( 'arkn-account-cards-style', ARKN_EDD_FORM_URL . 'assets/css/product-cards.css', array(), ARKN_EDD_FORM_VERSION );
		wp_register_script( 'arkn-account-cards-script', ARKN_EDD_FORM_URL . 'assets/js/product-cards.js', array(), ARKN_EDD_FORM_VERSION, true );
	}

	/**
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		wp_enqueue_style( 'arkn-account-cards-style' );
		wp_enqueue_script( 'arkn-account-cards-script' );

		if ( ! post_type_exists( 'download' ) ) {
			return '<p class="arkn-product-cards__message">' . esc_html__( 'Easy Digital Downloads فعال نیست.', 'arknight-edd-account-form' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'posts_per_page' => 12,
			),
			$atts,
			self::SHORTCODE
		);

		$query_args = array(
			'post_type'      => 'download',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, absint( $atts['posts_per_page'] ) ),
			'no_found_rows'  => true,
		);

		if ( taxonomy_exists( $this->download_category_taxonomy ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $this->download_category_taxonomy,
					'field'    => 'slug',
					'terms'    => $this->download_category_slug,
				),
			);
		}

		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return '<p class="arkn-product-cards__message">' . esc_html__( 'فعلاً محصولی برای نمایش وجود ندارد.', 'arknight-edd-account-form' ) . '</p>';
		}

		$cards = $this->prepare_cards_data( $query );
		wp_reset_postdata();

		$template_file = ARKN_EDD_FORM_DIR . 'templates/shortcode-arkn-account-cards.php';
		if ( ! file_exists( $template_file ) ) {
			return '';
		}

		ob_start();
		require $template_file;
		return ob_get_clean();
	}

	/**
	 * @param WP_Query $query Downloads query.
	 * @return array<int,array<string,mixed>>
	 */
	private function prepare_cards_data( WP_Query $query ) {
		$meta_rows = array(
			'authority_level'       => 'آتوریتی لول',
			'server'                => 'سرور',
			'character_banner_pity' => 'پیتی بنر کاراکتر',
			'weapon_banner_pity'    => 'پیتی بنر سلاح',
			'standard_banner_pity'  => 'پیتی بنر استاندارد',
			'remaining_wish'        => 'مقدار ویش مانده',
			'orundum'               => 'تعداد اروبریل',
			'originium'             => 'تعداد اریجئومتری',
			'arsenal_ticket'        => 'تعداد ارسنال تیکت',
			'character_potential'   => 'پوتنشال کاراکترها',
		);

		$character_map = $this->build_item_image_map( $this->admin->get_character_images() );
		$weapon_map    = $this->build_item_image_map( $this->admin->get_weapon_images() );
		$cards         = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$stats   = array();

			foreach ( $meta_rows as $meta_key => $label ) {
				$value   = (string) get_post_meta( $post_id, 'arkn_' . $meta_key, true );
				$stats[] = array(
					'label' => $label,
					'value' => '' !== $value ? $value : '-',
				);
			}

			$description = (string) get_post_meta( $post_id, 'arkn_description', true );
			$price       = (string) get_post_meta( $post_id, 'edd_price', true );

			$characters = $this->extract_meta_list( (string) get_post_meta( $post_id, 'arkn_selected_characters', true ) );
			$weapons    = $this->extract_meta_list( (string) get_post_meta( $post_id, 'arkn_selected_weapons', true ) );
			$gallery    = $this->extract_gallery_urls( (string) get_post_meta( $post_id, Arknight_EDD_Account_Form_Frontend::UPLOADED_IMAGE_META_KEY, true ) );

			$cards[] = array(
				'id'                      => (int) $post_id,
				'title'                   => get_the_title(),
				'stats'                   => $stats,
				'price'                   => '' !== $price ? number_format_i18n( (float) $price ) : '-',
				'description_hover'       => $this->prepare_hover_text( $description ),
				'characters_hover_items'  => $this->prepare_hover_items( $characters, $character_map ),
				'weapons_hover_items'     => $this->prepare_hover_items( $weapons, $weapon_map ),
				'gallery_images'          => $gallery,
			);
		}

		return $cards;
	}

	/**
	 * @param string $meta Comma separated values.
	 * @return array<int,string>
	 */
	private function extract_meta_list( $meta ) {
		if ( '' === $meta ) {
			return array();
		}

		$items = array_map( 'trim', explode( ',', $meta ) );
		$items = array_filter(
			$items,
			static function ( $item ) {
				return '' !== $item;
			}
		);

		return array_values( array_unique( $items ) );
	}

	/**
	 * @param string $gallery_ids Comma separated attachment IDs.
	 * @return array<int,array{url:string,alt:string}>
	 */
	private function extract_gallery_urls( $gallery_ids ) {
		$ids = $this->extract_meta_list( $gallery_ids );
		if ( empty( $ids ) ) {
			return array();
		}

		$images = array();
		foreach ( $ids as $id_raw ) {
			$attachment_id = absint( $id_raw );
			if ( $attachment_id <= 0 ) {
				continue;
			}

			$image_url = wp_get_attachment_image_url( $attachment_id, 'large' );
			if ( ! $image_url ) {
				continue;
			}

			$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			if ( '' === $alt ) {
				$alt = get_the_title( $attachment_id );
			}

			$images[] = array(
				'url' => esc_url( $image_url ),
				'alt' => sanitize_text_field( (string) $alt ),
			);
		}

		return $images;
	}

	/**
	 * @param array<int,array{name:string,file:string}> $items Raw items.
	 * @return array<string,string>
	 */
	private function build_item_image_map( $items ) {
		$map = array();

		foreach ( $items as $item ) {
			if ( empty( $item['name'] ) || empty( $item['file'] ) ) {
				continue;
			}

			$name         = sanitize_text_field( $item['name'] );
			$map[ $name ] = esc_url( ARKN_EDD_IMG_BASE_URL . ltrim( sanitize_file_name( $item['file'] ), '/' ) );
		}

		return $map;
	}

	/**
	 * @param string $text Raw text.
	 * @return string
	 */
	private function prepare_hover_text( $text ) {
		$text = trim( $text );
		return '' !== $text ? $text : '-';
	}

	/**
	 * @param array<int,string>   $items Item names.
	 * @param array<string,string> $image_map Name to image map.
	 * @return array<int,array{name:string,image:string}>
	 */
	private function prepare_hover_items( $items, $image_map ) {
		$list = array();
		foreach ( $items as $name ) {
			$list[] = array(
				'name'  => $name,
				'image' => isset( $image_map[ $name ] ) ? $image_map[ $name ] : '',
			);
		}

		return $list;
	}
}
