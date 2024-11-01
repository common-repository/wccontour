<?php
/**
 * Product Top Section Template
 *
 * This template can be overridden by copying it to yourtheme/wccontour/product-top-section.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WCCON\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wccon-product-top">
	
		<div class="wccon-product-top__left">
			<div class="wccon-scrl-container">
				<div class="wccon-product-top__row">
					<?php
					/**
					 * @hooked wccon_render_required_items - 5
					 * @hooked wccon_render_extra_items - 10
					 */
					do_action( 'wccon_builder_topbar_items', $data, $id, $cp, $type );
					?>
				</div>
			</div>
			<?php do_action( 'wccon_builder_topbar_row', $data, $id, $cp, $type ); ?>
		</div>

		<div class="wccon-product-top__right">
			<?php
			/**
			 * @hooked wccon_render_topbar_actions - 10
			 */
			do_action( 'wccon_builder_topbar_actions', $data, $id, $cp, $type );
			?>
		</div>
</div>
<div class="wccon-product-top__actions">
	<?php if ( is_user_logged_in() ) : ?>
			<button class="wccon-open-user-lists"><svg width="12" height="14"><use xlink:href="#icon-user"></use></svg><?php esc_html_e( 'My collections', 'wccontour' ); ?></button>
	<?php endif; ?>
			<button class="wccon-open-lists"><svg width="17" height="14"><use xlink:href="#icon-users"></use></svg><?php esc_html_e( 'Users\'s collections', 'wccontour' ); ?></button>
</div>
