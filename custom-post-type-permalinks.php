<?php
/*
Plugin Name: Custom Post Type Permalinks
Plugin URI: https://github.com/torounit/custom-post-type-permalinks
Description:  Add post archives of custom post type and customizable permalinks.
Author: Toro_Unit
Author URI: https://torounit.com/
Version: 2.1.2
Text Domain: custom-post-type-permalinks
License: GPL2 or later
Domain Path: /language/
*/


/**
 *
 * Custom Post Type Permalinks
 *
 * @package Custom_Post_Type_Permalinks
 * @version 2.1.2
 */

define( 'CPTP_PLUGIN_FILE', __FILE__ );
$data = get_file_data( __FILE__, array( 'ver' => 'Version', 'lang_dir' => 'Domain Path' ) );
define( 'CPTP_VERSION', $data['ver'] );
define( 'CPTP_DEFAULT_PERMALINK', '/%postname%/' );


/**
 *
 * Autoloader for CPTP.
 *
 * @since 1.0.0
 */
function cptp_class_loader( $class_name ) {
	$dir       = dirname( __FILE__ );
	$file_name = $dir . '/' . str_replace( '_', '/', $class_name ) . '.php';
	if ( is_readable( $file_name ) ) {
		include $file_name;
	}
}

spl_autoload_register( 'cptp_class_loader' );


/**
 * initialize Plugin
 */
add_action( 'plugins_loaded', array( CPTP::get_instance(), 'init' ) );


/**
 * Activation hooks.
 */
register_activation_hook( CPTP_PLUGIN_FILE, array( CPTP::get_instance(), 'activate' ) );

