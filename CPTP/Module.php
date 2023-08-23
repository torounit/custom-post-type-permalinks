<?php
/**
 * Abstract Module.
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * Class CPTP_Module
 */
abstract class CPTP_Module {

	/**
	 * Entry point.
	 */
	final public function init() {
		$this->register();
	}

	/**
	 * Register hook on CPTP_init.
	 */
	public function register() {
		add_action( 'cptp_init', array( $this, 'add_hook' ) );
	}

	/**
	 * Module hook point.
	 */
	abstract public function add_hook();

	/**
	 * Uninstall hooks
	 *
	 * @static
	 */
	public static function uninstall_hook() {
	}

	/**
	 * Fire on activate
	 */
	public function activation_hook() {
	}
}
