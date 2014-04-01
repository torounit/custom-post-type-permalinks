<?php


/**
 *
 * For load plugin.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */


class CPTP_Module_Init extends CPTP_Module {

	public function add_hook() {
		add_action( 'init', array( $this,'load_textdomain') );
		add_action( 'plugins_loaded', array( $this,'check_version') );
		register_uninstall_hook( __FILE__, array(__CLASS__, "uninstall" ));
	}

	/**
	 *
	 * check_version
	 * @since 0.8.6
	 *
	 */

	public function check_version() {
		$version = get_option('cptp_version', 0);
		if($version != CPTP_VERSION) {
			update_option('cptp_version', CPTP_VERSION);
			delete_option("no_taxonomy_structure");
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


	public static function uninstall() {
		CPTP_Util::get_post_types();
		delete_option( "cptp_version" );
		foreach ($post_types as $post_type):
			delete_option($post_type.'_structure');
		endforeach;
	}

}
