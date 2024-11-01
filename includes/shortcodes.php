<?php
/**
 * Shortcode Class.
 *
 * Building shortcodes.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Shortcodes class.
 */
class WCCON_Shortcodes {

	use WCCON\Instancetiable;

	public function __construct() {
		add_shortcode( 'wccon-builder', array( $this, 'output' ) );
	}

	/**
	 * Render shortcode.
	 */
	public function output( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'         => 0,
				'list_id'    => 0,
				'title'      => '',
				'show_title' => true,
				'class'      => '',
			),
			$atts,
			'wccon-builder'
		);
		if ( is_admin() ) {
			return;
		}
		$settings  = wccon_get_settings();
		$config_id = absint( $atts['id'] );
		$list_id   = isset( $_GET['wccon-list'] ) ? absint( $_GET['wccon-list'] ) : 0;
		if ( $atts['list_id'] ) {
			$list_id = absint( $atts['list_id'] );
		}
		if ( is_wc_endpoint_url( $settings['account_endpoint'] ) ) {
			$query_var       = get_query_var( $settings['account_endpoint'] );
			$query_var_value = explode( '/', $query_var );

			if ( count( $query_var_value ) > 1 && 'edit' === $query_var_value[0] && absint( $query_var_value[1] ) > 0 ) {
				$list_id = absint( $query_var_value[1] );
			}
		}
		if ( $config_id < 1 ) {
			return;
		}

		global $wpdb;

		$data_lists      = WCCON_DB::tables( 'saved_lists', 'name' );
		$data_name       = WCCON_DB::tables( 'data', 'name' );
		$data_compo      = WCCON_DB::tables( 'components', 'name' );
		$data_compo_meta = WCCON_DB::tables( 'components_meta', 'name' );
		$data_group      = WCCON_DB::tables( 'groups', 'name' );
		$data_group_meta = WCCON_DB::tables( 'groups_meta', 'name' );
		$data_widgets    = WCCON_DB::tables( 'widgets', 'name' );

		$config_data = array();
		$list_data   = array();
		$total_price = 0;
		$config_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$data_name} WHERE shortcode_id=%d", $config_id ), ARRAY_A );

		if ( ! $config_info ) {
			return;
		}
		$shortcode_groups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$data_group} WHERE config_id=%d AND parent_id=0", (int) $config_info['id'] ), ARRAY_A );
		if ( $list_id ) {
			$saved_list = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$data_lists} WHERE id=%d AND shortcode_id=%d", $list_id, (int) $config_info['id'] ), ARRAY_A );

			if ( ! empty( $saved_list ) && isset( $saved_list['list_data'] ) ) {
				$list_data = maybe_unserialize( $saved_list['list_data'] );

			} else {
				return esc_html__( 'Not found', 'wccontour' );
			}
		}

		if ( ! $shortcode_groups ) {
			return;
		}

		$wccon_sheme = array();
		foreach ( $shortcode_groups as $key_group => $group ) {

			$config_data['groups'][ $key_group ]               = $group;
			$group_components                                  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$data_compo}  WHERE group_id=%d ", (int) $group['id'] ), ARRAY_A );
			$sub_groups                                        = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$data_group} WHERE parent_id=%d ", (int) $group['id'] ), ARRAY_A );
			$config_data['groups'][ $key_group ]['components'] = array();

			// group scheme.
			$wccon_sheme['groups'][ $key_group ] = array(
				'id'         => $group['id'],
				'slug'       => $group['slug'],
				'components' => array(),
				'position'   => $group['position'],
			);

			if ( ! empty( $group_components ) ) {
				$component_index = 0;
				foreach ( $group_components as $key_component => $component ) {
					$component_index++;
					$config_data['groups'][ $key_group ]['components'][ $key_component ] = array(
						'id'       => $component['id'],
						'slug'     => $component['slug'],
						'title'    => $component['title'],
						'image_id' => $component['image_id'],
						'position' => $component['position'],
					);

					// meta
					$components_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$data_compo_meta} WHERE component_id=%d ", $component['id'] ), ARRAY_A );
					if ( ! empty( $components_meta ) ) {
						$components_meta = wp_list_pluck( $components_meta, 'meta_value', 'meta_key' );
						array_walk(
							$components_meta,
							function ( &$meta_value, $meta_key ) {
								$meta_value = maybe_unserialize( $meta_value );
							}
						);
						$config_data['groups'][ $key_group ]['components'][ $key_component ]['meta'] = $components_meta;
					}

					$component_widgets = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$data_widgets} WHERE component_id=%d ", $component['id'] ), ARRAY_A );

					if ( ! is_null( $component_widgets ) ) {
						$config_data['groups'][ $key_group ]['components'][ $key_component ]['widgets'] = maybe_unserialize( $component_widgets['widget_value'] );
					} else {
						$config_data['groups'][ $key_group ]['components'][ $key_component ]['widgets'] = array();
					}

					// products.
					if ( ! empty( $list_data ) ) {
						$components_list = wp_list_pluck( $list_data['groups'], 'components' );
						$components_list = array_merge( ...$components_list );
						$found_products  = $this->found_component_products( $component['slug'], $components_list );

						$config_data['groups'][ $key_group ]['components'][ $key_component ]['products'] = $found_products;
						foreach ( $found_products as $found_product ) {
							$product_id     = $found_product['variation_id'] ? $found_product['variation_id'] : $found_product['product_id'];
							$product_object = wc_get_product( $product_id );

							$total_price += $product_object->get_price() * (int) $found_product['quantity'];
						}
					}

					// component scheme.
					$wccon_sheme['groups'][ $key_group ]['components'][ $key_component ] = array(
						'id'       => $component['id'],
						'slug'     => $component['slug'],
						'type'     => 'component',
						'products' => ! empty( $list_data ) ? $config_data['groups'][ $key_group ]['components'][ $key_component ]['products'] : array(),
						'multiple' => $components_meta['multiple'] ?? 0,
						'position' => $component['position'],
					);
				}
			}

			if ( ! empty( $sub_groups ) ) {
				foreach ( $sub_groups as $key_sub_group => $sub_group ) {

					$config_data['groups'][ $key_group ]['components'][ $component_index ] = wp_parse_args( array( 'parent_id' => $group['slug'] ), $sub_group );

					$subgroup_components = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$data_compo} WHERE group_id=%d ", (int) $sub_group['id'] ), ARRAY_A );

					// subgroup meta.
					$subgroup_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$data_group_meta} WHERE group_id=%d ", (int) $sub_group['id'] ), ARRAY_A );

					if ( ! empty( $subgroup_meta ) ) {
						$subgroup_meta = wp_list_pluck( $subgroup_meta, 'meta_value', 'meta_key' );
						array_walk(
							$subgroup_meta,
							function ( &$meta_value, $meta_key ) {
								$meta_value = maybe_unserialize( $meta_value );
							}
						);
						$config_data['groups'][ $key_group ]['components'][ $component_index ]['meta'] = $subgroup_meta;
					}
					// subgroup scheme.
					$wccon_sheme['groups'][ $key_group ]['components'][ $component_index ] = array(
						'id'       => $sub_group['id'],
						'type'     => 'subgroup',
						'slug'     => $sub_group['slug'],
						'multiple' => $subgroup_meta['multiple'] ?? 0,
						'position' => $sub_group['position'],
					);
					if ( ! empty( $subgroup_components ) ) {

						foreach ( $subgroup_components as $key_component => $component ) {

							$config_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ] = array(
								'id'       => $component['id'],
								'slug'     => $component['slug'],
								'title'    => $component['title'],
								'image_id' => $component['image_id'],
								'position' => $component['position'],
							);

							// subcomponent meta.
							$subcomponents_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$data_compo_meta} WHERE component_id=%d ", $component['id'] ), ARRAY_A );
							if ( ! empty( $subcomponents_meta ) ) {
								$subcomponents_meta = wp_list_pluck( $subcomponents_meta, 'meta_value', 'meta_key' );
								array_walk(
									$subcomponents_meta,
									function ( &$meta_value, $meta_key ) {
										$meta_value = maybe_unserialize( $meta_value );
									}
								);
								$config_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['meta'] = $subcomponents_meta;
							}

							// subcomponents widgets.
							$subcomponent_widgets = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$data_widgets} WHERE component_id=%d ", $component['id'] ), ARRAY_A );

							if ( ! is_null( $subcomponent_widgets ) ) {

								$config_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['widgets'] = maybe_unserialize( $subcomponent_widgets['widget_value'] );
							} else {
								$config_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['widgets'] = array();
							}

							// subcomponent products.
							if ( ! empty( $list_data ) ) {
								$components_list = wp_list_pluck( $list_data['groups'], 'components' );
								$components_list = array_merge( ...$components_list );
								$found_products  = $this->found_component_products( $component['slug'], $components_list );

								$config_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['products'] = $found_products;
								foreach ( $found_products as $found_product ) {
									$product_id     = $found_product['variation_id'] ? $found_product['variation_id'] : $found_product['product_id'];
									$product_object = wc_get_product( $product_id );
									$total_price   += $product_object->get_price() * (int) $found_product['quantity'];
								}
							}

							// subcomponents scheme.

							$wccon_sheme['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ] = array(
								'id'       => $component['id'],
								'type'     => 'subcomponent',
								'slug'     => $component['slug'],
								'products' => ! empty( $list_data ) ? $config_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['products'] : array(),
								'multiple' => $subcomponents_meta['multiple'] ?? 0,
								'position' => $component['position'],
							);
						}

						$sorted_subcomponents = array_values( $config_data['groups'][ $key_group ]['components'][ $component_index ]['components'] );

						uasort(
							$sorted_subcomponents,
							function ( $a, $b ) {

								if ( (int) $a['position'] === (int) $b['position'] ) {

									return 0;
								}
								return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
							}
						);

						$config_data['groups'][ $key_group ]['components'][ $component_index ]['components'] = array_values( $sorted_subcomponents );

						// sort scheme subcomponents.
						$sorted_scheme_subcomponents = array_values( $wccon_sheme['groups'][ $key_group ]['components'][ $component_index ]['components'] );
						uasort(
							$sorted_scheme_subcomponents,
							function ( $a, $b ) {

								if ( (int) $a['position'] === (int) $b['position'] ) {

									return 0;
								}
								return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
							}
						);
						$wccon_sheme['groups'][ $key_group ]['components'][ $component_index ]['components'] = array_values( $sorted_scheme_subcomponents );
					}

					$component_index++;

				}
			}
			// group meta.
			$group_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$data_group_meta} WHERE group_id=%d ", $group['id'] ), ARRAY_A );
			if ( ! empty( $group_meta ) ) {
				$group_meta = wp_list_pluck( $group_meta, 'meta_value', 'meta_key' );
				array_walk(
					$group_meta,
					function ( &$meta_value, $meta_key ) {
						$meta_value = maybe_unserialize( $meta_value );
					}
				);
				$config_data['groups'][ $key_group ]['meta'] = $group_meta;
			}

			$sorted_components = array_values( $config_data['groups'][ $key_group ]['components'] );

			uasort(
				$sorted_components,
				function ( $a, $b ) {

					if ( (int) $a['position'] === (int) $b['position'] ) {

						return 0;
					}
					return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
				}
			);

			$config_data['groups'][ $key_group ]['components'] = array_values( $sorted_components );

			// sort scheme components.
			$sorted_scheme_components = array_values( $wccon_sheme['groups'][ $key_group ]['components'] );

			uasort(
				$sorted_scheme_components,
				function ( $a, $b ) {

					if ( (int) $a['position'] === (int) $b['position'] ) {

						return 0;
					}
					return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
				}
			);

			$wccon_sheme['groups'][ $key_group ]['components'] = array_values( $sorted_scheme_components );
		}

		$sorted_groups = array_values( $config_data['groups'] );

		uasort(
			$sorted_groups,
			function ( $a, $b ) {

				if ( (int) $a['position'] === (int) $b['position'] ) {

					return 0;
				}
				return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
			}
		);

		$config_data['groups'] = array_values( $sorted_groups );

		// sort scheme groups.
		$sorted_scheme_groups = array_values( $wccon_sheme['groups'] );

		uasort(
			$sorted_scheme_groups,
			function ( $a, $b ) {

				if ( (int) $a['position'] === (int) $b['position'] ) {

					return 0;
				}
				return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
			}
		);

		$wccon_sheme['groups'] = array_values( $sorted_scheme_groups );

		wp_enqueue_style( 'wccon-toastr' );
		wp_enqueue_script( 'wccon-toastr' );
		wp_enqueue_style( 'wccon-slider' );
		wp_enqueue_script( 'wccon-slider' );
		wp_enqueue_script( 'wccon-popper' );
		wp_enqueue_script( 'wccon-tippy' );

		wp_enqueue_script( 'wccon-builder' );

		$data_json = wp_json_encode( $wccon_sheme );

		$use_template = 'blank-builder.php';
		if ( $list_id ) {
			$use_template = 'saved-builder.php';
		}
		ob_start();

		wc_get_template(
			$use_template,
			array(
				'config_info'   => $config_info,
				'config_data'   => $config_data,
				'data_attr'     => $data_json,
				'total_price'   => $total_price,
				'builder_class' => $atts['class'],
				'builder_title' => $atts['title'],
				'show_title'    => wc_string_to_bool( $atts['show_title'] ),
			),
			'wccontour',
			WCCON_PLUGIN_PATH . '/templates/'
		);

		return ob_get_clean();
	}

	/**
	 * Get products from component data based on component slug.
	 */
	public function found_component_products( $slug, $components_list ) {
		foreach ( $components_list as $component ) {

			if ( $component['slug'] === $slug ) {
				if ( isset( $component['products'] ) && empty( $component['products'] ) ) {
					return array();
				}
				$new_component_products = array();

				foreach ( $component['products'] as $key => $component_product ) {

					$product_id     = $component_product['variation_id'] ? $component_product['variation_id'] : $component_product['product_id'];
					$product_object = wc_get_product( absint( $product_id ) );
					if ( ! $product_object ) {
						continue;
					}
					$new_component_products['products'][ $key ]          = $component_product;
					$new_component_products['products'][ $key ]['price'] = $product_object->get_price();
				}

				return $new_component_products['products'];
			} elseif ( is_array( $component ) && isset( $component['components'] ) ) {

				$found = $this->found_component_products( $slug, $component['components'] );
				if ( ! empty( $found ) ) {
					return $found;
				}
			}
		}
		return array();
	}
}
WCCON_Shortcodes::instance();
