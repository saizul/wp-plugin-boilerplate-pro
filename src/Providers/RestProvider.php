<?php
/**
 * REST services.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Providers;

use SaizulAmin\PluginBoilerplate\Container\Container;
use SaizulAmin\PluginBoilerplate\Rest\ItemsController;
use SaizulAmin\PluginBoilerplate\Rest\SettingsController;
use SaizulAmin\PluginBoilerplate\Settings\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the REST controllers.
 */
class RestProvider implements ServiceProvider {

	/**
	 * Bind services.
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ): void {
		$container->singleton( ItemsController::class );
		$container->singleton(
			SettingsController::class,
			static fn( Container $c ) => new SettingsController(
				$c->get( Repository::class ),
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
		$container->get( ItemsController::class )->hook();
		$container->get( SettingsController::class )->hook();
	}
}
