<?php
/**
 * Pure sanitising helpers (no WordPress functions, so they are unit-testable).
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Support;

/**
 * Small, predictable sanitisers. Each one accepts any input and always returns the declared type.
 * Sanitise on input, escape on output: these do not replace esc_html(), esc_attr() or esc_url().
 */
final class Sanitizer {

	/**
	 * Single-line plain text: tags removed, control characters removed, whitespace collapsed.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function text( mixed $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$text = strip_tags( (string) $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Pure helper without WordPress dependency.
		$text = (string) preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $text );
		$text = (string) preg_replace( '/\s+/u', ' ', $text );
		return trim( $text );
	}

	/**
	 * Email address, or an empty string when invalid.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function email( mixed $value ): string {
		$email = self::text( $value );
		$valid = filter_var( $email, FILTER_VALIDATE_EMAIL );
		return false === $valid ? '' : (string) $valid;
	}

	/**
	 * URL limited to the allowed schemes, or an empty string.
	 *
	 * @param mixed              $value   Raw value.
	 * @param array<int, string> $schemes Allowed schemes.
	 */
	public static function url( mixed $value, array $schemes = array( 'http', 'https' ) ): string {
		$url = self::text( $value );
		if ( '' === $url || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}
		$scheme = strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure helper without WordPress dependency.
		return in_array( $scheme, $schemes, true ) ? $url : '';
	}

	/**
	 * Boolean from common truthy strings and numbers.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function bool( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) || is_float( $value ) ) {
			return 0 !== (int) $value;
		}
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'on', 'yes' ), true );
		}
		return false;
	}

	/**
	 * Integer clamped to a range. Non-numeric input yields $default (also clamped).
	 *
	 * @param mixed $value   Raw value.
	 * @param int   $min     Minimum.
	 * @param int   $max     Maximum.
	 * @param int   $default Fallback.
	 */
	public static function int( mixed $value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX, int $default = 0 ): int {
		$number = is_numeric( $value ) ? (int) $value : $default;
		return max( $min, min( $max, $number ) );
	}

	/**
	 * One of a fixed list of choices.
	 *
	 * @param mixed              $value   Raw value.
	 * @param array<int, string> $allowed Allowed values.
	 * @param string             $default Fallback.
	 */
	public static function enum( mixed $value, array $allowed, string $default ): string {
		$candidate = is_scalar( $value ) ? (string) $value : '';
		return in_array( $candidate, $allowed, true ) ? $candidate : $default;
	}

	/**
	 * Lowercase slug: a-z, 0-9 and dashes.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function slug( mixed $value ): string {
		$slug = strtolower( self::text( $value ) );
		$slug = (string) preg_replace( '/[^a-z0-9]+/', '-', $slug );
		return trim( $slug, '-' );
	}
}
