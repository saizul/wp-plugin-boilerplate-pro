<?php
/**
 * Plugin kernel.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate;

use SaizulAmin\PluginBoilerplate\Container\Container;
use SaizulAmin\PluginBoilerplate\Providers\CliProvider;
use SaizulAmin\PluginBoilerplate\Providers\CronProvider;
use SaizulAmin\PluginBoilerplate\Providers\I18nProvider;
use SaizulAmin\PluginBoilerplate\Providers\PostTypeProvider;
use SaizulAmin\PluginBoilerplate\Providers\RestProvider;
use SaizulAmin\PluginBoilerplate\Providers\ServiceProvider;
use SaizulAmin\PluginBoilerplate\Providers\SettingsProvider;
use SaizulAmin\PluginBoilerplate\Providers\ShortcodeProvider;

/**
 * Owns the container and the list of service providers.
 */
final class Plugin {

	/**
	 * Shared instance.
	 */
	private static ?Plugin $instance = null;

	/**
	 * Service container.
	 */
	private Container $container;

	/**
	 * Registered providers.
	 *
	 * @var array<int, ServiceProvider>
	 */
	private array $providers = array();

	/**
	 * Whether boot() already ran.
	 */
	private bool $booted = false;

	/**
	 * Create the plugin and register its providers. Prefer Plugin::instance().
	 *
	 * @param Container|null $container Optional container, mainly for tests.
	 */
	public function __construct( ?Container $container = null ) {
		$this->container = $container ?? new Container();
	}

	/**
	 * Shared instance for the main plugin file.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * The service container.
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Provider classes loaded by default. Add your own here.
	 *
	 * @return array<int, class-string<ServiceProvider>>
	 */
	public static function defaultProviders(): array {
		return array(
			I18nProvider::class,
			SettingsProvider::class,
			PostTypeProvider::class,
			RestProvider::class,
			ShortcodeProvider::class,
			CronProvider::class,
			CliProvider::class,
		);
	}

	/**
	 * Register then boot every provider. Safe to call more than once.
	 *
	 * @param array<int, class-string<ServiceProvider>>|null $providers Override the provider list.
	 */
	public function boot( ?array $providers = null ): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		$this->container->instance( self::class, $this );
		$this->container->instance( Container::class, $this->container );
		$this->container->instance( 'plugin.version', defined( 'WPPBP_VERSION' ) ? WPPBP_VERSION : '0.0.0' );
		$this->container->instance( 'plugin.file', defined( 'WPPBP_FILE' ) ? WPPBP_FILE : '' );
		$this->container->instance( 'plugin.url', defined( 'WPPBP_URL' ) ? WPPBP_URL : '' );

		foreach ( $providers ?? self::defaultProviders() as $provider_class ) {
			/**
			 * Provider instance.
			 *
			 * @var ServiceProvider $provider
			 */
			$provider = new $provider_class();
			$provider->register( $this->container );
			$this->providers[] = $provider;
		}

		foreach ( $this->providers as $provider ) {
			$provider->boot( $this->container );
		}
	}

	/**
	 * Registered provider instances (after boot).
	 *
	 * @return array<int, ServiceProvider>
	 */
	public function providers(): array {
		return $this->providers;
	}
}
