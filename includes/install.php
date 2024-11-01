<?php
/**
 * Install Class.
 *
 * Creating and altering tables.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Install class.
 */
class WCCON_Install {
	use WCCON\Instancetiable;
	protected $wc_config_db_version = '1.0.8';
	protected $current_db_version;


	public function __construct() {
		$this->current_db_version = get_option( 'wccon_db_version' );
		if ( $this->wc_config_db_version !== $this->current_db_version ) {
			$this->install();
		}
	}

	/**
	 * Install tables.
	 */
	private function install() {
		global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$lists_table           = WCCON_DB::tables( 'saved_lists', 'name' );
			$config_table          = WCCON_DB::tables( 'data', 'name' );
			$components_table      = WCCON_DB::tables( 'components', 'name' );
			$components_meta_table = WCCON_DB::tables( 'components_meta', 'name' );
			$groups_table          = WCCON_DB::tables( 'groups', 'name' );
			$groups_meta_table     = WCCON_DB::tables( 'groups_meta', 'name' );
			$widgets_table         = WCCON_DB::tables( 'widgets', 'name' );

			$sql_data = "CREATE TABLE $config_table (
					id     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					shortcode_id MEDIUMINT NOT NULL,
					type VARCHAR(255) DEFAULT NULL,
					title VARCHAR(255) NOT NULL,
					page_id BIGINT(20) DEFAULT NULL,
					lang VARCHAR(8) DEFAULT NULL,
					PRIMARY KEY (id)	
				) $charset_collate;";

			$sql_group = "CREATE TABLE $groups_table (
				id     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				config_id  BIGINT(20) UNSIGNED NOT NULL,
				title VARCHAR(255) NOT NULL,
				slug VARCHAR(255) NOT NULL,
				parent_id BIGINT(20) UNSIGNED DEFAULT 0,
				image_id MEDIUMINT DEFAULT NULL,
				position SMALLINT DEFAULT NULL,
				PRIMARY KEY (id),
				FOREIGN KEY (config_id)
					REFERENCES {$config_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			$sql_group_meta = "CREATE TABLE $groups_meta_table (
				id     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				group_id  BIGINT(20) UNSIGNED NOT NULL,
				meta_key VARCHAR(255) NOT NULL,
				meta_value LONGTEXT,
				PRIMARY KEY (id),
				FOREIGN KEY (group_id)
					REFERENCES {$groups_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			$sql_component = "CREATE TABLE $components_table (
				id     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				group_id  BIGINT(20) UNSIGNED NOT NULL,
				title VARCHAR(255) NOT NULL,
				slug VARCHAR(255) NOT NULL,
				image_id MEDIUMINT DEFAULT NULL,
				position SMALLINT DEFAULT NULL,
				PRIMARY KEY (id),
				FOREIGN KEY (group_id)
					REFERENCES {$groups_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			$sql_component_meta = "CREATE TABLE $components_meta_table (
				id     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				component_id  BIGINT(20) UNSIGNED NOT NULL,
				meta_key VARCHAR(255) NOT NULL,
				meta_value LONGTEXT,
				PRIMARY KEY (id),
				FOREIGN KEY (component_id) 
					REFERENCES {$components_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			$sql_widgets = "CREATE TABLE $widgets_table (
					id     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				component_id  BIGINT(20) UNSIGNED NOT NULL,
				widget_value LONGTEXT,
				PRIMARY KEY (id),
				FOREIGN KEY (component_id) 
					REFERENCES {$components_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			$sql_saved_list = "CREATE TABLE $lists_table (
				id     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id  MEDIUMINT UNSIGNED,
				shortcode_id BIGINT(20) UNSIGNED NOT NULL,
				list_data LONGTEXT,
				created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				FOREIGN KEY (shortcode_id) 
				REFERENCES {$config_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql_data );
			dbDelta( $sql_group );
			dbDelta( $sql_group_meta );
			dbDelta( $sql_component );
			dbDelta( $sql_component_meta );
			dbDelta( $sql_widgets );
			dbDelta( $sql_saved_list );
			update_option( 'wccon_db_version', $this->wc_config_db_version );

	}
}
