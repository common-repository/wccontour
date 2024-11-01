<?php
/**
 * Saved list Edit Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/saved-lists-edit.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$data_lists = WCCON_DB::tables( 'saved_lists', 'name' );
$data_name  = WCCON_DB::tables( 'data', 'name' );

$saved_list = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$data_lists} WHERE id=%d AND user_id=%d", $id, $user_id ), ARRAY_A );
if ( ! empty( $saved_list ) ) {
	$config_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$data_name} WHERE id=%d", $saved_list['shortcode_id'] ), ARRAY_A );
	if ( ! empty( $config_data ) ) {
		echo do_shortcode( '[wccon-builder id="' . $config_data['shortcode_id'] . '" title="' . $config_data['title'] . '"]' );
	}
}
