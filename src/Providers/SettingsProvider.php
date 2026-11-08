<?php
/**
 * Settings services.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Providers;

use SaizulAmin\PluginBoilerplate\Admin\SettingsPage;
use SaizulAmin\PluginBoilerplate\Container\Container;
use SaizulAmin\PluginBoilerplate\Settings\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Binds the settings repository and shows the admin page in wp-admin only.
 */
class SettingsProvider implements ServiceProvider {

	/**
	 * Bind services.
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ): void {
		$container->singleton( Repository::class );
		$container->singleton(
			SettingsPage::class,
			static fn( Container $c ) => new SettingsPage(
				$c->get( Repository::class ),
				(string) $c->get( 'plugin.file' ),
				(string) $c->get( 'plugin.version' )
			)
		);
	}

	/**
	 * Attach hooks.
	 *
	 * @param Container $container Container.
	 */
	public function boot( Container $container ): void {
		if ( is_admin() ) {
			$container->get( SettingsPage::class )->hook();
		}
	}
}
