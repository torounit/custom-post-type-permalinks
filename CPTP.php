<?php

/**
 * CPTP
 *
 * Facade.
 *
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */


define( "CPTP_VERSION", "0.9.5.6" );
define( "CPTP_DEFAULT_PERMALINK", "/%postname%/" );
define( "CPTP_DIR", dirname( __FILE__ ) );


require_once CPTP_DIR.'/CPTP/Util.php';
require_once CPTP_DIR.'/CPTP/Module.php';
require_once CPTP_DIR.'/CPTP/Module/Setting.php';
require_once CPTP_DIR.'/CPTP/Module/Rewrite.php';
require_once CPTP_DIR.'/CPTP/Module/Admin.php';
require_once CPTP_DIR.'/CPTP/Module/Permalink.php';
require_once CPTP_DIR.'/CPTP/Module/GetArchives.php';
require_once CPTP_DIR.'/CPTP/Module/FlushRules.php';


class CPTP {

	private static $instance;

	private function __construct() {
		$this->load_modules();
		$this->init();
	}

	/**
	 *
	 * load_modules
	 *
	 * Load CPTP_Modules.
	 * @since 0.9.5
	 *
	 * */

	private function load_modules() {
		new CPTP_Module_Setting();
		new CPTP_Module_Rewrite();
		new CPTP_Module_Admin();
		new CPTP_Module_Permalink();
		new CPTP_Module_GetArchives();
		new CPTP_Module_FlushRules();
		do_action( "CPTP_load_modules" );

	}

	/**
	 *
	 * init
	 *
	 * Fire Module::add_hook
	 *
	 * @since 0.9.5
	 *
	 * */

	private function init() {
		do_action( "CPTP_init" );
	}

	/**
	 * Singleton
	 * @static
	 */
	public static function get_instance() {

		if (!isset(self::$_instance)) {
			self::$instance = new CPTP;
		}

		return self::$instance;
	}



}
