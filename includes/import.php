<?php
/**
 * Import Class.
 *
 * Handles import functionality.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Import class.
 */
class WCCON_Import {
	use WCCON\Instancetiable;
	public function __construct() {
		add_action( 'wp_ajax_wccon_ajax_demo_import', array( $this, 'demo_import' ) );
	}

	/**
	 * Ajax callback for importing one batch of products from a CSV.
	 */
	public function demo_import() {
		check_ajax_referer( 'wccon-nonce', 'nonce' );
		global $wpdb;

		$demo_id    = absint( $_POST['id'] );
		$last_index = isset( $_POST['position'] ) ? absint( wp_unslash( $_POST['position'] ) ) : false;

		if ( ! $demo_id ) {
			wp_send_json_error(
				array(
					'message' => 'ID not found',
				)
			);
		}

		$config_table     = WCCON_DB::tables( 'data', 'name' );
		$shortcodes_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$config_table}" );
		$shortcodes_count = $shortcodes_count ? (int) $shortcodes_count : 0;

		if ( ! wccon_fs()->can_use_premium_code() && $shortcodes_count > 0 ) {
			wp_send_json_error(
				array(
					'sh'      => $shortcodes_count,
					'message' => __( 'To use more builder items, upgrade to PRO' ),
				)
			);
		}

		$wp_upload_dir = wp_upload_dir();
		$file_name     = 'wccon-demo-' . $demo_id . '.csv';
		$download_to   = $wp_upload_dir['basedir'] . '/' . $file_name;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( ! file_exists( $download_to ) ) {
			$file_one_path = 'https://wccontour.evelynwaugh.com.ua/wp-content/uploads/' . $file_name;
			$download_file = download_url( $file_one_path, $timeout = 600 );

			if ( is_wp_error( $download_file ) ) {
				wp_send_json_error(
					array(
						'message' => 'Download failed: ' . $download_file->get_error_message(),
					)
				);
			}
			@copy( $download_file, $download_to );
			unlink( $download_file );
		}

		include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

		$params = array(

			'start_pos'       => $last_index ? $last_index : 0,
			'mapping'         => array_combine( $this->get_mappings()['map_from'], $this->get_mappings()['map_to'] ),
			'update_existing' => false,
			'lines'           => 20,
			'parse'           => true,

		);

		$importer         = WC_Product_CSV_Importer_Controller::get_importer( $download_to, $params );
		$results          = $importer->import();
		$percent_complete = $importer->get_percent_complete();
		$file_position    = $importer->get_file_position();

		if ( 100 === $percent_complete ) {
			// @codingStandardsIgnoreStart.
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_original_id' ) );
			$wpdb->delete( $wpdb->posts, array(
				'post_type'   => 'product',
				'post_status' => 'importing',
			) );
			$wpdb->delete( $wpdb->posts, array(
				'post_type'   => 'product_variation',
				'post_status' => 'importing',
			) );
			// @codingStandardsIgnoreEnd.

			// Clean up orphaned data.
			$wpdb->query(
				"
				DELETE {$wpdb->posts}.* FROM {$wpdb->posts}
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->posts}.post_parent
				WHERE wp.ID IS NULL AND {$wpdb->posts}.post_type = 'product_variation'
			"
			);
			$wpdb->query(
				"
				DELETE {$wpdb->postmeta}.* FROM {$wpdb->postmeta}
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->postmeta}.post_id
				WHERE wp.ID IS NULL
			"
			);
			// @codingStandardsIgnoreStart.
			$wpdb->query( "
				DELETE tr.* FROM {$wpdb->term_relationships} tr
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
				LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE wp.ID IS NULL
				AND tt.taxonomy IN ( '" . implode( "','", array_map( 'esc_sql', get_object_taxonomies( 'product' ) ) ) . "' )
			" );
			// @codingStandardsIgnoreEnd.

			$page_id = $this->set_wccon_data( $demo_id );
			// Send success.
			wp_send_json_success(
				array(
					'position'       => 'done',
					'position_index' => $file_position,
					'percentage'     => 100,
					'result'         => $results,
					'imported'       => count( $results['imported'] ),
					'failed'         => count( $results['failed'] ),
					'updated'        => count( $results['updated'] ),
					'skipped'        => count( $results['skipped'] ),
					'mappings'       => array_combine( $this->get_mappings()['map_from'], $this->get_mappings()['map_to'] ),
					'map'            => $this->get_mappings(),
					'page_url'       => get_permalink( $page_id ),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'position'       => 'process',
					'position_index' => $file_position,
					'percentage'     => $percent_complete,
					'imported'       => count( $results['imported'] ),
					'failed'         => count( $results['failed'] ),
					'updated'        => count( $results['updated'] ),
					'skipped'        => count( $results['skipped'] ),
				)
			);
		}
	}

	/**
	 * Mappings.
	 */
	public function get_mappings() {
		return array(
			'map_from' => array(
				'ID',
				'Type',
				'SKU',
				'Name',
				'Published',
				'Is featured?',
				'Visibility in catalog',
				'Short description',
				'Description',
				'Date sale price starts',
				'Date sale price ends',
				'Tax status',
				'Tax class',
				'In stock?',
				'Stock',
				'Low stock amount',
				'Backorders allowed?',
				'Sold individually?',
				'Weight (kg)',
				'Length (cm)',
				'Width (cm)',
				'Height (cm)',
				'Sale price',
				'Regular price',
				'Categories',
				'Tags',
				'Images',
				'Parent',
				'Position',
				'Attribute 1 name',
				'Attribute 1 value(s)',
				'Attribute 1 visible',
				'Attribute 1 global',
				'Attribute 2 name',
				'Attribute 2 value(s)',
				'Attribute 2 visible',
				'Attribute 2 global',
				'Attribute 3 name',
				'Attribute 3 value(s)',
				'Attribute 3 visible',
				'Attribute 3 global',

				'Attribute 4 name',
				'Attribute 4 value(s)',
				'Attribute 4 visible',
				'Attribute 4 global',

				'Attribute 5 name',
				'Attribute 5 value(s)',
				'Attribute 5 visible',
				'Attribute 5 global',

				'Meta: wccon_enable_compatibility',
				'Meta: wccon_global_compatibility',
				'Meta: wccon_compatibility_comparator',
				'Meta: wccon_strict_taxonomy',
				'Meta: wccon_strict_term',
				'Meta: wccon_enable_variation_compatibility',

			),
			'map_to'   => array(
				'id',
				'type',
				'sku',
				'name',
				'published',
				'featured',
				'catalog_visibility',
				'short_description',
				'description',
				'date_on_sale_from',
				'date_on_sale_to',
				'tax_status',
				'tax_class',
				'stock_status',
				'stock_quantity',
				'low_stock_amount',
				'backorders',
				'sold_individually',
				'weight',
				'length',
				'width',
				'height',
				'sale_price',
				'regular_price',
				'category_ids',
				'tag_ids',
				'images',
				'parent_id',
				'menu_order',
				'attributes:name1',
				'attributes:value1',
				'attributes:visible1',
				'attributes:taxonomy1',

				'attributes:name2',
				'attributes:value2',
				'attributes:visible2',
				'attributes:taxonomy2',

				'attributes:name3',
				'attributes:value3',
				'attributes:visible3',
				'attributes:taxonomy3',

				'attributes:name4',
				'attributes:value4',
				'attributes:visible4',
				'attributes:taxonomy4',

				'attributes:name5',
				'attributes:value5',
				'attributes:visible5',
				'attributes:taxonomy5',

				'meta:wccon_enable_compatibility',
				'meta:wccon_global_compatibility',
				'meta:wccon_compatibility_comparator',
				'meta:wccon_strict_taxonomy',
				'meta:wccon_strict_term',
				'meta:wccon_enable_variation_compatibility',

			),
		);
	}

	/**
	 * Create page for displaying builder.
	 */
	public function create_page( $demo_id, $shortcode_id ) {
		$post_title   = '';
		$post_content = '';
		if ( $demo_id == 1 ) {
			$post_title   = __( 'PC Builder', 'wccontour' );
			$post_content = '<!-- wp:shortcode -->[wccon-builder id="' . $shortcode_id . '" title="PC Builder"]<!-- /wp:shortcode -->';
		} elseif ( $demo_id == 2 ) {
			$post_title   = __( 'Bicycle Builder', 'wccontour' );
			$post_content = '<!-- wp:shortcode -->[wccon-builder id="' . $shortcode_id . '" title="Bicycle Builder"]<!-- /wp:shortcode -->';
		} elseif ( $demo_id == 3 ) {
			$post_title   = __( 'Camping Builder', 'wccontour' );
			$post_content = '<!-- wp:shortcode -->[wccon-builder id="' . $shortcode_id . '" title="Camping Builder"]<!-- /wp:shortcode -->';
		}
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_status'  => 'publish',
				'meta_input'   => array(
					'wccon_import' => '1',
				),
			)
		);

		return $page_id;
	}

	/**
	 * Set data from json file.
	 */
	public function set_wccon_data( $demo_id ) {

		$file_name = 'demo' . $demo_id . '.json';
		$file_path = WCCON_PLUGIN_PATH . '/assets/front/js/' . $file_name;

		// Read the JSON file contents.
		$json_data = file_get_contents( $file_path );

		// Decode the JSON data into an array.
		$data         = json_decode( $json_data, true );
		$shortcode_id = (int) $this->get_random_shortcode_id( $data['shortcode_id'] );

		// create page.
		$page_id = $this->create_page( $demo_id, $shortcode_id );

		global $wpdb;

		$config_table    = WCCON_DB::tables( 'data', 'name' );
		$data_group      = WCCON_DB::tables( 'groups', 'name' );
		$data_group_meta = WCCON_DB::tables( 'groups_meta', 'name' );
		$data_group      = WCCON_DB::tables( 'groups', 'name' );
		$data_group_meta = WCCON_DB::tables( 'groups_meta', 'name' );

		// insert.
		$wpdb->insert(
			$config_table,
			array(
				'shortcode_id' => $shortcode_id,
				'title'        => $data['title'],
				'type'         => $data['type'],
				'page_id'      => $page_id,

			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
			)
		);
		$config_id = $wpdb->insert_id;
		if ( ! empty( $data['groups'] ) ) {
			foreach ( $data['groups'] as $group ) {
				$wpdb->insert(
					$data_group,
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
				$this->store_components( $group, $config_id, $group_id );

				// meta groups.
				foreach ( $group['meta'] as $meta_key => $meta_value ) {
					$wpdb->insert(
						$data_group_meta,
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

		return $page_id;
	}

	/**
	 * Get random shortcode id.
	 */
	public function get_random_shortcode_id( $shortcode_id ) {
		global $wpdb;

		$config_table = WCCON_DB::tables( 'data', 'name' );

		$shortcode_exists = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table} WHERE shortcode_id=%d", $shortcode_id ), ARRAY_A );
		if ( $shortcode_exists ) {
			$shortcode_id = wp_rand( 1, 500 );
			return $this->get_random_shortcode_id( $shortcode_id );
		}
		return $shortcode_id;
	}

	/**
	 * Store components to db.
	 *
	 * @param array $group Group data.
	 * @param int   $config_id Builder/config id.
	 * @param int   $group_id Group ID.
	 * @return void
	 */
	public function store_components( array $group, int $config_id, int $group_id ) {
		global $wpdb;

		$data_compo      = WCCON_DB::tables( 'components', 'name' );
		$data_compo_meta = WCCON_DB::tables( 'components_meta', 'name' );
		$data_group      = WCCON_DB::tables( 'groups', 'name' );
		$data_group_meta = WCCON_DB::tables( 'groups_meta', 'name' );
		$data_widgets    = WCCON_DB::tables( 'widgets', 'name' );

		foreach ( $group['components'] as $component ) {
			$image_url     = $component['image_id'];
			$attachment_id = 0;
			if ( $image_url ) {
				$attachment_id = wccon_upload_from_url( $image_url );
			}
			if ( isset( $component['parent_id'] ) ) {
				$wpdb->insert(
					$data_group,
					array(
						'config_id' => $config_id,
						'title'     => $component['title'],
						'slug'      => $component['slug'],
						'parent_id' => $group_id,
						'image_id'  => (int) $attachment_id,
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
						$subimage_url  = $sub_component['image_id'];
						$attachment_id = 0;
						if ( $image_url ) {
							$attachment_id = wccon_upload_from_url( $subimage_url );
						}
						$wpdb->insert(
							$data_compo,
							array(
								'group_id' => $subgroup_id,
								'title'    => $sub_component['title'],
								'slug'     => $sub_component['slug'],
								'image_id' => (int) $attachment_id,
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
							if ( 'product_query' === $meta_key ) {
								$meta_value = $this->modify_product_query_meta( $meta_value );
							}

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
						$sub_component_widgets = $this->modify_widgets_meta( $sub_component['widgets'] );
						$wpdb->insert(
							$data_widgets,
							array(
								'component_id' => $sub_component_id,
								'widget_value' => maybe_serialize( $sub_component_widgets ),

							),
							array(
								'%d',
								'%s',
							)
						);

					}
				}
			} else {
				$image_url     = $component['image_id'];
				$attachment_id = 0;
				if ( $image_url ) {
					$attachment_id = wccon_upload_from_url( $image_url );
				}
				$wpdb->insert(
					$data_compo,
					array(
						'group_id' => $group_id,
						'title'    => $component['title'],
						'slug'     => $component['slug'],
						'image_id' => (int) $attachment_id,
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

				if ( 'product_query' === $meta_key ) {
					$meta_value = $this->modify_product_query_meta( $meta_value );

				}
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
			$component_widgets = $this->modify_widgets_meta( $component['widgets'] );
			$wpdb->insert(
				$data_widgets,
				array(
					'component_id' => (int) $component['id'] ? (int) $component['id'] : $component_id,
					'widget_value' => maybe_serialize( $component_widgets ),

				),
				array(
					'%d',
					'%s',
				)
			);

		}
	}

	/**
	 * Modify query meta.
	 */
	public function modify_product_query_meta( $value ) {
		$new_value = array();
		foreach ( $value as $key => $row ) {
			if ( 'product_tax' === $row['groupId'] ) {

				foreach ( $row['value'] as $term_key => $term_value ) {
					if ( strpos( $term_value, 'cat:' ) !== false ) {
						$term_slug                 = str_replace( 'cat:', '', $term_value );
						$term                      = get_term_by( 'slug', $term_slug, 'product_cat' );
						$row['value'][ $term_key ] = $term->term_id;
					} else {
						$row['value'][ $term_key ] = $term_value;
					}
				}
			}
			$new_value[ $key ] = $row;
		}
		return $new_value;
	}

	/**
	 * Modify widgets meta.
	 */
	public function modify_widgets_meta( $value ) {
		$new_value = array();
		foreach ( $value as $key => $row ) {
			if ( 'product_cat' === $row['value'] ) {
				$include_terms     = explode( ',', $row['include'] );
				$exclude_terms     = explode( ',', $row['exclude'] );
				$new_include_terms = array();
				$new_exclude_terms = array();
				foreach ( $include_terms as $term_value ) {
					$term                = get_term_by( 'slug', $term_value, 'product_cat' );
					$new_include_terms[] = $term->term_id;
				}
				foreach ( $exclude_terms as $term_value ) {
					$term                = get_term_by( 'slug', $term_value, 'product_cat' );
					$new_exclude_terms[] = $term->term_id;
				}
				$new_value[ $key ]            = $row;
				$new_value[ $key ]['include'] = implode( ',', $new_include_terms );
				$new_value[ $key ]['exclude'] = implode( ',', $new_exclude_terms );
			} else {
				$new_value[ $key ] = $row;
			}
		}
		return $new_value;
	}
}
