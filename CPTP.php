<?php
/**
 * CPTP core.
 *
 * @package Custom_Post_Type_Permalinks
 */

/**
 * CPTP
 *
 * @since 0.9.4
 * */
class CPTP {

	/**
	 * CPTP instance.
	 *
	 * @var CPTP
	 */
	private static $_instance;

	/**
	 * Module instances.
	 *
	 * @var  CPTP_Module[]
	 */
	public $modules;

	/**
	 * CPTP constructor.
	 */
	private function __construct() {
		$this->load_modules();
	}

	/**
	 *
	 * Load CPTP_Modules.
	 *
	 * @since 0.9.5
	 */
	private function load_modules() {
		$this->set_module( 'setting', new CPTP_Module_Setting() );
		$this->set_module( 'rewrite', new CPTP_Module_Rewrite() );
		$this->set_module( 'admin', new CPTP_Module_Admin() );
		$this->set_module( 'option', new CPTP_Module_Option() );
		$this->set_module( 'permalink', new CPTP_Module_Permalink() );
		$this->set_module( 'get_archives', new CPTP_Module_GetArchives() );
		$this->set_module( 'flush_rules', new CPTP_Module_FlushRules() );

		do_action( 'CPTP_load_modules', $this );
		do_action( 'cptp_load_modules', $this );
	}

	/**
	 * Initialize modules.
	 *
	 * @since 2.0.0
	 */
	private function init_modules() {
		foreach ( $this->modules as $module ) {
			$module->init();
		}

		do_action( 'CPTP_registered_modules', $this );
		do_action( 'cptp_registered_modules', $this );
	}

	/**
	 * Set module instance.
	 *
	 * @param String      $name Module Name.
	 * @param CPTP_Module $module Module instance.
	 *
	 * @since 1.5.0
	 */
	public function set_module( $name, CPTP_Module $module ) {
		$module = apply_filters( "CPTP_set_{$name}_module", $module );
		$module = apply_filters( "cptp_set_{$name}_module", $module );
		if ( $module instanceof CPTP_Module ) {
			$this->modules[ $name ] = $module;
		}
	}

	/**
	 * Init
	 *
	 * Fire Module::add_hook
	 *
	 * @since 0.9.5
	 */
	public function init() {
		$this->init_modules();
		do_action( 'CPTP_init' );
		do_action( 'cptp_init' );
	}

	/**
	 * Singleton
	 *
	 * @static
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new CPTP();
		}

		return self::$_instance;
	}


	/**
	 * Activation Hooks
	 * This function will browse initialized modules and execute their activation_hook methods.
	 * It will also set the uninstall_hook to the cptp_uninstall function which behaves the same way as this one.
	 *
	 * @since 2.0.0
	 */
	public function activate() {
		foreach ( $this->modules as $module ) {
			$module->activation_hook();
		}

		register_uninstall_hook( CPTP_PLUGIN_FILE, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Uninstall Hooks
	 * This function will browse initialized modules and execute their uninstall_hook methods.
	 *
	 * @since 2.0.0
	 */
	public static function uninstall() {
		$cptp = CPTP::get_instance();

		foreach ( $cptp->modules as $module ) {
			$module->uninstall_hook();
		}
	}
}
