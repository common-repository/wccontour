<?php
/**
 * Filled Component Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/filled-component.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id     = $product['variation_id'] ? $product['variation_id'] : $product['product_id'];
$product_object = wc_get_product( $product_id );
$settings       = wccon_get_settings();

$image_id             = $product_object->get_image_id();
$stock_status         = $product_object->get_stock_status();
$is_sold_individually = $product_object->is_sold_individually();
$is_individ_show      = apply_filters( 'wccon_sold_individually_enabled', true ) ? $is_sold_individually : false;
?>
<div class="wccon-component-product-item">
	<div class="wccon-component-product" data-product-id="<?php echo esc_attr( $product_id ); ?>">
		<div class="wccon-component-product__image">
			<?php
			echo wp_kses_post( $product_object->get_image( apply_filters( 'wccon_product_image_size', $settings['style']['image_size'] ) ) ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
			?>
		</div>
		<div class="wccon-component-product__body">
			<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="wccon-component-product__title" target="_blank">
				<?php echo wp_kses_post( $product_object->get_name() ); ?>
			</a>
			<?php
			if ( 'variation' === $product_object->get_type() ) :
				$variations = $product_object->get_variation_attributes( false );

				?>
				<div class="wccon-component-product__variations">
					<?php
					foreach ( $variations as $attribute_name => $variation_value ) :
						$attribute = wccon_get_attribute_taxonomy_by_name( $attribute_name );
						if ( taxonomy_exists( $attribute_name ) ) :
							$attr_term = get_term_by( 'slug', $variation_value, $attribute_name );
							if ( ! $attr_term ) {
								continue;
							}

							switch ( $attribute['attribute_type'] ) :


								case 'color':
									$color = get_term_meta( $attr_term->term_id, apply_filters( 'wccon_attribute_color_type_term', 'product_attribute_color' ), true );

									echo '<div class="product-attribute-type__color" data-attribute-name="' . esc_attr( $attribute_name ) . '" style="background-color:' . esc_attr( $color ) . '"></div>';
									break;
								case 'button':
								default:
									echo '<div class="product-attribute-type__button" data-attribute-name="' . esc_attr( $attribute_name ) . '">' . esc_html( $attr_term->name ) . '</div>';
									break;
							endswitch;
							?>
							<?php
						else :
							echo '<div class="product-attribute-type__button" data-attribute-name="' . esc_attr( $attribute_name ) . '">' . esc_html( $attr_term->name ) . '</div>';

						 endif;
						?>
						<?php endforeach; ?>

				</div>
			<?php endif; ?>
			<div class="wccon-component-product__meta">
				<?php if ( $product_object->get_sku() ) : ?>
					<div class="wccon-component-product__sku">
						<?php echo wp_kses_post( $product_object->get_sku() ); ?>
					</div>
				<?php endif; ?>
				<?php do_action( 'wccon_component_product_meta', $product_object ); ?>
	
			</div>
			
		</div>

	</div>
	<?php
	if ( 'instock' === $stock_status && ! $is_individ_show ) :
		?>
	<div class="wccon-component-product__quantity">
		<button class="minus">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
				<path d="M5 10 H15" fill="none" stroke="currentColor" stroke-width="2" />
			</svg>
		</button>
		<input type="number" class="" step="1" min="1" max="100" name="" value="<?php echo esc_attr( $product['quantity'] ); ?>" size="4" inputmode="" />
		<button class="plus">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
				<path d="M5 10 H15 M10 5 V15" fill="none" stroke="currentColor" stroke-width="2" />
			</svg>
		</button>
	</div>
		<?php
	elseif ( 'outofstock' === $stock_status ) :
		?>
		<div class="wccon-component-product__quantity wccon-component-product__outstock"><?php esc_html_e( 'Outofstock', 'wccontour' ); ?></div>
	<?php elseif ( $is_individ_show ) : ?>
		<div class="wccon-component-product__quantity issoldind"></div>
	<?php endif; ?>
	<div class="wccon-component-product__price">
		<?php echo wp_kses_post( $product_object->get_price_html() ); ?>
	</div>
	<div class="wccon-component-buttons">		
		<button class="wccon-refresh-button">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
				<path d="M21 21a1 1 0 0 1-1-1V16H16a1 1 0 0 1 0-2h5a1 1 0 0 1 1 1v5A1 1 0 0 1 21 21zM8 10H3A1 1 0 0 1 2 9V4A1 1 0 0 1 4 4V8H8a1 1 0 0 1 0 2z" />
				<path d="M12 22a10 10 0 0 1-9.94-8.89 1 1 0 0 1 2-.22 8 8 0 0 0 15.5 1.78 1 1 0 1 1 1.88.67A10 10 0 0 1 12 22zM20.94 12a1 1 0 0 1-1-.89A8 8 0 0 0 4.46 9.33a1 1 0 1 1-1.88-.67 10 10 0 0 1 19.37 2.22 1 1 0 0 1-.88 1.1z" />
			</svg>
		</button>

		<button class="wccon-close-button">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" enable-background="new 0 0 24 24" viewBox="0 0 24 24">
				<path d="M13.4,12l6.3-6.3c0.4-0.4,0.4-1,0-1.4c-0.4-0.4-1-0.4-1.4,0L12,10.6L5.7,4.3c-0.4-0.4-1-0.4-1.4,0c-0.4,0.4-0.4,1,0,1.4  l6.3,6.3l-6.3,6.3C4.1,18.5,4,18.7,4,19c0,0.6,0.4,1,1,1c0.3,0,0.5-0.1,0.7-0.3l6.3-6.3l6.3,6.3c0.2,0.2,0.4,0.3,0.7,0.3  s0.5-0.1,0.7-0.3c0.4-0.4,0.4-1,0-1.4L13.4,12z" />
			</svg>
		</button>
		<button class="wccon-collapse-button">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
			</svg>
		</button>
	</div>	
</div>
