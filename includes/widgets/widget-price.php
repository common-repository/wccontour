<?php
/**
 * Price Filter Widget
 *
 * Generates a range slider to filter products by price.
 *
 * @package WCCON\Widgets
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Widget_Price class.
 */
class WCCON_Widget_Price extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'widget_price_filter';
		$this->widget_description = __( 'Not intended to use.', 'wccontour' );
		$this->widget_id          = 'wccon_price_filter';
		$this->widget_name        = __( 'WCCON Price', 'wccontour' );
		$this->settings           = array(
			'title'      => array(
				'type'  => 'text',
				'std'   => __( 'Filter by price', 'wccontour' ),
				'label' => __( 'Title', 'wccontour' ),
			),
			'inputs'     => array(
				'type'  => 'checkbox',
				'std'   => false,
				'label' => __( 'Show inputs', 'wccontour' ),
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
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		global $wp;

		// Requires lookup table added in 3.6.
		if ( version_compare( get_option( 'woocommerce_db_version', null ), '3.6', '<' ) ) {
			return;
		}
		$wcc_object = isset( $instance['wcc_object'] ) ? $instance['wcc_object'] : $this->settings['wcc_object']['std'];

		$query_args = isset( $instance['query_args'] ) ? $instance['query_args'] : $this->settings['query_args']['std'];
		$inputs     = isset( $instance['inputs'] ) ? $instance['inputs'] : $this->settings['inputs']['std'];

		// Round values to nearest 10 by default.
		$step = max( apply_filters( 'woocommerce_price_filter_widget_step', 10 ), 1 );

		// Find min and max price in current result set.
		$prices    = $this->get_filtered_price( $wcc_object );
		$min_price = $prices->min_price;
		$max_price = $prices->max_price;

		// Check to see if we should add taxes to the prices if store are excl tax but display incl.
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		if ( wc_tax_enabled() && ! wc_prices_include_tax() && 'incl' === $tax_display_mode ) {
			$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
			$tax_rates = WC_Tax::get_rates( $tax_class );

			if ( $tax_rates ) {
				$min_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min_price, $tax_rates ) );
				$max_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $max_price, $tax_rates ) );
			}
		}

		$min_price   = apply_filters( 'woocommerce_price_filter_widget_min_amount', floor( $min_price / $step ) * $step );
		$max_price   = apply_filters( 'woocommerce_price_filter_widget_max_amount', ceil( $max_price / $step ) * $step );
		$start_price = $min_price;
		$end_price   = $max_price;

		if ( isset( $query_args['active'] ) && isset( $query_args['active']['prices'] ) ) {
			$start_price = $query_args['active']['prices'][0];
			$end_price   = $query_args['active']['prices'][1];

		}

		$this->widget_start( $args, $instance );
		echo '<div class="wccon-slider-wrapper ' . esc_attr( $inputs ? 'wccon-price-inputs' : '' ) . '">';
		if ( $inputs ) {
			echo '<div class="inps">';
			echo '<input type="number" name="min_price" class="inp-min" min="' . esc_attr( $min_price ) . '" max="' . esc_attr( $max_price ) . '" placeholder="' . esc_attr( $min_price ) . '" />';
			echo '<input type="number" name="max_price" class="inp-max" min="' . esc_attr( $min_price ) . '" max="' . esc_attr( $max_price ) . '" placeholder="' . esc_attr( $max_price ) . '" />';
			echo '</div>';
		}
		echo '<div id="wccon-slider" class="wccon-slider" data-min-price="' . esc_attr( $min_price ) . '" data-max-price="' . esc_attr( $max_price ) . '" data-start-price="' . esc_attr( $start_price ) . '" data-end-price="' . esc_attr( $end_price ) . '" ></div>';
		if ( $inputs ) {
			echo '<button class="wccon-price-filter-button">' . esc_html__( 'Apply', 'wccontour' ) . '</button>';
		}
		echo '</div>';
		$this->widget_end( $args );
	}

	/**
	 * Get filtered min price for current products.
	 *
	 * @return int
	 */
	protected function get_filtered_price( $wcc_object ) {
		global $wpdb;
		$wcc_join  = $wcc_object->get_join_clause();
		$wcc_where = $wcc_object->get_where_clause() ? " AND {$wcc_object->get_where_clause()}" : '';
		$sql       = "
			SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN (
				SELECT ID FROM {$wpdb->posts}
				{$wcc_join['join_sql']}
				WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
				AND {$wpdb->posts}.post_status = 'publish'
				{$wcc_where} 
			)";

		$sql = apply_filters( 'wccon_price_filter_sql', $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is safe.
		return $wpdb->get_row( $sql );
	}
}
