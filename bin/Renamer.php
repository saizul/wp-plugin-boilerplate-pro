<?php
/**
 * Renames the boilerplate identifiers. Pure PHP, no WordPress required.
 *
 * Used by bin/rename.php and by the unit tests. Delete it together with bin/rename.php
 * once you have renamed the project (`php bin/rename.php ... --cleanup` does that for you).
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tooling;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class Renamer {

	/**
	 * Identifiers currently used by the boilerplate.
	 */
	public const SOURCE = array(
		'name'       => 'WP Plugin Boilerplate Pro',
		'slug'       => 'wp-plugin-boilerplate-pro',
		'namespace'  => 'SaizulAmin\\PluginBoilerplate',
		'prefix'     => 'wppbp',
		'vendor'     => 'saizul',
		'author'     => 'Saizul Amin',
		'author_uri' => 'https://saizul.com',
	);

	/**
	 * Files that contain the source identifiers on purpose and must never be rewritten.
	 */
	public const SKIP_FILES = array(
		'bin/rename.php',
		'bin/Renamer.php',
		'tests/Unit/RenamerTest.php',
		'LICENSE',
	);

	/**
	 * Directories that are never scanned.
	 */
	public const SKIP_DIRS = array( '.git', 'vendor', 'node_modules', 'dist', 'build', '.idea', '.phpunit.cache' );

	/**
	 * File extensions (and bare names) treated as text.
	 */
	public const TEXT_EXTENSIONS = array( 'php', 'json', 'md', 'txt', 'xml', 'dist', 'yml', 'yaml', 'pot', 'po', 'sh', 'css', 'js', 'lock' );
	public const TEXT_NAMES      = array( '.gitignore', '.gitattributes', '.distignore', '.editorconfig' );

	/**
	 * Target identifiers.
	 *
	 * @var array<string, string>
	 */
	private array $target;

	/**
	 * Create a renamer.
	 *
	 * @param array<string, string> $target Values for name, slug, namespace, prefix, vendor, author, author_uri.
	 * @throws InvalidArgumentException When a value is invalid.
	 */
	public function __construct( array $target ) {
		$target = array_merge( self::SOURCE, $target );
		$errors = self::validate( $target );
		if ( array() !== $errors ) {
			throw new InvalidArgumentException( implode( "\n", $errors ) );
		}
		$this->target = $target;
	}

	/**
	 * Validate a full set of identifiers.
	 *
	 * @param array<string, string> $values Identifiers.
	 * @return array<int, string> Error messages, empty when valid.
	 */
	public static function validate( array $values ): array {
		$errors = array();

		$text = '/^[\p{L}\p{N}][\p{L}\p{N} _.:&-]*$/u';
		foreach ( array( 'name', 'author' ) as $key ) {
			$value = $values[ $key ] ?? '';
			if ( 1 !== preg_match( $text, $value ) || strlen( $value ) > 80 ) {
				$errors[] = sprintf( '%s must be 1-80 characters: letters, numbers, spaces and _ . : & - only.', $key );
			}
		}

		if ( 1 !== preg_match( '/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $values['slug'] ?? '' ) ) {
			$errors[] = 'slug must be lowercase letters, numbers and single dashes, starting with a letter (for example my-plugin).';
		}

		$segment = '[A-Z][A-Za-z0-9_]*';
		if ( 1 !== preg_match( '/^' . $segment . '(\\\\' . $segment . ')*$/', $values['namespace'] ?? '' ) ) {
			$errors[] = 'namespace must be StudlyCase segments separated by a backslash (for example Acme\\MyPlugin).';
		}

		if ( 1 !== preg_match( '/^[a-z][a-z0-9]{1,9}$/', $values['prefix'] ?? '' ) ) {
			$errors[] = 'prefix must be 2-10 lowercase letters or numbers, starting with a letter (for example mypl).';
		}

		if ( 1 !== preg_match( '/^[a-z0-9][a-z0-9_.-]*$/', $values['vendor'] ?? '' ) ) {
			$errors[] = 'vendor must be lowercase letters, numbers, dots, dashes or underscores (for example acme).';
		}

		$uri = $values['author_uri'] ?? '';
		if ( false === filter_var( $uri, FILTER_VALIDATE_URL ) || ! in_array( parse_url( $uri, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
			$errors[] = 'author_uri must be an http(s) URL.';
		}

		return $errors;
	}

	/**
	 * Fill in the identifiers that were not given, based on the plugin name.
	 *
	 * @param string                $name      Human readable plugin name.
	 * @param array<string, string> $overrides Explicit values that win over derived ones.
	 * @return array<string, string>
	 */
	public static function derive( string $name, array $overrides = array() ): array {
		$name  = trim( $name );
		$words = preg_split( '/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY );
		$words = false === $words ? array() : $words;

		$slug = strtolower( implode( '-', $words ) );
		if ( '' !== $slug && ctype_digit( $slug[0] ) ) {
			$slug = 'plugin-' . $slug;
		}

		$author = $overrides['author'] ?? self::SOURCE['author'];
		$vendor = $overrides['vendor'] ?? ( isset( $overrides['author'] ) ? self::slugify( $author ) : 'vendor' );

		$studly_name = implode( '', array_map( 'ucfirst', array_map( 'strtolower', $words ) ) );
		if ( '' === $studly_name || ctype_digit( $studly_name[0] ) ) {
			$studly_name = 'Plugin' . $studly_name;
		}
		$studly_vendor = implode( '', array_map( 'ucfirst', preg_split( '/[^A-Za-z0-9]+/', $vendor, -1, PREG_SPLIT_NO_EMPTY ) ?: array( 'Vendor' ) ) );
		if ( ctype_digit( $studly_vendor[0] ) ) {
			$studly_vendor = 'V' . $studly_vendor;
		}

		if ( count( $words ) > 1 ) {
			$prefix = strtolower( implode( '', array_map( static fn( string $w ): string => $w[0], array_slice( $words, 0, 5 ) ) ) );
		} else {
			$prefix = strtolower( substr( $words[0] ?? 'plug', 0, 4 ) );
		}
		if ( strlen( $prefix ) < 2 ) {
			$prefix .= 'pl';
		}
		if ( ctype_digit( $prefix[0] ) ) {
			$prefix = 'p' . $prefix;
		}

		return array_merge(
			array(
				'name'       => $name,
				'slug'       => $slug,
				'namespace'  => $studly_vendor . '\\' . $studly_name,
				'prefix'     => $prefix,
				'vendor'     => $vendor,
				'author'     => $author,
				'author_uri' => self::SOURCE['author_uri'],
			),
			$overrides
		);
	}

	/**
	 * Lowercase dash-separated identifier.
	 *
	 * @param string $value Input.
	 */
	public static function slugify( string $value ): string {
		return trim( strtolower( (string) preg_replace( '/[^A-Za-z0-9]+/', '-', $value ) ), '-' );
	}

	/**
	 * Search/replace pairs, applied in one pass (longest match first).
	 *
	 * @return array<string, string>
	 */
	public function replacements(): array {
		$source = self::SOURCE;
		$target = $this->target;

		return array(
			// Namespace as written in PHP and as escaped in JSON / single-quoted PHP strings.
			$source['namespace']                      => $target['namespace'],
			str_replace( '\\', '\\\\', $source['namespace'] ) => str_replace( '\\', '\\\\', $target['namespace'] ),
			$source['vendor'] . '/' . $source['slug'] => $target['vendor'] . '/' . $target['slug'],
			$source['name']                           => $target['name'],
			$source['slug']                           => $target['slug'],
			strtoupper( $source['prefix'] )           => strtoupper( $target['prefix'] ),
			$source['prefix']                         => $target['prefix'],
			$source['author']                         => $target['author'],
			// WordPress.org username style handle used in readme.txt (Contributors).
			'saizulamin'                              => str_replace( '-', '', self::slugify( $target['author'] ) ),
			$source['author_uri']                     => $target['author_uri'],
		);
	}

	/**
	 * Apply the replacements to a string.
	 *
	 * @param string $content Text.
	 */
	public function transform( string $content ): string {
		return strtr( $content, $this->replacements() );
	}

	/**
	 * Relative path after renaming (only the file name changes, for example the main plugin file).
	 *
	 * @param string $relative Path relative to the project root, forward slashes.
	 */
	public function targetPath( string $relative ): string {
		return strtr( $relative, array( self::SOURCE['slug'] => $this->target['slug'] ) );
	}

	/**
	 * Whether a relative path is a candidate for rewriting.
	 *
	 * @param string $relative Path relative to the project root.
	 */
	public static function isTextFile( string $relative ): bool {
		if ( in_array( $relative, self::SKIP_FILES, true ) ) {
			return false;
		}
		$base = basename( $relative );
		if ( in_array( $base, self::TEXT_NAMES, true ) ) {
			return true;
		}
		return in_array( strtolower( pathinfo( $base, PATHINFO_EXTENSION ) ), self::TEXT_EXTENSIONS, true );
	}

	/**
	 * Work out what would change.
	 *
	 * @param string $root Project root.
	 * @return array<int, array{from: string, to: string, changed: bool}>
	 */
	public function plan( string $root ): array {
		$root = rtrim( $root, '/\\' );
		$plan = array();

		foreach ( $this->files( $root ) as $relative ) {
			if ( ! self::isTextFile( $relative ) ) {
				continue;
			}
			$content = (string) file_get_contents( $root . '/' . $relative );
			$to      = $this->targetPath( $relative );
			$changed = $this->transform( $content ) !== $content;

			if ( $changed || $to !== $relative ) {
				$plan[] = array(
					'from'    => $relative,
					'to'      => $to,
					'changed' => $changed,
				);
			}
		}

		return $plan;
	}

	/**
	 * Rewrite files and rename the ones whose name contains the slug.
	 *
	 * @param string $root    Project root.
	 * @param bool   $dry_run Only compute the plan.
	 * @return array<int, array{from: string, to: string, changed: bool}> The plan that was (or would be) applied.
	 */
	public function apply( string $root, bool $dry_run = false ): array {
		$root = rtrim( $root, '/\\' );
		$plan = $this->plan( $root );

		if ( $dry_run ) {
			return $plan;
		}

		foreach ( $plan as $entry ) {
			$path = $root . '/' . $entry['from'];
			if ( $entry['changed'] ) {
				file_put_contents( $path, $this->transform( (string) file_get_contents( $path ) ) );
			}
			if ( $entry['to'] !== $entry['from'] ) {
				rename( $path, $root . '/' . $entry['to'] );
			}
		}

		return $plan;
	}

	/**
	 * List files below the root, skipping ignored directories.
	 *
	 * @param string $root Project root.
	 * @return array<int, string> Sorted relative paths with forward slashes.
	 */
	private function files( string $root ): array {
		$found    = array();
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		/** @var SplFileInfo $file */
		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
			$parts    = explode( '/', $relative );
			array_pop( $parts );
			if ( array() !== array_intersect( $parts, self::SKIP_DIRS ) ) {
				continue;
			}
			$found[] = $relative;
		}

		sort( $found );
		return $found;
	}
}
