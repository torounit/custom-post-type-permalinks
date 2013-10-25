<?php

/**
 *
 * CPTP
 *
 * Facade.
 * @package Custom_Post_Type_Permalinks
 * @since 0.9.4
 *
 * */

require_once dirname(__FILE__).'/CPTP/Setting.php';
require_once dirname(__FILE__).'/CPTP/Rewrite.php';
require_once dirname(__FILE__).'/CPTP/Admin.php';
require_once dirname(__FILE__).'/CPTP/Permalink.php';
require_once dirname(__FILE__).'/CPTP/GetArchives.php';
require_once dirname(__FILE__).'/CPTP/FlushRules.php';


class CPTP {

	public static $version = "0.9";

	public static $default_structure = '/%postname%/';

	public $setting,$rewrite,$admin,$permalink,$get_archives;

	public function __construct() {
		$this->setting = new CPTP_Setting();
		$this->rewrite = new CPTP_Rewrite();
		$this->admin = new CPTP_Admin();
		$this->permalink = new CPTP_Permalink();
		$this->get_archives = new CPTP_GetArchives();
		$this->flush_rules = new CPTP_FlushRules();
	}

	/**
	 *
	 * Add Action & filter hooks.
	 *
	 */
	public function add_hook() {
		$this->setting->add_hook();
		$this->rewirte->add_hook();
		$this->admin->add_hook();
		if(get_option( "permalink_structure") != "") {
			$this->permalink->add_hook();
			$this->get_archives->add_hook();
		}
		$this->flush_rules->add_hook();
	}
}

