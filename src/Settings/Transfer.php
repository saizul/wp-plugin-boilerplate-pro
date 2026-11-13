<?php
/**
 * Settings export and import, independent of WordPress.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Settings;

use JsonException;

/**
 * Builds a versioned export envelope and validates imports against the settings schema.
 *
 * An export looks like this:
 *
 *     {
 *         "format": "wppbp-settings",
 *         "format_version": 1,
 *         "plugin_version": "1.0.0",
 *         "settings": { "enabled": true, "layout": "grid", ... }
 *     }
 *
 * Imports accept either that envelope or a bare object of settings. Values are validated strictly
 * (no coercion, unknown keys rejected); keys that are left out keep their current value. Either the
 * whole import is valid or nothing changes.
 */
final class Transfer {

	public const FORMAT         = 'wppbp-settings';
	public const FORMAT_VERSION = 1;

	/**
	 * Largest JSON document accepted by fromJson(), in bytes.
	 */
	public const MAX_BYTES = 65536;

	/**
	 * Build the export envelope.
	 *
	 * @param array<string, mixed> $settings       Current settings.
	 * @param string               $plugin_version Version of the plugin that made the export.
	 * @return array{format: string, format_version: int, plugin_version: string, settings: array<string, mixed>}
	 */
	public static function export( array $settings, string $plugin_version ): array {
		return array(
			'format'         => self::FORMAT,
			'format_version' => self::FORMAT_VERSION,
			'plugin_version' => $plugin_version,
			'settings'       => Schema::sanitize( array_merge( Schema::defaults(), $settings ) ),
		);
	}

	/**
	 * Decode a JSON document and import it.
	 *
	 * @param string               $json    JSON text.
	 * @param array<string, mixed> $current Current settings.
	 */
	public static function fromJson( string $json, array $current ): ImportResult {
		$current = self::complete( $current );

		if ( strlen( $json ) > self::MAX_BYTES ) {
			return new ImportResult( $current, array(), array( 'json' => sprintf( 'The document is larger than %d bytes.', self::MAX_BYTES ) ) );
		}

		try {
			$payload = json_decode( $json, true, 16, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			return new ImportResult( $current, array(), array( 'json' => 'Invalid JSON: ' . $e->getMessage() . '.' ) );
		}

		return self::import( $payload, $current );
	}

	/**
	 * Validate a decoded payload and merge it over the current settings.
	 *
	 * @param mixed                $payload Envelope or bare settings object.
	 * @param array<string, mixed> $current Current settings.
	 */
	public static function import( mixed $payload, array $current ): ImportResult {
		$current = self::complete( $current );

		if ( ! self::is_object_like( $payload ) ) {
			return new ImportResult( $current, array(), array( 'settings' => 'Expected a JSON object of settings.' ) );
		}

		$errors = array();
		$values = $payload;

		if ( array_key_exists( 'format', $payload ) ) {
			$errors = self::validate_envelope( $payload );
			$values = $payload['settings'] ?? array();
		}

		if ( array() === $errors ) {
			$errors = Schema::validate( $values );
		}

		if ( array() !== $errors ) {
			return new ImportResult( $current, array(), $errors );
		}

		$settings = Schema::sanitize( array_merge( $current, $values ) );
		$changes  = array();

		foreach ( $settings as $key => $value ) {
			if ( $current[ $key ] !== $value ) {
				$changes[ $key ] = array(
					'from' => $current[ $key ],
					'to'   => $value,
				);
			}
		}

		return new ImportResult( $settings, $changes );
	}

	/**
	 * Check the envelope fields.
	 *
	 * @param array<array-key, mixed> $payload Decoded envelope.
	 * @return array<string, string>
	 */
	private static function validate_envelope( array $payload ): array {
		if ( self::FORMAT !== $payload['format'] ) {
			return array( 'format' => sprintf( 'Expected "%s"; this is not a settings export from this plugin.', self::FORMAT ) );
		}

		$version = $payload['format_version'] ?? null;
		if ( ! is_int( $version ) || $version < 1 ) {
			return array( 'format_version' => 'Must be a positive integer.' );
		}
		if ( $version > self::FORMAT_VERSION ) {
			return array( 'format_version' => sprintf( 'Version %d is newer than the supported version %d. Update the plugin first.', $version, self::FORMAT_VERSION ) );
		}

		if ( ! self::is_object_like( $payload['settings'] ?? null ) ) {
			return array( 'settings' => 'Expected a JSON object of settings.' );
		}

		return array();
	}

	/**
	 * An associative array (a decoded JSON object). An empty array counts, because json_decode()
	 * turns `{}` into one.
	 *
	 * @param mixed $value Value.
	 */
	private static function is_object_like( mixed $value ): bool {
		return is_array( $value ) && ( array() === $value || ! array_is_list( $value ) );
	}

	/**
	 * Current settings with every key present and sanitised, so the merge and diff are reliable.
	 *
	 * @param array<string, mixed> $current Current settings.
	 * @return array<string, mixed>
	 */
	private static function complete( array $current ): array {
		return Schema::sanitize( array_merge( Schema::defaults(), $current ) );
	}
}
