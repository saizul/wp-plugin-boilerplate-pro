<?php
/**
 * WP-CLI services.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Providers;

use SaizulAmin\PluginBoilerplate\Cli\ItemsCommand;
use SaizulAmin\PluginBoilerplate\Container\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `wp wppbp ...` when running under WP-CLI.
 */
class CliProvider implements ServiceProvider {

	/**
	 * Bind services.
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ): void {
		$container->singleton( ItemsCommand::class );
	}

	/**
	 * Register the command.
	 *
	 * @param Container $container Container.
	 */
	public function boot( Container $container ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'wppbp', $container->get( ItemsCommand::class ) );
		}
	}
}
