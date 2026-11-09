<?php
/**
 * Normalises the arguments shared by the items shortcode and the items block.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Support;

use SaizulAmin\PluginBoilerplate\Settings\Schema;

/**
 * Pure (no WordPress functions) so both entry points behave identically and the rules are unit-testable.
 */
final class ItemsArgs {

	public const MAX_COUNT = 100;

	/**
	 * Clamp and sanitise raw values. Empty or invalid values fall back to the given defaults.
	 *
	 * @param mixed $count           Raw item count.
	 * @param mixed $layout          Raw layout.
	 * @param mixed $search          Raw search term.
	 * @param mixed $default_count   Fallback count (usually the saved setting).
	 * @param mixed $default_layout  Fallback layout (usually the saved setting).
	 * @return array{count: int, layout: string, search: string}
	 */
	public static function normalize( mixed $count, mixed $layout, mixed $search, mixed $default_count = 10, mixed $default_layout = 'list' ): array {
		$fallback_count  = Sanitizer::int( $default_count, 1, self::MAX_COUNT, 10 );
		$fallback_layout = Sanitizer::enum( $default_layout, Schema::LAYOUTS, 'list' );

		return array(
			'count'  => Sanitizer::int( $count, 1, self::MAX_COUNT, $fallback_count ),
			'layout' => Sanitizer::enum( $layout, Schema::LAYOUTS, $fallback_layout ),
			'search' => Sanitizer::text( $search ),
		);
	}
}
