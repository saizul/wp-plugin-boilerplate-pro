<?php
/**
 * Settings schema: defaults and sanitisation, independent of WordPress.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Settings;

use SaizulAmin\PluginBoilerplate\Support\Sanitizer;

/**
 * Describes every setting once; the admin page, the repository and the tests all read from here.
 */
final class Schema {

	public const LAYOUTS = array( 'list', 'grid' );

	/**
	 * Field definitions keyed by option key.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		return array(
			'enabled'             => array(
				'type'    => 'bool',
				'default' => true,
			),
			'greeting'            => array(
				'type'    => 'text',
				'default' => 'Hello from the boilerplate',
			),
			'contact_email'       => array(
				'type'    => 'email',
				'default' => '',
			),
			'items_per_page'      => array(
				'type'    => 'int',
				'default' => 10,
				'min'     => 1,
				'max'     => 100,
			),
			'layout'              => array(
				'type'    => 'enum',
				'default' => 'list',
				'choices' => self::LAYOUTS,
			),
			'retention_days'      => array(
				'type'    => 'int',
				'default' => 30,
				'min'     => 1,
				'max'     => 365,
			),
			'delete_on_uninstall' => array(
				'type'    => 'bool',
				'default' => false,
			),
		);
	}

	/**
	 * Default value for every field.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array_map( static fn( array $field ) => $field['default'], self::fields() );
	}

	/**
	 * Sanitise submitted values. Unknown keys are dropped. A missing checkbox becomes false (browsers
	 * do not submit unchecked boxes); any other missing field falls back to its default.
	 *
	 * @param mixed $input Raw submitted data.
	 * @return array<string, mixed>
	 */
	public static function sanitize( mixed $input ): array {
		$input  = is_array( $input ) ? $input : array();
		$clean  = array();
		$fields = self::fields();

		foreach ( $fields as $key => $field ) {
			$raw = $input[ $key ] ?? null;

			$clean[ $key ] = match ( $field['type'] ) {
				'bool'  => Sanitizer::bool( $raw ),
				'email' => Sanitizer::email( $raw ),
				'int'   => Sanitizer::int( $raw, (int) $field['min'], (int) $field['max'], (int) $field['default'] ),
				'enum'  => Sanitizer::enum( $raw, $field['choices'], (string) $field['default'] ),
				default => null === $raw ? (string) $field['default'] : Sanitizer::text( $raw ),
			};
		}

		return $clean;
	}

	/**
	 * Strictly validate values without coercing them. Used for imports, where silently clamping or
	 * replacing a value would hide a mistake. Only the keys present in $input are checked, so a
	 * partial set of settings is valid.
	 *
	 * @param array<array-key, mixed> $input Values keyed by setting key.
	 * @return array<string, string> Error message keyed by setting key; empty when everything is valid.
	 */
	public static function validate( array $input ): array {
		$fields = self::fields();
		$errors = array();

		foreach ( $input as $key => $value ) {
			$key = (string) $key;

			if ( ! isset( $fields[ $key ] ) ) {
				$errors[ $key ] = 'Unknown setting.';
				continue;
			}

			$error = self::validate_field( $fields[ $key ], $value );
			if ( null !== $error ) {
				$errors[ $key ] = $error;
			}
		}

		return $errors;
	}

	/**
	 * Validate one value against its field definition.
	 *
	 * @param array<string, mixed> $field Field definition from fields().
	 * @param mixed                $value Value to check.
	 * @return string|null Error message, or null when the value is valid.
	 */
	private static function validate_field( array $field, mixed $value ): ?string {
		switch ( $field['type'] ) {
			case 'bool':
				return is_bool( $value ) ? null : 'Must be true or false.';

			case 'int':
				if ( ! is_int( $value ) ) {
					return 'Must be an integer.';
				}
				if ( $value < (int) $field['min'] || $value > (int) $field['max'] ) {
					return sprintf( 'Must be between %d and %d.', (int) $field['min'], (int) $field['max'] );
				}
				return null;

			case 'enum':
				return is_string( $value ) && in_array( $value, $field['choices'], true )
					? null
					: sprintf( 'Must be one of: %s.', implode( ', ', $field['choices'] ) );

			case 'email':
				if ( ! is_string( $value ) ) {
					return 'Must be a string.';
				}
				return '' === $value || Sanitizer::email( $value ) === $value
					? null
					: 'Must be a valid email address or an empty string.';

			default:
				return is_string( $value ) ? null : 'Must be a string.';
		}//end switch
	}
}
