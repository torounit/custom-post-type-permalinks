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


define( "CPTP_VERSION", "0.9.3" );
define( "CPTP_DEFAULT_PERMALINK", "/%postname%/" );

require_once dirname( __FILE__ ).'/CPTP/Util.php';
require_once dirname( __FILE__ ).'/CPTP/Module.php';

require_once dirname( __FILE__ ).'/CPTP/Module/Setting.php';
require_once dirname( __FILE__ ).'/CPTP/Module/Rewrite.php';
require_once dirname( __FILE__ ).'/CPTP/Module/Admin.php';
require_once dirname( __FILE__ ).'/CPTP/Module/Permalink.php';
require_once dirname( __FILE__ ).'/CPTP/Module/GetArchives.php';
require_once dirname( __FILE__ ).'/CPTP/Module/FlushRules.php';


class CPTP {

	public static $version = "0.9";
	public static $default_structure = '/%postname%/';
	/**
	 * Add Action & filter hooks.
	 *
	 */
	public function __construct() {
		do_action( "CPTP_init" );
	}

}
