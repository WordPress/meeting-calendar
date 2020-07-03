<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Meeting_Calendar
 */

namespace WordPressdotorg\Meeting_Calendar\Tests;

if ( 'cli' !== php_sapi_name() ) {
	return;
}

/**
 * Manually load the plugin being tested.
 */
function manually_load_plugin() {
	require_once dirname( __FILE__ ) . '/../plugin.php';
	require_once dirname( __FILE__ ) . '/../includes/wporg-meeting-install.php';
}

tests_add_filter( 'muplugins_loaded', __NAMESPACE__ . '\manually_load_plugin' );
