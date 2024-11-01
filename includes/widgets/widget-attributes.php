<?php
/**
 * WooCoommerce Attributes Widget
 *
 * Display attributes in filter's sidebar.
 *
 * @package WCCON\Widgets
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Widget_Attributes class.
 */
class WCCON_Widget_Attributes extends WC_Widget {

	/**
	 * Category ancestors.
	 *
	 * @var array
	 */
	public $cat_ancestors;

	/**
	 * Current Category.
	 *
	 * @var bool
	 */
	public $current_cat;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'widget_product_attributes';
		$this->widget_description = __( 'Not intended to use.', 'wccontour' );
		$this->widget_id          = 'wccon_product_attributes';
		$this->widget_name        = __( 'WCCON Attributes', 'wccontour' );
		$this->settings           = array(
			'title'      => array(
				'type'  => 'text',
				'std'   => __( 'Product Attributes', 'wccontour' ),
				'label' => __( 'Title', 'wccontour' ),
			),
			'taxonomy'   => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Taxonomy', 'wccontour' ),
			),

			'query_args' => array(
				'type'  => 'array',
				'std'   => array(),
				'label' => __( 'Query Args', 'wccontour' ),
			),
			'wcc_object' => array(
				'type'  => 'object',
				'std'   => new stdClass(),
				'label' => __( 'WCC Query', 'wccontour' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		global $wp_query, $post;

		$title = isset( $instance['title'] ) ? $instance['title'] : $this->settings['title']['std'];

		$taxonomy = isset( $instance['taxonomy'] ) ? $instance['taxonomy'] : $this->settings['taxonomy']['std'];

		$wcc_object = isset( $instance['wcc_object'] ) ? $instance['wcc_object'] : $this->settings['wcc_object']['std'];

		$query_args              = isset( $instance['query_args'] ) ? $instance['query_args'] : $this->settings['query_args']['std'];
		$list_args['menu_order'] = false;

		$tax_args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		);

		$terms       = get_terms( $tax_args );
		$term_counts = $this->get_filtered_term_product_counts( wp_list_pluck( $terms, 'term_id' ), $wcc_object, $taxonomy );
		if ( false === $term_counts ) {
			return;
		}
		$this->widget_starter( $args, $instance, count( $terms ) );

		$active_tax_values = array();
		$active_tax_key    = false;
		if ( isset( $query_args['active'] ) && isset( $query_args['active']['tax_query'] ) ) {
			$active_terms = $query_args['active']['tax_query'];

			foreach ( $active_terms as $active_term ) {
				if ( $active_term['tax_name'] === $taxonomy ) {
					$active_tax_key    = true;
					$active_tax_values = $active_term['tax_value'];

					break;
				}
			}
		}

		echo '<div class="wccon-widget-items"><div class="wccon-scrl-grey"><ul class="wccon-tax-filters">';
		foreach ( $terms as $key => $term ) {
			$option_classes = array();
			$options_atts   = '';
			if ( $key + 1 > apply_filters( 'wccon_widget_max_visible_options', 6, $instance ) ) {
				$option_classes[] = 'h-widget';
				$options_atts     = 'data-hide-widget';
			}
			$count = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : 0;
			echo '<li class="' . esc_attr( implode( ' ', $option_classes ) ) . '" ' . esc_attr( $options_atts ) . '><input id="' . esc_attr( 'term-' . $term->term_id ) . '" type="checkbox" name="wccon_tax_filter[' . esc_attr( $term->taxonomy ) . ']" value="' . esc_attr( $term->term_id ) . '" ' . checked( $active_tax_key && in_array( $term->term_id, $active_tax_values ), true, false ) . ' /><label for="' . esc_attr( 'term-' . $term->term_id ) . '">' . esc_html( $term->name ) . '<span class="wccon-term-count"> (' . esc_html( $count ) . ')</span></label></li>';
		}
		echo '</ul></div></div>';
		$this->widget_ender( $args, $instance, count( $terms ) );
	}


	protected function get_filtered_term_product_counts( $term_ids, $wcc_object, $taxonomy ) {
		global $wpdb;
		if ( empty( $term_ids ) ) {
			return false;
		}
		$term_ids_sql = '(' . implode( ',', array_map( 'absint', $term_ids ) ) . ')';

		$wccon_widget_clauses = $wcc_object->get_clauses_widget( array( 'tax_query' => array( 'tax_name' => $taxonomy ) ) );

		$wcc_join_sql = $wccon_widget_clauses['join_clause'];
		$wcc_where    = $wccon_widget_clauses['where_clause'] ? " ( {$wccon_widget_clauses['where_clause']} ) AND " : '';

		$tax_alias    = 'fterms';
		$to_join      = "INNER JOIN {$wpdb->term_relationships} AS term_relationships ON {$wpdb->posts}.ID = term_relationships.object_id
		INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
		INNER JOIN {$wpdb->terms} AS {$tax_alias} USING( term_id )";
		$wcc_join_sql = $to_join . ' ' . $wcc_join_sql;

		$tax_where = "AND  {$tax_alias}.term_id IN $term_ids_sql";
		// Generate query.
		$query           = array();
		$query['select'] = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) AS term_count, {$tax_alias}.term_id AS term_count_id";
		$query['from']   = "FROM {$wpdb->posts}";
		$query['join']   = $wcc_join_sql;

		$query['where'] = "
                WHERE {$wcc_where}
				{$wpdb->posts}.post_type IN ( 'product' )
                AND {$wpdb->posts}.post_status = 'publish'
                {$tax_where}";

		$query['group_by'] = "GROUP BY {$tax_alias}.term_id";
		$query             = apply_filters( 'woocommerce_get_filtered_term_product_counts_query', $query );
		$query_sql         = implode( ' ', $query );

		// We have a query - let's see if cached results of this query already exist.
		$query_hash = md5( $query_sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query_sql is safe.
		$results                      = $wpdb->get_results( $query_sql, ARRAY_A );
		$counts                       = array_map( 'absint', wp_list_pluck( $results, 'term_count', 'term_count_id' ) );
		$cached_counts[ $query_hash ] = $counts;

		return array_map( 'absint', (array) $cached_counts[ $query_hash ] );
	}

	/**
	 * Output the html at the start of a widget.
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance.
	 */
	public function widget_starter( $args, $instance, $count ) {

		$title = apply_filters( 'widget_title', $this->get_instance_title( $instance ), $instance, $this->id_base );

		echo '<div class="widget wccon_product_attributes wccon-widget opened">';

		echo '<div class="wccon-widget-head"><h3 class="widgettitle">' . esc_html( $title ) . '</h3><svg xmlns="http://www.w3.org/2000/svg" width="7" height="4" viewBox="0 0 7 4" fill="#8C8C8C">
		<path d="M3.154.145L.144 3.161a.491.491 0 0 0 .692.697l.002-.002L3.5 1.188l2.663 2.669c.19.191.5.192.692.002l.002-.002a.491.491 0 0 0 0-.695L3.847.145a.486.486 0 0 0-.693 0z"></path>
		</svg></div>';
		echo '<div class="wccon-widget-body active">';
	}

	/**
	 * Output the html at the end of a widget.
	 *
	 * @param  array $args Arguments.
	 */
	public function widget_ender( $args, $instance, $count ) {
		if ( (int) $count > apply_filters( 'wccon_widget_max_visible_options', 6, $instance ) ) {

			echo '<div class="widget-expander"><svg xmlns="http://www.w3.org/2000/svg" width="7" height="4" viewBox="0 0 7 4" fill="#8C8C8C">
			<path d="M3.154.145L.144 3.161a.491.491 0 0 0 .692.697l.002-.002L3.5 1.188l2.663 2.669c.19.191.5.192.692.002l.002-.002a.491.491 0 0 0 0-.695L3.847.145a.486.486 0 0 0-.693 0z"></path>
			</svg><span class="widget-expander-show">' . sprintf( /* translators: %s: attributes count */ esc_html__( 'More (%s)', 'wccontour' ), esc_attr( (int) $count - apply_filters( 'wccon_widget_max_visible_options', 6, $instance ) ) ) . '</span><span class="widget-expander-hide">' . esc_html__( 'Less', 'wccontour' ) . '</span></div>';
		}
		echo '</div></div>';

	}
}
