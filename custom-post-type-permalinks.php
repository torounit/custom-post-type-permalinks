<?php
/*
Plugin Name: Custom Post Type Permalinks
Plugin URI: http://www.torounit.com
Description:  Add post archives of custom post type and customizable permalinks.
Author: Toro_Unit
Author URI: http://www.torounit.com/plugins/custom-post-type-permalinks/
Version: 1.0.4
Text Domain: cptp
License: GPL2 or later
Domain Path: /language/
*/


/**
 *
 * Custom Post Type Permalinks
 *
 * @package Custom_Post_Type_Permalinks
 * @version 1.0.4
 *
 */

define( 'CPTP_PLUGIN_FILE', __FILE__ );
$data = get_file_data( __FILE__, array( 'ver' => 'Version', 'lang_dir' => 'Domain Path' ) );
define( 'CPTP_VERSION', $data['ver'] );
define( 'CPTP_DEFAULT_PERMALINK', '/%postname%/' );



/**
 *
 * Autoloader for CPTP.
 * @since 1.0.0
 *
 */
function cptp_class_loader( $class_name ) {
	$dir = dirname( __FILE__ );
	$file_name = $dir . '/'. str_replace( '_', '/', $class_name ).'.php';
	if ( is_readable( $file_name ) ) {
		include $file_name;
	}
}
spl_autoload_register( 'cptp_class_loader' );


/**
 *
 * Entry Point
 * @since 0.9.4
 *
 */
add_action( 'plugins_loaded', 'cptp_init_instance' );
function cptp_init_instance() {
	CPTP::get_instance();
}


