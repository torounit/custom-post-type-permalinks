<?php
/*
Plugin Name: Custom Post Type Permalinks
Plugin URI: http://www.torounit.com
Description:  Add post archives of custom post type and customizable permalinks.
Author: Toro_Unit
Author URI: http://www.torounit.com/plugins/custom-post-type-permalinks/
Version: 0.9.5.6
Text Domain: cptp
License: GPL2 or later
Domain Path: /language/
*/


/**
 *
 * Custom Post Type Permalinks
 *
 * @package Custom_Post_Type_Permalinks
 * @version 0.9.4
 *
 */


require_once dirname(__FILE__).'/CPTP.php';

add_action( 'plugins_loaded', 'cptp_init_instance' );
function cptp_init_instance() {
    CPTP::get_instance();
}


