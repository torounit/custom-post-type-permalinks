<?php
/**
 * PHPUnit bootstrap file
 *
 * @package custom-post-type-permalinks
 */

require dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/custom-post-type-permalinks.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

/**
 * Returns a random string that is never all digits.
 *
 * rand_str() returns hex characters, so it is all digits about 0.36% of the
 * time. An all-digit slug matches the page number groups in rewrite rules
 * (#129), and an all-digit post type or taxonomy name turns into an integer
 * array key. Either way, tests fail at random.
 *
 * @param int $length Length of the random part.
 *
 * @return string
 */
function cptp_rand_str( $length = 12 ) {
	return 'cptp' . rand_str( $length );
}
