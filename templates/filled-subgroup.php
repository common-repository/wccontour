<?php
/**
 * Filled Subgroup Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/filled-subgroup.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wccon-component-product-item">
	<div class="wccon-subgroup-products">
	<?php
	$settings      = wccon_get_settings();
	$product_index = 30;
	foreach ( $products as $key => $product ) :
			$product_index--;
			$product_id     = $product['variation_id'] ? $product['variation_id'] : $product['product_id'];
			$product_object = wc_get_product( $product_id );
		?>
		<div class="wccon-component-product" style="--proIndex:<?php echo esc_attr( $product_index ); ?>">
			<div class="wccon-component-product__image">
			<?php echo wp_kses_post( $product_object->get_image( apply_filters( 'wccon_product_image_size', $settings['style']['image_size'] ) ) ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="wccon-component-product__body">
				<a href="<?php echo esc_url( get_permalink( $product_object->get_id() ) ); ?>" class="wccon-component-product__title" target="_blank">
					<?php echo wp_kses_post( $product_object->get_name() ); ?>
				</a>
			</div>

		</div>
		<?php endforeach; ?>
	</div>
	<div class="wccon-component-buttons">		

		<button class="wccon-choose-button wccon-collapse-group">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
			</svg>
		</button>
	
	</div>	
</div>
