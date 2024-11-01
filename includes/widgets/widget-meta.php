<?php
/**
 * Product Meta Widget
 *
 * Display product metas in filter's sidebar.
 *
 * @package WCCON\Widgets
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Widget_Meta class.
 */
class WCCON_Widget_Meta extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'widget_meta_filter';
		$this->widget_description = __( 'Not intended to use.', 'wccontour' );
		$this->widget_id          = 'wccon_meta_filter';
		$this->widget_name        = __( 'WCCON Metas', 'wccontour' );
		$this->settings           = array(
			'title'      => array(
				'type'  => 'text',
				'std'   => __( 'Filter by price', 'wccontour' ),
				'label' => __( 'Title', 'wccontour' ),
			),
			'meta_key'   => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Meta key', 'wccontour' ),
			),
			'meta_value' => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Meta values', 'wccontour' ),
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

		$title           = isset( $instance['title'] ) ? $instance['title'] : $this->settings['title']['std'];
		$meta_key        = isset( $instance['meta_key'] ) ? $instance['meta_key'] : $this->settings['meta_key']['std'];
		$only_meta_value = isset( $instance['meta_value'] ) ? $instance['meta_value'] : $this->settings['meta_value']['std'];
		$wcc_object      = isset( $instance['wcc_object'] ) ? $instance['wcc_object'] : $this->settings['wcc_object']['std'];

		$query_args = isset( $instance['query_args'] ) ? $instance['query_args'] : $this->settings['query_args']['std'];

		$meta_values = array_filter( array_map( 'trim', explode( ',', $only_meta_value ) ) );
		if ( empty( $meta_values ) ) {
			$meta_values = $this->get_query_meta_values( $meta_key, $wcc_object );
		}
		$meta_counts       = $this->get_filtered_meta_product_counts( $meta_key, $meta_values, $wcc_object );
		$meta_counts_count = count( $meta_counts );

		$this->widget_starter( $args, $instance, $meta_counts_count ); // TO CHECK.

		$active_meta_values = array();
		$active_meta_key    = false;
		if ( isset( $query_args['active'] ) && isset( $query_args['active']['meta_query'] ) ) {
			$active_metas = $query_args['active']['meta_query'];

			// find if there is selected meta.
			foreach ( $active_metas as $active_meta ) {
				if ( $active_meta['meta_key'] === $meta_key ) {
					$active_meta_key    = true;
					$active_meta_values = $active_meta['meta_value'];

					break;
				}
			}
		}

		echo '<div class="wccon-widget-items"><div class="wccon-scrl-grey"><ul class="wccon-meta-filters">';
		foreach ( $meta_values as $key => $meta_value ) {

			$option_classes = array();
			$options_atts   = '';
			if ( $key + 1 > apply_filters( 'wccon_widget_max_visible_options', 6, $instance ) ) {
				$option_classes[] = 'h-widget';
				$options_atts     = 'data-hide-widget';
			}
			$count = isset( $meta_counts[ $meta_value ] ) ? $meta_counts[ $meta_value ] : 0;
			echo '<li class="' . esc_attr( implode( ' ', $option_classes ) ) . '" ' . esc_attr( $options_atts ) . '><input id="' . esc_attr( $meta_key . '_' . $key ) . '" type="checkbox" name="wccon_meta_filter[' . esc_attr( $meta_key ) . ']" value="' . esc_attr( $meta_value ) . '" ' . checked( $active_meta_key && in_array( $meta_value, $active_meta_values, true ), true, false ) . ' /><label for="' . esc_attr( $meta_key . '_' . $key ) . '">' . esc_html( $meta_value ) . '<span class="wccon-term-count"> (' . esc_html( $count ) . ')</span></label></li>';
		}
		echo '</ul></div></div>';
		$this->widget_ender( $args, $instance, $meta_counts_count );
	}


	protected function get_filtered_meta_product_counts( $meta_key, $meta_values, $wcc_object ) {
		global $wpdb;

		$meta_alias           = 'fmeta';
		$wccon_widget_clauses = $wcc_object->get_clauses_widget( array( 'meta_query' => array( 'meta_key' => $meta_key ) ) );

		$wcc_join_sql = $wccon_widget_clauses['join_clause'];
		$wcc_where    = $wccon_widget_clauses['where_clause'] ? " ( {$wccon_widget_clauses['where_clause']} ) AND " : '';
		$to_join      = " LEFT JOIN {$wpdb->postmeta} AS {$meta_alias} ON ({$meta_alias}.post_id = {$wpdb->posts}.ID)";
		$meta_where   = $wpdb->prepare( "AND ( {$meta_alias}.meta_key=%s AND {$meta_alias}.meta_value IN ('" . implode( "','", $meta_values ) . "') )", $meta_key );

		// Generate query.
		$query           = array();
		$query['select'] = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) AS meta_count, {$meta_alias}.meta_value AS meta_count_id";
		$query['from']   = "FROM {$wpdb->posts}";
		$query['join']   = $to_join . ' ' . $wcc_join_sql;

		$query['where'] =
			" WHERE  {$wcc_where}
				{$wpdb->posts}.post_type IN ( 'product' )
                AND {$wpdb->posts}.post_status = 'publish'
                {$meta_where} ";

		$query['group_by'] = "GROUP BY {$meta_alias}.meta_value";
		$query             = apply_filters( 'woocommerce_get_filtered_term_product_counts_query', $query );
		$query_sql         = implode( ' ', $query );

		// We have a query - let's see if cached results of this query already exist.
		$query_hash = md5( $query_sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query_sql is safe.
		$results = $wpdb->get_results( $query_sql, ARRAY_A );

		$counts                       = array_map( 'absint', wp_list_pluck( $results, 'meta_count', 'meta_count_id' ) );
		$cached_counts[ $query_hash ] = $counts;

		return array_map( 'absint', (array) $cached_counts[ $query_hash ] );
	}

	public function get_query_meta_values( $meta_key, $wcc_object ) {
		$post_ids = $wcc_object->query(
			array(
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		return wccon_get_meta_values( $meta_key, 'product', 'publish', array( 'ids' => $post_ids->posts ) );
	}

	/**
	 * Output the html at the start of a widget.
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance.
	 */
	public function widget_starter( $args, $instance, $count ) {

		$title = apply_filters( 'widget_title', $this->get_instance_title( $instance ), $instance, $this->id_base );

		echo '<div class="widget widget_meta_filter wccon-widget opened">';

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
