<?php


/**
 *
 * For load plugin.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */


class CPTP_Module_Setting extends CPTP_Module {

	public function add_hook() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'check_version' ) );
	}

	/**
	 *
	 * check_version
	 * @since 0.8.6
	 *
	 */

	public function check_version() {
		$version = get_option( 'cptp_version', 0 );
		if ( false === $version ){
			add_option( 'cptp_version', CPTP_VERSION );
		}
		else if ( CPTP_VERSION != $version ) {
			update_option( 'cptp_version', CPTP_VERSION );
		}
	}


	/**
	 *
	 * load textdomain
	 * @since 0.6.2
	 *
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'cptp', false, 'custom-post-type-permalinks/language' );
	}

}
