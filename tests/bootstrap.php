<?php
/**
 * PHPUnit bootstrap. Runs without WordPress: only pure classes are tested.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

$wppbp_root = dirname( __DIR__ );

if ( is_readable( $wppbp_root . '/vendor/autoload.php' ) ) {
	require_once $wppbp_root . '/vendor/autoload.php';
} else {
	// Allows `phpunit.phar` to run without `composer install`.
	spl_autoload_register(
		static function ( string $class_name ) use ( $wppbp_root ): void {
			$prefix = 'SaizulAmin\\PluginBoilerplate\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}
			$file = $wppbp_root . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

// The renamer is development tooling and is removed by `bin/rename.php --cleanup`.
if ( is_readable( $wppbp_root . '/bin/Renamer.php' ) ) {
	require_once $wppbp_root . '/bin/Renamer.php';
}
require_once __DIR__ . '/Unit/Fixtures/Fixtures.php';
