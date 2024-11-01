<?php
/**
 * Product Query Class.
 *
 * Building queries for WooCommerce products.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Product_Query class.
 */
class WCCON_Product_Query {

	/**
	 * A flat list of meta table aliases used in JOIN clauses.
	 *
	 * @var array
	 */
	protected $meta_table_aliases = array();


	/**
	 * A flat list of tax table aliases used in JOIN clauses.
	 *
	 * @var array
	 */
	protected $tax_table_aliases = array();

	/**
	 * Database table to query for the metadata.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $meta_table;

	public $args         = array();
	public $join_clause  = '';
	public $where_clause = '';
	public $settings     = array();

	public function __construct( array $args ) {

		$this->meta_table = WCCON_DB::tables( 'components_meta', 'name' );
		$this->args       = $args;
		$this->settings   = wccon_get_settings();
	}

	/**
	 * Get main query.
	 *
	 * @return WP_Query
	 */
	public function query( $args = array() ) {
		global $wpdb, $wpml_query_filter;

		// display only single and variation products.
		$needle_terms = get_terms(
			array(
				'taxonomy' => 'product_type',
				'fields'   => 'ids',
				'slug'     => array( 'simple', 'variable' ), // only this product types.
				'number'   => 2,

			)
		);
		$include_needle_type = " ( {$wpdb->term_relationships}.term_taxonomy_id IN (" . implode( ',', $needle_terms ) . ') )';
		$clauses             = $this->get_clauses();
		extract( $clauses ); // query_args comes.

		$query_args         = array_merge( $query_args, $args );
		$query_args['lang'] = ''; // disable polylang.
		$query_args         = apply_filters(
			'wccon_product_query_vars',
			$query_args
		);

		$posts_join = function( $join ) use ( $join_clause ) {
			global $wpdb;
			$join .= $join_clause;

			$this->join_clause = $join;
			return $join;
		};

		$posts_where   = function( $where ) use ( $where_query_string, $active_query_string, $include_needle_type ) {
			if ( '' === $where_query_string ) {
				return $where;
			}
			$this->where_clause = " {$include_needle_type} AND ( {$where_query_string} ) {$active_query_string}";

			$where_query_string = "( {$where_query_string} )";

			$query_string = " AND {$include_needle_type} AND {$where_query_string} {$active_query_string}";
			return $query_string . ' ' . $where;
		};
		$posts_orderby = function( $orderby ) use ( $orderby_query_string ) {
			if ( ! empty( $orderby_query_string ) ) {
				return $orderby_query_string;
			}
			return $orderby;
		};

		add_filter( 'posts_join', $posts_join );
		add_filter( 'posts_where', $posts_where );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby' ) );
		add_filter( 'posts_orderby', $posts_orderby );
		if ( $wpml_query_filter instanceof WPML_Query_Filter ) {
			remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10 );
			remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10 );
		}
		do_action( 'wccon_before_product_query', $query_args );

		$query = new \WP_Query( $query_args );

		remove_filter( 'posts_join', $posts_join );

		remove_filter( 'posts_where', $posts_where );
		remove_filter( 'posts_groupby', array( $this, 'posts_groupby' ) );
		remove_filter( 'posts_orderby', $posts_orderby );
		if ( $wpml_query_filter instanceof WPML_Query_Filter ) {
			add_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10 );
			add_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10 );
		}
		do_action( 'wccon_after_product_query', $query_args );
		return $query;
	}

	/**
	 * JOIN clauses.
	 */
	public function posts_join( $join ) {
		global $wpdb;
		$join .= "LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->term_relationships}.object_id={$wpdb->posts}.ID";
		return $join;
	}

	/**
	 * GROUPBY clauses.
	 */
	public function posts_groupby( $groupby ) {
		 global $wpdb;
		$groupby = "{$wpdb->posts}.ID";
		return $groupby;
	}

	/**
	 * All JOIN clauses.
	 */
	public function get_join_clause() {
		 return array(
			 'join_sql'   => $this->join_clause,
			 'meta_alias' => $this->meta_table_aliases,
			 'tax_alias'  => $this->tax_table_aliases,
		 );
	}

	/**
	 * WHERE clases
	 */
	public function get_where_clause() {
		return $this->where_clause;
	}

	/**
	 * Combine clauses.
	 */
	public function get_clauses() {
		$products_per_page = isset( $this->settings['product_limit'] ) ? absint( $this->settings['product_limit'] ) : 0;
		$products_per_page = $products_per_page > 0 ? $products_per_page : 10;
		$query_args        = array(
			'post_type'      => array( 'product' ),
			'posts_per_page' => apply_filters( 'wccon_products_per_page', $products_per_page ),
			'paged'          => 1,
			'orderby'        => 'menu_order title',
		);

		global $wpdb;

		$join_tax_query_string  = '';
		$join_meta_query_string = '';
		$join_clause            = '';
		$where_query_string     = '';
		$active_query_string    = '';
		$orderby_query_string   = '';

		$join_tax_query_string    .= " LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->term_relationships}.object_id={$wpdb->posts}.ID";
		$this->tax_table_aliases[] = $wpdb->term_relationships;

		if ( isset( $this->args['product_query'] ) ) {
			foreach ( $this->args['product_query'] as $key_query => $query ) {
				$next_relation    = $query['nextRelation'];
				$sub_query_string = '';
				if ( 'product_tax' === $query['groupId'] ) {
					if ( empty( $query['value'] ) ) {
						continue;
					}

					$inner_term_strings     = array();
					$relation               = $query['relation'];
					$i                      = count( $this->tax_table_aliases );
					$tax_alias              = $i ? 'tr' . $i : $wpdb->term_relationships;
					$join_tax_query_string .= " LEFT JOIN {$wpdb->term_relationships}";
					$join_tax_query_string .= $i ? " AS $tax_alias" : '';
					$join_tax_query_string .= " ON {$tax_alias}.object_id={$wpdb->posts}.ID";

					foreach ( $query['value'] as $term_id ) {
						if ( is_numeric( $term_id ) ) {
							$term                 = get_term( $term_id );
							$child_terms          = wccon_get_term_childrens( $term->taxonomy, $term->term_id );
							$allTermsIds          = array_merge( array( $term->term_id ), $child_terms );
							$inner_term_strings[] = "{$tax_alias}.term_taxonomy_id IN (" . implode( ',', $allTermsIds ) . ')';
						} else {
							$inner_term_strings[] = $wpdb->prepare(
								"EXISTS (
								SELECT 1
								FROM {$wpdb->term_relationships} tr
								INNER JOIN {$wpdb->term_taxonomy} tt
								ON tr.term_taxonomy_id=tt.term_taxonomy_id
								WHERE tt.taxonomy=%s
								AND tr.object_id={$wpdb->posts}.ID
							)",
								$term_id
							);
						}
					}
					// $sub_query_string .= '(' . implode( " {$relation} ", $inner_term_strings ) . ')';
					$sub_query_string         .= implode( " {$relation} ", $inner_term_strings );
					$this->tax_table_aliases[] = $tax_alias;
				} elseif ( 'product_meta' === $query['groupId'] ) {
					$i          = count( $this->meta_table_aliases );
					$meta_alias = $i ? 'mt' . $i : $wpdb->postmeta;

					$relation                = $query['relation'];
					$join_meta_query_string .= " LEFT JOIN {$wpdb->postmeta}";
					$join_meta_query_string .= $i ? " AS $meta_alias" : '';
					$join_meta_query_string .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";

					if ( ! empty( $query['meta_value'] ) ) {
						$sub_query_string .= $wpdb->prepare( "{$meta_alias}.meta_key=%s AND {$meta_alias}.meta_value=%s", $query['value'], $query['meta_value'] );
					} else {
						$sub_query_string .= $wpdb->prepare( "{$meta_alias}.meta_key=%s", $query['value'] );
					}
					$this->meta_table_aliases[] = $meta_alias;
				} elseif ( 'product_list' === $query['groupId'] ) {

					$product_list_query = '';
					if ( ! empty( $query['include'] ) ) {
						$format_include_list = implode( ',', array_map( 'absint', explode( ',', $query['include'] ) ) );
						$product_list_query .= "{$wpdb->posts}.ID IN ( $format_include_list )";
					}
					if ( ! empty( $query['exclude'] ) ) {
						$format_exclude_list = implode( ',', array_map( 'absint', explode( ',', $query['exclude'] ) ) );
						$connect_query       = ! empty( $query['include'] ) ? 'AND' : '';
						$product_list_query .= " $connect_query {$wpdb->posts}.ID NOT IN ( $format_exclude_list )";
					}

					$sub_query_string .= $product_list_query;

				}

				// handle next relation.
				if ( '' !== $sub_query_string ) {
					if ( isset( $this->args['product_query'][ $key_query + 1 ] ) ) {
						$where_query_string .= ' (' . $sub_query_string . ') ' . $next_relation . ' ';
					} else {
						$where_query_string .= ' (' . $sub_query_string . ')';
					}
				}
			}
		}
		if ( isset( $this->args['active'] ) ) {
			if ( isset( $this->args['active']['tax_query'] ) ) {
				foreach ( $this->args['active']['tax_query'] as $tax_data ) {
					$tax_value = is_array( $tax_data['tax_value'] ) ? implode( ',', $tax_data['tax_value'] ) : $tax_data['tax_value'];

					$i                         = count( $this->tax_table_aliases );
					$tax_alias                 = $i ? 'tr' . $i : $wpdb->term_relationships;
					$join_tax_query_string    .= " LEFT JOIN {$wpdb->term_relationships}";
					$join_tax_query_string    .= $i ? " AS $tax_alias" : '';
					$join_tax_query_string    .= " ON {$tax_alias}.object_id={$wpdb->posts}.ID";
					$active_query_string      .= " AND {$tax_alias}.term_taxonomy_id IN (" . $tax_value . ')';
					$this->tax_table_aliases[] = $tax_alias;
				}
			}

			if ( isset( $this->args['active']['meta_query'] ) ) {
				foreach ( $this->args['active']['meta_query'] as $meta_data ) {
					$meta_value = is_array( $meta_data['meta_value'] ) ? implode( "','", $meta_data['meta_value'] ) : $meta_data['meta_value'];

					$i                          = count( $this->meta_table_aliases );
					$meta_alias                 = $i ? 'mt' . $i : $wpdb->postmeta;
					$join_meta_query_string    .= " LEFT JOIN {$wpdb->postmeta}";
					$join_meta_query_string    .= $i ? " AS $meta_alias" : '';
					$join_meta_query_string    .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";
					$active_query_string       .= $wpdb->prepare( " AND {$meta_alias}.meta_key=%s AND {$meta_alias}.meta_value IN ('" . $meta_value . "')", $meta_data['meta_key'] );
					$this->meta_table_aliases[] = $meta_alias;
				}
			}

			if ( isset( $this->args['active']['prices'] ) && count( $this->args['active']['prices'] ) === 2 ) {
				$active_query_string .= $wpdb->prepare(
					' AND NOT (%f<wc_product_meta_lookup.min_price OR %f>wc_product_meta_lookup.max_price ) ',
					$this->args['active']['prices'][1],
					$this->args['active']['prices'][0]
				);

			}
			if ( isset( $this->args['active']['orderby'] ) ) {
				$orderby = $this->args['active']['orderby'];
				switch ( $orderby ) {
					case 'menu_order':
						$query_args['orderby'] = 'menu_order title';
						break;
					case 'date':
						$query_args['orderby'] = 'date ID';
						$query_args['order']   = 'ASC';
						break;
					case 'popularity':
						$orderby_query_string = ' wc_product_meta_lookup.total_sales DESC, wc_product_meta_lookup.product_id DESC';
						break;
					case 'rating':
						$orderby_query_string = ' wc_product_meta_lookup.average_rating DESC, wc_product_meta_lookup.rating_count DESC, wc_product_meta_lookup.product_id DESC ';
						break;
					case 'price':
						$orderby_query_string = ' wc_product_meta_lookup.min_price ASC, wc_product_meta_lookup.product_id ASC ';
						break;
					case 'price-desc':
						$orderby_query_string = ' wc_product_meta_lookup.max_price DESC, wc_product_meta_lookup.product_id DESC ';
						break;
				}
			}
			if ( isset( $this->args['active']['stock'] ) && 'instock' === $this->args['active']['stock'] ) {
				$i                          = count( $this->meta_table_aliases );
				$meta_alias                 = $i ? 'mt' . $i : $wpdb->postmeta;
				$join_meta_query_string    .= " LEFT JOIN {$wpdb->postmeta}";
				$join_meta_query_string    .= $i ? " AS $meta_alias" : '';
				$join_meta_query_string    .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";
				$active_query_string       .= " AND {$meta_alias}.meta_key='_stock_status' AND {$meta_alias}.meta_value='instock' ";
				$this->meta_table_aliases[] = $meta_alias;
			}

			if ( isset( $this->args['active']['search'] ) ) {

				$like                 = '%' . $wpdb->esc_like( $this->args['active']['search'] ) . '%';
				$active_query_string .= $wpdb->prepare( " AND (($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s) OR ($wpdb->posts.post_content LIKE %s))", $like, $like, $like );

			}
		}

		// build join clause.
		$join_clause .= $join_tax_query_string;

		$join_clause .= $join_meta_query_string;

		if ( isset( $this->args['active'] ) && isset( $this->args['active']['prices'] ) ) {
			if ( ! strstr( $join_clause, 'wc_product_meta_lookup' ) ) {
				$join_clause .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id ";
			}
		}
		if ( isset( $this->args['active'] ) && isset( $this->args['active']['orderby'] ) && in_array( $this->args['active']['orderby'], array( 'popularity', 'rating', 'price', 'price-desc' ), true ) ) {
			if ( ! strstr( $join_clause, 'wc_product_meta_lookup' ) ) {
				$join_clause .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
			}
		}

		return compact( 'query_args', 'active_query_string', 'join_clause', 'orderby_query_string', 'where_query_string' );
	}

	/**
	 * Get clauses for filter widgets.
	 */
	public function get_clauses_widget( $exclude = array() ) {

		global $wpdb;

		$join_tax_query_string  = '';
		$join_meta_query_string = '';
		$join_clause            = '';
		$where_query_string     = '';
		$active_query_string    = '';
		$orderby_query_string   = '';
		$tax_table_aliases      = array();
		$meta_table_aliases     = array();

		if ( isset( $this->args['product_query'] ) ) {
			foreach ( $this->args['product_query'] as $key_query => $query ) {
				$next_relation    = $query['nextRelation'];
				$sub_query_string = '';
				if ( 'product_tax' === $query['groupId'] ) {
					if ( empty( $query['value'] ) ) {
						continue;
					}

					$inner_term_strings     = array();
					$relation               = $query['relation'];
					$i                      = count( $tax_table_aliases );
					$tax_alias              = $i ? 'tr' . $i : $wpdb->term_relationships;
					$join_tax_query_string .= " LEFT JOIN {$wpdb->term_relationships}";
					$join_tax_query_string .= $i ? " AS $tax_alias" : '';
					$join_tax_query_string .= " ON {$tax_alias}.object_id={$wpdb->posts}.ID";

					foreach ( $query['value'] as $term_id ) {
						if ( is_numeric( $term_id ) ) {
							$term                 = get_term( $term_id );
							$child_terms          = wccon_get_term_childrens( $term->taxonomy, $term->term_id );
							$allTermsIds          = array_merge( array( $term->term_id ), $child_terms );
							$inner_term_strings[] = "{$tax_alias}.term_taxonomy_id IN (" . implode( ',', $allTermsIds ) . ')';
						} else {
							$inner_term_strings[] = $wpdb->prepare(
								"EXISTS (
								SELECT 1
								FROM {$wpdb->term_relationships} tr
								INNER JOIN {$wpdb->term_taxonomy} tt
								ON tr.term_taxonomy_id=tt.term_taxonomy_id
								WHERE tt.taxonomy=%s
								AND tr.object_id={$wpdb->posts}.ID
							)",
								$term_id
							);
						}
					}

					$sub_query_string   .= implode( " {$relation} ", $inner_term_strings );
					$tax_table_aliases[] = $tax_alias;
				} elseif ( 'product_meta' === $query['groupId'] ) {
					$i          = count( $meta_table_aliases );
					$meta_alias = $i ? 'mt' . $i : $wpdb->postmeta;

					$relation                = $query['relation'];
					$join_meta_query_string .= " LEFT JOIN {$wpdb->postmeta}";
					$join_meta_query_string .= $i ? " AS $meta_alias" : '';
					$join_meta_query_string .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";

					if ( ! empty( $query['meta_value'] ) ) {
						$sub_query_string .= $wpdb->prepare( "{$meta_alias}.meta_key=%s AND {$meta_alias}.meta_value=%s", $query['value'], $query['meta_value'] );
					} else {
						$sub_query_string .= $wpdb->prepare( "{$meta_alias}.meta_key=%s", $query['value'] );
					}
					$meta_table_aliases[] = $meta_alias;
				} elseif ( 'product_list' === $query['groupId'] ) {

					$product_list_query = '';
					if ( ! empty( $query['include'] ) ) {
						$format_include_list = implode( ',', array_map( 'absint', explode( ',', $query['include'] ) ) );
						$product_list_query .= "{$wpdb->posts}.ID IN ( $format_include_list )";
					}
					if ( ! empty( $query['exclude'] ) ) {
						$format_exclude_list = implode( ',', array_map( 'absint', explode( ',', $query['exclude'] ) ) );
						$connect_query       = ! empty( $query['include'] ) ? 'AND' : '';
						$product_list_query .= " $connect_query {$wpdb->posts}.ID NOT IN ( $format_exclude_list )";
					}

					$sub_query_string .= $product_list_query;

				}

				// handle next relation.
				if ( '' !== $sub_query_string ) {
					if ( isset( $this->args['product_query'][ $key_query + 1 ] ) ) {
						$where_query_string .= ' (' . $sub_query_string . ') ' . $next_relation . ' ';
					} else {
						$where_query_string .= ' (' . $sub_query_string . ')';
					}
				}
			}
		}
		if ( isset( $this->args['active'] ) ) {
			if ( isset( $this->args['active']['tax_query'] ) ) {
				foreach ( $this->args['active']['tax_query'] as $tax_data ) {

					// exclude tax for widgets.
					if ( isset( $exclude['tax_query'] ) && $exclude['tax_query']['tax_name'] === $tax_data['tax_name'] ) {
						continue;
					}
					$tax_value = is_array( $tax_data['tax_value'] ) ? implode( ',', $tax_data['tax_value'] ) : $tax_data['tax_value'];

					$i                      = count( $tax_table_aliases );
					$tax_alias              = $i ? 'tr' . $i : $wpdb->term_relationships;
					$join_tax_query_string .= " LEFT JOIN {$wpdb->term_relationships}";
					$join_tax_query_string .= $i ? " AS $tax_alias" : '';
					$join_tax_query_string .= " ON {$tax_alias}.object_id={$wpdb->posts}.ID";
					$active_query_string   .= " AND {$tax_alias}.term_taxonomy_id IN (" . $tax_value . ')';
					$tax_table_aliases[]    = $tax_alias;
				}
			}

			if ( isset( $this->args['active']['meta_query'] ) ) {
				foreach ( $this->args['active']['meta_query'] as $meta_data ) {

					// exclude meta for widget.
					if ( isset( $exclude['meta_query'] ) && $exclude['meta_query']['meta_key'] === $meta_data['meta_key'] ) {
						continue;
					}

					$meta_value = is_array( $meta_data['meta_value'] ) ? implode( "','", $meta_data['meta_value'] ) : $meta_data['meta_value'];

					$i                       = count( $meta_table_aliases );
					$meta_alias              = $i ? 'mt' . $i : $wpdb->postmeta;
					$join_meta_query_string .= " LEFT JOIN {$wpdb->postmeta}";
					$join_meta_query_string .= $i ? " AS $meta_alias" : '';
					$join_meta_query_string .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";
					$active_query_string    .= $wpdb->prepare( " AND {$meta_alias}.meta_key=%s AND {$meta_alias}.meta_value IN ('" . $meta_value . "')", $meta_data['meta_key'] );
					$meta_table_aliases[]    = $meta_alias;
				}
			}

			if ( isset( $this->args['active']['prices'] ) && count( $this->args['active']['prices'] ) === 2 ) {
				$active_query_string .= $wpdb->prepare(
					' AND NOT (%f<wc_product_meta_lookup.min_price OR %f>wc_product_meta_lookup.max_price ) ',
					$this->args['active']['prices'][1],
					$this->args['active']['prices'][0]
				);

			}
			if ( isset( $this->args['active']['orderby'] ) ) {
				$orderby = $this->args['active']['orderby'];
				switch ( $orderby ) {
					case 'menu_order':
						break;
					case 'date':
						break;
					case 'popularity':
						$orderby_query_string = ' wc_product_meta_lookup.total_sales DESC, wc_product_meta_lookup.product_id DESC';
						break;
					case 'rating':
						$orderby_query_string = ' wc_product_meta_lookup.average_rating DESC, wc_product_meta_lookup.rating_count DESC, wc_product_meta_lookup.product_id DESC ';
						break;
					case 'price':
						$orderby_query_string = ' wc_product_meta_lookup.min_price ASC, wc_product_meta_lookup.product_id ASC ';
						break;
					case 'price-desc':
						$orderby_query_string = ' wc_product_meta_lookup.max_price DESC, wc_product_meta_lookup.product_id DESC ';
						break;
				}
			}
			if ( isset( $this->args['active']['stock'] ) && 'instock' === $this->args['active']['stock'] ) {
				$i                       = count( $meta_table_aliases );
				$meta_alias              = $i ? 'mt' . $i : $wpdb->postmeta;
				$join_meta_query_string .= " LEFT JOIN {$wpdb->postmeta}";
				$join_meta_query_string .= $i ? " AS $meta_alias" : '';
				$join_meta_query_string .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";
				$active_query_string    .= " AND {$meta_alias}.meta_key='_stock_status' AND {$meta_alias}.meta_value='instock' ";
				$meta_table_aliases[]    = $meta_alias;
			}

			if ( isset( $this->args['active']['search'] ) ) {

				$like                 = '%' . $wpdb->esc_like( $this->args['active']['search'] ) . '%';
				$active_query_string .= $wpdb->prepare( " AND (($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s) OR ($wpdb->posts.post_content LIKE %s))", $like, $like, $like );

			}
		}

		// build join clause.
		$join_clause .= $join_tax_query_string;

		$join_clause .= $join_meta_query_string;

		if ( isset( $this->args['active'] ) && isset( $this->args['active']['prices'] ) ) {
			if ( ! strstr( $join_clause, 'wc_product_meta_lookup' ) ) {
				$join_clause .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id ";
			}
		}
		if ( isset( $this->args['active'] ) && isset( $this->args['active']['orderby'] ) && in_array( $this->args['active']['orderby'], array( 'popularity', 'rating', 'price', 'price-desc' ), true ) ) {
			if ( ! strstr( $join_clause, 'wc_product_meta_lookup' ) ) {
				$join_clause .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
			}
		}

		// build where clause.
		$where_clause = " {$where_query_string} {$active_query_string}";
		return compact( 'join_clause', 'orderby_query_string', 'where_clause' );
	}

	/**
	 * Active query string (not used for now).
	 *
	 * @since 1.0.0
	 */
	public function get_active_query_string( $active_args ) {
		global $wpdb;
		$join_meta_query_string = '';
		$active_query_string    = '';
		$join_tax_query_string  = '';
		if ( isset( $active_args['tax_query'] ) ) {
			foreach ( $active_args['tax_query'] as $tax_data ) {
				$tax_value = is_array( $tax_data['tax_value'] ) ? implode( ',', $tax_data['tax_value'] ) : $tax_data['tax_value'];

				$i                         = count( $this->tax_table_aliases );
				$tax_alias                 = $i ? 'tr' . $i : $wpdb->term_relationships;
				$join_tax_query_string    .= " LEFT JOIN {$wpdb->term_relationships}";
				$join_tax_query_string    .= $i ? " AS $tax_alias" : '';
				$join_tax_query_string    .= " ON {$tax_alias}.object_id={$wpdb->posts}.ID";
				$active_query_string      .= " AND {$tax_alias}.term_taxonomy_id IN (" . $tax_value . ')';
				$this->tax_table_aliases[] = $tax_alias;
			}
		}

		if ( isset( $active_args['meta_query'] ) ) {
			foreach ( $active_args['meta_query'] as $meta_data ) {
				$meta_value = is_array( $meta_data['meta_value'] ) ? implode( "','", $meta_data['meta_value'] ) : $meta_data['meta_value'];

				$i                          = count( $this->meta_table_aliases );
				$meta_alias                 = $i ? 'mt' . $i : $wpdb->postmeta;
				$join_meta_query_string    .= " LEFT JOIN {$wpdb->postmeta}";
				$join_meta_query_string    .= $i ? " AS $meta_alias" : '';
				$join_meta_query_string    .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";
				$active_query_string       .= $wpdb->prepare( " AND {$meta_alias}.meta_key=%s AND {$meta_alias}.meta_value IN ('" . $meta_value . "')", $meta_data['meta_key'] );
				$this->meta_table_aliases[] = $meta_alias;
			}
		}

		if ( isset( $active_args['prices'] ) && count( $active_args['prices'] ) === 2 ) {
			$active_query_string .= $wpdb->prepare(
				' AND NOT (%f<wc_product_meta_lookup.min_price OR %f>wc_product_meta_lookup.max_price ) ',
				$active_args['prices'][1],
				$active_args['prices'][0]
			);

		}
		if ( isset( $active_args['orderby'] ) ) {
			$orderby = $active_args['orderby'];
			switch ( $orderby ) {
				case 'menu_order':
					$query_args['orderby'] = 'menu_order title';
					break;
				case 'date':
					$query_args['orderby'] = 'date ID';
					$query_args['order']   = 'ASC';
					break;
				case 'popularity':
					$orderby_query_string = ' wc_product_meta_lookup.total_sales DESC, wc_product_meta_lookup.product_id DESC';
					break;
				case 'rating':
					$orderby_query_string = ' wc_product_meta_lookup.average_rating DESC, wc_product_meta_lookup.rating_count DESC, wc_product_meta_lookup.product_id DESC ';
					break;
				case 'price':
					$orderby_query_string = ' wc_product_meta_lookup.min_price ASC, wc_product_meta_lookup.product_id ASC ';
					break;
				case 'price-desc':
					$orderby_query_string = ' wc_product_meta_lookup.max_price DESC, wc_product_meta_lookup.product_id DESC ';
					break;
			}
		}
		if ( isset( $active_args['stock'] ) && 'instock' === $active_args['stock'] ) {
			$i                          = count( $this->meta_table_aliases );
			$meta_alias                 = $i ? 'mt' . $i : $wpdb->postmeta;
			$join_meta_query_string    .= " LEFT JOIN {$wpdb->postmeta}";
			$join_meta_query_string    .= $i ? " AS $meta_alias" : '';
			$join_meta_query_string    .= " ON {$meta_alias}.post_id={$wpdb->posts}.ID";
			$active_query_string       .= " AND {$meta_alias}.meta_key='_stock_status' AND {$meta_alias}.meta_value='instock' ";
			$this->meta_table_aliases[] = $meta_alias;
		}

		if ( isset( $active_args['search'] ) ) {

			$like                 = '%' . $wpdb->esc_like( $active_args['search'] ) . '%';
			$active_query_string .= $wpdb->prepare( " AND (($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_excerpt LIKE %s) OR ($wpdb->posts.post_content LIKE %s))", $like, $like, $like );

		}

		return array(
			'meta_join'    => $join_meta_query_string,
			'tax_join'     => $join_tax_query_string,
			'where_active' => $active_query_string,
		);
	}

}
