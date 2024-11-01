<?php
/**
 * Functions.
 *
 * @package WCCON\Functions
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store components to db.
 *
 * @param array $group Group data.
 * @param int   $config_id Builder/config id.
 * @param int   $group_id Group ID.
 * @return void
 */
function wccon_store_components( array $group, int $config_id, int $group_id ) {
	global $wpdb;

	$data_compo      = WCCON_DB::tables( 'components', 'name' );
	$data_compo_meta = WCCON_DB::tables( 'components_meta', 'name' );
	$data_group      = WCCON_DB::tables( 'groups', 'name' );
	$data_group_meta = WCCON_DB::tables( 'groups_meta', 'name' );
	$data_widgets    = WCCON_DB::tables( 'widgets', 'name' );

	foreach ( $group['components'] as $component ) {
		if ( isset( $component['parent_id'] ) ) {
			$wpdb->insert(
				$data_group,
				array(
					'config_id' => $config_id,
					'title'     => $component['title'],
					'slug'      => $component['slug'],
					'parent_id' => $group_id,
					'image_id'  => (int) $component['image_id'],
					'position'  => (int) $component['position'],

				),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
				)
			);
			$subgroup_id = $wpdb->insert_id;
			// subcomponents.
			if ( ! empty( $component['components'] ) ) {
				foreach ( $component['components'] as $sub_component ) {
					$wpdb->insert(
						$data_compo,
						array(
							'group_id' => $subgroup_id,
							'title'    => $sub_component['title'],
							'slug'     => $sub_component['slug'],
							'image_id' => (int) $sub_component['image_id'],
							'position' => (int) $sub_component['position'],

						),
						array(
							'%d',
							'%s',
							'%s',
							'%d',
							'%d',
						)
					);
					$sub_component_id = $wpdb->insert_id;
					// subcomponents meta.
					foreach ( $sub_component['meta'] as $meta_key => $meta_value ) {

						$wpdb->insert(
							$data_compo_meta,
							array(
								'component_id' => $sub_component_id,
								'meta_key'     => $meta_key,
								'meta_value'   => maybe_serialize( $meta_value ),

							),
							array(
								'%d',
								'%s',
								'%s',

							)
						);
					}

					// subcomponents widgets.
					$wpdb->insert(
						$data_widgets,
						array(
							'component_id' => $sub_component_id,
							'widget_value' => maybe_serialize( $sub_component['widgets'] ),

						),
						array(
							'%d',
							'%s',
						)
					);

				}
			}
		} else {
			$wpdb->insert(
				$data_compo,
				array(
					'group_id' => $group_id,
					'title'    => $component['title'],
					'slug'     => $component['slug'],
					'image_id' => (int) $component['image_id'],
					'position' => (int) $component['position'],

				),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
				)
			);
			$component_id = $wpdb->insert_id;
		}

		// meta component.
		foreach ( $component['meta'] as $meta_key => $meta_value ) {

			if ( isset( $component['parent_id'] ) ) {
				$wpdb->insert(
					$data_group_meta,
					array(
						'group_id'   => (int) $component['id'] ? (int) $component['id'] : $subgroup_id,
						'meta_key'   => $meta_key,
						'meta_value' => maybe_serialize( $meta_value ),

					),
					array(
						'%d',
						'%s',
						'%s',

					)
				);
			} else {
				// component meta.
				$wpdb->insert(
					$data_compo_meta,
					array(
						'component_id' => (int) $component['id'] ? (int) $component['id'] : $component_id,
						'meta_key'     => $meta_key,
						'meta_value'   => maybe_serialize( $meta_value ),

					),
					array(
						'%d',
						'%s',
						'%s',

					)
				);

			}
		}

		// component widgets.
		$wpdb->insert(
			$data_widgets,
			array(
				'component_id' => (int) $component['id'] ? (int) $component['id'] : $component_id,
				'widget_value' => maybe_serialize( $component['widgets'] ),

			),
			array(
				'%d',
				'%s',
			)
		);

	}
}

/**
 * Update components.
 *
 * @param array $group Group.
 * @param int   $shortcode_db_id Config ID.
 * @param int   $group_id Group ID.
 * @return void
 */
function wccon_update_components( array $group, int $shortcode_db_id, int $group_id ) {
	global $wpdb;

	$data_compo      = WCCON_DB::tables( 'components', 'name' );
	$data_compo_meta = WCCON_DB::tables( 'components_meta', 'name' );
	$data_group      = WCCON_DB::tables( 'groups', 'name' );
	$data_group_meta = WCCON_DB::tables( 'groups_meta', 'name' );
	$data_widgets    = WCCON_DB::tables( 'widgets', 'name' );

	$existing_components     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$data_compo}  WHERE group_id=%d ", $group_id ), ARRAY_A );
	$existing_components_ids = wp_list_pluck( $existing_components, 'id' );

	$existing_sub_groups     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$data_group} WHERE config_id=%d AND parent_id=%d ", $shortcode_db_id, $group_id ), ARRAY_A );
	$existing_sub_groups_ids = wp_list_pluck( $existing_sub_groups, 'id' );

	foreach ( $group['components'] as $component ) {

		// parent_id start.
		if ( isset( $component['parent_id'] ) ) {

			$existing_sub_components     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$data_compo} WHERE group_id=%d ", (int) $component['id'] ), ARRAY_A );
			$existing_sub_components_ids = wp_list_pluck( $existing_sub_components, 'id' );

			if ( in_array( $component['id'], $existing_sub_groups_ids ) ) {
				$wpdb->update(
					$data_group,
					array(

						'title'    => $component['title'],
						'slug'     => $component['slug'],
						'image_id' => (int) $component['image_id'],
						'position' => (int) $component['position'],

					),
					array(
						'id' => (int) $component['id'],
					),
					array(

						'%s',
						'%s',
						'%d',
						'%d',
					)
				);

				if ( ! empty( $component['components'] ) ) {

					foreach ( $component['components'] as $sub_component ) {

						if ( in_array( $sub_component['id'], $existing_sub_components_ids ) ) {
							$wpdb->update(
								$data_compo,
								array(

									'title'    => $sub_component['title'],
									'slug'     => $sub_component['slug'],
									'image_id' => (int) $sub_component['image_id'],
									'position' => (int) $sub_component['position'],

								),
								array(
									'id' => (int) $sub_component['id'],
								),
								array(

									'%s',
									'%s',
									'%d',
									'%d',
								)
							);
						} else {
							$wpdb->insert(
								$data_compo,
								array(
									'group_id' => (int) $component['id'],
									'title'    => $sub_component['title'],
									'slug'     => $sub_component['slug'],
									'image_id' => (int) $sub_component['image_id'],
									'position' => (int) $sub_component['position'],

								),
								array(
									'%d',
									'%s',
									'%s',
									'%d',
									'%d',
								)
							);
							$sub_component_id = $wpdb->insert_id;
						}
						// subcomponents meta.

						foreach ( $sub_component['meta'] as $meta_key => $meta_value ) {
							$existing_meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$data_compo_meta} WHERE component_id=%d AND meta_key=%s", (int) $sub_component['id'], $meta_key ) );

							$wpdb->replace(
								$data_compo_meta,
								array(
									'id'           => $existing_meta_id,
									'component_id' => (int) $sub_component['id'] ? (int) $sub_component['id'] : $sub_component_id,
									'meta_key'     => $meta_key,
									'meta_value'   => maybe_serialize( $meta_value ),

								),
								array(
									'%d',
									'%d',
									'%s',
									'%s',

								)
							);
						}

						// subcomponents widgets.
						$existing_widget_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$data_widgets} WHERE component_id=%d ", (int) $sub_component['id'] ) );

						$wpdb->replace(
							$data_widgets,
							array(
								'id'           => $existing_widget_id,
								'component_id' => (int) $sub_component['id'] ? (int) $sub_component['id'] : $sub_component_id,
								'widget_value' => maybe_serialize( $sub_component['widgets'] ),

							),
							array(
								'%d',
								'%d',
								'%s',

							)
						);

					}
				}
				// update existing group meta.
				foreach ( $component['meta'] as $meta_key => $meta_value ) {

					$subgroup_id      = (int) $component['id'];
					$existing_meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$data_group_meta} WHERE group_id=%d AND meta_key=%s", $subgroup_id, $meta_key ) );
					$wpdb->replace(
						$data_group_meta,
						array(
							'id'         => $existing_meta_id,
							'group_id'   => $subgroup_id,
							'meta_key'   => $meta_key,
							'meta_value' => maybe_serialize( $meta_value ),

						),
						array(
							'%d',
							'%d',
							'%s',
							'%s',

						)
					);
				}
			} else {
				$wpdb->insert(
					$data_group,
					array(
						'config_id' => $shortcode_db_id,
						'title'     => $component['title'],
						'slug'      => $component['slug'],
						'parent_id' => $group_id,
						'image_id'  => (int) $component['image_id'],
						'position'  => (int) $component['position'],

					),
					array(
						'%d',
						'%s',
						'%s',
						'%d',
						'%d',
						'%d',
					)
				);
				$subgroup_id = $wpdb->insert_id;

				if ( ! empty( $component['components'] ) ) {

					foreach ( $component['components'] as $sub_component ) {

						$wpdb->insert(
							$data_compo,
							array(
								'group_id' => $subgroup_id,
								'title'    => $sub_component['title'],
								'slug'     => $sub_component['slug'],
								'image_id' => (int) $sub_component['image_id'],
								'position' => (int) $sub_component['position'],

							),
							array(
								'%d',
								'%s',
								'%s',
								'%d',
								'%d',
							)
						);
						$sub_component_id = $wpdb->insert_id;

						// subcomponents meta.

						foreach ( $sub_component['meta'] as $meta_key => $meta_value ) {

							$wpdb->insert(
								$data_compo_meta,
								array(
									'component_id' => $sub_component_id,
									'meta_key'     => $meta_key,
									'meta_value'   => maybe_serialize( $meta_value ),

								),
								array(
									'%d',
									'%s',
									'%s',

								)
							);
						}

						// subcomponents widgets.
						$wpdb->insert(
							$data_widgets,
							array(
								'component_id' => $sub_component_id,
								'widget_value' => maybe_serialize( $sub_component['widgets'] ),

							),
							array(
								'%d',
								'%s',
							)
						);

					}
				}

				foreach ( $component['meta'] as $meta_key => $meta_value ) {
					$wpdb->insert(
						$data_group_meta,
						array(
							'group_id'   => $subgroup_id,
							'meta_key'   => $meta_key,
							'meta_value' => maybe_serialize( $meta_value ),

						),
						array(
							'%d',
							'%s',
							'%s',

						)
					);
				}
			}

			// remove subcomponents.
			$current_sub_components_ids = wp_list_pluck( $component['components'], 'id' );
			foreach ( $existing_sub_components as $component ) {
				if ( ! in_array( $component['id'], $current_sub_components_ids ) ) {

					$wpdb->delete( $data_compo, array( 'id' => (int) $component['id'] ), array( '%d' ) );
				}
			}
		}
		// parent_id end.
		else {
			if ( in_array( $component['id'], $existing_components_ids ) ) {
				$wpdb->update(
					$data_compo,
					array(

						'title'    => $component['title'],
						'slug'     => $component['slug'],
						'image_id' => (int) $component['image_id'],
						'position' => (int) $component['position'],

					),
					array(
						'id' => (int) $component['id'],
					),
					array(

						'%s',
						'%s',
						'%d',
						'%d',

					)
				);
			} else {
				$wpdb->insert(
					$data_compo,
					array(
						'group_id' => $group_id,
						'title'    => $component['title'],
						'slug'     => $component['slug'],
						'image_id' => (int) $component['image_id'],
						'position' => (int) $component['position'],

					),
					array(
						'%d',
						'%s',
						'%s',
						'%d',
						'%d',
					)
				);
				$component_id = $wpdb->insert_id;
			}

			// meta component.
			foreach ( $component['meta'] as $meta_key => $meta_value ) {

				$component_id     = (int) $component['id'] ? (int) $component['id'] : $component_id;
				$existing_meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$data_compo_meta} WHERE component_id=%d AND meta_key=%s", $component_id, $meta_key ) );
				$wpdb->replace(
					$data_compo_meta,
					array(
						'id'           => $existing_meta_id,
						'component_id' => $component_id,
						'meta_key'     => $meta_key,
						'meta_value'   => maybe_serialize( $meta_value ),

					),
					array(
						'%d',
						'%d',
						'%s',
						'%s',

					)
				);
			}

			// component widgets.
			$component_id       = (int) $component['id'] ? (int) $component['id'] : $component_id;
			$existing_widget_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$data_widgets} WHERE component_id=%d", $component_id ) );
			$wpdb->replace(
				$data_widgets,
				array(
					'id'           => $existing_widget_id,
					'component_id' => $component_id,
					'widget_value' => maybe_serialize( $component['widgets'] ),

				),
				array(
					'%d',
					'%d',
					'%s',

				)
			);

		}
	}
	// components first level.
	$raw_components         = array_values(
		array_filter(
			$group['components'],
			function ( $value ) {
				if ( isset( $value['parent_id'] ) ) {
					return false;
				}
				return true;
			}
		)
	);
	$current_components_ids = wp_list_pluck( $raw_components, 'id' );
	foreach ( $existing_components as $component ) {
		if ( ! in_array( $component['id'], $current_components_ids ) ) {

			$wpdb->delete( $data_compo, array( 'id' => (int) $component['id'] ), array( '%d' ) );
		}
	}
	// remove subgroups.
	$raw_sub_groups         = array_values(
		array_filter(
			$group['components'],
			function ( $value ) {
				if ( isset( $value['parent_id'] ) ) {
					return true;
				}
				return false;
			}
		)
	);
	$current_sub_groups_ids = wp_list_pluck( $raw_sub_groups, 'id' );
	foreach ( $existing_sub_groups as $component ) {
		if ( ! in_array( $component['id'], $current_sub_groups_ids ) ) {

			$wpdb->delete( $data_group, array( 'id' => (int) $component['id'] ), array( '%d' ) );
		}
	}
}

/**
 * All product taxonomies.
 */
function wccon_get_all_product_taxonomies( $lang = false ) {
	return array_merge( wccon_get_product_taxonomies( $lang ), wccon_get_product_attributes( $lang ) );
}

/**
 * Product taxonomies.
 */
function wccon_get_product_taxonomies( $lang = false ) {

	$taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ) ), 'objects' );
	if ( $lang ) {
		$terms_data = wp_cache_get( 'wccon_product_taxonomies_search_' . $lang );
	} else {
		$terms_data = wp_cache_get( 'wccon_product_taxonomies_search' );
	}
	if ( $terms_data === false ) {
		$wccon_multilang = WCCON_Multilang::instance();
		if ( $lang && $wccon_multilang->is_wpml_enabled() ) {
			$wccon_multilang->switch_lang( $lang );
		}
		$terms_data = array();
		foreach ( $taxonomies as $key => $taxonomy ) {
			if ( preg_match( '/^pa_/', $taxonomy->name ) ) {
				continue;
			}
			if ( 'product_type' === $taxonomy->name ) {
				continue;
			}

			$terms_data[] = array(
				'label'    => $taxonomy->labels->name,
				'value'    => $taxonomy->name,

				'children' => wccon_get_taxonomy_hierarchy( $taxonomy->name, $lang ),
			);

		}
		if ( $lang ) {
			if ( $wccon_multilang->is_wpml_enabled() ) {
				$wccon_multilang->restore_lang();
			}
			wp_cache_set( 'wccon_product_taxonomies_search_' . $lang, $terms_data );
		} else {
			wp_cache_set( 'wccon_product_taxonomies_search', $terms_data );
		}
	}
	return $terms_data;
}

/**
 * Linear product taxonomies.
 */
function wccon_get_linear_product_taxonomies( $post_lang = false ) {

	$wccon_multilang = WCCON_Multilang::instance();
	$lang            = $wccon_multilang->get_lang();
	if ( $post_lang ) {
		$lang = $post_lang;
	}
	$taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ) ), 'objects' );
	if ( $lang ) {
		$terms_data = wp_cache_get( 'wccon_linear_product_taxonomies_search_' . $lang );
	} else {
		$terms_data = wp_cache_get( 'wccon_linear_product_taxonomies_search' );
	}
	if ( false === $terms_data ) {
		$attr_labels = wc_get_attribute_taxonomy_labels();
		$terms_data  = array();
		if ( $lang && $wccon_multilang->is_wpml_enabled() ) {
			$wccon_multilang->switch_lang( 'all' );
		}
		foreach ( $taxonomies as $key => $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
				)
			);
			if ( preg_match( '/^pa_/', $taxonomy->name ) ) {
				$taxonomy_name = $attr_labels[ str_replace( 'pa_', '', $taxonomy->name ) ] . ' (attribute)';
			} else {

				$taxonomy_name = $taxonomy->labels->name;
			}
			$formatted_terms   = array_map(
				function ( $el ) use ( $taxonomy_name ) {
					return array(
						'label' => $el->name . ' (' . $taxonomy_name . ')',
						'value' => $el->term_id,
					);
				},
				$terms
			);
			$formatted_terms[] = array(
				'label' => $taxonomy_name,
				'value' => $taxonomy->name,
			);

			$terms_data = array_merge( $terms_data, $formatted_terms );
		}
		if ( $lang ) {
			if ( $wccon_multilang->is_wpml_enabled() ) {
				$wccon_multilang->restore_lang();
			}
			wp_cache_set( 'wccon_linear_product_taxonomies_search_' . $lang, $terms_data );
		} else {
			wp_cache_set( 'wccon_linear_product_taxonomies_search', $terms_data );
		}
	}
	return $terms_data;
}

/**
 * Product attributes.
 */
function wccon_get_product_attributes( $lang = false ) {

	if ( $lang ) {
		$terms_data = wp_cache_get( 'wccon_product_attributes_search_' . $lang );
	} else {
		$terms_data = wp_cache_get( 'wccon_product_attributes_search' );
	}
	$taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ) ), 'objects' );

	if ( $terms_data === false ) {
		$attr_labels     = wc_get_attribute_taxonomy_labels();
		$terms_data      = array();
		$wccon_multilang = WCCON_Multilang::instance();
		if ( $lang && $wccon_multilang->is_wpml_enabled() ) {
			$wccon_multilang->switch_lang( $lang );
		}
		foreach ( $taxonomies as $key => $taxonomy ) {
			if ( ! preg_match( '/^pa_/', $taxonomy->name ) ) {
				continue;
			}

			$taxonomy_name = $attr_labels[ str_replace( 'pa_', '', $taxonomy->name ) ];
			$terms_data[]  = array(
				'label'    => $taxonomy_name . ' (attribute)',
				'value'    => $taxonomy->name,

				'children' => wccon_get_taxonomy_hierarchy( $taxonomy->name, $lang ),
			);
		}
		if ( $lang ) {
			if ( $wccon_multilang->is_wpml_enabled() ) {
				$wccon_multilang->restore_lang();
			}
			wp_cache_set( 'wccon_product_attributes_search_' . $lang, $terms_data );
		} else {
			wp_cache_set( 'wccon_product_attributes_search', $terms_data );
		}
	}
	return $terms_data;
}

/**
 * Taxonomy hierarchy.
 */
function wccon_get_taxonomy_hierarchy( $taxonomy, $lang, $parent = 0, $exclude = 0 ) {
	$taxonomy  = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
	$term_args = array(
		'taxonomy'   => $taxonomy,
		'parent'     => $parent,
		'hide_empty' => false,
		'exclude'    => $exclude,
	);
	$terms     = get_terms(
		apply_filters( 'wccon_taxonomy_term_args', $term_args, $taxonomy, $lang )
	);

	$children = array();
	foreach ( $terms as $term ) {
		$term->children = wccon_get_taxonomy_hierarchy( $taxonomy, $lang, $term->term_id, $exclude );
		$term->label    = $term->name;
		$children[]     = $term;
	}
	return $children;
}

/**
 * Recursive function to get all childrens of term.
 */
function wccon_get_term_childrens( $taxonomy, $parent = 0 ) {
	$taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
	$terms    = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'parent'     => $parent,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	$children = array();
	foreach ( $terms as $term_id ) {

		$children[] = array_merge( array( $term_id ), wccon_get_term_childrens( $taxonomy, $term_id ) );
	}
	return array_merge( ...$children );
}

/**
 * Search term by label.
 */
function wccon_search_term( $data, $user_field, $all = false ) {
	$found_elements = array();
	$attr_labels    = wc_get_attribute_taxonomy_labels();
	foreach ( $data as $term ) {
		if ( strpos( mb_strtolower( $term->label ), mb_strtolower( $user_field ) ) !== false ) {
			if ( ! $term->taxonomy ) {
				if ( ! $all ) {
					continue;
				}
				$found_elements[] = array(
					'label' => $term->label,
					'value' => $term->value,
				);

			} elseif ( preg_match( '/^pa_/', $term->taxonomy ) ) {
				$taxonomy_name    = $attr_labels[ str_replace( 'pa_', '', $term->taxonomy ) ];
				$label_name       = $term->name . ' (' . $taxonomy_name . ')';
				$found_elements[] = array(
					'label' => $label_name,
					'value' => $term->term_id,
				);
			} else {
				$taxonomy_name    = get_taxonomy( $term->taxonomy );
				$taxonomy_name    = $taxonomy_name->labels->name;
				$label_name       = $term->name . ' (' . $taxonomy_name . ')';
				$found_elements[] = array(
					'label' => $label_name,
					'value' => $term->term_id,
				);
			}
		}
		if ( isset( $term->children ) ) {
			$found_elements = array_merge( $found_elements, wccon_search_term( $term->children, $user_field ) );
		}
	}
	return $found_elements;
}

/**
 * Get array of meta keys.
 */
function wccon_get_product_meta_keys() {
	global $wpdb;
	$limit_keys = apply_filters( 'wccon_postmeta_form_limit', 190 );
	$sql_meta   = "SELECT DISTINCT meta_key
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID=pm.post_id
			WHERE p.post_type='product'
			ORDER BY meta_key
			LIMIT %d";
	$meta_keys  = $wpdb->get_col( $wpdb->prepare( $sql_meta, $limit_keys ) );
	$meta_keys  = array_map(
		function ( $el ) {
			return array(
				'label' => $el,
				'value' => $el,
			);
		},
		$meta_keys
	);

	return $meta_keys;
}

/**
 * Get meta values.
 */
function wccon_get_meta_values( $key = '', $type = 'post', $status = 'publish', $args = array() ) {

	global $wpdb;

	if ( empty( $key ) ) {
		return;
	}
	$ids = isset( $args['ids'] ) ? array_map( 'absint', $args['ids'] ) : false;
	if ( $ids ) {
		$ids_clause = ' AND p.ID IN (' . implode( ',', $ids ) . ')';
	} else {
		$ids_clause = '';
	}
	$results = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT (pm.meta_value) FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s 
        AND p.post_status = %s 
        AND p.post_type = %s
		{$ids_clause}",
			$key,
			$status,
			$type
		)
	);

	return array_filter(
		$results,
		function( $el ) {
			return $el !== '' && ! is_null( $el );
		}
	);
}

/**
 * Valid locations for filter widgets.
 */
function wccon_valid_component_locations() {
	return apply_filters(
		'wccon_valid_component_locations',
		array(
			'product_tax',
			'product_attribute',
			'product_meta',
		)
	);
}

/**
 * Query params.
 */
function wccon_query_component_params() {
	return array(
		'product_tax',
		'product_attribute',
		'product_meta',
	);
}

/**
 * Filter widget query. Not used.
 */
function wccon_filter_widget_query( $widget_data ) {
	$default_args = wccon_query_component_params();
	$widget_query = array_filter(
		$widget_data,
		function ( $value ) use ( $default_args ) {
			if ( in_array( $value['groupId'], $default_args ) ) {
				return true;
			}
			return false;
		}
	);
	return $widget_query;
}

/**
 * Render dropdown select.
 */
function wccon_dropdown_select( string $label, string $type, array $args ) {
	$default_value = array_values( $args )[0];
	echo '<div class="wccon-dropdown-select" data-type="' . esc_attr( $type ) . '">';
	echo '<span class="wccon-dropdown-inner">' . esc_html( $label ) . '<span>' . esc_html( $default_value ) . '</span><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
	<path d="M6 9L12 15L18 9" stroke="#8DA3C6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	</svg></span>';
	echo '<ul>';
	foreach ( $args as $option_key => $option_value ) {
		echo '<li><a href="#' . esc_attr( $option_key ) . '">' . esc_html( $option_value ) . '</a></li>';
	}
	echo '</ul></div>';
}

/**
 * Render Pagination.
 *
 * @param int $current_page Current page number.
 * @param int $total_pages Total pages for query result.
 */
function wccon_product_pagination( $current_page, $total_pages ) {

	if ( $total_pages < 2 ) {
		return;
	}

	global $wp;
	$args     = apply_filters(
		'wccon_pagination_args',
		array(
			'total'        => $total_pages,
			'current'      => $current_page,
			'aria_current' => 'page',
			'prev_next'    => true,
			'prev_text'    => __( '&laquo; Previous', 'wccontour' ),
			'next_text'    => __( 'Next &raquo;', 'wccontour' ),
			'end_size'     => 1,
			'mid_size'     => 2,
		)
	);
	$total    = (int) $args['total'];
	$current  = (int) $args['current'];
	$end_size = (int) $args['end_size'];
	if ( $end_size < 1 ) {
		$end_size = 1;
	}
	$mid_size = (int) $args['mid_size'];
	if ( $mid_size < 0 ) {
		$mid_size = 2;
	}
	$dots = false;

	for ( $n = 1; $n <= $total_pages; $n++ ) {
		if ( $n == $current_page ) {
			$page_links[] = sprintf(
				'<span aria-current="%s" class="wccon-pagination-item current">%s</span>',
				esc_attr( $args['aria_current'] ),
				number_format_i18n( $n )
			);

			$dots = true;
		} else {
			if ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) {
				$link         = add_query_arg( array( 'conpage' => $n ), home_url( $wp->request ) );
				$page_links[] = sprintf(
					'<a class="wccon-pagination-item" href="%s" data-link="%s">%s</a>',
					esc_url( $link ),
					esc_attr( $n ),
					number_format_i18n( $n )
				);

				$dots = true;
			} elseif ( $dots ) {
				$page_links[] = '<span class="dots">&hellip;</span>';

				$dots = false;
			}
		}
	}

	$html  = '';
	$html .= "<ul class='wccon-pagination'>\n\t<li>";
	$html .= implode( "</li>\n\t<li>", $page_links );
	$html .= "</li>\n</ul>\n";

	return wp_kses_post( $html );
}

/**
 * Render variation dropdown.
 */
function wccon_variation_dropdown( $args ) {
	$args = wp_parse_args(
		apply_filters( 'wccon_dropdown_variation_attribute_options_args', $args ),
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'wccontour' ),
			'term_ids'         => array(),
		)
	);

			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = (bool) $args['show_option_none'];
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'wccontour' );

	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];

	}

			$html  = '<div class="product-attribute-group">';
			$html .= '<label>' . wc_attribute_label( $attribute ) . '</label>';
			$html .= '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
			$html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms         = wc_get_product_terms(
				$product->get_id(),
				$attribute,
				array(
					'fields' => 'all',
				)
			);
			$selected_term = false;
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options, true ) ) {

					if ( ! empty( $args['term_ids'] ) && ! $selected_term ) {
						if ( in_array( $term->term_id, $args['term_ids'], true ) ) {
							$args['selected'] = $term->slug;
							$selected_term    = true;
						}
					} elseif ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {

						$args['selected'] = $args['product']->get_variation_default_attribute( $args['attribute'] );
						$selected_term    = true;

					}
					$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
				}
			}
		} else {
			foreach ( $options as $option ) {

				$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
				$html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
			}
		}
	}

			$html .= '</select>';
			$html .= '</div>';

	return $html;
}

/**
 * Render variation nice dropdown.
 */
function wccon_variation_nice_dropdown( $args ) {
	$args = wp_parse_args(
		apply_filters( 'wccon_dropdown_variation_attribute_options_args', $args ),
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'wccontour' ),
			'term_ids'         => array(),
		)
	);

			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = (bool) $args['show_option_none'];
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'wccontour' );

	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];

	}

			$html = '<div class="product-attribute-group product-attribute-group__nice-select ">';

			$html              .= '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '" style="display:none;">';
			$html              .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			$ul_options         = '<li data-value="">' . esc_html( $show_option_none_text ) . '</li>';
			$selected_attr_name = '';
	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms         = wc_get_product_terms(
				$product->get_id(),
				$attribute,
				array(
					'fields' => 'all',
				)
			);
			$selected_term = false;
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options, true ) ) {

					if ( ! empty( $args['term_ids'] ) && ! $selected_term ) {
						if ( in_array( $term->term_id, $args['term_ids'], true ) ) {
							$args['selected'] = $term->slug;
							$selected_term    = true;
						}
					} elseif ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {

						$args['selected'] = $args['product']->get_variation_default_attribute( $args['attribute'] );
						$selected_term    = true;

					}
					$html              .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
					$checked_term       = sanitize_title( $args['selected'] ) === $term->slug ? 'true' : 'false';
					$selected_attr_name = $term->name;
					$ul_options        .= '<li role="option" aria-selected="' . $checked_term . '" data-value="' . esc_attr( $term->slug ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</li>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				$checked_term       = sanitize_title( $args['selected'] ) === sanitize_title( $option ) ? 'true' : 'false';
				$selected_attr_name = $option;
				$selected           = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
				$html              .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
				$ul_options        .= '<li role="option" aria-selected="' . $checked_term . '" data-value="' . esc_attr( $option ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</li>';

			}
		}
	}

			$html .= '</select>';
			$html .= '<span class="product-attribute-selected">' . wc_attribute_label( $attribute ) . '<span>' . esc_html( $selected_attr_name ) . '</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="">
			<path d="M6 9L12 15L18 9" stroke="#121212" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
			</svg></span>';
			$html .= '<ul data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">' . $ul_options . '</ul>';
			$html .= '</div>';

	return $html;
}

/**
 * Render variation color type.
 */
function wccon_variation_color_type( $args ) {
	$args = wp_parse_args(
		apply_filters( 'wccon_dropdown_variation_attribute_options_args', $args ),
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'wccontour' ),
			'term_ids'         => array(),
		)
	);

			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = (bool) $args['show_option_none'];
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'wccontour' );

	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];

	}

			$html = '<div class="product-attribute-group product-attribute-group__color ">';

			$html              .= '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '" style="display:none;">';
			$html              .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			$ul_options         = '';
			$selected_attr_name = '';
	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms         = wc_get_product_terms(
				$product->get_id(),
				$attribute,
				array(
					'fields' => 'all',
				)
			);
			$selected_term = false;
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options, true ) ) {

					if ( ! empty( $args['term_ids'] ) && ! $selected_term ) {
						if ( in_array( $term->term_id, $args['term_ids'], true ) ) {
							$args['selected'] = $term->slug;
							$selected_term    = true;
						}
					} elseif ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {

						$args['selected'] = $args['product']->get_variation_default_attribute( $args['attribute'] );
						$selected_term    = true;

					}
					$color              = get_term_meta( $term->term_id, apply_filters( 'wccon_attribute_color_type_term', 'product_attribute_color' ), true );
					$html              .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
					$checked_term       = sanitize_title( $args['selected'] ) === $term->slug ? 'true' : 'false';
					$selected_attr_name = $term->name;
					$ul_options        .= '<li aria-checked="' . $checked_term . '" data-value="' . esc_attr( $term->slug ) . '" title="' . esc_attr( $term->name ) . '"><div style="background-color:' . esc_attr( $color ) . ';"></div></li>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				$checked_term       = sanitize_title( $args['selected'] ) === sanitize_title( $option ) ? 'true' : 'false';
				$selected_attr_name = $option;
				$selected           = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
				$html              .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
				$ul_options        .= '<li aria-checked="' . $checked_term . '" data-value="' . esc_attr( $option ) . '" title="' . esc_attr( $option ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</li>';

			}
		}
	}

			$html .= '</select>';
			$html .= '<span class="product-attribute-label">' . wc_attribute_label( $attribute ) . '</span>';
			$html .= '<ul role="radiogroup" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">' . $ul_options . '</ul>';
			$html .= '</div>';

	return $html;
}

/**
 * Render variation button type.
 */
function wccon_variation_button_type( $args ) {
	$args = wp_parse_args(
		apply_filters( 'wccon_dropdown_variation_attribute_options_args', $args ),
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'wccontour' ),
			'term_ids'         => array(),
		)
	);

			$options               = $args['options'];
			$product               = $args['product'];
			$attribute             = $args['attribute'];
			$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
			$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
			$class                 = $args['class'];
			$show_option_none      = (bool) $args['show_option_none'];
			$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'wccontour' );

	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];

	}

			$html = '<div class="product-attribute-group product-attribute-group__button">';

			$html              .= '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '" style="display:none;">';
			$html              .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';
			$ul_options         = '';
			$selected_attr_name = '';
	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms         = wc_get_product_terms(
				$product->get_id(),
				$attribute,
				array(
					'fields' => 'all',
				)
			);
			$selected_term = false;
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options, true ) ) {

					if ( ! empty( $args['term_ids'] ) && ! $selected_term ) {
						if ( in_array( $term->term_id, $args['term_ids'], true ) ) {
							$args['selected'] = $term->slug;
							$selected_term    = true;
						}
					} elseif ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {

						$args['selected'] = $args['product']->get_variation_default_attribute( $args['attribute'] );
						$selected_term    = true;

					}
					$color              = get_term_meta( $term->term_id, apply_filters( 'wccon_attribute_color_type_term', 'product_attribute_color' ), true );
					$html              .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
					$checked_term       = sanitize_title( $args['selected'] ) === $term->slug ? 'true' : 'false';
					$selected_attr_name = $term->name;
					$ul_options        .= '<li aria-checked="' . $checked_term . '" data-value="' . esc_attr( $term->slug ) . '" title="' . esc_attr( $term->name ) . '">' . esc_html( $term->name ) . '</li>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				$checked_term       = sanitize_title( $args['selected'] ) === sanitize_title( $option ) ? 'true' : 'false';
				$selected_attr_name = $option;
				$selected           = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
				$html              .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
				$ul_options        .= '<li aria-checked="' . $checked_term . '" data-value="' . esc_attr( $option ) . '" title="' . esc_attr( $option ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</li>';

			}
		}
	}

			$html .= '</select>';
			$html .= '<span class="product-attribute-label">' . wc_attribute_label( $attribute ) . '</span>';
			$html .= '<ul role="radiogroup" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">' . $ul_options . '</ul>';
			$html .= '</div>';

	return $html;
}

/**
 * Get settings.
 *
 * @return array
 */
function wccon_get_settings() {
	$default_args = array(
		'account_endpoint' => 'wccon-builder',
		'account_title'    => __( 'Saved lists', 'wccontour' ),
		'list_limit'       => 10,
		'product_limit'    => 10,
		'delete_data'      => false,
		'enabled_compat'   => wccon_fs()->can_use_premium_code() ? true : false,
		'local_storage'    => wccon_fs()->can_use_premium_code() ? true : false,
		'count_list'       => 30,
		'style'            => array(
			'sticky_desktop'    => true,
			'sticky_tablet'     => false,
			'sticky_mobile'     => false,
			'button_variations' => false,
			'image_size'        => 'medium',
		),
		'multilang'        => array(
			'show_modal'   => false,
			'show_account' => false,
		),
		'socials'          => array(

			'items' => array(
				'link'      => 'enabled',
				'facebook'  => 'enabled',
				'twitter'   => 'enabled',
				'pinterest' => '',
				'telegram'  => '',
				'viber'     => '',
				'whatsapp'  => '',
				'linkedin'  => '',
			),
		),
	);
	$settings     = wp_cache_get( 'wccon_settings' );
	if ( false === $settings ) {
		$settings = get_option( 'wccon_settings' );
		if ( ! $settings ) {
			return $default_args;
		}
		wp_cache_set( 'wccon_settings', $settings );
		return $settings;
	}
	return $settings;
}

/**
 * Get social items.
 *
 * @return array
 */
function wccon_get_social_links( $page_id, $list_id, $type = 'users' ) {
	$wccon_settings = wccon_get_settings();
	$socials_links  = array();
	global $wp;
	$wccon_multilang = WCCON_Multilang::instance();

	$social_base_url = $page_id ? get_permalink( $page_id ) : home_url( $wp->request );
	if ( $wccon_multilang->is_wpml_enabled() && $page_id ) {
		$post_language_details = apply_filters( 'wpml_post_language_details', null, $page_id );
		$wccon_multilang->switch_lang( $post_language_details['language_code'] );
		$social_base_url = get_permalink( $page_id );
		$wccon_multilang->restore_lang();
	}

	$social_list = $wccon_settings['socials']['items'];
	foreach ( $social_list as $social_name => $social_enabled ) {

		if ( 'enabled' === $social_enabled ) {

			switch ( $social_name ) {
				case 'link':
					$link = add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url );

					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'Link', 'wccontour' ),
						'class'  => 'share-link',
						'svg_id' => 'share-link',
						'link'   => $link,
					);
					break;
				case 'telegram':
					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'Telegram', 'wccontour' ),
						'class'  => 'share-telegram',
						'svg_id' => 'share-telegram',
						'link'   => 'https://t.me/share/url?text=&url=' . add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url ),
					);
					break;
				case 'viber':
					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'Viber', 'wccontour' ),
						'class'  => 'share-viber',
						'svg_id' => 'share-viber',
						'link'   => 'viber://forward/?text=' . add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url ),
					);
					break;
				case 'facebook':
					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'Facebook', 'wccontour' ),
						'class'  => 'share-fb',
						'svg_id' => 'share-fb',
						'link'   => 'https://www.facebook.com/sharer/sharer.php?u=' . add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url ),
					);
					break;
				case 'twitter':
					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'Twitter', 'wccontour' ),
						'class'  => 'share-twitter',
						'svg_id' => 'share-twitter',
						'link'   => 'https://twitter.com/intent/tweet?url=' . add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url ),
					);
					break;
				case 'pinterest':
					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'Pinterest', 'wccontour' ),
						'class'  => 'share-pin',
						'svg_id' => 'share-pin',
						'link'   => 'https://pinterest.com/pin/create/button/?url=' . add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url ),
					);
					break;
				case 'linkedin':
					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'LinkedIn', 'wccontour' ),
						'class'  => 'share-in',
						'svg_id' => 'share-in',
						'link'   => 'https://www.linkedin.com/shareArticle?mini=true&url=' . add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url ),
					);
					break;
				case 'whatsapp':
					$socials_links[] = array(
						'name'   => $social_name,
						'title'  => __( 'WhatsApp', 'wccontour' ),
						'class'  => 'share-wapp',
						'svg_id' => 'share-wapp',
						'link'   => 'https://api.whatsapp.com/send?text=' . add_query_arg( array( 'wccon-list' => $list_id ), $social_base_url ),
					);
					break;
			}
		}
	}

	return apply_filters( 'wccon_socials_links', $socials_links, $list_id );
}

/**
 * Check whether component is compatible.
 */
function wccon_component_is_compatible( $component_slug, $cp_data, $clone = 0 ) {
	$compatible_status = 'not_selected';
	foreach ( $cp_data as $cp_row ) {
		if ( isset( $cp_row['slug'] ) && $cp_row['slug'] === $component_slug && (int) $cp_row['clone'] === (int) $clone ) {
			$compatible_status = true;
			if ( ! $cp_row['compatible'] ) {
				$compatible_status = false;
				break;
			}
		}
	}
	return $compatible_status;
}

/**
 * Get tippy text for component.
 *
 * @param string $component_slug Slug.
 * @param array  $cp_data Compatibility Data.
 * @param int    $clone Is component is cloned.
 */
function wccon_component_tippy_text( $component_slug, $cp_data, $clone = 0 ) {
	$tippy_text = '';
	foreach ( $cp_data as $cp_row ) {
		if ( isset( $cp_row['slug'] ) && $cp_row['slug'] === $component_slug && (int) $cp_row['clone'] === (int) $clone ) {

			if ( ! $cp_row['compatible'] ) {
				$tippy_text = $cp_row['tippyText'];
				break;
			}
		}
	}
	return $tippy_text;
}

/**
 * Check whether subgroup is compatible.
 */
function wccon_subgroup_is_compatible( $sub_products, $cp_data ) {
	$compatible_status = 'not_selected';
	foreach ( $cp_data as $cp_row ) {
		foreach ( $sub_products as $product ) {
			if ( isset( $cp_row['slug'] ) && $cp_row['slug'] === $product['component'] && (int) $cp_row['clone'] === (int) $product['clone'] ) {
				$compatible_status = true;
				if ( ! $cp_row['compatible'] ) {
					$compatible_status = false;
					return $compatible_status;
				}
			}
		}
	}
	return $compatible_status;
}

/**
 * Account edit wccon-builder link.
 */
function wccon_get_edit_account_link( $list_id ) {
	$wccon_settings = wccon_get_settings();
	$link           = untrailingslashit( wc_get_account_endpoint_url( $wccon_settings['account_endpoint'] ) ) . '/edit/' . $list_id;
	return apply_filters( 'wccon_edit_account_link', $link, $list_id );
}

/**
 * Users link.
 */
function wccon_get_edit_users_list_link( $page_id, $list_id ) {
	global $wp;
	$base_url = $page_id ? get_permalink( $page_id ) : home_url( $wp->request );
	$link     = add_query_arg( array( 'wccon-list' => $list_id ), $base_url );
	return apply_filters( 'wccon_edit_users_list_link', $link, $list_id, $page_id );
}

/**
 * Check whether compatible is enabled in Settings.
 */
function wccon_is_compatibility_enabled() {

	if ( ! wccon_fs()->can_use_premium_code() ) {
		return false;
	}

	$settings              = wccon_get_settings();
	$enabled_compatibility = $settings['enabled_compat'] ?? true;
	if ( $enabled_compatibility ) {
		return true;
	}
		return false;
}

/**
 * Check whether localStorage is enabled in Settings.
 */
function wccon_local_storage_enabled() {
	if ( ! wccon_fs()->can_use_premium_code() ) {
		return false;
	}

	$settings              = wccon_get_settings();
	$enabled_local_storage = $settings['local_storage'] ?? true;
	if ( $enabled_local_storage ) {
		return true;
	}
		return false;
}

/**
 * Upload media from external url.
 */
function wccon_upload_from_url( $url, $title = null ) {

	require_once ABSPATH . '/wp-load.php';
	require_once ABSPATH . '/wp-admin/includes/image.php';
	require_once ABSPATH . '/wp-admin/includes/file.php';
	require_once ABSPATH . '/wp-admin/includes/media.php';

	// Download url to a temp file
	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) {
		return false;
	}

	$filename  = pathinfo( $url, PATHINFO_FILENAME );
	$extension = pathinfo( $url, PATHINFO_EXTENSION );

	if ( ! $extension ) {

		$mime = mime_content_type( $tmp );
		$mime = is_string( $mime ) ? sanitize_mime_type( $mime ) : false;

		$mime_extensions = array(

			'text/plain'         => 'txt',
			'text/csv'           => 'csv',
			'application/msword' => 'doc',
			'image/jpg'          => 'jpg',
			'image/jpeg'         => 'jpeg',
			'image/gif'          => 'gif',
			'image/png'          => 'png',
			'video/mp4'          => 'mp4',
		);

		if ( isset( $mime_extensions[ $mime ] ) ) {

			$extension = $mime_extensions[ $mime ];
		} else {

			@unlink( $tmp );
			return false;
		}
	}

	$args = array(
		'name'     => "$filename.$extension",
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $args, 0, $title );
	@unlink( $tmp );

	if ( is_wp_error( $attachment_id ) ) {
		return false;
	}
	return (int) $attachment_id;
}

/**
 * Get nonce2.
 */
function wccon_get_nonce2() {
	if ( wccon_fs()->can_use_premium_code() ) {
		return true;
	}
	return false;
}

/**
 * Get languages from WPML\Polylang.
 */
function wccon_get_languages() {
	global $sitepress;
	$return_languages = array();
	if ( class_exists( 'SitePress' ) && $sitepress instanceof SitePress ) {
		$languages    = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
		$default_lang = $sitepress->get_default_language();
		foreach ( $languages as $return_language ) {
			$return_languages[] = array(
				'default' => $default_lang,
				'active'  => $return_language['active'],
				'name'    => $return_language['native_name'],
				'code'    => $return_language['language_code'],
				'flag'    => $return_language['country_flag_url'],
			);
		}
		return $return_languages;
	}
	if ( function_exists( 'pll_the_languages' ) ) {
		$languages    = pll_the_languages(
			array(
				'raw'           => 1,
				'echo'          => 0,
				'hide_if_empty' => 0,
				'show_flags'    => 1,
			)
		);
		$default_lang = pll_default_language( 'slug' );
		foreach ( $languages as $return_language ) {

			$return_languages[] = array(
				'default' => $default_lang,
				'active'  => $return_language['current_lang'],
				'name'    => $return_language['name'],
				'code'    => $return_language['slug'],
				'flag'    => $return_language['flag'],
			);
		}
		return $return_languages;
	}
	return false;
}

/**
 * Get attribute taxonomy by name.
 */
function wccon_get_attribute_taxonomy_by_name( $attribute_name ) {

	$transient_key = sprintf( 'wccon_variation_swatches_cache_attribute_taxonomy_%s', $attribute_name );

	if ( ! taxonomy_exists( $attribute_name ) ) {
		return false;
	}

	if ( 'pa_' === substr( $attribute_name, 0, 3 ) ) {
		$attribute_name = str_replace( 'pa_', '', wc_sanitize_taxonomy_name( $attribute_name ) );
	} else {
		return false;
	}

	if ( false === ( $attribute_taxonomy = get_transient( $transient_key ) ) ) {

		global $wpdb;
		$table_name         = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
		$attribute_taxonomy = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name}  WHERE attribute_name=%s", $attribute_name ), ARRAY_A );

		set_transient( $transient_key, $attribute_taxonomy );
	}

	return apply_filters( 'wccon_variation_swatches_get_wc_attribute_taxonomy', $attribute_taxonomy, $attribute_name );
}

/**
 * Returns the main instance of WCCON_Plugin.
 *
 * @since  1.0.0
 * @return WCCON_Plugin
 */
function WCCON() {
	return WCCON_Plugin::instance();
}
