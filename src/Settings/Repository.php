<?php
/**
 * Reads and writes the plugin option.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper around get_option() that always returns a complete, sanitised settings array.
 */
class Repository {

	public const OPTION = 'wppbp_settings';
	public const GROUP  = 'wppbp_settings_group';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return Schema::defaults();
	}

	/**
	 * All settings merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION, array() );
		return Schema::sanitize( array_merge( Schema::defaults(), is_array( $stored ) ? $stored : array() ) );
	}

	/**
	 * One setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Returned when the key is unknown.
	 * @return mixed
	 */
	public function get( string $key, mixed $default = null ): mixed {
		$all = $this->all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Restore defaults.
	 */
	public function reset(): void {
		update_option( self::OPTION, Schema::defaults() );
	}
}
