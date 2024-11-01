<?php
/**
 * WCCON Template.
 *
 * Actions and functions for the templating system.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wccon_product_list_topbar', 'wccon_product_list_orderby', 5 );
add_action( 'wccon_product_list_topbar', 'wccon_product_list_available', 10 );
add_action( 'wccon_product_list_topbar', 'wccon_product_list_search', 15 );


add_action( 'wccon_builder_topbar_items', 'wccon_render_required_items', 5, 4 );
add_action( 'wccon_builder_topbar_items', 'wccon_render_extra_items', 10, 4 );

add_action( 'wccon_builder_topbar_actions', 'wccon_render_topbar_actions', 10, 2 );

// product meta && attributes.
add_action( 'wccon_builder_product_item_meta', 'wccon_render_product_item_meta', 5 );
add_action( 'wccon_builder_product_item_attributes', 'wccon_render_product_item_attributes', 10, 3 );



function wccon_product_list_orderby() {
	$product_list_orderby_options = apply_filters(
		'wccon_product_list_orderby',
		array(
			'menu_order' => __( 'Default sorting', 'wccontour' ),
			'popularity' => __( 'Sort by popularity', 'wccontour' ),
			'rating'     => __( 'Sort by average rating', 'wccontour' ),
			'date'       => __( 'Sort by latest', 'wccontour' ),
			'price'      => __( 'Sort by price: low to high', 'wccontour' ),
			'price-desc' => __( 'Sort by price: high to low', 'wccontour' ),
		)
	);

	wccon_dropdown_select( __( 'Sort by:', 'wccontour' ), 'orderby', $product_list_orderby_options );
}

function wccon_product_list_available() {
	$product_list_stock_options = apply_filters(
		'wccon_product_list_available',
		array(
			'all'     => __( 'All', 'wccontour' ),
			'instock' => __( 'In stock', 'wccontour' ),
		)
	);

	wccon_dropdown_select( __( 'Stock', 'wccontour' ), 'stock', $product_list_stock_options );
}

function wccon_product_list_search() {
	?>
	<div class="product-list-search">
		<input type="text" id="wccon-product-search" placeholder="<?php esc_attr_e( 'Search', 'wccontour' ); ?>" >
	</div>
	<?php
}

function wccon_render_required_items( $data, $id, $cp_data, $type ) {

	$components              = array_merge( ...wp_list_pluck( $data['groups'], 'components' ) );
	$components              = apply_filters( 'wccon_required_components_data', $components, $id, $type );
	$enabled_compatibility   = wccon_is_compatibility_enabled();
	$has_required_components = false;
	foreach ( $components as $component ) {
		if ( isset( $component['meta']['required'] ) && wc_string_to_bool( $component['meta']['required'] ) ) {
			$has_required_components = true;
			break;
		}
		if ( isset( $component['components'] ) ) {
			// find if there is required item in subgroup.
			$subcomponent_is_required = false;
			foreach ( $component['components'] as $subcomponent ) {
				$subcomponent_is_required = isset( $subcomponent['meta']['required'] ) ? wc_string_to_bool( $subcomponent['meta']['required'] ) : false;
				if ( $subcomponent_is_required ) {
					$has_required_components = true;
					break 2;
				}
			}
		}
	}
	if ( ! $has_required_components ) {
		return;
	}
	?>

	<div class="wccon-product-top__required">
		<span><?php echo esc_html( apply_filters( 'wccon_product_top_required_title', __( 'Required', 'wccontour' ), $data ) ); ?></span>
		<div class="product-top__required-items">
	<?php

	foreach ( $components as $component ) :
		$compatible_class = '';
		if ( isset( $component['components'] ) ) {
			// find if there is required item in subgroup.
			$subcomponent_is_required = false;
			foreach ( $component['components'] as $subcomponent ) {
				$subcomponent_is_required = isset( $subcomponent['meta']['required'] ) ? wc_string_to_bool( $subcomponent['meta']['required'] ) : false;
				if ( $subcomponent_is_required ) {
					break;
				}
			}
			if ( ! $subcomponent_is_required ) {
				continue;
			}
			foreach ( $component['components'] as $subcomponent ) {
				if ( ! $enabled_compatibility ) {
					$compatible_class = '';
					break;
				}

				if ( $enabled_compatibility && $cp_data !== false && 'saved' === $type ) {
					$compatible_status = wccon_component_is_compatible( $subcomponent['slug'], $cp_data );
					if ( true === $compatible_status ) {
						$compatible_class = 'compatible';

					} elseif ( false === $compatible_status ) {
						$compatible_class = 'incompatible';
						break;
					}
				}
			}
		} else {
			$component_is_required = isset( $component['meta']['required'] ) ? wc_string_to_bool( $component['meta']['required'] ) : false;
			if ( ! $component_is_required ) {
				continue;
			}
			if ( ! $enabled_compatibility ) {
				$compatible_class = '';

			}
			if ( $enabled_compatibility && $cp_data !== false && 'saved' === $type ) {
				$compatible_status = wccon_component_is_compatible( $component['slug'], $cp_data );
				if ( true === $compatible_status ) {
					$compatible_class = 'compatible';

				} elseif ( false === $compatible_status ) {
					$compatible_class = 'incompatible';

				}
			}
		}
		?>
	
	<div class="product-top__required-item <?php echo esc_attr( $compatible_class ); ?>" data-image-slug="<?php echo esc_attr( $component['slug'] ); ?>">
			<?php
			$component_image_id = $component['image_id'];
			if ( $component_image_id ) {
				echo wp_kses_post(
					wp_get_attachment_image(
						$component_image_id,
						'thumbnail',
						'',
						array(
							'alt'   => $component['title'],
							'title' => $component['title'],

						)
					)
				);
			} else {
				echo '<span class="product-title">' . esc_html( $component['title'] ) . '</span>';
			}

			?>
	</div>
			<?php

	endforeach;
	?>
	</div>
	</div>
	<?php
}

function wccon_render_extra_items( $data, $id, $cp_data, $type ) {
	$components            = array_merge( ...wp_list_pluck( $data['groups'], 'components' ) );
	$components            = apply_filters( 'wccon_extra_components_data', $components, $id, $type );
	$enabled_compatibility = wccon_is_compatibility_enabled();
	$has_extra_components  = false;
	foreach ( $components as $component ) {
		if ( isset( $component['meta']['extra'] ) && wc_string_to_bool( $component['meta']['extra'] ) ) {
			$has_extra_components = true;
			break;
		}
	}
	if ( ! $has_extra_components ) {
		return;
	}
	?>

	<div class="wccon-product-top__extra">
		<span><?php echo apply_filters( 'wccon_product_top_extra_title', esc_html__( 'Extra', 'wccontour' ), $data ); ?></span>
		<div class="product-top__extra-items">
			<?php
			foreach ( $components as $component ) :

				$component_is_extra = isset( $component['meta']['extra'] ) ? wc_string_to_bool( $component['meta']['extra'] ) : false;
				$component_is_extra = apply_filters( 'wccon_is_component_extra', $component_is_extra, $component, $id );

				if ( ! $component_is_extra ) {
					continue;
				}
					$compatible_class = '';

				if ( ! $enabled_compatibility ) {
					$compatible_class = '';

				}
				if ( $enabled_compatibility && $cp_data !== false && 'saved' === $type ) {
					$compatible_status = wccon_component_is_compatible( $component['slug'], $cp_data );
					if ( true === $compatible_status ) {
						$compatible_class = 'compatible';

					} elseif ( false === $compatible_status ) {
						$compatible_class = 'incompatible';

					}
				}
				?>
					<div class="product-top__extra-item <?php echo esc_attr( $compatible_class ); ?>" data-image-slug="<?php echo esc_attr( $component['slug'] ); ?>">
							<?php
							$component_image_id = $component['image_id'];
							if ( $component_image_id ) {
								echo wp_kses_post(
									wp_get_attachment_image(
										$component_image_id,
										'thumbnail',
										'',
										array(
											'alt'   => $component['title'],
											'title' => $component['title'],
										)
									)
								);
							} else {
								echo '<span class="product-title">' . esc_html( $component['title'] ) . '</span>';
							}

							?>
					</div>
				<?php endforeach; ?>

		</div>
	</div>
	<?php

}

function wccon_render_topbar_actions( $data, $id ) {

	$list_id = isset( $_GET['wccon-list'] ) ? absint( $_GET['wccon-list'] ) : false;
	if ( is_wc_endpoint_url( 'wccon-builder' ) ) {
		$query_var   = get_query_var( 'wccon-builder' );
		$array_value = explode( '/', $query_var );

		if ( count( $array_value ) > 1 && 'edit' === $array_value[0] && absint( $array_value[1] ) > 0 ) {
			$list_id = absint( $array_value[1] );
		}
	}
	?>
	<ul class="wccon-topbar-actions">
		<li>
			<button class="wccon-clear-list-button"><svg><use xlink:href="#kitrml"></use></svg><span><?php esc_html_e( 'Clear', 'wccontour' ); ?></span></button>
		</li>
		<?php if ( is_user_logged_in() ) : ?>
		<li>
			<button class="wccon-save-list-button"><svg><use xlink:href="#kitsave"></use></svg><span><?php esc_html_e( 'Save', 'wccontour' ); ?></span></button>
		</li>
			<?php endif; ?>
		<?php if ( $list_id ) : ?>
		<li>
			<button class="wccon-share-list-button"><svg><use xlink:href="#kitshare"></use></svg><span><?php esc_html_e( 'Share', 'wccontour' ); ?></span></button>
		</li>
		<?php endif; ?>
	</ul>
			<?php if ( $list_id ) : ?>
	<div class="wccon-share-box">
	
				<?php

				$socials_links = wccon_get_social_links( $data['page_id'], $list_id );

				foreach ( $socials_links as $socials_link ) :
					?>
				<a href="<?php echo esc_attr( $socials_link['link'] ); ?>" class="<?php echo esc_attr( $socials_link['class'] ); ?>">
				<svg>
					<use xlink:href="<?php echo esc_attr( '#' . $socials_link['svg_id'] ); ?>"></use>
				</svg>
			</a>
			<?php endforeach; ?>
			<button class="wccon-share-close">
				<svg><use xlink:href="#icon-close"></use></svg>
			</button>
	</div>
	<?php endif; ?>
	<?php
}

function wccon_render_product_item_meta( $product ) {

	$wcc_product       = WCCON_Product::get_product( $product );
	$product_meta_data = apply_filters( 'wccon_product_list_meta', $wcc_product->get_product_list_meta() );

	foreach ( $product_meta_data as $meta ) {
		if ( is_callable( $meta['render'] ) ) {
			call_user_func( $meta['render'] );
		}
	}

}
function wccon_render_product_item_attributes( $product, $attributes, $term_ids ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return;
	}
	if ( ! $attributes ) {
		return;
	}
	$wccon_settings    = wccon_get_settings();
	$button_variations = $wccon_settings['style']['button_variations'];

	foreach ( $attributes as $attribute_name => $options ) :
		$attribute = wccon_get_attribute_taxonomy_by_name( $attribute_name );

		$select_render = apply_filters( 'wccon_use_raw_select_variations', false, $attribute ) ? 'wccon_variation_dropdown' : 'wccon_variation_nice_dropdown';
		$color_render  = apply_filters( 'wccon_use_color_variations', true, $attribute ) ? 'wccon_variation_color_type' : $select_render;
		$button_render = apply_filters( 'wccon_use_button_variations', true, $attribute ) ? 'wccon_variation_button_type' : $select_render;
		if ( $button_variations ) {
			$select_render = 'wccon_variation_button_type';
		}
		$default_render = apply_filters( 'wccon_use_default_variations', 'wccon_variation_button_type' );
		if ( taxonomy_exists( $attribute_name ) ) {
			switch ( $attribute['attribute_type'] ) {

				case 'button':
					echo wp_kses_post(
						$button_render(
							array(
								'options'   => $options,
								'attribute' => $attribute_name,
								'product'   => $product,

								'term_ids'  => $term_ids,
							)
						)
					);

					break;
				case 'color':
					echo wp_kses_post(
						$color_render(
							array(
								'options'   => $options,
								'attribute' => $attribute_name,
								'product'   => $product,

								'term_ids'  => $term_ids,
							)
						)
					);
					break;
				case 'select':
					echo wp_kses_post(
						$select_render(
							array(
								'options'   => $options,
								'attribute' => $attribute_name,
								'product'   => $product,

								'term_ids'  => $term_ids,
							)
						)
					);
					break;
				default:
					echo wp_kses_post(
						$default_render(
							array(
								'options'   => $options,
								'attribute' => $attribute_name,
								'product'   => $product,

								'term_ids'  => $term_ids,
							)
						)
					);

					break;
			}
		} else {
			echo wp_kses_post(
				$default_render(
					array(
						'options'   => $options,
						'attribute' => $attribute_name,
						'product'   => $product,

						'term_ids'  => $term_ids,
					)
				)
			);
		}

		do_action( 'wccon_render_custom_type_variation_attr', $attribute, $product, $attributes, $term_ids );
		endforeach;
	if ( ! empty( $attributes ) ) {
		echo '<a href="#" class="wccon-reset_variations" title="' . esc_attr__( 'Reset', 'wccontour' ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24">
		<path d="M21 21a1 1 0 0 1-1-1V16H16a1 1 0 0 1 0-2h5a1 1 0 0 1 1 1v5A1 1 0 0 1 21 21zM8 10H3A1 1 0 0 1 2 9V4A1 1 0 0 1 4 4V8H8a1 1 0 0 1 0 2z"></path>
		<path d="M12 22a10 10 0 0 1-9.94-8.89 1 1 0 0 1 2-.22 8 8 0 0 0 15.5 1.78 1 1 0 1 1 1.88.67A10 10 0 0 1 12 22zM20.94 12a1 1 0 0 1-1-.89A8 8 0 0 0 4.46 9.33a1 1 0 1 1-1.88-.67 10 10 0 0 1 19.37 2.22 1 1 0 0 1-.88 1.1z"></path>
	</svg>' . esc_html__( 'Reset attributes', 'wccontour' ) . '</a>';
	}

}
