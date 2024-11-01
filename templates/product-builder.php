<?php
/**
 * Product Builder Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/product-builder.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wccon-product-list" >
	<?php
	$query_args = array();

	$query_args['product_query'] = $component['meta']['product_query'] ?? array();
	$query_args                  = apply_filters( 'wccon_product_query_args', $query_args );

	$wcc_query = new WCCON_Product_Query( $query_args );
	$query     = $wcc_query->query();

	?>
	<aside class="wccon-scrl-container">
	<?php do_action( 'wccon_sidebar_products_start', $component ); ?>
	<div class="wccon-clear-block">
		<button class="wccon-clear-all">
			<svg viewBox="0 0 9 8" xmlns="http://www.w3.org/2000/svg">
				<path d="M5.95 4l2.852 2.852a.673.673 0 0 1-.949.953l-.002-.002L4.999 4.95 2.147 7.803a.673.673 0 0 1-.948.002l-.003-.002a.673.673 0 0 1 0-.95L4.05 4 1.195 1.15a.673.673 0 1 1 .95-.952L5 3.049 7.85.197a.673.673 0 0 1 .95.95L5.95 4z"></path>
			</svg>
			<?php esc_html_e( 'Clear all', 'wccontour' ); ?>
		</button>
		<button class="wccon-close-filters">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="close"><path d="M13.41,12l6.3-6.29a1,1,0,1,0-1.42-1.42L12,10.59,5.71,4.29A1,1,0,0,0,4.29,5.71L10.59,12l-6.3,6.29a1,1,0,0,0,0,1.42,1,1,0,0,0,1.42,0L12,13.41l6.29,6.3a1,1,0,0,0,1.42,0,1,1,0,0,0,0-1.42Z"></path></svg>
		</button>
	</div>
		<form action="" id="wccon-filter-form">
		<?php do_action( 'wccon_filter_widgets_start', $component ); ?>

		<?php

		$default_locations = wccon_valid_component_locations();
		$valid_location    = in_array( 'product_tax', $default_locations, true );
		if ( ! empty( $component['widgets'] ) ) :


			$tax_widgets = array_filter(
				$component['widgets'],
				function( $el ) {
					return $el['groupId'] === 'product_tax' && $el['value'] !== '';
				}
			);
			foreach ( $component['widgets'] as $widget ) {
				switch ( $widget['groupId'] ) {
					case 'product_tax':
						if ( $widget['value'] === '' ) {
							break;
						}
						the_widget(
							'WCCON_Widget_Taxonomies',
							array(
								'title'         => $widget['label'],
								'taxonomy'      => $widget['value'],
								'include'       => $widget['include'],
								'exclude'       => $widget['exclude'],
								'depth'         => $widget['depth'],
								'orderby'       => $widget['orderby'],
								'show_children' => $widget['show_children'],
								'query_args'    => $query_args,
								'wcc_object'    => $wcc_query,
							)
						);
						break;
					case 'product_attribute':
						if ( $widget['value'] === '' ) {
							break;
						}
						the_widget(
							'WCCON_Widget_Attributes',
							array(
								'title'      => $widget['label'],
								'taxonomy'   => $widget['value'],
								'query_args' => $query_args,
								'wcc_object' => $wcc_query,
							)
						);
						break;

					case 'price':
						the_widget(
							'WCCON_Widget_Price',
							array(
								'title'      => $widget['label'],
								'inputs'     => $widget['inputs'],
								'query_args' => $query_args,
								'wcc_object' => $wcc_query,

							)
						);
						break;
					case 'product_meta':
						if ( $widget['value'] === '' ) {
							break;
						}
						the_widget(
							'WCCON_Widget_Meta',
							array(
								'title'      => $widget['label'],
								'meta_key'   => $widget['value'],
								'meta_value' => $widget['meta_value'],

								'query_args' => $query_args,
								'wcc_object' => $wcc_query,
							)
						);
						break;


				}
			}
		endif;
		?>
		<?php do_action( 'wccon_filter_widgets_end', $component ); ?>
		</form>
	</aside>
	<div class="wccon-products-part">
		
		<?php do_action( 'wccon_product_list_topbar_before' ); ?>
		<div class="wccon-products__filters">
			<button class="wccon-filters-btn">
				<svg><use xlink:href="#wccon-filters"></use></svg>
				<span><?php echo wp_kses_post( apply_filters( 'wccon_filters_button_text', esc_html__( 'Filters', 'wccontour' ) ) ); ?></span>
			</button>
		</div>
		<div class="wccon-products__topbar">
			<?php
			/**
			 * @hooked wcc_product_list_orderby - 5
			 * @hooked wcc_product_list_available - 10
			 * @hooked wcc_product_list_search - 15
			 */
			do_action( 'wccon_product_list_topbar' );
			?>
		</div>
		<?php do_action( 'wccon_product_list_topbar_after' ); ?>

		<?php

		echo '<div class="wccon-products-body wccon-scrl-container">';

		if ( $query->have_posts() ) :
			echo '<div class="wccon-product-items">';


			while ( $query->have_posts() ) :
				$query->the_post();

				wc_get_template(
					'product-list.php',
					array(
						'term_ids'          => $term_ids ?? array(),
						'selected_products' => $component['selected_products'],
					),
					'wccontour',
					WCCON_PLUGIN_PATH . '/templates/'
				);
			endwhile;
			echo '</div>';

			echo wp_kses_post( wccon_product_pagination( $query->query_vars['paged'], $query->max_num_pages ) ); // WPCS: XSS ok.
			wp_reset_postdata();

		else :
			echo '<div class="wccon-product-items"><div class="wccon-no-products"><p>' . wp_kses_post( apply_filters( 'wccon_no_products_text', __( 'Nothing found', 'wccontour' ) ) ) . '</p></div></div>';
		endif;
		echo '</div>';
		?>
	</div>
</div>
