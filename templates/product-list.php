<?php
/**
 * Product list Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/product-list.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$term_ids              = $term_ids ?? array();
$product               = wc_get_product( get_the_ID() );
$max_height_desc       = apply_filters( 'wccon_max_height_description', '43px' );
$settings              = wccon_get_settings();
$selected_products_ids = array();
if ( ! empty( $selected_products ) ) {
	foreach ( $selected_products as $selected_product ) {
		$product_id              = $selected_product['variation_id'] ? $selected_product['variation_id'] : $selected_product['product_id'];
		$selected_products_ids[] = absint( $product_id );
	}
}
?>
<?php if ( $product->is_type( 'variable' ) ) : ?>
	<?php
		$data_store = WC_Data_Store::load( 'product' );

		$default_attributes  = $product->get_default_attributes();
		$modified_attributes = array();
		array_walk(
			$default_attributes,
			function( $value, &$key ) use ( &$modified_attributes ) {

				$key                         = 'attribute_' . $key;
				$modified_attributes[ $key ] = $value;
			}
		);
		$variation_id  = $data_store->find_matching_product_variation( $product, $modified_attributes );
		$new_attr_data = function( $data, $product ) {
			unset( $data['dimensions'] );
			unset( $data['dimensions_html'] );
			unset( $data['dimensions_html'] );
			unset( $data['variation_description'] );
			unset( $data['weight'] );
			unset( $data['weight_html'] );

			return $data;
		};

		$product_price      = $variation_id ? wc_get_price_to_display( wc_get_product( $variation_id ) ) : '';
		$product_price_html = $variation_id ? wc_get_product( $variation_id )->get_price_html() : $product->get_price_html();

		$attributes = $product->get_variation_attributes();
		add_filter( 'woocommerce_available_variation', $new_attr_data, 10, 2 );
		$available_variations = $product->get_available_variations();
		remove_filter( 'woocommerce_available_variation', $new_attr_data, 10 );
		$attribute_keys  = array_keys( $attributes );
		$variations_json = wp_json_encode( $available_variations );

	?>
<div class="wccon-product-item" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-variation-id="<?php echo esc_attr( $variation_id ? $variation_id : '' ); ?>"  data-product_variations="<?php echo wc_esc_json( $variations_json ); ?>" data-price="<?php echo esc_attr( $product_price ); ?>">
	<div class="product-item__image">
		<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
			<img src="<?php echo esc_url( wp_get_attachment_image_url( $product->get_image_id(), apply_filters( 'wccon_product_image_size', $settings['style']['image_size'] ) ) ); ?>"  alt="<?php echo esc_attr( $product->get_name() ); ?>">	
		</a>
	</div>
	<div class="product-item__body">
		<?php
		 $product_description   = apply_filters( 'wccon_product_list_description', wp_kses_post( $product->get_short_description() ), $product );
		 $overflown_description = mb_strlen( $product_description ) > apply_filters( 'wccon_desc_show_toggle', 100 );
		?>
		<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" target="_blank" class="wccon-product-title"><?php echo wp_kses_post( $product->get_name() ); ?>
		
		</a>
		<div class="product-item__desc" style="max-height:<?php echo esc_attr( $max_height_desc ); ?>">
		<?php

			echo wp_kses_post( wc_format_content( $product_description ) );
		?>
		</div>
		<div class="product-item__meta">
			<?php
			/**
			 * @hooked wccon_render_product_item_meta - 5
			 */
			do_action( 'wccon_builder_product_item_meta', $product );
			?>
			
		</div>
		<div class="product-item__attributes">
			<?php
			/**
			 * @hooked wccon_render_product_item_attributes - 10
			 */
			do_action( 'wccon_builder_product_item_attributes', $product, $attributes, $term_ids );
			?>
			
		</div>
	</div>
	<div class="product-item__actions">
			<div class="product-item__price">
				<p class="price"><?php echo wp_kses_post( apply_filters( 'wccon_product_price_html', $product_price_html, $product ) ); ?></p>
				<?php $stock_status = $product->get_stock_status(); ?>
				<p class="stock <?php echo esc_attr( $stock_status ); ?>">
					
					<?php if ( 'instock' === $stock_status ) : ?>
						<?php $stock_status_text = esc_html__( 'In stock', 'wccontour' ); ?>
						<svg><use xlink:href="#icon-chk-small"></use></svg>
					<?php else : ?>
						<?php $stock_status_text = esc_html__( 'Out of stock', 'wccontour' ); ?>
						<svg><use xlink:href="#icon-x-small"></use></svg>
					<?php endif; ?>
					<?php echo wp_kses_post( apply_filters( 'wccon_product_price_html', $stock_status_text, $stock_status ) ); ?>
				</p>
			</div>
			<?php if ( in_array( $variation_id, $selected_products_ids, true ) ) : ?>
				<div class="product-item__added">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<path fill="none" d="M0 0h24v24H0V0z"/>
						<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z"/>
					</svg>
				</div>
			<?php else : ?>
			<button class="product-item__add" <?php echo esc_attr( 'outofstock' === $stock_status ? 'disabled' : '' ); ?>>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
					<path fill="none" d="M0 0h24v24H0V0z"/>
					<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z"/>
				</svg>
			</button>
			<?php endif; ?>
	</div>
</div>
	<?php
else :
	$product_info      = apply_filters(
		'wccon_product_info_args',
		array(
			'sold_individually' => $product->is_sold_individually(),
		),
		$product
	);
	$product_info_json = wp_json_encode( $product_info );

	?>

	<div class="wccon-product-item" data-product-info="<?php echo wc_esc_json( $product_info_json ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-price="<?php echo esc_attr( wc_get_price_to_display( $product ) ); ?>">
	<div class="product-item__image">
		<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
			<img src="<?php echo esc_url( wp_get_attachment_image_url( $product->get_image_id(), apply_filters( 'wccon_product_image_size', $settings['style']['image_size'] ) ) ); ?>"  alt="<?php echo esc_attr( $product->get_name() ); ?>">	
			
		</a>

	</div>
	<div class="product-item__body">
	<?php
	 $product_description   = apply_filters( 'wccon_product_list_description', wp_kses_post( $product->get_short_description() ), $product );
	 $overflown_description = mb_strlen( $product_description ) > apply_filters( 'wccon_desc_show_toggle', 100 );

	?>
		<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" target="_blank" class="wccon-product-title"><?php echo wp_kses_post( $product->get_name() ); ?>

		</a>
		<div class="product-item__desc" style="max-height:<?php echo esc_attr( $max_height_desc ); ?>">

			<?php
			echo wp_kses_post( wc_format_content( $product_description ) );
			?>
		</div>
		<div class="product-item__meta">
			<?php
			/**
			 * @hooked wccon_render_product_item_meta - 5
			 */
			do_action( 'wccon_builder_product_item_meta', $product );
			?>
		</div>
	</div>
	<div class="product-item__actions">
			<div class="product-item__price">
				<p class="price"><?php echo wp_kses_post( apply_filters( 'wccon_product_price_html', $product->get_price_html(), $product ) ); ?></p>
				<?php $stock_status = $product->get_stock_status(); ?>
				<p class="stock <?php echo esc_attr( $stock_status ); ?>">
					
					<?php if ( 'instock' === $stock_status ) : ?>
						<?php $stock_status_text = esc_html__( 'In stock', 'wccontour' ); ?>
						<svg><use xlink:href="#icon-chk-small"></use></svg>
					<?php else : ?>
						<?php $stock_status_text = esc_html__( 'Out of stock', 'wccontour' ); ?>
						<svg><use xlink:href="#icon-x-small"></use></svg>
					<?php endif; ?>
					<?php echo wp_kses_post( apply_filters( 'wccon_product_price_html', $stock_status_text, $stock_status ) ); ?>
				</p>
			</div>
			<?php if ( in_array( $product->get_id(), $selected_products_ids, true ) ) : ?>
				<div class="product-item__added">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<path fill="none" d="M0 0h24v24H0V0z"/>
						<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z"/>
					</svg>
				</div>
			<?php else : ?>
			<button class="product-item__add" <?php echo esc_attr( 'outofstock' === $stock_status ? 'disabled' : '' ); ?>>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
					<path fill="none" d="M0 0h24v24H0V0z"/>
					<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z"/>
				</svg>
			</button>
			<?php endif; ?>
	</div>
</div>

	<?php endif; ?>
