<?php
/**
 * AJAX Class
 *
 * Handles AJAX requests.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Ajax class.
 */
class WCCON_Ajax {
	use WCCON\Instancetiable;
	public function __construct() {

		// back
		add_action( 'wp_ajax_wccon_save_builder', array( $this, 'save' ) );

		add_action( 'wp_ajax_wccon_remove_builder', array( $this, 'remove' ) );
		add_action( 'wp_ajax_wccon_product_tax', array( $this, 'async_select_taxonomies' ) );
		add_action( 'wp_ajax_wccon_product_attribute', array( $this, 'async_select_attributes' ) );

		add_action( 'wp_ajax_wccon_all_product_tax', array( $this, 'async_select_all_taxonomies' ) );
		add_action( 'wp_ajax_wccon_all_products', array( $this, 'async_select_all_products' ) );
		add_action( 'wp_ajax_wccon_all_pages', array( $this, 'async_select_all_pages' ) );

		add_action( 'wp_ajax_wccon_update_list_item', array( $this, 'update_list_item' ) );
		add_action( 'wp_ajax_wccon_save_settings', array( $this, 'save_settings' ) );

		// front
		add_action( 'wp_ajax_wccon_component_products', array( $this, 'get_component_products' ) );
		add_action( 'wp_ajax_nopriv_wccon_component_products', array( $this, 'get_component_products' ) );
		add_action( 'wp_ajax_wccon_filter_builder', array( $this, 'handle_filter_builder' ) );
		add_action( 'wp_ajax_nopriv_wccon_filter_builder', array( $this, 'handle_filter_builder' ) );

		add_action( 'wp_ajax_wccon_buy_product', array( $this, 'buy_product' ) );
		add_action( 'wp_ajax_nopriv_wccon_buy_product', array( $this, 'buy_product' ) );

		add_action( 'wp_ajax_wccon_add_product', array( $this, 'add_product' ) );
		add_action( 'wp_ajax_nopriv_wccon_add_product', array( $this, 'add_product' ) );

		add_action( 'wp_ajax_wccon_remove_product', array( $this, 'remove_product' ) );
		add_action( 'wp_ajax_nopriv_wccon_remove_product', array( $this, 'remove_product' ) );

		// load list
		add_action( 'wp_ajax_wccon_load_list', array( $this, 'load_list' ) );
		add_action( 'wp_ajax_nopriv_wccon_load_list', array( $this, 'load_list' ) );

		// buy list products
		add_action( 'wp_ajax_wccon_buy_list', array( $this, 'buy_list_products' ) );
		add_action( 'wp_ajax_nopriv_wccon_buy_list', array( $this, 'buy_list_products' ) );

		// save list
		add_action( 'wp_ajax_wccon_save_list', array( $this, 'save_list' ) );

		// remove list
		add_action( 'wp_ajax_wccon_remove_list', array( $this, 'remove_list' ) );

		// users lists.
		add_action( 'wp_ajax_wccon_users_list', array( $this, 'users_list' ) );
		add_action( 'wp_ajax_nopriv_wccon_users_list', array( $this, 'users_list' ) );

		// user's list.
		add_action( 'wp_ajax_wccon_user_list', array( $this, 'user_list' ) );
		add_action( 'wp_ajax_nopriv_wccon_user_list', array( $this, 'user_list' ) );

		// load more
		add_action( 'wp_ajax_wccon_load_more', array( $this, 'load_more' ) );
		add_action( 'wp_ajax_nopriv_wccon_load_more', array( $this, 'load_more' ) );

		// delete all plugin data with tables.
		add_action( 'wp_ajax_wccon_delete_all_data', array( $this, 'delete_plugin_data' ) );

		// test wpml.
		add_action( 'wp_ajax_test_wpml_wccon', array( $this, 'test_wpml' ) );

	}

	/**
	 * Save builder from admin page.
	 */
	public function save() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$data            = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );
		$shortcode_id    = (int) $data['shortcode_id'];
		$shortcode_db_id = (int) $data['id'];

		global $wpdb;

		$config_table      = WCCON_DB::tables( 'data', 'name' );
		$groups_table      = WCCON_DB::tables( 'groups', 'name' );
		$groups_meta_table = WCCON_DB::tables( 'groups_meta', 'name' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- using table name as a variable is acceptable.
		$shortcode_exists = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table} WHERE shortcode_id=%d", $shortcode_id ), ARRAY_A );

		// update & insert & delete.
		if ( $shortcode_exists ) {
			if ( ! $shortcode_db_id ) {
				wp_send_json_error(); // ????? REMOVE THIS
			}
			$wpdb->update(
				$config_table,
				array(
					'shortcode_id' => $shortcode_id,
					'title'        => $data['title'],
					'type'         => sanitize_title( $data['type'] ),
					'page_id'      => $data['page_id'],
					'lang'         => $data['lang'],

				),
				array(
					'id' => $shortcode_db_id,
				),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%s',
				)
			);

			$existing_groups     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE config_id=%d AND parent_id=0 ", $shortcode_db_id ), ARRAY_A );
			$existing_groups_ids = wp_list_pluck( $existing_groups, 'id' );
			if ( ! empty( $data['groups'] ) ) {

				foreach ( $data['groups'] as $group ) {
					if ( in_array( $group['id'], $existing_groups_ids ) ) {
						$wpdb->update(
							$groups_table,
							array(
								'title'    => $group['title'],
								'image_id' => (int) $group['image_id'],
								'position' => (int) $group['position'],
							),
							array( 'id' => (int) $group['id'] ),
							array( '%s', '%d', '%d' )
						);

						// update components.
						wccon_update_components( $group, $shortcode_db_id, (int) $group['id'] );
					} else {
						$wpdb->insert(
							$groups_table,
							array(
								'config_id' => $shortcode_db_id,
								'title'     => $group['title'],
								'slug'      => $group['slug'],
								'image_id'  => (int) $group['image_id'],
								'position'  => (int) $group['position'],

							),
							array(
								'%d',
								'%s',
								'%s',
								'%d',
								'%d',
							)
						);
						$group_id = $wpdb->insert_id;

						// insert components.
						wccon_store_components( $group, $shortcode_db_id, $group_id );
					}

					// meta groups
					foreach ( $group['meta'] as $meta_key => $meta_value ) {
						$group_id         = (int) $group['id'] ? (int) $group['id'] : $group_id;
						$existing_meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$groups_meta_table} WHERE group_id=%d AND meta_key=%s", $group_id, $meta_key ) );

						$wpdb->replace(
							$groups_meta_table,
							array(
								'id'         => $existing_meta_id,
								'group_id'   => $group_id,
								'meta_key'   => $meta_key,
								'meta_value' => $meta_value,

							),
							array(
								'%d',
								'%d',
								'%s',
								'%s',

							)
						);

					}
				}
			}
			$current_group_ids = wp_list_pluck( $data['groups'], 'id' );
			foreach ( $existing_groups as $group ) {
				if ( ! in_array( $group['id'], $current_group_ids ) ) {

					$wpdb->delete( $groups_table, array( 'id' => (int) $group['id'] ), array( '%d' ) );
				}
			}
		}
		// insert.
		else {
			$wpdb->insert(
				$config_table,
				array(
					'shortcode_id' => $shortcode_id,
					'title'        => $data['title'],
					'type'         => sanitize_title( $data['type'] ),
					'page_id'      => $data['page_id'],
					'lang'         => $data['lang'],

				),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%s',
				)
			);
			$config_id = $wpdb->insert_id;
			if ( ! empty( $data['groups'] ) ) {
				foreach ( $data['groups'] as $group ) {
					$wpdb->insert(
						$groups_table,
						array(
							'config_id' => $config_id,
							'title'     => $group['title'],
							'slug'      => $group['slug'],
							'image_id'  => (int) $group['image_id'],
							'position'  => (int) $group['position'],

						),
						array(
							'%d',
							'%s',
							'%s',
							'%d',
							'%d',
						)
					);
					$group_id = $wpdb->insert_id;
					// components.
					wccon_store_components( $group, $config_id, $group_id );

					// meta groups.
					foreach ( $group['meta'] as $meta_key => $meta_value ) {
						$wpdb->insert(
							$groups_meta_table,
							array(
								'group_id'   => $group_id,
								'meta_key'   => $meta_key,
								'meta_value' => $meta_value,

							),
							array(
								'%d',
								'%s',
								'%s',

							)
						);
					}
				}
			}
		}

		wp_send_json_success(
			array(
				'data'     => $data,
				'new_data' => $this->get_shortcodes_data_by_id( $shortcode_id ),
			)
		);
	}

	/**
	 * Helper function to get builder items by id.
	 */
	public function get_shortcodes_data_by_id( $shortcode_id ) {

		global $wpdb;

		$config_table      = WCCON_DB::tables( 'data', 'name' );
		$components_table  = WCCON_DB::tables( 'components', 'name' );
		$compo_meta_table  = WCCON_DB::tables( 'components_meta', 'name' );
		$groups_table      = WCCON_DB::tables( 'groups', 'name' );
		$groups_meta_table = WCCON_DB::tables( 'groups_meta', 'name' );
		$widgets_table     = WCCON_DB::tables( 'widgets', 'name' );

		$shortcodes_data = array();
		$shortcode       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table} WHERE shortcode_id=%d", $shortcode_id ), ARRAY_A );

		if ( $shortcode ) {

			   $shortcodes_data  = $shortcode;
			   $shortcode_groups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE config_id=%d AND parent_id=0", (int) $shortcode['id'] ), ARRAY_A );

			if ( ! empty( $shortcode_groups ) ) {

				foreach ( $shortcode_groups as $key_group => $group ) {

					$shortcodes_data['groups'][ $key_group ] = $group;
					$group_components                        = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$components_table}  WHERE group_id=%d ", (int) $group['id'] ), ARRAY_A );
					$sub_groups                              = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE parent_id=%d ", (int) $group['id'] ), ARRAY_A );
					$shortcodes_data['groups'][ $key_group ]['components'] = array();
					if ( ! empty( $group_components ) ) {
						$component_index = 0;
						foreach ( $group_components as $key_component => $component ) {
							$component_index++;
							$shortcodes_data['groups'][ $key_group ]['components'][ $key_component ] = array(
								'id'       => $component['id'],
								'slug'     => $component['slug'],
								'title'    => $component['title'],
								'image_id' => $component['image_id'],
								'position' => $component['position'],
							);
							// meta
							$components_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$compo_meta_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );
							if ( ! empty( $components_meta ) ) {
								$components_meta = wp_list_pluck( $components_meta, 'meta_value', 'meta_key' );
								array_walk(
									$components_meta,
									function ( &$meta_value, $meta_key ) {
										$meta_value = maybe_unserialize( $meta_value );
									}
								);
								$shortcodes_data['groups'][ $key_group ]['components'][ $key_component ]['meta'] = $components_meta;
							}
							// widgets.
							$component_widgets = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$widgets_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );

							if ( ! is_null( $component_widgets ) ) {
								$shortcodes_data['groups'][ $key_group ]['components'][ $key_component ]['widgets'] = maybe_unserialize( $component_widgets['widget_value'] );
							} else {
								$shortcodes_data['groups'][ $key_group ]['components'][ $key_component ]['widgets'] = array();
							}
						}
					}

					if ( ! empty( $sub_groups ) ) {
						foreach ( $sub_groups as $key_sub_group => $sub_group ) {

							$shortcodes_data['groups'][ $key_group ]['components'][ $component_index ] = wp_parse_args( array( 'parent_id' => $group['slug'] ), $sub_group );

							$subgroup_components = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$components_table} WHERE group_id=%d ", (int) $sub_group['id'] ), ARRAY_A );

							if ( ! empty( $subgroup_components ) ) {
								foreach ( $subgroup_components as $key_component => $component ) {

									$shortcodes_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ] = array(
										'id'       => $component['id'],
										'slug'     => $component['slug'],
										'title'    => $component['title'],
										'image_id' => $component['image_id'],
										'position' => $component['position'],
									);

									// subcomponents meta.
									$subcomponents_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$compo_meta_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );
									if ( ! empty( $subcomponents_meta ) ) {
										$subcomponents_meta = wp_list_pluck( $subcomponents_meta, 'meta_value', 'meta_key' );
										array_walk(
											$subcomponents_meta,
											function ( &$meta_value, $meta_key ) {
												$meta_value = maybe_unserialize( $meta_value );
											}
										);
										$shortcodes_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['meta'] = $subcomponents_meta;
									}

									// subcomponent widgets.
									$subcomponent_widgets = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$widgets_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );

									if ( ! is_null( $subcomponent_widgets ) ) {

										$shortcodes_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['widgets'] = maybe_unserialize( $subcomponent_widgets['widget_value'] );
									} else {
										$shortcodes_data['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['widgets'] = array();
									}
								}

								$sorted_subcomponents = array_values( $shortcodes_data['groups'][ $key_group ]['components'][ $component_index ]['components'] );

								uasort(
									$sorted_subcomponents,
									function ( $a, $b ) {

										if ( (int) $a['position'] === (int) $b['position'] ) {

											return 0;
										}
										return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
									}
								);

								$shortcodes_data['groups'][ $key_group ]['components'][ $component_index ]['components'] = array_values( $sorted_subcomponents );
							}

							$subgroup_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$groups_meta_table} WHERE group_id=%d ", (int) $sub_group['id'] ), ARRAY_A );
							if ( ! empty( $subgroup_meta ) ) {
								$subgroup_meta = wp_list_pluck( $subgroup_meta, 'meta_value', 'meta_key' );
								array_walk(
									$subgroup_meta,
									function ( &$meta_value, $meta_key ) {
										$meta_value = maybe_unserialize( $meta_value );
									}
								);
								$shortcodes_data['groups'][ $key_group ]['components'][ $component_index ]['meta'] = $subgroup_meta;
							}

							$component_index++;
						}
					}
					// group meta.
					$group_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$groups_meta_table} WHERE group_id=%d ", $group['id'] ), ARRAY_A );
					if ( ! empty( $group_meta ) ) {
						$group_meta = wp_list_pluck( $group_meta, 'meta_value', 'meta_key' );
						array_walk(
							$group_meta,
							function ( &$meta_value, $meta_key ) {
								$meta_value = maybe_unserialize( $meta_value );
							}
						);
						$shortcodes_data['groups'][ $key_group ]['meta'] = $group_meta;
					}

					$sorted_components = array_values( $shortcodes_data['groups'][ $key_group ]['components'] );

					uasort(
						$sorted_components,
						function ( $a, $b ) {

							if ( (int) $a['position'] === (int) $b['position'] ) {

								return 0;
							}
							return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
						}
					);

					$shortcodes_data['groups'][ $key_group ]['components'] = array_values( $sorted_components );
				}
				$sorted_groups = array_values( $shortcodes_data['groups'] );

				uasort(
					$sorted_groups,
					function ( $a, $b ) {

						if ( (int) $a['position'] === (int) $b['position'] ) {

							return 0;
						}
						return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
					}
				);

				$shortcodes_data['groups'] = array_values( $sorted_groups );
			}
		}
		return $shortcodes_data;
	}

	/**
	 * Delete builder item from admin.
	 */
	public function remove() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$shortcode_id = absint( wp_unslash( $_POST['id'] ) );

		if ( $shortcode_id < 1 ) {
			wp_send_json_error();
		}

		global $wpdb;

		$config_table = WCCON_DB::tables( 'data', 'name' );

		$shortcode_exists = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table} WHERE id=%d", $shortcode_id ), ARRAY_A );

		if ( $shortcode_exists ) {
			$wpdb->delete( $config_table, array( 'id' => $shortcode_id ), array( '%d' ) );
		}

		wp_send_json_success(
			array(
				'data' => $shortcode_id,
			)
		);
	}

	/**
	 * Get products list with extra data via ajax.
	 */
	public function async_select_taxonomies() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		if ( ! isset( $_POST['field'] ) ) {
			wp_send_json_error();
		}
		$user_field = sanitize_text_field( wp_unslash( $_POST['field'] ) );

		$lang = false;

		if ( isset( $_POST['lang'] ) ) {
			$lang = sanitize_text_field( wp_unslash( $_POST['lang'] ) );
		}

		$product_taxonomies = array_merge( ...wp_list_pluck( wccon_get_product_taxonomies( $lang ), 'children' ) );

		$found_elements = wccon_search_term( $product_taxonomies, $user_field );

		wp_send_json_success(
			array(
				'fields' => $found_elements,

			)
		);
	}

	/**
	 * Get attributes list with extra data via ajax.
	 */
	public function async_select_attributes() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		if ( ! isset( $_POST['field'] ) ) {
			wp_send_json_error();
		}
		$user_field = sanitize_text_field( wp_unslash( $_POST['field'] ) );

		$lang = false;

		if ( isset( $_POST['lang'] ) ) {
			$lang = sanitize_text_field( wp_unslash( $_POST['lang'] ) );
		}

		$product_attributes = array_merge( ...wp_list_pluck( wccon_get_product_attributes( $lang ), 'children' ) );

		$found_elements = wccon_search_term( $product_attributes, $user_field );

		wp_send_json_success(
			array(
				'fields' => $found_elements,

			)
		);
	}

	/**
	 * Get all taxonomies list with extra data via ajax.
	 */
	public function async_select_all_taxonomies() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		if ( ! isset( $_POST['field'] ) ) {
			wp_send_json_error();
		}
		$all  = false;
		$lang = false;
		if ( isset( $_POST['all'] ) ) {
			$all = true;
		}
		if ( isset( $_POST['lang'] ) ) {
			$lang = sanitize_text_field( wp_unslash( $_POST['lang'] ) );
		}
		$user_field = sanitize_text_field( wp_unslash( $_POST['field'] ) );
		if ( $all ) {
			$product_taxonomies = array_merge(
				array_map(
					function ( $el ) {
						$returned_array  = array(
							'label' => $el['label'],
							'value' => $el['value'],
						);
						$returned_object = (object) $returned_array;
						return $returned_object;
					},
					wccon_get_all_product_taxonomies( $lang )
				),
				array_merge( ...wp_list_pluck( wccon_get_all_product_taxonomies( $lang ), 'children' ) )
			);
		} else {
			$product_taxonomies = array_merge( ...wp_list_pluck( wccon_get_all_product_taxonomies( $lang ), 'children' ) );
		}

		$found_elements = wccon_search_term( $product_taxonomies, $user_field, $all );

		wp_send_json_success(
			array(
				'fields' => $found_elements,
				'tax'    => wccon_get_all_product_taxonomies( $lang ),
			)
		);
	}

	/**
	 * Get all products list with extra data via ajax.
	 */
	public function async_select_all_products() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		if ( ! isset( $_POST['field'] ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		$user_field = sanitize_text_field( wp_unslash( $_POST['field'] ) );

		$product = '%' . $wpdb->esc_like( $user_field ) . '%';
		$result  = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM $wpdb->posts WHERE post_type IN ('product', 'product_variation') AND post_status = 'publish' AND post_title LIKE %s ", $product ), ARRAY_A );

		wp_send_json_success(
			array(
				'fields' => $result,

			)
		);
	}

	/**
	 * Get all pages list via ajax.
	 */
	public function async_select_all_pages() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		if ( ! isset( $_POST['field'] ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		$user_field = sanitize_text_field( wp_unslash( $_POST['field'] ) );

		$page   = '%' . $wpdb->esc_like( $user_field ) . '%';
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM $wpdb->posts WHERE post_type='page' AND post_status = 'publish' AND post_title LIKE %s ", $page ), ARRAY_A );

		wp_send_json_success(
			array(
				'fields' => $result,

			)
		);
	}

	/**
	 * Filter product list on frontend.
	 */
	public function handle_filter_builder() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$term_ids          = array();
		$prices            = array();
		$metas             = array();
		$tax_data          = array();
		$search            = '';
		$page_link         = 1;
		$selected_products = array();
		if ( isset( $_POST['prices'] ) && ! empty( $_POST['prices'] ) ) {
			$prices = array_map( 'floatval', explode( ',', wc_clean( wp_unslash( $_POST['prices'] ) ) ) );
		}
		if ( isset( $_POST['tax_query'] ) && ! empty( $_POST['tax_query'] ) ) {
			// $term_ids = array_map( 'absint', explode( ',', wc_clean( wp_unslash( $_POST['tax_query'] ) ) ) );
			$tax_data = wc_clean( json_decode( wp_unslash( $_POST['tax_query'] ), true ) );
		}

		if ( isset( $_POST['meta_query'] ) ) {
			$metas = wc_clean( json_decode( wp_unslash( $_POST['meta_query'] ), true ) );
		}
		if ( isset( $_POST['pagination'] ) ) {
			$page_link = wc_clean( wp_unslash( $_POST['pagination'] ) );
		}
		if ( isset( $_POST['search'] ) ) {
			$search = trim( wc_clean( wp_unslash( $_POST['search'] ) ) );
		}
		if ( isset( $_POST['selected_products'] ) ) {
			$selected_products = wc_clean( json_decode( wp_unslash( $_POST['selected_products'] ), true ) );
		}

		$component_id = absint( wp_unslash( $_POST['component_id'] ) );
		if ( ! $component_id ) {
			wp_send_json_error();
		}
		global $wpdb;
		$compo_meta_table = WCCON_DB::tables( 'components_meta', 'name' );

		$compo_table       = WCCON_DB::tables( 'components', 'name' );
		$widgets_table     = WCCON_DB::tables( 'widgets', 'name' );
		$component         = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$compo_table} WHERE id=%d ", $component_id ), ARRAY_A );
		$components_meta   = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$compo_meta_table} WHERE component_id=%d ", $component_id ), ARRAY_A );
		$component['meta'] = array();
		if ( ! empty( $components_meta ) ) {
			$components_meta = wp_list_pluck( $components_meta, 'meta_value', 'meta_key' );
			array_walk(
				$components_meta,
				function ( &$meta_value, $meta_key ) {
					$meta_value = maybe_unserialize( $meta_value );
				}
			);
			$component['meta'] = $components_meta;
		}

		$component_widgets    = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$widgets_table} WHERE component_id=%d ", $component_id ), ARRAY_A );
		$component['widgets'] = array();
		if ( ! is_null( $component_widgets ) ) {
			$component['widgets'] = maybe_unserialize( $component_widgets['widget_value'] );
		}

		// add selected products.
		$component['selected_products'] = $selected_products;
		$add_active_filters             = function( $query_args ) use ( $tax_data, $prices, $metas, $search ) {
			$query_args['active'] = array();

			if ( ! empty( $tax_data ) ) {
				$query_args['active']['tax_query'] = $tax_data;
			}
			if ( ! empty( $metas ) ) {
				$query_args['active']['meta_query'] = $metas;
			}
			if ( ! empty( $prices ) && count( $prices ) === 2 ) {
				$query_args['active']['prices'] = $prices;
			}
			if ( ! empty( $search ) ) {
				$query_args['active']['search'] = $search;
			}
			$query_args['active']['orderby'] = wc_clean( wp_unslash( $_POST['orderby'] ) );
			$query_args['active']['stock']   = wc_clean( wp_unslash( $_POST['stock'] ) );
			return $query_args;
		};
		$add_query_vars                 = function( $query_vars ) use ( $page_link ) {
			$query_vars['paged'] = $page_link;
			return $query_vars;
		};
		add_filter( 'wccon_product_query_args', $add_active_filters );
		add_filter( 'wccon_product_query_vars', $add_query_vars );
		ob_start();
		wc_get_template(
			'product-builder.php',
			array(
				'component' => $component,
				'term_ids'  => $tax_data,
			),
			'wccontour',
			WCCON_PLUGIN_PATH . '/templates/'
		);
		$html = ob_get_clean();
		remove_filter( 'wccon_product_query_args', $add_active_filters );
		remove_filter( 'wccon_product_query_vars', $add_query_vars );

		wp_send_json_success(
			array(
				'data'      => $tax_data,
				'html'      => $html,
				'component' => $component,
				'prices'    => $prices,

			)
		);
	}

	/**
	 * Add products to cart.
	 */
	public function buy_product() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$data     = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );
		$products = $this->retrieve_products( $data, array() );
		foreach ( $products as $product ) {
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product['product_id'], $product['quantity'] );
			$cart_item_data    = apply_filters( 'wccon_cart_item_data', array(), $product );
			if ( $passed_validation ) {
				WC()->cart->add_to_cart( $product['product_id'], $product['quantity'], $product['variation_id'], $product['variation'], $cart_item_data );
			}
		}
		do_action( 'wccon_after_buy_products', $data, $products );

		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		$return_data = array(
			'fragments' => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',

				)
			),
			'cart_hash' => WC()->cart->get_cart_hash(),
			'products'  => $products,
			'data'      => $data,
			'redirect'  => wc_get_cart_url(),
		);
		$respose_args = apply_filters(
			'wccon_buy_products_response_args',
			$return_data
		);
		wp_send_json_success( $respose_args );
	}

	/**
	 * Add products to cart from account page.
	 */
	public function buy_list_products() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$list_id = absint( wp_unslash( $_POST['list_id'] ) );

		if ( ! $list_id ) {
			wp_send_json_error();
		}
		global $wpdb;
		$data_lists = WCCON_DB::tables( 'saved_lists', 'name' );
		$saved_list = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$data_lists} WHERE id=%d", $list_id ), ARRAY_A );
		if ( $saved_list && ! empty( $saved_list ) ) {
			$list_data = maybe_unserialize( $saved_list['list_data'] );
			$products  = $this->retrieve_products( $list_data['groups'], array() );

			foreach ( $products as $product ) {
				$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product['product_id'], $product['quantity'] );
				$cart_item_data    = apply_filters( 'wccon_cart_item_data', array(), $product );
				if ( $passed_validation ) {
					WC()->cart->add_to_cart( $product['product_id'], $product['quantity'], $product['variation_id'], $product['variation'], $cart_item_data );
				}
			}
			ob_start();

			woocommerce_mini_cart();

			$mini_cart = ob_get_clean();

			$return_data = array(
				'fragments' => apply_filters(
					'woocommerce_add_to_cart_fragments',
					array(
						'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',

					)
				),
				'cart_hash' => WC()->cart->get_cart_hash(),
				'products'  => $products,
			);
			wp_send_json_success( $return_data );
		} else {
			wp_send_json_error();
		}

	}

	/**
	 * Add product to builder configuration on frontend.
	 */
	public function add_product() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$data         = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );
		$product_data = wc_clean( wp_unslash( $_POST['product'] ) );
		$product_id   = absint( $product_data['product_id'] );

		if ( $product_id < 1 ) {
			wp_send_json_error();
		}
		$variation_id = $product_data['variation_id'];
		if ( $variation_id ) {
			$product_id = $variation_id;
		}
		$products        = $this->retrieve_products( $data, array() );
		$compatible_info = $this->run_compatibility( $products );

		wp_send_json_success(
			array(
				'data'  => $data,
				'whole' => $product_data,
				'cd'    => $compatible_info,
			)
		);
	}

	/**
	 * Helper function to retrieve products from data.
	 */
	public function retrieve_products( $data, $result = array() ) {
		foreach ( $data as $group ) {
			if ( isset( $group['components'] ) ) {
				foreach ( $group['components'] as $component ) {
					if ( isset( $component['products'] ) && ! empty( $component['products'] ) ) {
						$result = array_merge( $component['products'], $result );
					}
					if ( isset( $component['components'] ) ) {
						$result = $this->retrieve_products( $component['components'], $result );
					}
				}
			}
			if ( isset( $group['products'] ) && ! empty( $group['products'] ) ) {
				$result = array_merge( $group['products'], $result );
			}
		}
		return $result;
	}

	/**
	 * Remove product from builder configuration on frontend.
	 */
	public function remove_product() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$data = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );

		$products = $this->retrieve_products( $data, array() );

		$compatible_info = $this->run_compatibility( $products );

		wp_send_json_success(
			array(
				'data'     => $data,
				'cd'       => $compatible_info,
				'products' => $products,
			)
		);
	}

	/**
	 * Helper function to run compatibility.
	 */
	public function run_compatibility( $products ) {

		$compatible_info = array();

		return $compatible_info;
	}

	/**
	 * Get products for choosen component on builder frontend.
	 */
	public function get_component_products() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$component_id = isset( $_POST['component_id'] ) ? absint( wp_unslash( $_POST['component_id'] ) ) : false;
		$subgroup_id  = isset( $_POST['subgroup_id'] ) ? absint( wp_unslash( $_POST['subgroup_id'] ) ) : false;
		$products     = isset( $_POST['products'] ) ? wc_clean( wp_unslash( $_POST['products'] ) ) : array();
		if ( ! $component_id ) {
			wp_send_json_error();
		}
		global $wpdb;
		$compo_meta_table = WCCON_DB::tables( 'components_meta', 'name' );

		$compo_table   = WCCON_DB::tables( 'components', 'name' );
		$widgets_table = WCCON_DB::tables( 'widgets', 'name' );

		$component = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$compo_table} WHERE id=%d", $component_id ), ARRAY_A );
		if ( ! $component || empty( $component ) ) {
			wp_send_json_error();
		}
		$component_data  = array(
			'id'                => $component['id'],
			'slug'              => $component['slug'],
			'title'             => $component['title'],
			'image_id'          => $component['image_id'],
			'position'          => $component['position'],
			'selected_products' => $products,
		);
		$components_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$compo_meta_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );
		if ( ! empty( $components_meta ) ) {
			$components_meta = wp_list_pluck( $components_meta, 'meta_value', 'meta_key' );
			array_walk(
				$components_meta,
				function ( &$meta_value, $meta_key ) {
					$meta_value = maybe_unserialize( $meta_value );
				}
			);
			$component_data['meta'] = $components_meta;
		}

		$component_widgets = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$widgets_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );

		if ( ! is_null( $component_widgets ) ) {
			$component_data['widgets'] = maybe_unserialize( $component_widgets['widget_value'] );
		} else {
			$component_data['widgets'] = array();
		}

		ob_start();
		wc_get_template( 'product-builder.php', array( 'component' => $component_data ), 'wccontour', WCCON_PLUGIN_PATH . '/templates/' );

		$html = ob_get_clean();
		wp_send_json_success(
			array(
				'comp' => $component_id,
				'sub'  => $subgroup_id,
				'html' => $html,
			)
		);
	}

	/**
	 * Load product list from localStorage replacing old values with new one.
	 */
	public function load_list() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$data         = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );
		$shortcode_id = absint( wp_unslash( $_POST['id'] ) );
		$list_id      = absint( wp_unslash( $_POST['list_id'] ) ); // ??.
		if ( ! $shortcode_id ) {
			wp_send_json_error(
				array(
					'message' => apply_filters( 'wccon_load_wrong_shortcode_message', __( 'Wrong ID', 'wccontour' ) ),
				)
			);
		}

		$settings = wccon_get_settings();

		global $wpdb;

		$data_compo_meta = WCCON_DB::tables( 'components_meta', 'name' );

		$new_data      = array();
		$data_for_db   = array();
		$product_sheme = array();
		foreach ( $data['groups'] as $key => $group ) {
			$new_data['groups'][ $key ]['slug']     = $group['slug'];
			$new_data['groups'][ $key ]['id']       = $group['id'];
			$new_data['groups'][ $key ]['position'] = $group['position'];

			$data_for_db['groups'][ $key ]['slug']     = $group['slug'];
			$data_for_db['groups'][ $key ]['id']       = $group['id'];
			$data_for_db['groups'][ $key ]['position'] = $group['position'];

			$product_sheme['groups'][ $key ]['slug']     = $group['slug'];
			$product_sheme['groups'][ $key ]['id']       = $group['id'];
			$product_sheme['groups'][ $key ]['position'] = $group['position'];

			// ensure that components will be anyway.
			$new_data['groups'][ $key ]['components']      = array();
			$data_for_db['groups'][ $key ]['components']   = array();
			$product_sheme['groups'][ $key ]['components'] = array();

			foreach ( $group['components'] as $comp_key => $component ) {
				// subgroup.
				if ( isset( $component['type'] ) && 'subgroup' === $component['type'] ) {

					$new_data['groups'][ $key ]['components'][ $comp_key ]      = array(
						'id'         => $component['id'],
						'type'       => $component['type'],
						'slug'       => $component['slug'],
						'position'   => $component['position'],
						'components' => array(),
					);
					$data_for_db['groups'][ $key ]['components'][ $comp_key ]   = array(
						'id'         => $component['id'],
						'type'       => $component['type'],
						'slug'       => $component['slug'],
						'position'   => $component['position'],
						'components' => array(),
					);
					$product_sheme['groups'][ $key ]['components'][ $comp_key ] = array(
						'id'         => $component['id'],
						'type'       => $component['type'],
						'slug'       => $component['slug'],
						'position'   => $component['position'],
						'components' => array(),
					);
					if ( isset( $component['components'] ) && ! empty( $component['components'] ) ) {
						foreach ( $component['components'] as $subcomp_key => $subcomponent ) {
							$subcomponent_multiple = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$data_compo_meta} WHERE component_id=%d AND meta_key=%s", (int) $subcomponent['id'], 'multiple' ) );
							$new_data['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ]    = array(
								'id'       => $subcomponent['id'],
								'type'     => $subcomponent['type'],
								'multiple' => is_null( $subcomponent_multiple ) ? '' : $subcomponent_multiple,
								'slug'     => $subcomponent['slug'],
								'position' => $subcomponent['position'],
								'products' => array(),
							);
							$data_for_db['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ] = array(
								'id'       => $subcomponent['id'],
								'type'     => $subcomponent['type'],
								'slug'     => $subcomponent['slug'],
								'products' => array(),

							);

							$product_sheme['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ] = array(
								'id'       => $subcomponent['id'],
								'type'     => $subcomponent['type'],
								'multiple' => is_null( $subcomponent_multiple ) ? '' : $subcomponent_multiple,
								'slug'     => $subcomponent['slug'],
								'position' => $subcomponent['position'],
								'products' => array(),

							);
							// subcomponents products.
							if ( isset( $subcomponent['products'] ) ) {
								foreach ( $subcomponent['products'] as $pr_key => $product_data ) {
									$product_id = $product_data['variation_id'] ? $product_data['variation_id'] : $product_data['product_id'];
									$product    = wc_get_product( $product_id );

									// if no product or product was removed.
									if ( ! $product ) {
										continue;
									}

									$new_data['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ]['products'][ $pr_key ] = apply_filters(
										'wccon_load_list_args',
										array(
											'clone'        => $product_data['clone'],
											'component'    => $product_data['component'],
											'price'        => $product->get_price(),
											'display_price' => apply_filters( 'wccon_product_price_html', $product->get_price_html(), $product ),
											'product_id'   => $product_data['product_id'],
											'quantity'     => $product_data['quantity'],
											'variation'    => $product_data['variation'],
											'variation_id' => $product_data['variation_id'],
											'stock_status' => $product->get_stock_status(),
											'stock'        => $product->get_stock_quantity(),
											'sold_individually' => $product->is_sold_individually(),
											'image'        => $product->get_image( apply_filters( 'wccon_product_image_size', $settings['style']['image_size'] ) ),
											'sku'          => $product->get_sku(),
											'description'  => $product->get_short_description(),
											'name'         => $product->get_name(),
											'link'         => get_permalink( $product->get_id() ),
											'attributes'   => array(),

										),
										$product,
										$product_data
									);

									// set attributes for variation product.
									if ( $product->is_type( 'variation' ) ) {
										$variation_attributes = $this->get_variation_attributes( $product );

										$new_data['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ]['products'][ $pr_key ]['attributes'] = $variation_attributes;
									}

									$data_for_db['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ]['products'][ $pr_key ] = array(
										'clone'        => $product_data['clone'],
										'component'    => $product_data['component'],
										'product_id'   => $product_data['product_id'],
										'quantity'     => $product_data['quantity'],
										'variation'    => $product_data['variation'],
										'variation_id' => $product_data['variation_id'],

									);

									$product_sheme['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ]['products'][ $pr_key ] = array(
										'clone'        => $product_data['clone'],
										'component'    => $product_data['component'],
										'price'        => $product->get_price(),
										'product_id'   => $product_data['product_id'],
										'quantity'     => $product_data['quantity'],
										'variation'    => $product_data['variation'],
										'variation_id' => $product_data['variation_id'],
									);
								}
							}
						}
					}
				} else {
					// components.
					$component_multiple = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$data_compo_meta} WHERE component_id=%d AND meta_key=%s", (int) $component['id'], 'multiple' ) );
					$component_extra    = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$data_compo_meta} WHERE component_id=%d AND meta_key=%s", (int) $component['id'], 'extra' ) );

					$new_data['groups'][ $key ]['components'][ $comp_key ]      = array(
						'id'       => $component['id'],
						'multiple' => is_null( $component_multiple ) ? '' : $component_multiple,
						'extra'    => is_null( $component_extra ) ? '' : $component_extra,
						'type'     => $component['type'],
						'slug'     => $component['slug'],
						'position' => $component['position'],
						'products' => array(),
					);
					$data_for_db['groups'][ $key ]['components'][ $comp_key ]   = array(
						'id'       => $component['id'],
						'type'     => $component['type'],
						'slug'     => $component['slug'],
						'products' => array(),
					);
					$product_sheme['groups'][ $key ]['components'][ $comp_key ] = array(
						'id'       => $component['id'],
						'multiple' => is_null( $component_multiple ) ? '' : $component_multiple,
						'extra'    => is_null( $component_extra ) ? '' : $component_extra,
						'type'     => $component['type'],
						'slug'     => $component['slug'],
						'position' => $component['position'],
						'products' => array(),
					);

					if ( isset( $component['products'] ) && ! empty( $component['products'] ) ) {
						foreach ( $component['products'] as $pr_key => $product_data ) {
							$product_id = $product_data['variation_id'] ? $product_data['variation_id'] : $product_data['product_id'];
							$product    = wc_get_product( $product_id );

							// if no product or product was removed.
							if ( ! $product ) {
								continue;
							}

							$new_data['groups'][ $key ]['components'][ $comp_key ]['products'][ $pr_key ] = apply_filters(
								'wccon_load_list_args',
								array(
									'clone'             => $product_data['clone'],
									'component'         => $product_data['component'],
									'price'             => $product->get_price(),
									'display_price'     => apply_filters( 'wccon_product_price_html', $product->get_price_html(), $product ),
									'product_id'        => $product_data['product_id'],
									'quantity'          => $product_data['quantity'],
									'variation'         => $product_data['variation'],
									'variation_id'      => $product_data['variation_id'],
									'stock_status'      => $product->get_stock_status(),
									'stock'             => $product->get_stock_quantity(),
									'sold_individually' => $product->is_sold_individually(),
									'image'             => $product->get_image( apply_filters( 'wccon_product_image_size', $settings['style']['image_size'] ) ),
									'sku'               => $product->get_sku(),
									'description'       => $product->get_short_description(),
									'name'              => $product->get_name(),
									'link'              => get_permalink( $product->get_id() ),
									'attributes'        => array(),

								),
								$product,
								$product_data
							);

							// set attributes for variation product.
							if ( $product->is_type( 'variation' ) ) {
								$variation_attributes = $this->get_variation_attributes( $product );

								$new_data['groups'][ $key ]['components'][ $comp_key ]['products'][ $pr_key ]['attributes'] = $variation_attributes;
							}

							$data_for_db['groups'][ $key ]['components'][ $comp_key ]['products'][ $pr_key ] = array(
								'clone'        => $product_data['clone'],
								'component'    => $product_data['component'],
								'product_id'   => $product_data['product_id'],
								'quantity'     => $product_data['quantity'],
								'variation'    => $product_data['variation'],
								'variation_id' => $product_data['variation_id'],

							);
							$product_sheme['groups'][ $key ]['components'][ $comp_key ]['products'][ $pr_key ] = array(
								'clone'        => $product_data['clone'],
								'component'    => $product_data['component'],
								'price'        => $product->get_price(),
								'product_id'   => $product_data['product_id'],
								'quantity'     => $product_data['quantity'],
								'variation'    => $product_data['variation'],
								'variation_id' => $product_data['variation_id'],

							);

						}
					}
				}
			}
		}
		$products = $this->retrieve_products( $new_data['groups'], array() );

		$compatible_info = $this->run_compatibility( $products );

		wp_send_json_success(
			array(

				'id'             => $shortcode_id,
				'data'           => $new_data,
				'cd'             => $compatible_info,
				'product_scheme' => $product_sheme,
				'raw'            => $data,
			)
		);
	}

	/**
	 * Save current configuration for logged in users.
	 */
	public function save_list() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$data          = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );
		$shortcode_id  = absint( wp_unslash( $_POST['id'] ) );
		$saved_list_id = absint( wp_unslash( $_POST['saved_list_id'] ) );
		$user_id       = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'No user found', 'wccontour' ),
				)
			);
		}
		global $wpdb;
		$lists_table      = WCCON_DB::tables( 'saved_lists', 'name' );
		$config_table     = WCCON_DB::tables( 'data', 'name' );
		$compo_meta_table = WCCON_DB::tables( 'components_meta', 'name' );

		$config_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table} WHERE id=%d", $shortcode_id ), ARRAY_A );
		if ( ! $config_data ) {
			wp_send_json_error(
				array(
					'message' => __( 'No records found', 'wccontour' ),
				)
			);
		}
		$settings      = wccon_get_settings();
		$allowed_count = $settings['count_list'];
		$users_list    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM {$lists_table} WHERE user_id=%d", $user_id ) );
		if ( (int) $users_list > (int) $allowed_count ) {
			wp_send_json_error(
				array(
					'lists_count' => $users_list,
					'message'     => apply_filters( 'wccon_max_list_reached_message', __( 'You have reached max allowed lists', 'wccontour' ) ),
				)
			);
		}
		$data_for_db  = array();
		$authors_list = false;
		if ( $saved_list_id ) {
			$authors_lisd_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$lists_table} as dt WHERE dt.id=%d AND dt.user_id=%d", $saved_list_id, $user_id ), ARRAY_A );
			if ( ! empty( $authors_lisd_db ) ) {
				$authors_list = true;
			}
		}
		foreach ( $data['groups'] as $key => $group ) {

			$data_for_db['groups'][ $key ]['slug']       = $group['slug'];
			$data_for_db['groups'][ $key ]['id']         = $group['id'];
			$data_for_db['groups'][ $key ]['position']   = $group['position'];
			$data_for_db['groups'][ $key ]['components'] = array();

			foreach ( $group['components'] as $comp_key => $component ) {
				// subgroup.
				if ( isset( $component['type'] ) && 'subgroup' === $component['type'] ) {

					$data_for_db['groups'][ $key ]['components'][ $comp_key ] = array(
						'id'         => $component['id'],
						'type'       => $component['type'],
						'slug'       => $component['slug'],
						'components' => array(),
						'position'   => $component['position'],
					);

					foreach ( $component['components'] as $subcomp_key => $subcomponent ) {

						$data_for_db['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ] = array(
							'id'       => $subcomponent['id'],
							'type'     => $subcomponent['type'],
							'slug'     => $subcomponent['slug'],
							'position' => $component['position'],
							'products' => array(),
						);

						// subcomponents products.
						if ( isset( $subcomponent['products'] ) ) {
							foreach ( $subcomponent['products'] as $pr_key => $product_data ) {

								$data_for_db['groups'][ $key ]['components'][ $comp_key ]['components'][ $subcomp_key ]['products'][ $pr_key ] = array(
									'clone'        => $product_data['clone'],
									'component'    => $product_data['component'],
									'product_id'   => $product_data['product_id'],
									'quantity'     => $product_data['quantity'],
									'variation'    => $product_data['variation'],
									'variation_id' => $product_data['variation_id'],

								);
							}
						}
					}
				} else {
					// components.

					$data_for_db['groups'][ $key ]['components'][ $comp_key ] = array(
						'id'       => $component['id'],
						'type'     => $component['type'],
						'slug'     => $component['slug'],
						'position' => $component['position'],
						'products' => array(),
					);

					if ( isset( $component['products'] ) ) {
						foreach ( $component['products'] as $pr_key => $product_data ) {

							$data_for_db['groups'][ $key ]['components'][ $comp_key ]['products'][ $pr_key ] = array(
								'clone'        => $product_data['clone'],
								'component'    => $product_data['component'],
								'product_id'   => $product_data['product_id'],
								'quantity'     => $product_data['quantity'],
								'variation'    => $product_data['variation'],
								'variation_id' => $product_data['variation_id'],

							);

						}
					}
				}
			}
		}

		$data_for_db = apply_filters( 'wccon_saved_lists_data', $data_for_db, $shortcode_id, $saved_list_id );

		// save list or update.
		if ( $authors_list && $saved_list_id ) {
			$list_id = $wpdb->update(
				$lists_table,
				array(
					'shortcode_id' => $shortcode_id,
					'list_data'    => maybe_serialize( $data_for_db ),

				),
				array(
					'id'      => $saved_list_id,
					'user_id' => $user_id,
				),
				array(
					'%d',
					'%s',

				),
				array(
					'%d',
					'%d',
				)
			);
		} else {
			$wpdb->insert(
				$lists_table,
				array(
					'user_id'      => $user_id,
					'shortcode_id' => $shortcode_id,
					'list_data'    => maybe_serialize( $data_for_db ),

				),
				array(
					'%d',
					'%d',
					'%s',

				)
			);

		}

		wp_send_json_success(
			array(
				'id' => $saved_list_id ? $saved_list_id : $wpdb->insert_id,

			)
		);

	}

	/**
	 * Remove Saved list from account page.
	 */
	public function remove_list() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$list_id = absint( wp_unslash( $_POST['listId'] ) );
		if ( ! $list_id ) {
			wp_send_json_error();
		}
		global $wpdb;
		$lists_table = WCCON_DB::tables( 'saved_lists', 'name' );
		$row_deleted = $wpdb->delete( $lists_table, array( 'id' => $list_id ), array( '%d' ) );
		wp_send_json_success(
			array(
				'list_id' => $list_id,
				'deleted' => $row_deleted,
			)
		);
	}

	/**
	 * Get other user's lists for displaying in popup window.
	 */
	public function users_list() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$shortcode_id = absint( wp_unslash( $_POST['shortcode_id'] ) );
		ob_start();
		wc_get_template(
			'users-lists.php',
			array(
				'ajax'         => true,
				'shortcode_id' => $shortcode_id,
			),
			'wccontour',
			WCCON_PLUGIN_PATH . '/templates/'
		);
		$html = ob_get_clean();
		wp_send_json_success(
			array(
				'html' => $html,
			)
		);
	}

	/**
	 * Get current user's lists for displaying in popup window.
	 */
	public function user_list() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$shortcode_id = absint( wp_unslash( $_POST['shortcode_id'] ) );
		ob_start();
		wc_get_template(
			'saved-lists.php',
			array(
				'ajax'         => true,
				'shortcode_id' => $shortcode_id,
			),
			'wccontour',
			WCCON_PLUGIN_PATH . '/templates/'
		);
		$html = ob_get_clean();
		wp_send_json_success(
			array(
				'html' => $html,
			)
		);
	}

	/**
	 * Save admin settings.
	 */
	public function save_settings() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$data           = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );
		$wccon_settings = get_option( 'wccon_settings' );
		$return_args    = array();
		if ( $wccon_settings && $data['account_endpoint'] !== $wccon_settings['account_endpoint'] ) {
			$return_args['flushed'] = true;

		}

		update_option( 'wccon_settings', $data );

		wp_send_json_success( $return_args );
	}

	/**
	 * Update list from settings page.
	 */
	public function update_list_item() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$id   = absint( $_POST['id'] );
		$data = wc_clean( json_decode( wp_unslash( $_POST['data'] ), true ) );

		if ( ! $id ) {
			wp_send_json_error(
				array(
					'message' => __( 'ID not valid', 'wccontour' ),
				)
			);
		}
		if ( $data === null && json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error(
				array(
					'message' => __( 'JSON is not valid', 'wccontour' ),
				)
			);
		}
		if ( ! is_array( $data ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'JSON is not valid', 'wccontour' ),
				)
			);
		}

		global $wpdb;

		$config_table     = WCCON_DB::tables( 'data', 'name' );
		$groups_table     = WCCON_DB::tables( 'groups', 'name' );
		$group_meta_table = WCCON_DB::tables( 'groups_meta', 'name' );

		$config_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table} WHERE id=%d", $id ), ARRAY_A );
		if ( $config_info && ! empty( $data ) && isset( $data['shortcode_id'] ) ) {
			$wpdb->update(
				$config_table,
				array(
					'shortcode_id' => $data['shortcode_id'],
					'title'        => $data['title'],
					'type'         => sanitize_title( $data['type'] ),
					'page_id'      => $data['page_id'],
					'lang'         => $data['lang'],

				),
				array(
					'id' => $id,
				),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%s',
				)
			);

			$existing_groups     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE config_id=%d AND parent_id=0 ", $id ), ARRAY_A );
			$existing_groups_ids = wp_list_pluck( $existing_groups, 'id' );
			if ( ! empty( $data['groups'] ) ) {

				foreach ( $data['groups'] as $group ) {
					if ( in_array( $group['id'], $existing_groups_ids ) ) {
						$wpdb->update(
							$groups_table,
							array(
								'title'    => $group['title'],
								'image_id' => (int) $group['image_id'],
								'position' => (int) $group['position'],
							),
							array( 'id' => (int) $group['id'] ),
							array( '%s', '%d', '%d' )
						);

						// update components.
						wccon_update_components( $group, $id, (int) $group['id'] );
					} else {
						$wpdb->insert(
							$groups_table,
							array(
								'config_id' => $id,
								'title'     => $group['title'],
								'slug'      => $group['slug'],
								'image_id'  => (int) $group['image_id'],
								'position'  => (int) $group['position'],

							),
							array(
								'%d',
								'%s',
								'%s',
								'%d',
								'%d',
							)
						);
						$group_id = $wpdb->insert_id;

						// insert components.
						wccon_store_components( $group, $id, $group_id );
					}

					// meta groups
					foreach ( $group['meta'] as $meta_key => $meta_value ) {
						$group_id         = (int) $group['id'] ? (int) $group['id'] : $group_id;
						$existing_meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$group_meta_table} WHERE group_id=%d AND meta_key=%s", $group_id, $meta_key ) );

						$wpdb->replace(
							$group_meta_table,
							array(
								'id'         => $existing_meta_id,
								'group_id'   => $group_id,
								'meta_key'   => $meta_key,
								'meta_value' => $meta_value,

							),
							array(
								'%d',
								'%d',
								'%s',
								'%s',

							)
						);
					}
				}
			}
			$current_group_ids = wp_list_pluck( $data['groups'], 'id' );
			foreach ( $existing_groups as $group ) {
				if ( ! in_array( $group['id'], $current_group_ids ) ) {

					$wpdb->delete( $groups_table, array( 'id' => (int) $group['id'] ), array( '%d' ) );
				}
			}
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'ID is not exists', 'wccontour' ),
				)
			);
		}

		wp_send_json_success();
	}

	/**
	 * Delete plugin data from settings page.
	 */
	public function delete_plugin_data() {

		check_ajax_referer( 'wccon-nonce', 'nonce' );
		global $wpdb;

		$config_table = WCCON_DB::tables( 'data', 'name' );
		$wpdb->query( "DELETE FROM {$config_table}" );

		delete_post_meta_by_key( 'wccon_enable_variation_compatibility' );
		delete_post_meta_by_key( 'wccon_compatibility_variation' );
		delete_post_meta_by_key( 'wccon_compatibility_comparator' );
		delete_post_meta_by_key( 'wccon_strict_taxonomy' );
		delete_post_meta_by_key( 'wccon_strict_term' );
		delete_post_meta_by_key( 'wccon_compatibility_data' );
		delete_post_meta_by_key( 'wccon_enable_compatibility' );
		delete_post_meta_by_key( 'wccon_global_compatibility' );

		wp_send_json_success();
	}

	/**
	 * Load more ajax.
	 */
	public function load_more() {

		check_ajax_referer( 'wccon-nonce', 'nonce' );
		$current_page = absint( wp_unslash( $_POST['currentPage'] ) );
		$list_type    = sanitize_text_field( wp_unslash( $_POST['listType'] ) );
		$shortcode_id = absint( wp_unslash( $_POST['shortcode_id'] ) );

		$template = '';
		if ( 'user' === $list_type ) {
			$template = 'saved-lists.php';
		} elseif ( 'users' === $list_type ) {
			$template = 'users-lists.php';
		}
		if ( ! $template ) {
			wp_send_json_error();
		}
		if ( ! $current_page ) {
			wp_send_json_error();
		}
		$_REQUEST['conpage'] = $current_page + 1;
		ob_start();
		wc_get_template(
			$template,
			array(
				'ajax'         => true,
				'shortcode_id' => $shortcode_id,
			),
			'wccontour',
			WCCON_PLUGIN_PATH . '/templates/'
		);
		$html = ob_get_clean();
		wp_send_json_success(
			array(
				'html' => $html,
			)
		);
	}

	/**
	 * Test wpml ajax.
	 */
	public function test_wpml() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		global $sitepress;
		$current_lang = apply_filters( 'wpml_current_language', null );
		$sitepress->switch_lang( 'en' );
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		$term  = get_term( 159 );
		$sitepress->switch_lang( $current_lang );

		wp_send_json_success(
			array(
				'tax'   => wccon_get_all_product_taxonomies(),
				'terms' => $terms,
				'term'  => $term,
			)
		);
	}

	/**
	 * Helper function to get variation attributes.
	 */
	public function get_variation_attributes( $product ) {
		$variation_attributes = array();
		$variations           = $product->get_variation_attributes( false );
		foreach ( $variations as $attribute_name => $variation_value ) {
			$attribute = wccon_get_attribute_taxonomy_by_name( $attribute_name );
			if ( taxonomy_exists( $attribute_name ) ) {
				$term = get_term_by( 'slug', $variation_value, $attribute_name );
				if ( ! $term ) {
					continue;
				}
				if ( 'color' === $attribute['attribute_type'] ) {

					$color                  = get_term_meta( $term->term_id, apply_filters( 'wccon_attribute_color_type_term', 'product_attribute_color' ), true );
					$variation_attributes[] = array(
						'type'      => 'color',
						'attribute' => $attribute_name,
						'value'     => $color,
						'name'      => $term->name,
					);
				} else {

					$variation_attributes[] = array(
						'type'      => 'button',
						'attribute' => $attribute_name,
						'value'     => $variation_value,
						'name'      => $term->name,
					);
				}
			} else {
				$variation_attributes[] = array(
					'type'      => 'button',
					'attribute' => $attribute_name,
					'value'     => $variation_value,
				);
			}
		}
								return $variation_attributes;
	}

}
