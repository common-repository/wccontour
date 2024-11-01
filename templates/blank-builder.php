<?php
/**
 * Blank Builder Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/blank-builder.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings      = wccon_get_settings();
$local_storage = $settings['local_storage'];

?>
<div class="wccon-builder-wrapper <?php echo esc_attr( $local_storage ? '' : 'wccon-builder-nostorage' ); ?>" data-wccon-builder="<?php echo esc_attr( $config_info['id'] ); ?>" data-wccon-scheme="<?php echo wc_esc_json( $data_attr ); ?>">
		
		<?php if ( $builder_title && $show_title ) : ?>
			<h3><?php echo wp_kses_post( $builder_title ); ?></h3>
		<?php endif; ?>
		
		<?php
		wc_get_template(
			'product-top-section.php',
			array(
				'data' => array_merge( $config_data, $config_info ),
				'id'   => $config_info['id'],
				'cp'   => false,
				'type' => 'raw',
			),
			'wccontour',
			WCCON_PLUGIN_PATH . '/templates/'
		);
		?>
		<?php foreach ( $config_data['groups'] as $group ) : ?>
			<div class="wccon-group" data-group="<?php echo esc_attr( $group['slug'] ); ?>">
				<div class="wccon-group-row">
					<h3><?php echo wp_kses_post( $group['title'] ); ?></h3>
					<?php
					if ( $group['meta']['description'] ) :
						?>
						<div class="wccon-tooltip" data-tippy="<?php echo esc_attr( $group['meta']['description'] ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25">
								<use xlink:href="#wccon-tip"></use>
							</svg>
					
						</div>
						
						<?php endif; ?>
				</div>
				
				<?php if ( ! empty( $group['components'] ) ) : ?>
					<div class="wccon-component-wrapper">
					<?php $extra_components = array(); ?>
						<?php foreach ( $group['components'] as $component ) : ?>
							<?php
								$component_is_extra = isset( $component['meta']['extra'] ) ? wc_string_to_bool( $component['meta']['extra'] ) : false;

							if ( $component_is_extra ) {
								array_push( $extra_components, $component );
								continue;
							}
								$component_classes = array( 'wccon-component' );
							if ( isset( $component['parent_id'] ) ) {
								$component_classes[] = 'wccon-component-has-children';
							}
								$component_classes = apply_filters( 'wccon_component_classes', $component_classes, $component );
							?>
							<?php if ( ! isset( $component['parent_id'] ) ) : ?>
								<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $component_classes ) ) ); ?>" data-component-id="<?php echo esc_attr( $component['id'] ); ?>" data-component-slug="<?php echo esc_attr( $component['slug'] ); ?>" <?php echo esc_attr( isset( $component['meta']['multiple'] ) && $component['meta']['multiple'] ) ? 'data-multiple="1"' : ''; ?>>
									<div class="wccon-component-inner">
										<div class="wccon-component__title">
											<div class="wccon-component__selected">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
													<use xlink:href="#icon-check"></use>
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
										<div class="wccon-component__icon">
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
										</div>
									</div>

									

								</div>
							<?php else : ?>
								<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $component_classes ) ) ); ?>" data-component-id="<?php echo esc_attr( $component['id'] ); ?>" data-component-slug="<?php echo esc_attr( $component['slug'] ); ?>" <?php echo esc_attr( isset( $component['meta']['multiple'] ) && $component['meta']['multiple'] ) ? 'data-multiple="1"' : ''; ?>>
									<div class="wccon-component-inner">
										<div class="wccon-component__title">
											<div class="wccon-component__selected">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
													<use xlink:href="#icon-check"></use>
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
										<div class="wccon-component__icon">
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
											<button class="wccon-choose-button wccon-collapse-group">
												<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512"><path d="M256 213.7l174.2 167.2c4.3 4.2 11.4 4.1 15.8-.2l30.6-29.9c4.4-4.3 4.5-11.3.2-15.5L264.1 131.1c-2.2-2.2-5.2-3.2-8.1-3-3-.1-5.9.9-8.1 3L35.2 335.3c-4.3 4.2-4.2 11.2.2 15.5L66 380.7c4.4 4.3 11.5 4.4 15.8.2L256 213.7z"/>
												</svg>
											</button>
									
										</div>
									</div>
									<?php foreach ( $component['components'] as $subcomponent ) : ?>
										
										<div class="wccon-component" data-component-id="<?php echo esc_attr( $subcomponent['id'] ); ?>" data-component-slug="<?php echo esc_attr( $subcomponent['slug'] ); ?>" <?php echo esc_attr( isset( $subcomponent['meta']['multiple'] ) && $subcomponent['meta']['multiple'] ) ? 'data-multiple="1"' : ''; ?>>
											<div class="wccon-component-inner">
												<div class="wccon-component__title">
													<div class="wccon-component__selected">
														<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
															<use xlink:href="#icon-check"></use>
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
												<div class="wccon-component__icon">
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
												</div>
											</div>

											

										</div>

									<?php endforeach; ?>
								</div>
							<?php endif; ?>

						<?php endforeach; ?>

						
					</div>
				<?php endif; ?>
			</div>

			
			<?php
		endforeach;
		?>

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
					<div class="wccon-total-price"></div>
					<button class="wccon-add-to-cart"><svg><use xlink:href="#iwccon-cart"></use></svg><?php esc_html_e( 'Add to cart', 'wccontour' ); ?></button>
					
				</div>
			</div>
		</div>
		<?php wc_get_template( 'icons.php', array(), 'wccontour', WCCON_PLUGIN_PATH . '/templates/' ); ?>
		<?php wc_get_template( 'templates.php', array(), 'wccontour', WCCON_PLUGIN_PATH . '/templates/' ); ?>
