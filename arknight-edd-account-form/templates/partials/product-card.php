<?php
/**
 * Single product card partial.
 *
 * @var array<string,mixed> $card_data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_id     = isset( $card_data['id'] ) ? absint( $card_data['id'] ) : wp_rand( 10, 9999 );
$product_url = get_permalink( $card_id );
?>
<article class="arkn-product-card" data-arkn-card>
	<div class="arkn-product-card__headline">
		<a class="arkn-product-card__view-btn" href="<?php echo esc_url( $product_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'مشاهده', 'arknight-edd-account-form' ); ?></a>
		<h3 class="arkn-product-card__title"><a href="<?php echo esc_url( $product_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $card_data['title'] ); ?></a></h3>
	</div>

	<div class="arkn-product-card__tabs" role="tablist" aria-label="<?php esc_attr_e( 'نمایش کارت', 'arknight-edd-account-form' ); ?>">
		<button type="button" class="arkn-product-card__tab is-active" data-arkn-tab-trigger data-target="info-<?php echo esc_attr( (string) $card_id ); ?>" role="tab" aria-selected="true"><?php esc_html_e( 'اطلاعات آکانت', 'arknight-edd-account-form' ); ?></button>
		<button type="button" class="arkn-product-card__tab" data-arkn-tab-trigger data-target="gallery-<?php echo esc_attr( (string) $card_id ); ?>" role="tab" aria-selected="false"><?php esc_html_e( 'گالری تصاویر', 'arknight-edd-account-form' ); ?></button>
	</div>

	<div class="arkn-product-card__panel is-active" data-arkn-tab-panel="info-<?php echo esc_attr( (string) $card_id ); ?>" role="tabpanel">
		<div class="arkn-product-card__stats">
			<?php foreach ( $card_data['stats'] as $row ) : ?>
				<div class="arkn-product-card__stat-chip">
					<span class="arkn-product-card__label"><?php echo esc_html( (string) $row['label'] ); ?></span>
					<span class="arkn-product-card__value"><?php echo esc_html( (string) $row['value'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="arkn-product-card__footer">
			<div class="arkn-product-card__price-wrap">
				<span class="arkn-product-card__price-label"><?php esc_html_e( 'قیمت', 'arknight-edd-account-form' ); ?></span>
				<strong class="arkn-product-card__price"><?php echo esc_html( (string) $card_data['price'] ); ?></strong>
			</div>


			<div class="arkn-product-card__desktop-only">
				<div class="arkn-product-card__hover" data-arkn-hover>
					<button type="button" class="arkn-product-card__hover-trigger" data-arkn-hover-trigger><?php esc_html_e( 'توضیحات', 'arknight-edd-account-form' ); ?></button>
					<div class="arkn-product-card__hover-content" data-arkn-hover-content>
						<p class="arkn-product-card__hover-text"><?php echo esc_html( (string) $card_data['description_hover'] ); ?></p>
					</div>
				</div>

				<div class="arkn-product-card__hover" data-arkn-hover>
					<button type="button" class="arkn-product-card__hover-trigger" data-arkn-hover-trigger><?php esc_html_e( 'کاراکترهای 6 ستاره', 'arknight-edd-account-form' ); ?></button>
					<div class="arkn-product-card__hover-content" data-arkn-hover-content>
						<?php if ( empty( $card_data['characters_hover_items'] ) ) : ?>
							<p class="arkn-product-card__empty">-</p>
						<?php else : ?>
							<ul class="arkn-product-card__mini-list">
								<?php foreach ( $card_data['characters_hover_items'] as $item ) : ?>
									<li class="arkn-product-card__mini-item">
										<?php if ( ! empty( $item['image'] ) ) : ?>
											<img src="<?php echo esc_url( (string) $item['image'] ); ?>" alt="<?php echo esc_attr( (string) $item['name'] ); ?>" loading="lazy" />
										<?php endif; ?>
										<span><?php echo esc_html( (string) $item['name'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>

				<div class="arkn-product-card__hover" data-arkn-hover>
					<button type="button" class="arkn-product-card__hover-trigger" data-arkn-hover-trigger><?php esc_html_e( 'سلاح‌های 6 ستاره', 'arknight-edd-account-form' ); ?></button>
					<div class="arkn-product-card__hover-content" data-arkn-hover-content>
						<?php if ( empty( $card_data['weapons_hover_items'] ) ) : ?>
							<p class="arkn-product-card__empty">-</p>
						<?php else : ?>
							<ul class="arkn-product-card__mini-list">
								<?php foreach ( $card_data['weapons_hover_items'] as $item ) : ?>
									<li class="arkn-product-card__mini-item">
										<?php if ( ! empty( $item['image'] ) ) : ?>
											<img src="<?php echo esc_url( (string) $item['image'] ); ?>" alt="<?php echo esc_attr( (string) $item['name'] ); ?>" loading="lazy" />
										<?php endif; ?>
										<span><?php echo esc_html( (string) $item['name'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="arkn-product-card__panel" data-arkn-tab-panel="gallery-<?php echo esc_attr( (string) $card_id ); ?>" role="tabpanel" hidden>
		<?php if ( empty( $card_data['gallery_images'] ) ) : ?>
			<p class="arkn-product-card__empty"><?php esc_html_e( 'تصویری ثبت نشده است.', 'arknight-edd-account-form' ); ?></p>
		<?php else : ?>
			<div class="arkn-gallery" data-arkn-gallery>
				<div class="arkn-gallery__track" data-arkn-gallery-track>
					<?php foreach ( $card_data['gallery_images'] as $image ) : ?>
						<figure class="arkn-gallery__slide">
							<img src="<?php echo esc_url( (string) $image['url'] ); ?>" alt="<?php echo esc_attr( (string) $image['alt'] ); ?>" loading="lazy" />
						</figure>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $card_data['gallery_images'] ) > 1 ) : ?>
					<div class="arkn-gallery__actions">
						<button type="button" class="arkn-gallery__btn" data-arkn-gallery-prev aria-label="<?php esc_attr_e( 'قبلی', 'arknight-edd-account-form' ); ?>">‹</button>
						<button type="button" class="arkn-gallery__btn" data-arkn-gallery-next aria-label="<?php esc_attr_e( 'بعدی', 'arknight-edd-account-form' ); ?>">›</button>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</article>
