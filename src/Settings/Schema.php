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
}
