<?php
/**
 * Cron services.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Providers;

use SaizulAmin\PluginBoilerplate\Container\Container;
use SaizulAmin\PluginBoilerplate\Cron\CleanupTask;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the cleanup event handler.
 */
class CronProvider implements ServiceProvider {

	/**
	 * Bind services.
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ): void {
		$container->singleton( CleanupTask::class );
	}

	/**
	 * Attach hooks.
	 *
	 * @param Container $container Container.
	 */
	public function boot( Container $container ): void {
		$container->get( CleanupTask::class )->hook();
	}
}
