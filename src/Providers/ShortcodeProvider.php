<?php
/**
 * Shortcode and block services.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Providers;

use SaizulAmin\PluginBoilerplate\Blocks\ItemsBlock;
use SaizulAmin\PluginBoilerplate\Container\Container;
use SaizulAmin\PluginBoilerplate\Shortcodes\ItemsShortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers shortcodes and the dynamic items block.
 */
class ShortcodeProvider implements ServiceProvider {

	/**
	 * Bind services.
	 *
	 * @param Container $container Container.
	 */
	public function register( Container $container ): void {
		$container->singleton( ItemsShortcode::class );
		$container->singleton( ItemsBlock::class );
	}

	/**
	 * Attach hooks.
	 *
	 * @param Container $container Container.
	 */
	public function boot( Container $container ): void {
		$container->get( ItemsShortcode::class )->hook();
		$container->get( ItemsBlock::class )->hook();
	}
}
