<?php
/**
 * DB helper Class
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_DB class.
 */
class WCCON_DB {

	/**
	 * Get tables names.
	 */
	public static function tables( $table = null, $return = 'all' ) {
		global $wpdb;
		$lists_table           = $wpdb->prefix . 'wccon_saved_lists';
		$data_table            = $wpdb->prefix . 'wccon_configs';
		$components_table      = $wpdb->prefix . 'wccon_components';
		$components_meta_table = $wpdb->prefix . 'wccon_components_meta';
		$groups_table          = $wpdb->prefix . 'wccon_groups';
		$groups_meta_table     = $wpdb->prefix . 'wccon_groups_meta';
		$widgets_table         = $wpdb->prefix . 'wccon_widgets';

		$tables = array(
			'saved_lists'     => array(
				'name' => $lists_table,
			),
			'data'            => array(
				'name' => $data_table,
			),
			'components'      => array(
				'name' => $components_table,
			),
			'components_meta' => array(
				'name' => $components_meta_table,
			),
			'groups'          => array(
				'name' => $groups_table,
			),
			'groups_meta'     => array(
				'name' => $groups_meta_table,
			),
			'widgets'         => array(
				'name' => $widgets_table,
			),
		);

		if ( is_null( $table ) ) {
			return $tables;
		} elseif ( is_null( $table ) && 'name' === $return ) {
			return wp_list_pluck( $tables, 'name' );
		}

		switch ( $return ) {
			case 'all':
				return isset( $tables[ $table ] ) ? $tables[ $table ] : false;

			case 'name':
				return isset( $tables[ $table ] ) ? $tables[ $table ]['name'] : false;
		}

			return false;
	}


}
