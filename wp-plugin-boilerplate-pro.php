<?php
/**
 * Plugin Name:       WP Plugin Boilerplate Pro
 * Plugin URI:        https://github.com/saizul/wp-plugin-boilerplate-pro
 * Description:       A modern WordPress plugin starter: PSR-4 autoloading, a small DI container, service providers, settings, REST API, shortcode, custom post type, WP-CLI and cron.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            Saizul Amin
 * Author URI:        https://saizul.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-plugin-boilerplate-pro
 * Domain Path:       /languages
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPPBP_VERSION', '1.0.0' );
define( 'WPPBP_FILE', __FILE__ );
define( 'WPPBP_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPPBP_URL', plugin_dir_url( __FILE__ ) );

// This file must stay compatible with old PHP versions so the notice below can render.
if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: current PHP version. */
						__( 'WP Plugin Boilerplate Pro requires PHP 8.1 or higher. You are running PHP %s.', 'wp-plugin-boilerplate-pro' ),
						PHP_VERSION
					)
				)
			);
		}
	);
	return;
}

// Prefer the Composer autoloader; fall back to a tiny PSR-4 loader when vendor/ is absent.
if ( is_readable( WPPBP_DIR . 'vendor/autoload.php' ) ) {
	require_once WPPBP_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix = 'SaizulAmin\\PluginBoilerplate\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}
			$relative = str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) );
			$file     = WPPBP_DIR . 'src/' . $relative . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

register_activation_hook( __FILE__, array( \SaizulAmin\PluginBoilerplate\Lifecycle::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \SaizulAmin\PluginBoilerplate\Lifecycle::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\SaizulAmin\PluginBoilerplate\Plugin::instance()->boot();
	}
);
