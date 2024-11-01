<?php
/**
 * User's lists Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/users-lists.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$lists_table           = WCCON_DB::tables( 'saved_lists', 'name' );
$config_table          = WCCON_DB::tables( 'data', 'name' );
$components_table      = WCCON_DB::tables( 'components', 'name' );
$components_meta_table = WCCON_DB::tables( 'components_meta', 'name' );
$groups_table          = WCCON_DB::tables( 'groups', 'name' );
$ajax                  = $ajax ?? false;
$config_id             = $shortcode_id ?? 0;
// var_dump( $wpdb );
$user_id = get_current_user_id();

$wccon_settings = wccon_get_settings();
$lists_per_page = apply_filters( 'wccon_lists_per_page', absint( $wccon_settings['list_limit'] ), $ajax );
$lists_per_page = $lists_per_page > 0 ? $lists_per_page : 10;

$current_page = isset( $_REQUEST['conpage'] ) && is_numeric( $_REQUEST['conpage'] ) ? absint( $_REQUEST['conpage'] ) : 1;


$offset = ( $current_page - 1 ) * $lists_per_page;

// multilang support.

$all_account_lang = $wccon_settings['multilang']['show_account'];
$show_lang_modal  = $wccon_settings['multilang']['show_modal'];

$wccon_multilang = WCCON_Multilang::instance();
$current_lang    = $wccon_multilang->get_lang();
$default_lang    = $wccon_multilang->get_default_lang();

$config_type = 'builder';

// get total lists.
if ( $current_lang && ! $show_lang_modal && $ajax ) {

	if ( $current_lang !== $default_lang ) {
		$total_saved_lists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$lists_table} lt LEFT JOIN {$config_table} ct ON (lt.shortcode_id=ct.id) WHERE lt.user_id !=%d AND lt.shortcode_id=%d AND ct.lang=%s", $user_id, $config_id, $current_lang ) );

	} else {
		$total_saved_lists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$lists_table} lt LEFT JOIN {$config_table} ct ON (lt.shortcode_id=ct.id) WHERE lt.user_id !=%d AND lt.shortcode_id=%d AND (ct.lang=%s OR ct.lang='')", $user_id, $config_id, $current_lang ) );

	}
} elseif ( $current_lang && $show_lang_modal && $ajax ) {
	$config_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table}  WHERE id=%d", $config_id ), ARRAY_A );
	$config_type = $config_data ? $config_data['type'] : 'builder';

	// get by type for multilang.
	$total_saved_lists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$lists_table} lt LEFT JOIN {$config_table} ct ON (lt.shortcode_id=ct.id) WHERE lt.user_id !=%d AND ct.type=%s LIMIT %d OFFSET %d", $user_id, $config_type, $lists_per_page, $offset ) );

} elseif ( $ajax ) {
	$total_saved_lists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$lists_table} WHERE user_id !=%d AND shortcode_id=%d", $user_id, $config_id ) );

} else {
	$total_saved_lists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$lists_table} WHERE user_id !=%d", $user_id ) );

}

// get lists.
if ( $current_lang && ! $show_lang_modal && $ajax ) {

	// get by id for current lang.
	if ( $current_lang !== $default_lang ) {
		$saved_lists = $wpdb->get_results( $wpdb->prepare( "SELECT lt.* FROM {$lists_table} lt LEFT JOIN {$config_table} ct ON (lt.shortcode_id=ct.id) WHERE lt.user_id !=%d AND lt.shortcode_id=%d AND ct.lang=%s LIMIT %d OFFSET %d", $user_id, $config_id, $current_lang, $lists_per_page, $offset ), ARRAY_A );
	} else {
		$saved_lists = $wpdb->get_results( $wpdb->prepare( "SELECT lt.* FROM {$lists_table} lt LEFT JOIN {$config_table} ct ON (lt.shortcode_id=ct.id) WHERE lt.user_id !=%d AND lt.shortcode_id=%d AND (ct.lang=%s OR ct.lang='') LIMIT %d OFFSET %d", $user_id, $config_id, $current_lang, $lists_per_page, $offset ), ARRAY_A );

	}
} elseif ( $current_lang && $show_lang_modal && $ajax ) {

	// get by type for multilang.
	$saved_lists = $wpdb->get_results( $wpdb->prepare( "SELECT lt.* FROM {$lists_table} lt LEFT JOIN {$config_table} ct ON (lt.shortcode_id=ct.id) WHERE lt.user_id !=%d AND ct.type=%s LIMIT %d OFFSET %d", $user_id, $config_type, $lists_per_page, $offset ), ARRAY_A );


} elseif ( $ajax ) {
	$saved_lists = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$lists_table} WHERE user_id !=%d AND shortcode_id=%d LIMIT %d OFFSET %d", $user_id, $config_id, $lists_per_page, $offset ), ARRAY_A );

} else {
	$saved_lists = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$lists_table} WHERE user_id !=%d LIMIT %d OFFSET %d", $user_id, $lists_per_page, $offset ), ARRAY_A );

}


$total_pages = ceil( $total_saved_lists / $lists_per_page );


$content_exists = false;

?>
<div class="wccon-saved-lists">
<?php
foreach ( $saved_lists as $list ) :

	$config_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$config_table}  WHERE id=%d", absint( $list['shortcode_id'] ) ), ARRAY_A );
	$list_lang   = $config_data['lang'];


	$list_data = maybe_unserialize( $list['list_data'] );

	$components_list = wp_list_pluck( $list_data['groups'], 'components' );
	$components_list = array_merge( ...$components_list );
	$total_price     = 0;
	$date_list       = $list['created_at'];
	$formatted_date  = date( 'Y-m-d', strtotime( $date_list ) );
	$formatted_date  = apply_filters( 'wccon_saved_list_date', $formatted_date, $date_list );
	$page_id         = $config_data['page_id'];
	$content_exists  = true;
	?>
<article class="wccon-saved-list" data-id="<?php echo esc_attr( $list['id'] ); ?>">
	<div class="saved-lists__header">
		<?php
		/* translators: %s: date created */
		 echo wp_kses_post( sprintf( esc_html__( 'Assebly from %s', 'wccontour' ), $formatted_date ) );
		?>
	</div>
	<div class="saved-lists__body">
		
		<?php
		$saved_items_classes = array( 'saved-lists__items' );
		if ( ! wccon_fs()->can_use_premium_code() || empty( $list_thumbs ) ) {
			$saved_items_classes[] = 'no-thumbs';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $saved_items_classes ) ) ); ?>">
			<div class="wccon-scrl-container">
				<ul>
			<?php
			foreach ( $components_list as $component ) :
				?>
				<?php if ( isset( $component['components'] ) ) : ?>

					<?php
					if ( ! empty( $component['components'] ) ) :
						foreach ( $component['components'] as $subcomponent ) :
							$component_data = $wpdb->get_results( $wpdb->prepare( "SELECT title, image_id FROM {$components_table} WHERE id=%d", absint( $subcomponent['id'] ) ), ARRAY_A );

							// if component was removed from config then skip.
							if ( ! $component_data ) {
								continue;
							}
							?>
							<?php
							if ( ! empty( $subcomponent['products'] ) ) :
								foreach ( $subcomponent['products'] as $product_data ) :
									?>
									<li class="saved-lists__item">
										<?php
										$product_id     = $product_data['variation_id'] ? $product_data['variation_id'] : $product_data['product_id'];
										$product_object = wc_get_product( $product_id );
										if ( ! $product_object ) {
											continue;
										}
										$total_price += $product_object->get_price() * (int) $product_data['quantity'];
										?>
										<div class="saved-lists__item-image">
										<?php
										echo wp_kses_post(
											wp_get_attachment_image(
												$component_data[0]['image_id'],
												'thumbnail',
												'',
												array(
													'alt' => wp_kses_post( $component_data[0]['title'] ),
													'title' => wp_kses_post( $component_data[0]['title'] ),
												)
											)
										);
										?>
											<?php if ( (int) $product_data['quantity'] > 1 ) : ?>
											<div class="saved-lists__item-badge">
													<?php echo esc_html( $product_data['quantity'] ); ?>
											</div>
											<?php endif; ?>
										</div>
										<span title="<?php echo esc_attr( $product_object->get_name() ); ?>" class="saved-lists__item-title"><?php echo wp_kses_post( $product_object->get_name() ); ?></span>
									</li>
									<?php
						endforeach;
endif;
endforeach;
endif;
					?>
					<?php
					else :
						$component_data = $wpdb->get_results( $wpdb->prepare( "SELECT title, image_id FROM {$components_table} WHERE id=%d", absint( $component['id'] ) ), ARRAY_A );

						// if component was removed from config then skip.
						if ( ! $component_data ) {
							continue;
						}
						?>
						<?php
						if ( ! empty( $component['products'] ) ) :
							foreach ( $component['products'] as $product_data ) :
								?>
						<li class="saved-lists__item">
								<?php
								$product_id     = $product_data['variation_id'] ? $product_data['variation_id'] : $product_data['product_id'];
								$product_object = wc_get_product( $product_id );
								if ( ! $product_object ) {
									continue;
								}
								$total_price += $product_object->get_price() * (int) $product_data['quantity'];
								?>
							<div class="saved-lists__item-image">
								<?php
								echo wp_kses_post(
									wp_get_attachment_image(
										$component_data[0]['image_id'],
										'thumbnail',
										'',
										array(
											'alt'   => wp_kses_post( $component_data[0]['title'] ),
											'title' => wp_kses_post( $component_data[0]['title'] ),
										)
									)
								);
								?>
								<?php if ( (int) $product_data['quantity'] > 1 ) : ?>
								<div class="saved-lists__item-badge">
										<?php echo esc_html( $product_data['quantity'] ); ?>
								</div>
								<?php endif; ?>
							</div>
							<span title="<?php echo esc_attr( $product_object->get_name() ); ?>" class="saved-lists__item-title"><?php echo wp_kses_post( $product_object->get_name() ); ?></span>
						</li>
								<?php
					endforeach;
endif;
						?>
					<?php endif; ?>
			
				<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
	<div class="saved-lists__footer">
		<div class="saved-lists__share">
	
			<a href="<?php echo esc_url( wccon_get_edit_users_list_link( $page_id, $list['id'] ) ); ?>" title="<?php esc_attr_e( 'Edit', 'wccontour' ); ?>" class="saved-lists__button-edit wccon-list-button-edit">
				<svg><use xlink:href="#icon-pen"></use></svg>
			</a>
			<button title="<?php esc_attr_e( 'Share', 'wccontour' ); ?>" class="saved-lists__button-share wccon-list-button-share">
				<svg><use xlink:href="#kitshare"></use></svg>
			</button>
		</div>
		<div class="saved-lists__total">
			<p><?php echo wp_kses_post( wc_price( $total_price ) ); ?></p>
			<button type="button" class="wccon-list-button-buy" data-id="<?php echo esc_attr( $list['id'] ); ?>">
				<svg><use xlink:href="#icon-cart"></use></svg>
				<?php esc_html_e( 'Buy', 'wccontour' ); ?>
			</button>
		</div>
	</div>
	<div class="saved-lists__share-box">
		<?php
		$socials_links = wccon_get_social_links( $page_id, $list['id'], 'users' );

		foreach ( $socials_links as $socials_link ) :
			?>
				<a href="<?php echo esc_attr( $socials_link['link'] ); ?>" class="<?php echo esc_attr( $socials_link['class'] ); ?>" title="<?php echo esc_attr( $socials_link['title'] ); ?>">
				<svg>
					<use xlink:href="<?php echo esc_attr( '#' . $socials_link['svg_id'] ); ?>"></use>
				</svg>
			</a>
			<?php endforeach; ?>
			<button class="wccon-close-share">
				<svg><use xlink:href="#icon-close"></use></svg>
			</button>
	</div>
</article>

<?php endforeach; ?>
<?php if ( ! $content_exists ) : ?>
	<div class="wccon-no-lists">
		<p><?php echo wp_kses_post( apply_filters( 'wccon_no_lists_found_text', esc_html__( 'Nothing found', 'wccontour' ) ) ); ?></p>
	</div>
	<?php endif; ?>
</div>

<?php
if ( $ajax && $total_pages > $current_page ) {
	echo '<div class="wccon-button-container"><button class="wccon-load-more" data-current="' . esc_attr( $current_page ) . '" data-type="users" data-total="' . esc_attr( $total_pages ) . '">' . wp_kses_post( apply_filters( 'wccon_load_more_button_text', esc_html__( 'Load more', 'wccontour' ) ) ) . '</button></div>';
} else {
	echo wp_kses_post( wccon_product_pagination( $current_page, $total_pages ) ); // WPCS: XSS ok.
}

?>
