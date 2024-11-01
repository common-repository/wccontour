<?php
/**
 * Product Class.
 *
 * Helper class for WooCommerce products.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Product class.
 */
class WCCON_Product {
	private static $instances = array();
	private $data;

	public function __construct( $product ) {
		$this->data = $product;
	}

	/**
	 * Get product instance.
	 */
	public static function get_product( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}
		if ( ! $product ) {
			return;
		}

		if ( ! array_key_exists( $product->get_id(), self::$instances ) ) {
			self::$instances[ $product->get_id() ] = new self( $product );
		}
		return self::$instances[ $product->get_id() ];
	}

	/**
	 * List meta data.
	 */
	public function get_product_list_meta() {
		return array(
			array(
				'id'     => 'rating',
				'render' => $this->render_reviews(),
			),
			array(
				'id'     => 'sku',
				'render' => $this->render_sku(),
			),
		);
	}

	/**
	 * Render ratings.
	 */
	public function render_reviews() {

		if ( ! wc_review_ratings_enabled() ) {
			return;
		}

		$rating_count = $this->data->get_rating_count();
		$review_count = $this->data->get_review_count();
		$average      = $this->data->get_average_rating();

		if ( $rating_count > 0 ) : ?>

			<div class="wccon-product-rating">
				<?php
				echo wp_kses_post( wc_get_rating_html( $average, $rating_count ) ); // WPCS: XSS ok.
				?>
				<?php if ( comments_open() ) : ?>
					<?php //phpcs:disable 
					?>
					<span class="count"><?php echo '(' .  esc_html($review_count) . ')';?></span>
					<?php // phpcs:enable 
					?>
				<?php endif ?>
			</div>

			<?php
		endif;
	}

	/**
	 * Render SKU.
	 */
	public function render_sku() {
		if ( wc_product_sku_enabled() && ( $this->data->get_sku() || $this->data->is_type( 'variable' ) ) ) :
			$sku        = $this->data->get_sku();
			$sku_output = $sku ? esc_html( $sku ) : esc_html__( 'N/A', 'wccontour' );
			?>

			<span class="wccon-product-sku"><?php esc_html_e( 'SKU:', 'wccontour' ); ?> <span class="sku"><?php echo wp_kses_post( $sku_output ); // WPCS: XSS ok. ?></span></span>

			<?php
		endif;
	}
}
