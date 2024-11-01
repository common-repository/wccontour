<?php
/**
 * Admin Class
 *
 * Handles generic Admin and WooCommerce account page functionality .
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Admin class.
 */
class WCCON_Admin {
	use WCCON\Instancetiable;

	protected $endpoint_slug  = 'wccon-builder';
	protected $endpoint_title = '';
	public $settings          = array();
	public function __construct() {

		$settings = wccon_get_settings();
		if ( $settings ) {
			$this->endpoint_slug  = ! empty( $settings['account_endpoint'] ) ? sanitize_title( $settings['account_endpoint'] ) : 'wccon-builder';
			$this->endpoint_title = ! empty( $settings['account_title'] ) ? esc_html( $settings['account_title'] ) : esc_html( apply_filters( 'wccon_account_endpoint_title', __( 'Saved lists', 'wccontour' ) ) );
			$this->settings       = $settings;
		}
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'render_builder' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'render_settings' ) );

		add_action( 'admin_head', array( $this, 'for_correct_react' ) );

		// register sidebar.
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		// add_filter( 'widget_form_callback', array( $this, 'show_widgets_controls' ), 10, 2 );
		add_filter( 'widget_display_callback', array( $this, 'show_widgets_front' ), 10, 2 );

		// add endpoint.
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ), 0 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'new_menu_items' ) );
		add_action( 'woocommerce_account_' . $this->endpoint_slug . '_endpoint', array( $this, 'show_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'account_redirect' ) );

		// clear transients.
		add_action( 'woocommerce_attribute_added', array( $this, 'clear_cache_on_attribute_added' ), 10, 2 );
		add_action( 'woocommerce_attribute_updated', array( $this, 'clear_cache_on_attribute_updated' ), 10, 3 );
		add_action( 'woocommerce_attribute_deleted', array( $this, 'clear_cache_on_attribute_deleted' ), 10, 3 );

	}

	/**
	 * Add admin page.
	 */
	public function add_page() {
		add_menu_page(
			__( 'WC Contour', 'wccontour' ),
			__( 'WC Contour', 'wccontour' ),
			'manage_options',
			'wccon-settings',
			null,
			'dashicons-tagcloud',
			6
		);

		add_submenu_page( 'wccon-settings', __( 'Settings', 'wccontour' ), __( 'Settings', 'wccontour' ), 'manage_woocommerce', 'wccon-settings', array( $this, 'display_settings' ) );
		add_submenu_page( 'wccon-settings', __( 'Builder', 'wccontour' ), __( 'Builder', 'wccontour' ), 'manage_woocommerce', 'wccon-builder', array( $this, 'display_builder' ) );

	}

	/**
	 * Display builder in admin.
	 */
	public function display_builder() {
		?>
		<div id="wc-contour"></div>
		<?php
	}

	/**
	 * Display settings in admin.
	 */
	public function display_settings() {
		?>
		<div id="wccon-settings"></div>
		<?php
	}

	/**
	 * Enqueue settings scripts.
	 */
	public function render_settings() {
		if ( strpos( get_current_screen()->base, 'wccon-settings' ) === false ) {
			return;
		}
		global $sitepress;
		$data = wccon_get_settings();

		$script_asset_path = WCCON_PLUGIN_PATH . 'assets/dist/settings.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_asset_path ),
			);
		$ajaxurl           = admin_url( 'admin-ajax.php' );
		if ( $sitepress instanceof SitePress ) {
			$ajaxurl = add_query_arg( array( 'lang' => $sitepress->get_current_language() ), $ajaxurl ); // really?
		}
		list($shortcodes_data,$displayed_pages) = $this->get_shortcodes_data();
		wp_enqueue_script( 'wccon-settings', plugins_url( 'assets/dist/settings.js', WCCON_PLUGIN_DIR ), $script_asset['dependencies'], $script_asset['version'], true );
		wp_localize_script(
			'wccon-settings',
			'WCCON_SETTINGS',
			array(
				'ajax_url'    => $ajaxurl,
				'shortcodes'  => $shortcodes_data,
				'data'        => $data,
				'images_path' => plugins_url( 'assets/images/', WCCON_PLUGIN_DIR ),
				'image_sizes' => get_intermediate_image_sizes(),
				'nonce'       => wp_create_nonce( 'wccon-nonce' ),
				'pro'         => wccon_fs()->can_use_premium_code(),
				'pricing_url' => admin_url( 'admin.php?page=wccon-settings-pricing' ),
			)
		);
	}

	/**
	 * Enqueue builder scripts.
	 */
	public function render_builder() {

		if ( strpos( get_current_screen()->base, 'wccon-builder' ) === false ) {
			return;
		}

		// meta keys.
		$meta_keys         = wccon_get_product_meta_keys();
		$script_asset_path = WCCON_PLUGIN_PATH . 'assets/dist/app.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_asset_path ),
			);

		$product_taxonomies = array_merge(
			array_map(
				function( $el ) {
					return array(
						'label' => $el['label'],
						'value' => $el['value'],
					);
				},
				wccon_get_product_taxonomies()
			),
			array_merge( ...wp_list_pluck( wccon_get_product_taxonomies(), 'children' ) )
		);

		$ajaxurl                                = admin_url( 'admin-ajax.php' );
		list($shortcodes_data,$displayed_pages) = $this->get_shortcodes_data();
		wp_enqueue_media();
		wp_enqueue_script( 'wccon-admin-builder', plugins_url( 'assets/dist/app.js', WCCON_PLUGIN_DIR ), $script_asset['dependencies'], $script_asset['version'], true );
		wp_localize_script(
			'wccon-admin-builder',
			'WCCON_BUILDER_ADMIN',
			array(
				'ajax_url'              => $ajaxurl,
				'shortcodes'            => $shortcodes_data,
				'product_tax'           => wccon_get_product_taxonomies(),
				'product_attributes'    => wccon_get_product_attributes(),
				'test'                  => $product_taxonomies,
				'product_meta'          => $meta_keys,
				'formatted_product_tax' => wccon_get_linear_product_taxonomies(),
				'displayed_pages'       => $displayed_pages,
				'languages'             => wccon_get_languages(),

				'terms'                 => get_terms(
					array(
						'taxonomy'   => 'product_cat',

						'hide_empty' => false,
						'fields'     => 'ids',
					)
				),

				'nonce'                 => wp_create_nonce( 'wccon-nonce' ),
				'pro'                   => wccon_fs()->can_use_premium_code(),
				'pricing_url'           => admin_url( 'admin.php?page=wccon-settings-pricing' ),
			)
		);
	}


	/**
	 * Helper function to get builder rows from db.
	 */
	public function get_shortcodes_data() {

		 global $wpdb;

		$config_table      = WCCON_DB::tables( 'data', 'name' );
		$components_table  = WCCON_DB::tables( 'components', 'name' );
		$compo_meta_table  = WCCON_DB::tables( 'components_meta', 'name' );
		$groups_table      = WCCON_DB::tables( 'groups', 'name' );
		$groups_meta_table = WCCON_DB::tables( 'groups_meta', 'name' );
		$widgets_table     = WCCON_DB::tables( 'widgets', 'name' );

		$shortcodes_data = array();
		$shortcodes      = $wpdb->get_results( "SELECT * FROM {$config_table}", ARRAY_A );
		$displayed_pages = array();

		if ( ! empty( $shortcodes ) ) {

			foreach ( $shortcodes as $key => $shortcode ) {

				$displayed_page_id = absint( $shortcode['page_id'] );
				$displayed_page    = get_post( $displayed_page_id );
				if ( $displayed_page_id && $displayed_page ) {

					$displayed_pages[] = array(
						'label' => $displayed_page->post_title,
						'value' => $displayed_page_id,
					);
				} else {
					$displayed_pages[] = array(
						'label' => __( 'Select page', 'wccontour' ),
						'value' => '',
					);
				}

				$shortcodes_data[ $key ] = $shortcode;
				$shortcode_groups        = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE config_id=%d AND parent_id=0", (int) $shortcode['id'] ), ARRAY_A );

				$existing_sub_components     = $wpdb->get_results( $wpdb->prepare( "SELECT c.id, c.title, c.group_id, c.slug, c.image_id, c.position FROM {$components_table} c LEFT JOIN {$groups_table} g ON (g.id=c.group_id AND g.parent_id=%d) WHERE c.group_id=%d ", 50, 51 ), ARRAY_A );
				$existing_sub_components_ids = wp_list_pluck( $existing_sub_components, 'id' );

				$shortcodes_data[ $key ]['groups'] = array();

				if ( ! empty( $shortcode_groups ) ) {

					foreach ( $shortcode_groups as $key_group => $group ) {

						$shortcodes_data[ $key ]['groups'][ $key_group ] = $group;
						$group_components                                = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$components_table}  WHERE group_id=%d ", (int) $group['id'] ), ARRAY_A );
						$sub_groups                                      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE parent_id=%d ", (int) $group['id'] ), ARRAY_A );
						$shortcodes_data[ $key ]['groups'][ $key_group ]['components'] = array();
						if ( ! empty( $group_components ) ) {
							$component_index = 0;
							foreach ( $group_components as $key_component => $component ) {
								$component_index++;
								$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $key_component ] = array(
									'id'       => $component['id'],
									'slug'     => $component['slug'],
									'title'    => $component['title'],
									'image_id' => $component['image_id'],
									'position' => $component['position'],
								);
								// meta.
								$components_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$compo_meta_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );
								if ( ! empty( $components_meta ) ) {
									$components_meta = wp_list_pluck( $components_meta, 'meta_value', 'meta_key' );
									array_walk(
										$components_meta,
										function ( &$meta_value, $meta_key ) {
											$meta_value = maybe_unserialize( $meta_value );
										}
									);
									$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $key_component ]['meta'] = $components_meta;
								}
								// widgets.
								$component_widgets = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$widgets_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );

								if ( ! is_null( $component_widgets ) ) {
									$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $key_component ]['widgets'] = maybe_unserialize( $component_widgets['widget_value'] );
								} else {
									$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $key_component ]['widgets'] = array();
								}
							}
						}

						if ( ! empty( $sub_groups ) ) {
							foreach ( $sub_groups as $key_sub_group => $sub_group ) {
								$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ] = wp_parse_args( array( 'parent_id' => $group['slug'] ), $sub_group );

								$subgroup_components = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$components_table} WHERE group_id=%d ", (int) $sub_group['id'] ), ARRAY_A );

								if ( ! empty( $subgroup_components ) ) {
									foreach ( $subgroup_components as $key_component => $component ) {

										$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ] = array(
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
											$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['meta'] = $subcomponents_meta;
										}

										// subcomponent widgets.
										$subcomponent_widgets = $wpdb->get_row( $wpdb->prepare( "SELECT widget_value FROM {$widgets_table} WHERE component_id=%d ", $component['id'] ), ARRAY_A );

										if ( ! is_null( $subcomponent_widgets ) ) {

											$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['widgets'] = maybe_unserialize( $subcomponent_widgets['widget_value'] );
										} else {
											$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ]['components'][ $key_component ]['widgets'] = array();
										}
									}

									$sorted_subcomponents = array_values( $shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ]['components'] );

									uasort(
										$sorted_subcomponents,
										function ( $a, $b ) {

											if ( (int) $a['position'] === (int) $b['position'] ) {

												return 0;
											}
											return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
										}
									);

									$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ]['components'] = array_values( $sorted_subcomponents );
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
									$shortcodes_data[ $key ]['groups'][ $key_group ]['components'][ $component_index ]['meta'] = $subgroup_meta;
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
							$shortcodes_data[ $key ]['groups'][ $key_group ]['meta'] = $group_meta;
						}

						$sorted_components = array_values( $shortcodes_data[ $key ]['groups'][ $key_group ]['components'] );

						uasort(
							$sorted_components,
							function ( $a, $b ) {

								if ( (int) $a['position'] === (int) $b['position'] ) {

									return 0;
								}
								return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
							}
						);

						$shortcodes_data[ $key ]['groups'][ $key_group ]['components'] = array_values( $sorted_components );
					}
					$sorted_groups = array_values( $shortcodes_data[ $key ]['groups'] );

					uasort(
						$sorted_groups,
						function ( $a, $b ) {

							if ( (int) $a['position'] === (int) $b['position'] ) {

								return 0;
							}
							return ( (int) $a['position'] < (int) $b['position'] ) ? -1 : 1;
						}
					);

					$shortcodes_data[ $key ]['groups'] = array_values( $sorted_groups );
				}
			}
		}

		if ( wccon_fs()->can_use_premium_code() ) {
			return array( $shortcodes_data, $displayed_pages );
		} else {
			return array( array_slice( $shortcodes_data, 0, 1 ), $displayed_pages );
		}

	}

	/**
	 * Add metabox to product page.
	 */
	public function render_metabox__premium_only() {
		$enabled_compatibility = $this->settings['enabled_compat'] ?? true;
		if ( $enabled_compatibility ) {
			add_meta_box( 'wccon-compatibility', __( 'Product compatibility', 'wccontour' ), array( $this, 'single_compatibility' ), 'product', 'normal', 'high' );
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function single_compatibility__premium_only( $post ) {
		?>
		<div class="wccon-compatibility-section single-compat">

		</div>
		<?php
	}

	/**
	 * Render variation compatibility blocks.
	 */
	public function variation_compatibility__premium_only( $loop, $variation_data, $variation ) {
		?>
		<div class="wccon-compatibility-section" data-variation-id="<?php echo esc_attr( $variation->ID ); ?>">

		</div>
		<?php

	}

	/**
	 * For displaying correct Material UI styles.
	 */
	public function for_correct_react() {

		if ( strpos( get_current_screen()->base, 'wccon-builder' ) !== false || strpos( get_current_screen()->base, 'wccon-settings' ) !== false ) {
			?>
			<style>
				#wpbody-content > div.info, #wpbody-content > div.notice, #wpbody-content > div.message {
					display: none !important;
				} 
			
			</style>
			<?php
		}
		?>
		<style>
			#wccon-settings {
				margin: 20px 15px;	
			}
			#wc-contour {
				margin: 20px 15px;
			}
			.MuiFormControl-root .MuiFormLabel-root {
				float: none;
				width: auto;
				margin: 0;
			}

			.MuiFormControl-root input[type=color],
			.MuiFormControl-root input[type=date],

			.MuiFormControl-root input[type=number],
			.MuiFormControl-root input[type=text],
			.MuiFormControl-root input[type=tel],
			.MuiFormControl-root select,
			.MuiFormControl-root textarea {
				background-color: transparent;
				border: 0;
				width: 100%;
				padding: 16.5px 14px;
				min-height: 30px;
				box-sizing: border-box;
			}
			.MuiFormControl-root input[type=checkbox]:disabled {
				opacity: 0;
			}
			.MuiFormControl-root input[type=number],
			.MuiFormControl-root input[type=text],
			.MuiFormControl-root input[type=tel] {
				min-height: 56px;
			}
			.wccon-async-select.MuiFormControl-root input[type=text] {
				min-height: 25px;
			}
		
			.MuiFormControl-root input[type=checkbox]:focus,
			.MuiFormControl-root input[type=color]:focus,

			.MuiFormControl-root input[type=number]:focus,
			.MuiFormControl-root input[type=password]:focus,
			.MuiFormControl-root input[type=radio]:focus,
			.MuiFormControl-root input[type=text]:focus,
			.MuiFormControl-root input[type=tel]:focus,
			.MuiFormControl-root select:focus,
			.MuiFormControl-root textarea:focus {
				border-color: transparent;
				box-shadow: none;
				outline: 0;
				border-radius: 0;
			}

			.MuiFormControl-root .MuiSwitch-input {
				height: 100%;
			}

			#wccon-compatibility.woocommerce_options_panel label,
			#wccon-compatibility.woocommerce_options_panel legend {
				float: unset;
				width: auto;
				padding: 0;
				margin: 0;
			}
			
		</style>
			<?php

	}


	/**
	 * Register widgets.
	 */
	public function register_widget() {

		register_widget( 'WCCON_Widget_Taxonomies' );
		register_widget( 'WCCON_Widget_Attributes' );
		register_widget( 'WCCON_Widget_Price' );
		register_widget( 'WCCON_Widget_Meta' );

	}

	/**
	 * Remove for plugin widgets any controls. They are private.
	 */
	public function show_widgets_controls( $instance, $widget ) {

		if ( ! isset( $widget->widget_id ) ) {
			return $instance;
		}

		if ( is_admin() && in_array( $widget->widget_id, array( 'wccon_product_taxonomies', 'wccon_product_attributes', 'wccon_meta_filter', 'wccon_price_filter' ) ) ) {
			return false;
		}
		return $instance;
	}

	/**
	 * Prevent displaying widgets not from ajax call.
	 */
	public function show_widgets_front( $instance, $widget ) {

		if ( ! isset( $widget->widget_id ) ) {
			return $instance;
		}

		if ( ! wp_doing_ajax() && in_array( $widget->widget_id, array( 'wccon_product_taxonomies', 'wccon_product_attributes', 'wccon_meta_filter', 'wccon_price_filter' ) ) ) {
			return false;
		}
		return $instance;
	}

	/**
	 * Add WooCommerce account endpoint.
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( $this->endpoint_slug, EP_ROOT | EP_PAGES );
		$wccon_flushed = get_option( 'wccon_flushed' );

		// flush.
		if ( ! $wccon_flushed || $wccon_flushed !== $this->endpoint_slug ) {
			update_option( 'wccon_flushed', $this->endpoint_slug );
			flush_rewrite_rules();

		}

	}

	/**
	 * Add query var.
	 */
	public function add_query_vars( $vars ) {
		$vars[ $this->endpoint_slug ] = $this->endpoint_slug;

		return $vars;
	}

	/**
	 * Add menu item to account page.
	 */
	public function new_menu_items( $items ) {
		$new = array( $this->endpoint_slug => $this->endpoint_title );

		$items = array_merge( $new, $items );
		return $items;
	}

	/**
	 * Show proper template on account page.
	 */
	public function show_endpoint( $value ) {
		$array_value = explode( '/', $value );
		if ( count( $array_value ) > 1 && 'edit' === $array_value[0] && absint( $array_value[1] ) > 0 ) {
			$list_id = absint( $array_value[1] );
			wc_get_template(
				'saved-lists-edit.php',
				array(
					'id'      => $list_id,
					'user_id' => get_current_user_id(),
				),
				'wccontour',
				WCCON_PLUGIN_PATH . '/templates/'
			);
		} else {

			wc_get_template( 'saved-lists.php', array(), 'wccontour', WCCON_PLUGIN_PATH . '/templates/' );
		}

	}

	/**
	 * Redirect from account page if list doesn't belong to current user.
	 */
	public function account_redirect() {
		global $wpdb;
		$user_id = get_current_user_id();
		if ( is_wc_endpoint_url( $this->endpoint_slug ) ) {
			$query_var   = get_query_var( $this->endpoint_slug );
			$array_value = explode( '/', $query_var );

			if ( count( $array_value ) > 1 && 'edit' === $array_value[0] && absint( $array_value[1] ) > 0 ) {
				$lists_table = WCCON_DB::tables( 'saved_lists', 'name' );
				$list_id     = absint( $array_value[1] );
				$list        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$lists_table} WHERE id=%d AND user_id=%d", $list_id, $user_id ), ARRAY_A );

				if ( empty( $list ) ) {
					wp_redirect( wc_get_account_endpoint_url( $this->endpoint_slug ) );
					exit;
				}
			}
		}

	}

	/**
	 * Clear cache.
	 */
	public function clear_cache_on_attribute_added( $id, $data ) {

		$transient_key = sprintf( 'wccon_variation_swatches_cache_attribute_taxonomy_%s', wc_attribute_taxonomy_name( $data['attribute_name'] ) );
		delete_transient( $transient_key );
	}

	/**
	 * Clear cache.
	 */
	public function clear_cache_on_attribute_updated( $id, $data, $old_slug ) {

		$transient_key     = sprintf( 'wccon_variation_swatches_cache_attribute_taxonomy_%s', wc_attribute_taxonomy_name( $data['attribute_name'] ) );
		$transient_key_old = sprintf( 'wccon_variation_swatches_cache_attribute_taxonomy_%s', wc_attribute_taxonomy_name( $old_slug ) );

		delete_transient( $transient_key );
		delete_transient( $transient_key_old );
	}

	/**
	 * Clear cache.
	 */
	public function clear_cache_on_attribute_deleted( $id, $name, $taxonomy ) {

		$transient_key = sprintf( 'wccon_variation_swatches_cache_attribute_taxonomy_%s', wc_attribute_taxonomy_name( $name ) );
		delete_transient( $transient_key );
	}

}

WCCON_Admin::instance();
