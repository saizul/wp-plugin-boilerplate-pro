#!/usr/bin/env php
<?php
/**
 * Turns this boilerplate into your own plugin.
 *
 * Example:
 *
 *     php bin/rename.php --name="Acme Reviews" [--slug=acme-reviews] [--namespace="Acme\Reviews"]
 *                        [--prefix=acrv] [--vendor=acme] [--author="Jane Doe"]
 *                        [--author-uri=https://example.com] [--dry-run] [--cleanup]
 *
 * Only --name is required; everything else is derived from it. Run `--dry-run` first.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require_once __DIR__ . '/Renamer.php';

use SaizulAmin\PluginBoilerplate\Tooling\Renamer;

/**
 * Parse --key=value and --flag arguments.
 *
 * @param array<int, string> $argv Raw arguments without the script name.
 * @return array<string, string|bool>
 */
function wppbp_parse_args( array $argv ): array {
	$out = array();
	foreach ( $argv as $arg ) {
		if ( 1 === preg_match( '/^--([a-z-]+)=(.*)$/s', $arg, $m ) ) {
			$out[ $m[1] ] = $m[2];
		} elseif ( 1 === preg_match( '/^--([a-z-]+)$/', $arg, $m ) ) {
			$out[ $m[1] ] = true;
		} else {
			fwrite( STDERR, "Unknown argument: {$arg}\n" );
			exit( 2 );
		}
	}
	return $out;
}

$args = wppbp_parse_args( array_slice( $argv, 1 ) );

if ( isset( $args['help'] ) || ! isset( $args['name'] ) || ! is_string( $args['name'] ) ) {
	fwrite( STDOUT, "Usage: php bin/rename.php --name=\"Acme Reviews\" [--slug=] [--namespace=] [--prefix=] [--vendor=] [--author=] [--author-uri=] [--dry-run] [--cleanup]\n" );
	exit( isset( $args['help'] ) ? 0 : 2 );
}

$overrides = array();
foreach ( array( 'slug', 'namespace', 'prefix', 'vendor', 'author' ) as $key ) {
	if ( isset( $args[ $key ] ) && is_string( $args[ $key ] ) ) {
		$overrides[ $key ] = $args[ $key ];
	}
}
if ( isset( $args['author-uri'] ) && is_string( $args['author-uri'] ) ) {
	$overrides['author_uri'] = $args['author-uri'];
}

$values   = Renamer::derive( $args['name'], $overrides );
$problems = Renamer::validate( $values );
if ( array() !== $problems ) {
	fwrite( STDERR, "Invalid values:\n  - " . implode( "\n  - ", $problems ) . "\n" );
	exit( 2 );
}

$renamer = new Renamer( $values );
$root    = dirname( __DIR__ );
$dry_run = isset( $args['dry-run'] );

echo "Renaming with:\n";
foreach ( $values as $key => $value ) {
	printf( "  %-10s %s\n", $key, $value );
}
echo "\n";

if ( Renamer::SOURCE['author'] === $values['author'] ) {
	echo "Note: --author was not given, so the author headers keep their original value.\n\n";
}

$plan = $renamer->apply( $root, $dry_run );
foreach ( $plan as $entry ) {
	printf(
		"%s %s%s\n",
		$dry_run ? 'would update' : 'updated',
		$entry['from'],
		$entry['to'] !== $entry['from'] ? ' -> ' . $entry['to'] : ''
	);
}
printf( "\n%d file(s) %s.\n", count( $plan ), $dry_run ? 'would change' : 'changed' );

if ( $dry_run ) {
	exit( 0 );
}

if ( isset( $args['cleanup'] ) ) {
	foreach ( Renamer::SKIP_FILES as $file ) {
		if ( 'LICENSE' !== $file && is_file( $root . '/' . $file ) ) {
			unlink( $root . '/' . $file );
			echo "removed {$file}\n";
		}
	}
}

echo "Next steps: rename this folder to '{$values['slug']}', run `composer dump-autoload`, regenerate the .pot file and update the README.\n";
