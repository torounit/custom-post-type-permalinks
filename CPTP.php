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
class CPTP {

	private static $_instance;

	/** @var  CPTP_Module[] */
	public $modules;

	private function __construct() {
		$this->load_modules();
		$this->init();
	}

	/**
	 * load_modules
	 *
	 * Load CPTP_Modules.
	 * @since 0.9.5
	 *
	 */
	private function load_modules() {
		$this->modules['setting']      = new CPTP_Module_Setting();
		$this->modules['rewrite']      = new CPTP_Module_Rewrite();
		$this->modules['admin']        = new CPTP_Module_Admin();
		$this->modules['option']       = new CPTP_Module_Option();
		$this->modules['permalink']    = new CPTP_Module_Permalink();
		$this->modules['get_archives'] = new CPTP_Module_GetArchives();
		$this->modules['flush_rules']  = new CPTP_Module_FlushRules();

		do_action( 'CPTP_load_modules', $this );

		foreach ( $this->modules as $module ) {
			$module->register();
		}

		do_action( 'CPTP_registered_modules', $this );

	}

	/**
	 * init
	 *
	 * Fire Module::add_hook
	 *
	 * @since 0.9.5
	 *
	 */
	private function init() {
		do_action( 'CPTP_init' );
	}

	/**
	 * Singleton
	 * @static
	 */
	public static function get_instance() {

		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new CPTP;
		}

		return self::$_instance;
	}


}
