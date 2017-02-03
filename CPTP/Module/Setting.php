<?php


/**
 *
 * For load plugin.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 * */


class CPTP_Module_Setting extends CPTP_Module {

	public function add_hook() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		$this->check_version();
	}

	/**
	 *
	 * check_version
	 *
	 * @since 0.8.6
	 */

	public function check_version() {
		update_option( 'cptp_version', CPTP_VERSION );
	}


	/**
	 *
	 * load textdomain
	 *
	 * @since 0.6.2
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'custom-post-type-permalinks', false, 'custom-post-type-permalinks/language' );
	}
}
