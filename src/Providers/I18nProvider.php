<?php
/**
 * Translations.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Providers;

use SaizulAmin\PluginBoilerplate\Container\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the text domain on init (WordPress 6.7+ warns about earlier loading).
 */
class I18nProvider implements ServiceProvider {

	/**
	 * No services to bind.
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ): void {}

	/**
	 * Hook the loader.
	 *
	 * @param Container $container Container.
	 */
	public function boot( Container $container ): void {
		$file = (string) $container->get( 'plugin.file' );

		add_action(
			'init',
			static function () use ( $file ): void {
				load_plugin_textdomain(
					'wp-plugin-boilerplate-pro',
					false,
					dirname( plugin_basename( $file ) ) . '/languages'
				);
			}
		);
	}
}
