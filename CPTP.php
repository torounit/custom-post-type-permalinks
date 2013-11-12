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
define( "CPTP_DIR", dirname( __FILE__ ) );


require_once CPTP_DIR.'/CPTP/Util.php';
require_once CPTP_DIR.'/CPTP/Loader.php';


class CPTP {


	public function __construct() {

		CPTP_Loader::load_module(
			array(
				"Setting",
				"Rewrite",
				"Admin",
				"Permalink",
				"GetArchives",
				"FlushRules"
			)
		);

		do_action( "CPTP_init" );
	}

}
