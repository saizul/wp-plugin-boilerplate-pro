<?php
/**
 * Service provider contract.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Providers;

use SaizulAmin\PluginBoilerplate\Container\Container;

/**
 * Providers group related services. register() only binds services; boot() attaches hooks.
 * Every provider is registered before any provider is booted, so boot() may rely on all bindings.
 */
interface ServiceProvider {

	/**
	 * Bind services into the container. Do not call WordPress hooks that depend on other services here.
	 *
	 * @param Container $container The plugin container.
	 */
	public function register( Container $container ): void;

	/**
	 * Add actions, filters and shortcodes.
	 *
	 * @param Container $container The plugin container.
	 */
	public function boot( Container $container ): void;
}
