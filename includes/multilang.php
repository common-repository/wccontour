<?php
/**
 * Multilang Class.
 *
 * WPML/Polylang helper class.
 *
 * @since 1.0.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCCON_Multilang class.
 */
class WCCON_Multilang {

	use WCCON\Instancetiable;

	protected static $initial_lang = null;

	public function __construct() {
		add_filter( 'wccon_taxonomy_term_args', array( $this, 'pll_get_terms' ), 10, 3 );
	}

	public function is_wpml_enabled() {
		 global $sitepress;
		if ( $sitepress instanceof SitePress ) {
			return true;
		}
		return false;
	}
	public function is_pll_enabled() {
		if ( function_exists( 'pll_the_languages' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get current lang.
	 *
	 * @return string|null
	 */
	public function get_lang() {
		global $sitepress;

		if ( $this->is_wpml_enabled() ) {
			return $sitepress->get_current_language();
		}

		if ( $this->is_pll_enabled() ) {
			return pll_current_language();
		}

		return null;
	}

	/**
	 * Get default lang.
	 *
	 * @return string|null
	 */
	public function get_default_lang() {
		global $sitepress;
		if ( $this->is_wpml_enabled() ) {
			return $sitepress->get_default_language();
		}
		if ( $this->is_pll_enabled() ) {
			return pll_default_language( 'slug' );
		}
		return null;
	}

	/**
	 * Switch lang.
	 *
	 * @param string $lang Language.
	 * @return string|null
	 */
	public function switch_lang( $lang ) {
		global $sitepress;

		if ( $sitepress instanceof SitePress ) {
			if ( $lang != $sitepress->get_current_language() ) {
				if ( self::$initial_lang === null ) {
					self::$initial_lang = $sitepress->get_current_language();
				}
				$sitepress->switch_lang( $lang );
				$GLOBALS['wp_locale'] = new WP_Locale();
			}

			return $lang;
		}

		return null;
	}

	/**
	 * Switch  to default lang.
	 *
	 * @return string|null
	 */
	public function switch_to_default() {
		global $sitepress;

		if ( $sitepress instanceof SitePress ) {
			return $this->switch_lang( $sitepress->get_default_language() );
		}

		return null;
	}

	/**
	 * Restore  lang.
	 */
	public function restore_lang() {
		if ( self::$initial_lang !== null ) {
			$this->switch_lang( self::$initial_lang );
			self::$initial_lang = null;
		}
	}

	public function pll_get_terms( $args, $taxonomy, $lang ) {
		if ( $lang && $this->is_pll_enabled() ) {
			$args['lang'] = $lang;
		}
		return $args;
	}
}
