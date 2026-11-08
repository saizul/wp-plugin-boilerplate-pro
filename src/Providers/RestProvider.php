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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the REST controller.
 */
class RestProvider implements ServiceProvider {

	/**
	 * Bind services.
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ): void {
		$container->singleton( ItemsController::class );
	}

	/**
	 * Attach hooks.
	 *
	 * @param Container $container Container.
	 */
	public function boot( Container $container ): void {
		$container->get( ItemsController::class )->hook();
	}
}
