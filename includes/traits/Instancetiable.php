<?php
/**
 * Handles Instancetiable.
 *
 * @package WCCON\Traits
 * @version 1.0.0
 */
namespace WCCON;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialisable trait.
 */
trait Instancetiable {

	/**
	 * Instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
