<?php
/**
 * Saved Builder Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/saved-builder.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$retrieved_products    = WCCON()->ajax->retrieve_products( $config_data['groups'], array() );
$cp_data               = WCCON()->ajax->run_compatibility( $retrieved_products );
$enabled_compatibility = wccon_is_compatibility_enabled();
$settings              = wccon_get_settings();

?>

<div class="wccon-builder-wrapper <?php echo esc_attr( $builder_class ); ?>" data-wccon-builder="<?php echo esc_attr( $config_info['id'] ); ?>" data-wccon-scheme="<?php echo wc_esc_json( $data_attr ); ?>">
	
	<?php if ( $builder_title && $show_title ) : ?>
		<h3><?php echo wp_kses_post( $builder_title ); ?></h3>
	<?php endif; ?>
	
	<?php
		wc_get_template(
			'product-top-section.php',
			array(
				'data' => array_merge( $config_data, $config_info ),
				'id'   => $config_info['id'],
				'cp'   => $cp_data,
				'type' => 'saved',
			),
			'wccontour',
			WCCON_PLUGIN_PATH . '/templates/'
		);
		?>
	
	<?php
	foreach ( $config_data['groups'] as $group ) :
		?>
		<div class="wccon-group" data-group="<?php echo esc_attr( $group['slug'] ); ?>">
			<div class="wccon-group-row">
				<h3><?php echo wp_kses_post( $group['title'] ); ?></h3>
				<?php if ( $group['meta']['description'] ) : ?>
					<div class="wccon-tooltip" data-tippy="<?php echo esc_attr( wp_kses_post( $group['meta']['description'] ) ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25">
							<use xlink:href="#wccon-tip"></use>
						</svg>
				
					</div>
						
				<?php endif; ?>
			</div>
			<?php
			if ( ! empty( $group['components'] ) ) :
				?>
				<div class="wccon-component-wrapper">
					<?php $extra_components = array(); ?>
					<?php
					foreach ( $group['components'] as $component ) :
						$component_is_extra = isset( $component['meta']['extra'] ) ? wc_string_to_bool( $component['meta']['extra'] ) : false;

						if ( $component_is_extra ) {
							array_push( $extra_components, $component );
							continue;
						}
						?>
						<?php
							$component_classes = array( 'wccon-component' );
						?>
						<?php
						if ( ! isset( $component['parent_id'] ) ) :
							$component_has_products   = false;
							$cloned_components        = array();
							$component_single_classes = $component_classes;
							if ( isset( $component['products'] ) && ! empty( $component['products'] ) ) {
								$component_has_products = true;
								$product_id             = $component['products'][0]['variation_id'] ? $component['products'][0]['variation_id'] : $component['products'][0]['product_id'];
								$product_object         = wc_get_product( $product_id );
								$extra_classes          = array( 'selected' );
								if ( 'outofstock' === $product_object->get_stock_status() ) {
									$extra_classes[] = 'out-ofstock';
								}
								$component_single_classes = array_merge( $component_classes, $extra_classes );

							}
							$component_single_classes = apply_filters( 'wccon_component_classes', $component_single_classes, $component );

							$compatible_class = '';
							$tippy_text       = '';
							if ( $enabled_compatibility ) {
								$compatible_status = wccon_component_is_compatible( $component['slug'], $cp_data );

								$compatible_icon = true;
								if ( true === $compatible_status ) {
									$compatible_class = 'compatible';
									$compatible_icon  = true;
								} elseif ( false === $compatible_status ) {
									$compatible_class = 'incompatible';
									$compatible_icon  = false;
									$tippy_text       = wccon_component_tippy_text( $component['slug'], $cp_data );
								}
							} elseif ( $component_has_products && ! $enabled_compatibility ) {
								$compatible_class = 'compatible';
								$compatible_icon  = true;
							} else {
								$compatible_icon = true;
							}
							?>
							<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $component_single_classes ) ) ); ?>" data-component-id="<?php echo esc_attr( $component['id'] ); ?>" data-component-slug="<?php echo esc_attr( $component['slug'] ); ?>" <?php echo esc_attr( isset( $component['meta']['multiple'] ) && $component['meta']['multiple'] ) ? 'data-multiple="1"' : ''; ?> data-tippy="<?php echo esc_attr( $tippy_text ); ?>">
								<div class="wccon-component-inner">
									<div class="wccon-component__title">
										<div class="wccon-component__selected <?php echo esc_attr( $compatible_class ); ?>" title="<?php echo esc_attr( $component['title'] ); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
											<?php if ( true === $compatible_icon ) : ?>
													<use xlink:href="#icon-check"></use>
													<?php elseif ( false === $compatible_icon ) : ?>
														<use xlink:href="#icon-uncheck"></use>
														<?php endif; ?>
												
											</svg>
										</div>
										<span><?php echo esc_html( $component['title'] ); ?></span>
										<?php if ( $component['meta']['description'] ) : ?>
												<div class="wccon-tooltip" data-tippy="<?php echo esc_attr( wp_kses_post( $component['meta']['description'] ) ); ?>">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25">
														<use xlink:href="#wccon-tip"></use>
													</svg>
											
												</div>
												
										<?php endif; ?>
									</div>
									<div class="wccon-component__icon" style="<?php echo esc_attr( $component_has_products ? 'display:none;' : '' ); ?>">
										<img src="<?php echo esc_url( wp_get_attachment_image_url( $component['image_id'] ) ); ?>" srcset="<?php echo esc_attr( wp_get_attachment_image_srcset( $component['image_id'] ) ); ?>" alt="">
										<?php if ( isset( $component['components'] ) ) : ?>
											<div class="wccon-component__expand"><?php esc_html_e( '— Expand —', 'wccontour' ); ?></div>
											<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
											
										<?php else : ?>
											<div class="wccon-component__choose"><?php esc_html_e( '— Choose —', 'wccontour' ); ?></div>
											<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
										<?php endif; ?>
									</div>


									<div class="wccon-component__body">
										<?php if ( $component_has_products ) : ?>
											<?php
											wc_get_template(
												'filled-component.php',
												array(
													'product'  => $component['products'][0],
													'multiple' => isset( $component['meta']['multiple'] ) ? $component['meta']['multiple'] : 0,
												),
												'wccontour',
												WCCON_PLUGIN_PATH . '/templates/'
											);
											$cloned_products = array_shift( $component['products'] );
											?>
											<?php else : ?>
										<button class="wccon-choose-button">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
												<path fill="none" d="M0 0h24v24H0V0z" />
												<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z" />
											</svg>
										</button>
										<button class="wccon-collapse-button">
											<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
											</svg>
										</button>
										<?php endif; ?>
									</div>
								</div>
							</div>
							<?php if ( ! empty( $component['products'] ) ) : ?>
								<?php
								foreach ( $component['products'] as $cloned_key => $cloned_product ) :
									$product_id     = $cloned_product['variation_id'] ? $cloned_product['variation_id'] : $cloned_product['product_id'];
									$product_object = wc_get_product( $product_id );
									$extra_classes  = array( 'selected' );
									if ( 'outofstock' === $product_object->get_stock_status() ) {
										$extra_classes[] = 'out-ofstock';
									}
									$component_single_classes = array_merge( $component_classes, $extra_classes );
									$component_single_classes = apply_filters( 'wccon_component_classes', $component_single_classes, $component );

									$compatible_class = '';
									$tippy_text       = '';
									if ( $enabled_compatibility ) {
										$compatible_status = wccon_component_is_compatible( $component['slug'], $cp_data, $cloned_product['clone'] );

										$compatible_icon = true;
										if ( true === $compatible_status ) {
											$compatible_class = 'compatible';
											$compatible_icon  = true;
										} elseif ( false === $compatible_status ) {
											$compatible_class = 'incompatible';
											$compatible_icon  = false;
											$tippy_text       = wccon_component_tippy_text( $component['slug'], $cp_data, $cloned_product['clone'] );
										}
									} else {
										$compatible_class = 'compatible';
										$compatible_icon  = true;
									}
									?>
							<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $component_single_classes ) ) ); ?>" data-component-id="<?php echo esc_attr( $component['id'] ); ?>" data-component-slug="<?php echo esc_attr( $component['slug'] ); ?>" <?php echo esc_attr( isset( $component['meta']['multiple'] ) && $component['meta']['multiple'] ) ? 'data-multiple="1"' : ''; ?> data-copy="<?php echo esc_attr( $cloned_product['clone'] ); ?>" data-tippy="<?php echo esc_attr( $tippy_text ); ?>">
								<div class="wccon-component-inner">
									<div class="wccon-component__title">
										<div class="wccon-component__selected <?php echo esc_attr( $compatible_class ); ?>" title="<?php echo esc_attr( $component['title'] ); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
												
											<?php if ( true === $compatible_icon ) : ?>
													<use xlink:href="#icon-check"></use>
													<?php elseif ( false === $compatible_icon ) : ?>
														<use xlink:href="#icon-uncheck"></use>
														<?php endif; ?>
											</svg>
										</div>
										<span><?php echo esc_html( $component['title'] ); ?></span>
										<?php if ( $component['meta']['description'] ) : ?>
											<div class="wccon-tooltip" data-tippy="<?php echo esc_attr( wp_kses_post( $component['meta']['description'] ) ); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25">
													<use xlink:href="#wccon-tip"></use>
												</svg>
										
											</div>
											
										<?php endif; ?>
									</div>
									<div class="wccon-component__icon" style="display:none;">
										<img src="<?php echo esc_url( wp_get_attachment_image_url( $component['image_id'] ) ); ?>" srcset="<?php echo esc_attr( wp_get_attachment_image_srcset( $component['image_id'] ) ); ?>" alt="">
										<?php if ( isset( $component['components'] ) ) : ?>
											<div class="wccon-component__expand"><?php esc_html_e( '— Expand —', 'wccontour' ); ?></div>
											<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
											
										<?php else : ?>
											<div class="wccon-component__choose"><?php esc_html_e( '— Choose —', 'wccontour' ); ?></div>
											<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
										<?php endif; ?>
									</div>


									<div class="wccon-component__body">
										<?php if ( $component_has_products ) : ?>
											<?php
											wc_get_template(
												'filled-component.php',
												array(
													'product'  => $component['products'][ $cloned_key ],
													'multiple' => $component['meta']['multiple'],
												),
												'wccontour',
												WCCON_PLUGIN_PATH . '/templates/'
											);

											?>
											<?php else : ?>
										<button class="wccon-choose-button">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
												<path fill="none" d="M0 0h24v24H0V0z" />
												<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z" />
											</svg>
										</button>
										<button class="wccon-collapse-button">
											<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
											</svg>
										</button>
										<?php endif; ?>
									</div>
								</div>
							</div>
									<?php endforeach; ?>
								<?php endif; ?>
							<?php
						else :
							$subgroup_has_products = false;
							$subgroup_products     = array();
							$subgroup_classes      = $component_classes;
							foreach ( $component['components'] as $subcomponent ) {

								if ( isset( $subcomponent['products'] ) ) {
									$subgroup_classes = array_merge( $subgroup_classes, array( 'wccon-component-has-children' ) );
									if ( ! empty( $subcomponent['products'] ) ) {
										$subgroup_has_products = true;
										$subgroup_classes      = array_merge( $subgroup_classes, array( 'selected' ) );
										$subgroup_products     = array_merge( $subcomponent['products'], $subgroup_products );
									}
								}
							}

							$subgroup_classes     = apply_filters( 'wccon_component_classes', $subgroup_classes, $component );
							$sub_compatible_class = '';
							$tippy_text           = '';
							if ( $enabled_compatibility ) {
								$sub_compatible_status = wccon_subgroup_is_compatible( $subgroup_products, $cp_data );

								$sub_compatible_icon = true;
								if ( true === $sub_compatible_status ) {
									$sub_compatible_class = 'compatible';
									$sub_compatible_icon  = true;
								} elseif ( false === $sub_compatible_status ) {
									$sub_compatible_class = 'incompatible';
									$sub_compatible_icon  = false;
									// $tippy_text           = wccon_component_tippy_text();
								}
							} else {
								if ( ! empty( $subgroup_products ) ) {
									$sub_compatible_class = 'compatible';
								}

								$sub_compatible_icon = true;
							}
							?>
							<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $subgroup_classes ) ) ); ?>" data-component-id="<?php echo esc_attr( $component['id'] ); ?>" data-component-slug="<?php echo esc_attr( $component['slug'] ); ?>" <?php echo esc_attr( isset( $component['meta']['multiple'] ) && $component['meta']['multiple'] ) ? 'data-multiple="1"' : ''; ?>>
								<div class="wccon-component-inner">
									<div class="wccon-component__title">
										<div class="wccon-component__selected <?php echo esc_attr( $sub_compatible_class ); ?>" title="<?php echo esc_attr( $component['title'] ); ?>">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
											<?php if ( true === $sub_compatible_icon ) : ?>
													<use xlink:href="#icon-check"></use>
													<?php elseif ( false === $sub_compatible_icon ) : ?>
														<use xlink:href="#icon-uncheck"></use>
														<?php endif; ?>
											</svg>
										</div>
										<span><?php echo esc_html( $component['title'] ); ?></span>
										<?php if ( $component['meta']['description'] ) : ?>
												<div class="wccon-tooltip" data-tippy="<?php echo esc_attr( wp_kses_post( $component['meta']['description'] ) ); ?>">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25">
														<use xlink:href="#wccon-tip"></use>
													</svg>
											
												</div>
												
											<?php endif; ?>
									</div>
									<div class="wccon-component__icon" style="<?php echo esc_attr( $subgroup_has_products ? 'display:none;' : '' ); ?>">
										<img src="<?php echo esc_url( wp_get_attachment_image_url( $component['image_id'] ) ); ?>" srcset="<?php echo esc_attr( wp_get_attachment_image_srcset( $component['image_id'] ) ); ?>" alt="">
										<?php if ( isset( $component['components'] ) ) : ?>
											<div class="wccon-component__expand"><?php esc_html_e( '— Expand —', 'wccontour' ); ?></div>
											<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
											
										<?php else : ?>
											<div class="wccon-component__choose"><?php esc_html_e( '— Choose —', 'wccontour' ); ?></div>
											<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
										<?php endif; ?>
									</div>
									<div class="wccon-component__body">
										<?php if ( $subgroup_has_products ) : ?>
											<?php
											wc_get_template(
												'filled-subgroup.php',
												array(
													'products'  => $subgroup_products,
												),
												'wccontour',
												WCCON_PLUGIN_PATH . '/templates/'
											);
											?>

											<?php else : ?>
										<button class="wccon-choose-button wccon-collapse-group">
											<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
											</svg>
										</button>
										<?php endif; ?>
									</div>
								</div>
								<?php foreach ( $component['components'] as $subcomponent ) : ?>
									<?php
									 $is_multiiple              = isset( $subcomponent['meta']['multiple'] ) && $subcomponent['meta']['multiple'];
									 $subcomponent_has_products = false;
									 $cloned_subproducts        = array();
									 $component_single_classes  = $component_classes;
									 $tippy_text                = '';
									if ( isset( $subcomponent['products'] ) && ! empty( $subcomponent['products'] ) ) {

										$subcomponent_has_products = true;
										$extra_classes             = array( 'selected' );
										$product_id                = $subcomponent['products'][0]['variation_id'] ? $subcomponent['products'][0]['variation_id'] : $subcomponent['products'][0]['product_id'];
										$product_object            = wc_get_product( $product_id );
										if ( 'outofstock' === $product_object->get_stock_status() ) {
											$extra_classes[] = 'out-ofstock';
										}
										$component_single_classes = array_merge( $component_classes, $extra_classes );

									}
									$component_single_classes = apply_filters( 'wccon_component_classes', $component_single_classes, $subcomponent );

									$compatible_class = '';
									if ( $enabled_compatibility ) {
										$compatible_status = wccon_component_is_compatible( $subcomponent['slug'], $cp_data );

										$compatible_icon = true;
										if ( true === $compatible_status ) {
											$compatible_class = 'compatible';
											$compatible_icon  = true;
										} elseif ( false === $compatible_status ) {
											$compatible_class = 'incompatible';
											$compatible_icon  = false;
											$tippy_text       = wccon_component_tippy_text( $subcomponent['slug'], $cp_data );

										}
									} else {
										if ( $subcomponent_has_products ) {
											$compatible_class = 'compatible';
										}
										$compatible_icon = true;
									}
									?>
									<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $component_single_classes ) ) ); ?>" data-component-id="<?php echo esc_attr( $subcomponent['id'] ); ?>" data-component-slug="<?php echo esc_attr( $subcomponent['slug'] ); ?>" <?php echo esc_attr( $is_multiiple ) ? 'data-multiple="1"' : ''; ?> data-tippy="<?php echo esc_attr( $tippy_text ); ?>">
										<div class="wccon-component-inner">
											<div class="wccon-component__title">
												<div class="wccon-component__selected <?php echo esc_attr( $compatible_class ); ?>" title="<?php echo esc_attr( $subcomponent['title'] ); ?>">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
													<?php if ( true === $compatible_icon ) : ?>
													<use xlink:href="#icon-check"></use>
													<?php elseif ( false === $compatible_icon ) : ?>
														<use xlink:href="#icon-uncheck"></use>
														<?php endif; ?>
													</svg>
												</div>
												<span><?php echo esc_html( $subcomponent['title'] ); ?></span>
												<?php if ( $subcomponent['meta']['description'] ) : ?>
													<div class="wccon-tooltip" data-tippy="<?php echo esc_attr( wp_kses_post( $subcomponent['meta']['description'] ) ); ?>">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25">
															<use xlink:href="#wccon-tip"></use>
														</svg>
												
													</div>
													
												<?php endif; ?>
											</div>
											<div class="wccon-component__icon" style="<?php echo esc_attr( $subcomponent_has_products ? 'display:none;' : '' ); ?>">
												<img src="<?php echo esc_url( wp_get_attachment_image_url( $subcomponent['image_id'] ) ); ?>" srcset="<?php echo esc_attr( wp_get_attachment_image_srcset( $subcomponent['image_id'] ) ); ?>" alt="">
												<?php if ( isset( $subcomponent['components'] ) ) : ?>
													<div class="wccon-component__expand"><?php esc_html_e( '— Expand —', 'wccontour' ); ?></div>
													<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
												<?php else : ?>
													<div class="wccon-component__choose"><?php esc_html_e( '— Choose —', 'wccontour' ); ?></div>
													<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
												<?php endif; ?>
											</div>
											<div class="wccon-component__body">
											<?php if ( $subcomponent_has_products ) : ?>
												<?php
												wc_get_template(
													'filled-component.php',
													array(
														'product'  => $subcomponent['products'][0],
														'multiple' => $is_multiiple,
													),
													'wccontour',
													WCCON_PLUGIN_PATH . '/templates/'
												);
												$cloned_subproducts = array_shift( $subcomponent['products'] );
												?>

											<?php else : ?>
												<button class="wccon-choose-button">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
														<path fill="none" d="M0 0h24v24H0V0z" />
														<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z" />
													</svg>
												</button>
												<button class="wccon-collapse-button">
													<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
													</svg>
												</button>
												<?php endif; ?>
											</div>
										</div>
									</div>
									<?php if ( ! empty( $subcomponent['products'] ) ) : ?>
										<?php
										foreach ( $subcomponent['products'] as $cloned_key => $subcomponent_product ) :
											$product_id     = $subcomponent_product['variation_id'] ? $subcomponent_product['variation_id'] : $subcomponent_product['product_id'];
											$product_object = wc_get_product( $product_id );
											$extra_classes  = array( 'selected' );
											if ( 'outofstock' === $product_object->get_stock_status() ) {
												$extra_classes[] = 'out-ofstock';
											}
											$component_single_classes = array_merge( $component_classes, $extra_classes );
											$component_single_classes = apply_filters( 'wccon_component_classes', $component_single_classes, $subcomponent_product );
											$tippy_text               = '';
											if ( $enabled_compatibility ) {
												$compatible_status = wccon_component_is_compatible( $subcomponent['slug'], $cp_data, $subcomponent_product['clone'] );
												$compatible_class  = '';
												$compatible_icon   = true;
												if ( true === $compatible_status ) {
													$compatible_class = 'compatible';
													$compatible_icon  = true;
												} elseif ( false === $compatible_status ) {
													$compatible_class = 'incompatible';
													$compatible_icon  = false;
													$tippy_text       = wccon_component_tippy_text( $subcomponent['slug'], $cp_data, $subcomponent_product['clone'] );

												}
											} else {
												$compatible_class = 'compatible';
												$compatible_icon  = true;
											}
											?>
									<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $component_single_classes ) ) ); ?>" data-component-id="<?php echo esc_attr( $subcomponent['id'] ); ?>" data-component-slug="<?php echo esc_attr( $subcomponent['slug'] ); ?>" <?php echo esc_attr( $is_multiiple ) ? 'data-multiple="1"' : ''; ?> data-copy="<?php echo esc_attr( $subcomponent_product['clone'] ); ?>" data-tippy="<?php echo esc_attr( $tippy_text ); ?>">
										<div class="wccon-component-inner">
											<div class="wccon-component__title">
												<div class="wccon-component__selected <?php echo esc_attr( $compatible_class ); ?>" title="<?php echo esc_attr( $subcomponent['title'] ); ?>">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
													<?php if ( true === $compatible_icon ) : ?>
													<use xlink:href="#icon-check"></use>
													<?php elseif ( false === $compatible_icon ) : ?>
														<use xlink:href="#icon-uncheck"></use>
														<?php endif; ?>
													</svg>
												</div>
												<span><?php echo esc_html( $subcomponent['title'] ); ?></span>
												<?php if ( $subcomponent['meta']['description'] ) : ?>
													<div class="wccon-tooltip" data-tippy="<?php echo esc_attr( wp_kses_post( $subcomponent['meta']['description'] ) ); ?>">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25">
															<use xlink:href="#wccon-tip"></use>
														</svg>
												
													</div>
													
												<?php endif; ?>
											</div>
											<div class="wccon-component__icon" style="display:none;">
												<img src="<?php echo esc_url( wp_get_attachment_image_url( $subcomponent['image_id'] ) ); ?>" srcset="<?php echo esc_attr( wp_get_attachment_image_srcset( $subcomponent['image_id'] ) ); ?>" alt="">
												<?php if ( isset( $subcomponent['components'] ) ) : ?>
													<div class="wccon-component__expand"><?php esc_html_e( '— Expand —', 'wccontour' ); ?></div>
													<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
												<?php else : ?>
													<div class="wccon-component__choose"><?php esc_html_e( '— Choose —', 'wccontour' ); ?></div>
													<div class="wccon-component__collapse"><?php esc_html_e( '— Collapse —', 'wccontour' ); ?></div>
												<?php endif; ?>
											</div>
											<div class="wccon-component__body">
											<?php if ( $subcomponent_has_products ) : ?>
												<?php
												wc_get_template(
													'filled-component.php',
													array(
														'product'  => $subcomponent['products'][ $cloned_key ],
														'multiple' => $is_multiiple,
													),
													'wccontour',
													WCCON_PLUGIN_PATH . '/templates/'
												);

												?>

											<?php else : ?>
												<button class="wccon-choose-button">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
														<path fill="none" d="M0 0h24v24H0V0z" />
														<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z" />
													</svg>
												</button>
												<button class="wccon-collapse-button">
													<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
													</svg>
												</button>
												<?php endif; ?>
											</div>
										</div>
									</div>
											<?php endforeach; ?>
										<?php endif; ?>

								<?php endforeach; ?>
							</div>
						<?php endif; ?>

					<?php endforeach; ?>

				</div>

		</div>
	
		<?php endif; ?>
	<?php endforeach; ?>
	<?php
	$bottom_block_classes = array( 'wccon-bottom-block' );
	$bottom_block_sticky  = true;
	if ( $settings['style']['sticky_desktop'] ) {
		$bottom_block_classes[] = 'desktop_sticky';
		$bottom_block_sticky    = false;
	}
	if ( $settings['style']['sticky_tablet'] ) {
		$bottom_block_classes[] = 'tablet_sticky';
		$bottom_block_sticky    = false;
	}
	if ( $settings['style']['sticky_mobile'] ) {
		$bottom_block_classes[] = 'mobile_sticky';
		$bottom_block_sticky    = false;
	}
	if ( $bottom_block_sticky ) {
		$bottom_block_classes[] = 'no-sticky';
	}
	?>
	
	<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $bottom_block_classes ) ) ); ?>">
		
		<div class="wccon-bottom-container">
			<div class="wccon-total-price"><?php echo wp_kses_post( wc_price( $total_price ) ); ?></div>
			<button class="wccon-add-to-cart"><svg><use xlink:href="#iwccon-cart"></use></svg><?php esc_html_e( 'Add to cart', 'wccontour' ); ?></button>
			
		</div>
	</div>
</div>
<?php wc_get_template( 'icons.php', array(), 'wccontour', WCCON_PLUGIN_PATH . '/templates/' ); ?>
<?php wc_get_template( 'templates.php', array(), 'wccontour', WCCON_PLUGIN_PATH . '/templates/' ); ?>
