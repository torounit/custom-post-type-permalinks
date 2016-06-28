<?php

abstract class CPTP_Module {

	final public function init() {
		$this->register();
	}

	public function register() {
		add_action( 'CPTP_init', array( $this, 'add_hook' ) );
	}

	abstract function add_hook();

	/**
	 * uninstall hooks
	 *
	 * @static
	 */
	public static function uninstall_hook() {
	}

	/**
	 * fire on activate
	 */
	public function activation_hook() {
	}
}
