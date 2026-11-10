<?php
/**
 * PHPUnit bootstrap for the integration suite. Runs inside the wp-env `tests-cli`
 * container, where the WordPress test library lives in WP_TESTS_DIR.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

$wppbp_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $wppbp_tests_dir ) {
	$wppbp_tests_dir = '/wordpress-phpunit';
}

if ( ! is_readable( $wppbp_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test library not found in {$wppbp_tests_dir}. Run the suite through wp-env (see CONTRIBUTING.md).\n" );
	exit( 1 );
}

// The WordPress test library needs the Yoast polyfills to run on PHPUnit 9.
if ( ! getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	putenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH=' . __DIR__ . '/vendor/yoast/phpunit-polyfills' );
}

require_once $wppbp_tests_dir . '/includes/functions.php';

/**
 * Load the plugin before WordPress finishes booting. The main file is found by its
 * "Plugin Name" header so this keeps working after the project is renamed.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		$root = dirname( __DIR__, 2 );
		foreach ( (array) glob( $root . '/*.php' ) as $file ) {
			if ( preg_match( '/^\s*\*\s*Plugin Name:/mi', (string) file_get_contents( $file, false, null, 0, 2048 ) ) ) {
				require_once $file;
				return;
			}
		}
		throw new RuntimeException( 'Main plugin file not found.' );
	}
);

require $wppbp_tests_dir . '/includes/bootstrap.php';
