<?php


/**
 *
 * For load plugin.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */


class CPTP_Setting {

	public function add_hook() {
		add_action( 'init', array( $this,'load_textdomain') );
		add_action( 'plugins_loaded', array( $this,'check_version') );
	}

	/**
	 *
	 * check_version
	 * @since 0.8.6
	 *
	 */

	public function check_version() {
		$version = get_option('cptp_version', 0);
		if($version != CPTP::$version) {
			update_option('cptp_version', CPTP::$version);
		}
	}


	/**
	 *
	 * load textdomain
	 * @since 0.6.2
	 *
	 */
	public function load_textdomain() {
		load_plugin_textdomain('cptp',false,'custom-post-type-permalinks/language');
	}

}

