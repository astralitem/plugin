<?php
/**
 * Product cards shortcode template.
 *
 * @var array<int,array<string,mixed>> $cards
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="arkn-product-cards" data-arkn-cards>
	<?php foreach ( $cards as $card_data ) : ?>
		<?php require ARKN_EDD_FORM_DIR . 'templates/partials/product-card.php'; ?>
	<?php endforeach; ?>
</div>
