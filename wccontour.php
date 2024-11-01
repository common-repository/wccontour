<?php
/**
 * Plugin Name: WC Contour - Product Bundles Builder for WooCommerce
 * Plugin URI: https://wccontour.evelynwaugh.com.ua/
 * Description: Enables product configuration through an intuitive builder, empowering customers to create and save product bundles.
 * Version: 1.0.0
 * Author: wsjrcatarri
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: wccontour
 * WC requires at least: 5.0.0
 * WC tested up to:      8.5.2
 *
 * @package WCCON
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wccon_fs' ) ) {
	/**
	 * Create a helper function for easy SDK access.
	 */
	function wccon_fs() {
		global $wccon_fs;

		if ( ! isset( $wccon_fs ) ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/freemius/start.php';

			$wccon_fs = fs_dynamic_init(
				array(
					'id'             => '14166',
					'slug'           => 'wccontour',
					'premium_slug'   => 'wccontour-premium',
					'type'           => 'plugin',
					'public_key'     => 'pk_d0c93c0b6bf5a6ff3140d3466e792',
					'is_premium'     => false,
					'premium_suffix' => 'Pro',

					'has_addons'     => false,
					'has_paid_plans' => true,
					'menu'           => array(
						'slug'    => 'wccon-settings',
						'contact' => true,
						'support' => false,
					),
					'is_live'        => true,
				)
			);
		}

		return $wccon_fs;
	}

	// Init Freemius.
	wccon_fs();
	// Signal that SDK was initiated.
	do_action( 'wccon_fs_loaded' );

	wccon_fs()->add_action( 'after_uninstall', 'wccon_fs_uninstall_cleanup' );
}


if ( ! defined( 'WCCON_PLUGIN_PATH' ) ) {
	define( 'WCCON_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WCCON_PLUGIN_DIR' ) ) {
	define( 'WCCON_PLUGIN_DIR', __FILE__ );
}

define( 'WCCON_PLUGIN_VERSION', '1.0.0' );
require_once WCCON_PLUGIN_PATH . 'includes/traits/Instancetiable.php';

/**
 * Class WCCON_Plugin
 *
 * Main class to load plugin and include all files.
 *
 * @version 1.0.0
 * @author wsjrcatarri
 */
class WCCON_Plugin {

	public $ajax;

	use WCCON\Instancetiable;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		self::activate_plugin();

		add_action( 'plugins_loaded', array( $this, 'run' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Register activation/uninstall hooks.
	 */
	public static function activate_plugin() {
		register_activation_hook( __FILE__, array( 'WCCON_Plugin', 'activate' ) );
		// register_uninstall_hook( __FILE__, array( 'WCCON_Plugin', 'uninstall' ) );
	}

	/**
	 * Run plugin.
	 */
	public function run() {
		$validated = $this->check_plugin();
		if ( ! $validated ) {
			return;
		}

		$this->includes();
		$this->ajax = new WCCON_Ajax();
	}

	/**
	 * Load the textdomain based on WP language.
	 */
	public function load_textdomain() {

		load_plugin_textdomain( 'wccontour', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Include all files.
	 */
	public function includes() {
		require WCCON_PLUGIN_PATH . 'includes/db.php';

		require_once WCCON_PLUGIN_PATH . 'includes/install.php';
		WCCON_Install::instance();

		require_once WCCON_PLUGIN_PATH . 'includes/multilang.php';
		require WCCON_PLUGIN_PATH . 'includes/functions.php';

		require_once WCCON_PLUGIN_PATH . 'includes/admin.php';
		require_once WCCON_PLUGIN_PATH . 'includes/ajax.php';
		require_once WCCON_PLUGIN_PATH . 'includes/shortcodes.php';
		require_once WCCON_PLUGIN_PATH . 'includes/frontend.php';
		require_once WCCON_PLUGIN_PATH . 'includes/product-query.php';
		require_once WCCON_PLUGIN_PATH . 'includes/product.php';
		require_once WCCON_PLUGIN_PATH . 'includes/front-actions.php';
		require_once WCCON_PLUGIN_PATH . 'includes/import.php';

		WCCON_Import::instance(); // only for admin.
		// widgets.
		require_once WCCON_PLUGIN_PATH . 'includes/widgets/widget-taxonomies.php';
		require_once WCCON_PLUGIN_PATH . 'includes/widgets/widget-attributes.php';
		require_once WCCON_PLUGIN_PATH . 'includes/widgets/widget-price.php';
		require_once WCCON_PLUGIN_PATH . 'includes/widgets/widget-meta.php';

	}

	/**
	 * On plugin activation.
	 */
	public static function activate() {

	}

	/**
	 * On plugin uninstallation.
	 */
	public static function uninstall() {
		require WCCON_PLUGIN_PATH . 'includes/db.php';
		require WCCON_PLUGIN_PATH . 'includes/functions.php';

		$settings = wccon_get_settings();
		if ( $settings['delete_data'] ) {
			global $wpdb;
			$lists_table           = WCCON_DB::tables( 'saved_lists', 'name' );
			$config_table          = WCCON_DB::tables( 'data', 'name' );
			$components_table      = WCCON_DB::tables( 'components', 'name' );
			$components_meta_table = WCCON_DB::tables( 'components_meta', 'name' );
			$groups_table          = WCCON_DB::tables( 'groups', 'name' );
			$groups_meta_table     = WCCON_DB::tables( 'groups_meta', 'name' );
			$widgets_table         = WCCON_DB::tables( 'widgets', 'name' );

			$wpdb->query( "DELETE FROM {$config_table}" );

			$wpdb->query( "DROP TABLE IF EXISTS {$widgets_table}" );
			$wpdb->query( "DROP TABLE IF EXISTS {$components_meta_table}" );
			$wpdb->query( "DROP TABLE IF EXISTS {$components_table}" );
			$wpdb->query( "DROP TABLE IF EXISTS {$groups_meta_table}" );
			$wpdb->query( "DROP TABLE IF EXISTS {$groups_table}" );
			$wpdb->query( "DROP TABLE IF EXISTS {$lists_table}" );
			$wpdb->query( "DROP TABLE IF EXISTS {$config_table}" );
			delete_option( 'wccon_db_version' );
			delete_option( 'wccon_settings' );
			delete_option( 'wccon_flushed' );
			delete_post_meta_by_key( 'wccon_enable_variation_compatibility' );
			delete_post_meta_by_key( 'wccon_compatibility_variation' );
			delete_post_meta_by_key( 'wccon_compatibility_comparator' );
			delete_post_meta_by_key( 'wccon_strict_taxonomy' );
			delete_post_meta_by_key( 'wccon_strict_term' );
			delete_post_meta_by_key( 'wccon_compatibility_data' );
			delete_post_meta_by_key( 'wccon_enable_compatibility' );
			delete_post_meta_by_key( 'wccon_global_compatibility' );
		}
	}

	/**
	 * Check plugin requirements.
	 */
	public function check_plugin() {
		$errors = array();
		// php verison check.
		if ( ! function_exists( 'phpversion' ) || version_compare( phpversion(), '7.4', '<' ) ) {
			$errors[] = 'php_error';
		}
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$errors[] = 'wc_not';
		}
		// Wocommerce version check.
		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '5.0.0', '<' ) ) {
			$errors[] = 'wc_error';
		}
		if ( count( $errors ) > 0 ) {
			add_action(
				'admin_notices',
				function () use ( $errors ) {
					if ( in_array( 'php_error', $errors ) ) {

						?>
					<div class="notice notice-error">
						<p>
							<?php esc_html_e( 'WC Contour requires at least 7.4 php version.', 'wccontour' ); ?>
						</p>
					</div>
						<?php
					}
					if ( in_array( 'wc_error', $errors ) && ! in_array( 'wc_not', $errors ) ) {

						?>
					<div class="notice notice-error">
						<p>
							<?php esc_html_e( 'WooCommerce must be active and have at least 5.0.0 version.', 'wccontour' ); ?>
						</p>
					</div>
						<?php

					}
					if ( in_array( 'wc_not', $errors ) ) {

						?>
					<div class="notice notice-error">
						<p>
							<?php esc_html_e( 'WooCommerce must be active.', 'wccontour' ); ?>
						</p>
					</div>
						<?php

					}

				}
			);
			return false;
		}
		return true;
	}

}

/**
 * HPOS compatible.
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Uninstall.
 */
function wccon_fs_uninstall_cleanup() {
	if ( ! class_exists( 'WCCON_DB' ) ) {
		require WCCON_PLUGIN_PATH . 'includes/db.php';
	}
	if ( ! function_exists( 'wccon_get_settings' ) ) {
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

		$settings = get_option( 'wccon_settings' );
		if ( ! $settings ) {
			$settings = $default_args;
		}
	} else {
		$settings = wccon_get_settings();
	}

	update_option( 'wcccon_inactive', 1 );
	if ( $settings['delete_data'] ) {
		global $wpdb;
		$lists_table           = WCCON_DB::tables( 'saved_lists', 'name' );
		$config_table          = WCCON_DB::tables( 'data', 'name' );
		$components_table      = WCCON_DB::tables( 'components', 'name' );
		$components_meta_table = WCCON_DB::tables( 'components_meta', 'name' );
		$groups_table          = WCCON_DB::tables( 'groups', 'name' );
		$groups_meta_table     = WCCON_DB::tables( 'groups_meta', 'name' );
		$widgets_table         = WCCON_DB::tables( 'widgets', 'name' );

		$wpdb->query( "DELETE FROM {$config_table}" );

		$wpdb->query( "DROP TABLE IF EXISTS {$widgets_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$components_meta_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$components_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$groups_meta_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$groups_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$lists_table}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$config_table}" );
		delete_option( 'wccon_db_version' );
		delete_option( 'wccon_settings' );
		delete_option( 'wccon_flushed' );
		delete_post_meta_by_key( 'wccon_enable_variation_compatibility' );
		delete_post_meta_by_key( 'wccon_compatibility_variation' );
		delete_post_meta_by_key( 'wccon_compatibility_comparator' );
		delete_post_meta_by_key( 'wccon_strict_taxonomy' );
		delete_post_meta_by_key( 'wccon_strict_term' );
		delete_post_meta_by_key( 'wccon_compatibility_data' );
		delete_post_meta_by_key( 'wccon_enable_compatibility' );
		delete_post_meta_by_key( 'wccon_global_compatibility' );
	}
}


/**
 * Init plugin.
 */
WCCON_Plugin::instance();
