<?php
/**
 * Frontend Class.
 *
 * Handles generic Frontend functionality.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Frontend class.
 */
class WCCON_Frontend {

	use WCCON\Instancetiable;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'woocommerce_available_variation', array( $this, 'modify_variation_data' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'add_modal' ) );
	}

	/**
	 * Enqueue scrips/styles.
	 */
	public function enqueue() {

		$settings = wccon_get_settings();

		$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$suffix       = $script_debug ? '' : '.min';
		// $suffix      = ''; // test.
		wp_register_style( 'wccon-toastr', plugins_url( 'assets/front/plugins/toastr.min.css', WCCON_PLUGIN_DIR ), array(), '2.1.4', 'all' );
		wp_register_script( 'wccon-toastr', plugins_url( 'assets/front/plugins/toastr.min.js', WCCON_PLUGIN_DIR ), array( 'jquery' ), '2.1.4', false );
		wp_register_style( 'wccon-slider', plugins_url( 'assets/front/plugins/nouislider.css', WCCON_PLUGIN_DIR ), array(), '15.6.1', 'all' );
		wp_register_script( 'wccon-slider', plugins_url( 'assets/front/plugins/nouislider.min.js', WCCON_PLUGIN_DIR ), array(), '15.6.1', false );
		wp_register_script( 'wccon-popper', plugins_url( 'assets/front/plugins/popper.min.js', WCCON_PLUGIN_DIR ), array(), '2.11.6', false );
		wp_register_script( 'wccon-tippy', plugins_url( 'assets/front/plugins/tippy.min.js', WCCON_PLUGIN_DIR ), array(), '6.0', false );

		wp_localize_script(
			'wccon-slider',
			'wccon_price_slider_params',
			array(
				'currency_format_num_decimals' => 0,
				'currency_format_symbol'       => get_woocommerce_currency_symbol(),
				'currency_format_decimal_sep'  => esc_attr( wc_get_price_decimal_separator() ),
				'currency_format_thousand_sep' => esc_attr( wc_get_price_thousand_separator() ),
				'currency_format'              => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ),
			)
		);
		 // wp_register_style( 'wccon-builder', plugins_url( 'assets/dist/app.css', WCCON_PLUGIN_DIR ), array(), time(), 'all' );
		 wp_register_style( 'wccon-builder', plugins_url( 'assets/front/css/builder.min.css', WCCON_PLUGIN_DIR ), array(), WCCON_PLUGIN_VERSION, 'all' );
		$nonce2 = wccon_get_nonce2();
		global $wpdb,$post;

		$config_table = WCCON_DB::tables( 'data', 'name' );

		$shortcodes = $wpdb->get_results( "SELECT id FROM {$config_table}", ARRAY_A );

		if ( $shortcodes ) {
			$shortcodes = array_map( 'absint', wp_list_pluck( $shortcodes, 'id' ) );
		}

		$shortcodes_pages = $wpdb->get_col( "SELECT page_id FROM {$config_table}" );

		$shortcodes_pages = array_map( 'absint', $shortcodes_pages );

		// enqueue styles for associated pages.
		foreach ( $shortcodes_pages as $shortcodes_page ) {
			if ( $shortcodes_page && is_page( $shortcodes_pages ) ) {
				wp_enqueue_style( 'wccon-builder' );

			} elseif ( is_front_page() || is_home() ) {
				wp_enqueue_style( 'wccon-builder' );
			}
		}

		// load style on wc account page.
		if ( is_wc_endpoint_url( $settings['account_endpoint'] ) ) {
			wp_enqueue_style( 'wccon-builder' );
		}

		// trying apply style for 'incontent' shortcodes.

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wccon-builder' ) ) {
			wp_enqueue_style( 'wccon-builder' );
		}

		/**
		 * Add pages to load styles for.
		 */
		do_action( 'wccon_after_load_front_styles', $shortcodes_pages );

		wp_register_script( 'wccon-builder', plugins_url( 'assets/front/js/builder' . $suffix . '.js', WCCON_PLUGIN_DIR ), array( 'jquery', 'wp-util', 'js-cookie', 'jquery-blockui', 'wp-hooks' ), WCCON_PLUGIN_VERSION, true );
		wp_localize_script(
			'wccon-builder',
			'WCCON_BUILDER_FRONT',
			array(
				'ajax_url'                    => admin_url( 'admin-ajax.php' ),
				'price_position'              => get_option( 'woocommerce_currency_pos' ),
				'decimals'                    => wc_get_price_decimals(),
				'currency_format_symbol'      => get_woocommerce_currency_symbol(),
				'decimal_separator'           => wc_get_price_decimal_separator(),
				'thousand_separator'          => wc_get_price_thousand_separator(),
				'sold_individually'           => apply_filters( 'wccon_sold_individually_enabled', true ),
				'endpoint'                    => wc_get_account_endpoint_url( $settings['account_endpoint'] ),
				'endpoint_slug'               => $settings['account_endpoint'],
				'compatibility_enabled'       => wccon_is_compatibility_enabled(),
				'local_storage'               => wccon_local_storage_enabled(),
				'saved_config_ids'            => $shortcodes,
				'i18n_success_message'        => __( 'Saved!', 'wccontour' ),
				'i18n_success_message_remove' => __( 'Removed successfully!', 'wccontour' ),
				'i18n_copied_message'         => __( 'Copied!', 'wccontour' ),

				'i18n_error_message'          => __( 'Something went wrong. Try again.', 'wccontour' ),
				'i18n_added_to_cart'          => __( 'Added to cart!', 'wccontour' ),
				'i18n_remove_list'            => __( 'Are you sure?', 'wccontour' ),
				'languages'                   => wccon_get_languages(),

				'i18n_sku_na'                 => __( 'N/A', 'wccontour' ),
				'nonce'                       => wp_create_nonce( 'wccon-nonce' ),
				'nonce2'                      => $nonce2,
			)
		);

	}

	/**
	 * Modify variation data.
	 */
	public function modify_variation_data( $data, $product ) {

		if ( wp_doing_ajax() ) {
			$data['range_price']  = $product->get_price_html();
			$data['stock_html']   = '<svg><use xlink:href="#icon-chk-small"></use></svg>' . __( ' In stock', 'wccontour' );
			$data['nostock_html'] = '<svg><use xlink:href="#icon-x-small"></use></svg>' . __( ' Out of stock', 'wccontour' );
		}
		return $data;
	}

	/**
	 * Add modals to builder page only once.
	 */
	public function add_modal() {
		?>
		<div class="wccon-modal" id="wccon-users-list" style="display:none;">
			<div class="wccon-modal-inner">
				<div class="wccon-modal__header">
					<span class="wccon-modal__title"><?php echo wp_kses_post( apply_filters( 'wccon_users_collection_title', esc_html__( 'Users\'s collections', 'wccontour' ) ) ); ?></span>
					<button class="wccon-modal__close">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="close"><path d="M13.41,12l6.3-6.29a1,1,0,1,0-1.42-1.42L12,10.59,5.71,4.29A1,1,0,0,0,4.29,5.71L10.59,12l-6.3,6.29a1,1,0,0,0,0,1.42,1,1,0,0,0,1.42,0L12,13.41l6.29,6.3a1,1,0,0,0,1.42,0,1,1,0,0,0,0-1.42Z"/></svg>
					</button>
				</div>
				<div class="wccon-modal__body">

				</div>
			</div>
		</div>
		<div class="wccon-modal" id="wccon-user-list" style="display:none;">
			<div class="wccon-modal-inner">
				<div class="wccon-modal__header">
					<span class="wccon-modal__title"><?php echo wp_kses_post( apply_filters( 'wccon_user_collection_title', esc_html__( 'My collections', 'wccontour' ) ) ); ?></span>
					<button class="wccon-modal__close">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="close"><path d="M13.41,12l6.3-6.29a1,1,0,1,0-1.42-1.42L12,10.59,5.71,4.29A1,1,0,0,0,4.29,5.71L10.59,12l-6.3,6.29a1,1,0,0,0,0,1.42,1,1,0,0,0,1.42,0L12,13.41l6.29,6.3a1,1,0,0,0,1.42,0,1,1,0,0,0,0-1.42Z"/></svg>
					</button>
				</div>
				<div class="wccon-modal__body">

				</div>
			</div>
		</div>

		<div class="wccon-modal" id="wccon-share-modal" style="display:none;">
			<div class="wccon-modal-inner">
				<div class="wccon-modal__header">
					<button class="wccon-modal__close">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="close"><path d="M13.41,12l6.3-6.29a1,1,0,1,0-1.42-1.42L12,10.59,5.71,4.29A1,1,0,0,0,4.29,5.71L10.59,12l-6.3,6.29a1,1,0,0,0,0,1.42,1,1,0,0,0,1.42,0L12,13.41l6.29,6.3a1,1,0,0,0,1.42,0,1,1,0,0,0,0-1.42Z"/></svg>
					</button>
				</div>
				<div class="wccon-modal__body">

				</div>
			</div>
		</div>
		<?php
	}
}
WCCON_Frontend::instance();
