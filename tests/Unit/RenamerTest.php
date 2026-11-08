<?php
/**
 * Renamer tests, including a full rename of a copy of this project.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SaizulAmin\PluginBoilerplate\Tooling\Renamer;

final class RenamerTest extends TestCase {

	private string $tmp = '';

	protected function setUp(): void {
		$this->tmp = sys_get_temp_dir() . '/wppbp-renamer-' . bin2hex( random_bytes( 4 ) );
		mkdir( $this->tmp, 0777, true );
	}

	protected function tearDown(): void {
		self::remove( $this->tmp );
	}

	private static function remove( string $path ): void {
		if ( is_file( $path ) || is_link( $path ) ) {
			unlink( $path );
			return;
		}
		if ( ! is_dir( $path ) ) {
			return;
		}
		foreach ( scandir( $path ) ?: array() as $entry ) {
			if ( '.' !== $entry && '..' !== $entry ) {
				self::remove( $path . '/' . $entry );
			}
		}
		rmdir( $path );
	}

	private static function copyTree( string $from, string $to ): void {
		mkdir( $to, 0777, true );
		foreach ( scandir( $from ) ?: array() as $entry ) {
			if ( in_array( $entry, array( '.', '..', '.git', 'vendor', '.phpunit.cache', 'dist' ), true ) ) {
				continue;
			}
			is_dir( "$from/$entry" ) ? self::copyTree( "$from/$entry", "$to/$entry" ) : copy( "$from/$entry", "$to/$entry" );
		}
	}

	private function values(): array {
		return array(
			'name'       => 'Acme Reviews',
			'slug'       => 'acme-reviews',
			'namespace'  => 'Acme\\Reviews',
			'prefix'     => 'acrv',
			'vendor'     => 'acme',
			'author'     => 'Jane Doe',
			'author_uri' => 'https://example.com',
		);
	}

	public function test_valid_values_pass_validation(): void {
		$this->assertSame( array(), Renamer::validate( $this->values() ) );
		$this->assertSame( array(), Renamer::validate( Renamer::SOURCE ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function invalidProvider(): array {
		return array(
			'slug uppercase'     => array( 'slug', 'Acme' ),
			'slug underscore'    => array( 'slug', 'acme_reviews' ),
			'slug double dash'   => array( 'slug', 'acme--reviews' ),
			'slug digit first'   => array( 'slug', '1acme' ),
			'namespace lower'    => array( 'namespace', 'acme\\reviews' ),
			'namespace slash'    => array( 'namespace', 'Acme/Reviews' ),
			'namespace trailing' => array( 'namespace', 'Acme\\' ),
			'prefix too short'   => array( 'prefix', 'a' ),
			'prefix too long'    => array( 'prefix', 'abcdefghijk' ),
			'prefix underscore'  => array( 'prefix', 'ac_rv' ),
			'name quote'         => array( 'name', "Acme' Reviews" ),
			'name comment end'   => array( 'name', 'Acme */ Reviews' ),
			'name empty'         => array( 'name', '' ),
			'author newline'     => array( 'author', "Jane\nDoe" ),
			'vendor uppercase'   => array( 'vendor', 'Acme' ),
			'uri javascript'     => array( 'author_uri', 'javascript:alert(1)' ),
			'uri missing'        => array( 'author_uri', 'example.com' ),
		);
	}

	#[DataProvider( 'invalidProvider' )]
	public function test_invalid_values_are_rejected( string $key, string $value ): void {
		$values         = $this->values();
		$values[ $key ] = $value;

		$this->assertNotSame( array(), Renamer::validate( $values ) );
	}

	public function test_constructor_throws_on_invalid_values(): void {
		$this->expectException( InvalidArgumentException::class );
		new Renamer( array( 'slug' => 'Bad Slug' ) + $this->values() );
	}

	public function test_derive_from_multi_word_name(): void {
		$values = Renamer::derive( 'My Awesome Plugin', array( 'author' => 'Jane Doe' ) );

		$this->assertSame( 'my-awesome-plugin', $values['slug'] );
		$this->assertSame( 'JaneDoe\\MyAwesomePlugin', $values['namespace'] );
		$this->assertSame( 'map', $values['prefix'] );
		$this->assertSame( 'jane-doe', $values['vendor'] );
		$this->assertSame( array(), Renamer::validate( $values ) );
	}

	public function test_derive_from_single_word_name(): void {
		$values = Renamer::derive( 'Reviews' );

		$this->assertSame( 'reviews', $values['slug'] );
		$this->assertSame( 'revi', $values['prefix'] );
		$this->assertSame( 'Vendor\\Reviews', $values['namespace'] );
	}

	public function test_derive_handles_leading_digit(): void {
		$values = Renamer::derive( '3D Gallery' );

		$this->assertSame( 'plugin-3d-gallery', $values['slug'] );
		$this->assertSame( 'Vendor\\Plugin3dGallery', $values['namespace'] );
		$this->assertSame( 'p3g', $values['prefix'] );
		$this->assertSame( array(), Renamer::validate( $values ) );
	}

	public function test_derive_overrides_win(): void {
		$values = Renamer::derive(
			'Acme Reviews',
			array(
				'prefix' => 'rev',
				'slug'   => 'reviews-pro',
			)
		);

		$this->assertSame( 'rev', $values['prefix'] );
		$this->assertSame( 'reviews-pro', $values['slug'] );
	}

	public function test_transform_replaces_namespace_in_php_and_json_forms(): void {
		$renamer = new Renamer( $this->values() );

		$this->assertSame( 'namespace Acme\\Reviews\\Admin;', $renamer->transform( 'namespace SaizulAmin\\PluginBoilerplate\\Admin;' ) );
		$this->assertSame( '"Acme\\\\Reviews\\\\": "src/"', $renamer->transform( '"SaizulAmin\\\\PluginBoilerplate\\\\": "src/"' ) );
		$this->assertSame( "'Acme\\\\Reviews\\\\'", $renamer->transform( "'SaizulAmin\\\\PluginBoilerplate\\\\'" ) );
	}

	public function test_transform_replaces_slug_name_prefix_and_package(): void {
		$renamer = new Renamer( $this->values() );
		$input   = "Plugin Name: WP Plugin Boilerplate Pro\nText Domain: wp-plugin-boilerplate-pro\n"
			. "define( 'WPPBP_VERSION' ); get_option( 'wppbp_settings' ); \"saizul/wp-plugin-boilerplate-pro\"";

		$out = $renamer->transform( $input );

		$this->assertStringContainsString( 'Plugin Name: Acme Reviews', $out );
		$this->assertStringContainsString( 'Text Domain: acme-reviews', $out );
		$this->assertStringContainsString( "'ACRV_VERSION'", $out );
		$this->assertStringContainsString( "'acrv_settings'", $out );
		$this->assertStringContainsString( '"acme/acme-reviews"', $out );
		$this->assertStringNotContainsString( 'wppbp', strtolower( $out ) );
	}

	public function test_transform_replaces_author(): void {
		$out = ( new Renamer( $this->values() ) )->transform( 'Author: Saizul Amin, https://saizul.com' );

		$this->assertSame( 'Author: Jane Doe, https://example.com', $out );
		$this->assertSame( 'Contributors: janedoe', ( new Renamer( $this->values() ) )->transform( 'Contributors: saizulamin' ) );
	}

	public function test_transform_is_a_noop_for_unrelated_text(): void {
		$this->assertSame( 'nothing here', ( new Renamer( $this->values() ) )->transform( 'nothing here' ) );
	}

	public function test_target_path_renames_main_file_and_pot(): void {
		$renamer = new Renamer( $this->values() );

		$this->assertSame( 'acme-reviews.php', $renamer->targetPath( 'wp-plugin-boilerplate-pro.php' ) );
		$this->assertSame( 'languages/acme-reviews.pot', $renamer->targetPath( 'languages/wp-plugin-boilerplate-pro.pot' ) );
		$this->assertSame( 'src/Plugin.php', $renamer->targetPath( 'src/Plugin.php' ) );
	}

	public function test_is_text_file(): void {
		$this->assertTrue( Renamer::isTextFile( 'src/Plugin.php' ) );
		$this->assertTrue( Renamer::isTextFile( '.gitignore' ) );
		$this->assertTrue( Renamer::isTextFile( 'readme.txt' ) );
		$this->assertFalse( Renamer::isTextFile( 'assets/logo.png' ) );
		$this->assertFalse( Renamer::isTextFile( 'bin/Renamer.php' ) );
		$this->assertFalse( Renamer::isTextFile( 'LICENSE' ) );
	}

	private function fixture(): void {
		mkdir( $this->tmp . '/src', 0777, true );
		mkdir( $this->tmp . '/vendor/x', 0777, true );
		mkdir( $this->tmp . '/bin', 0777, true );
		file_put_contents( $this->tmp . '/wp-plugin-boilerplate-pro.php', "<?php\n// Plugin Name: WP Plugin Boilerplate Pro\n" );
		file_put_contents( $this->tmp . '/src/Thing.php', "<?php\nnamespace SaizulAmin\\PluginBoilerplate;\n" );
		file_put_contents( $this->tmp . '/src/Untouched.php', "<?php\n// nothing\n" );
		file_put_contents( $this->tmp . '/vendor/x/Lib.php', "<?php // wppbp\n" );
		file_put_contents( $this->tmp . '/bin/Renamer.php', "<?php // wppbp\n" );
		file_put_contents( $this->tmp . '/logo.png', 'wppbp binary' );
	}

	public function test_plan_lists_only_files_that_change(): void {
		$this->fixture();
		$plan  = ( new Renamer( $this->values() ) )->plan( $this->tmp );
		$paths = array_column( $plan, 'from' );

		$this->assertSame( array( 'src/Thing.php', 'wp-plugin-boilerplate-pro.php' ), $paths );
	}

	public function test_dry_run_does_not_touch_files(): void {
		$this->fixture();
		( new Renamer( $this->values() ) )->apply( $this->tmp, true );

		$this->assertFileExists( $this->tmp . '/wp-plugin-boilerplate-pro.php' );
		$this->assertStringContainsString( 'SaizulAmin', (string) file_get_contents( $this->tmp . '/src/Thing.php' ) );
	}

	public function test_apply_rewrites_and_renames(): void {
		$this->fixture();
		( new Renamer( $this->values() ) )->apply( $this->tmp );

		$this->assertFileDoesNotExist( $this->tmp . '/wp-plugin-boilerplate-pro.php' );
		$this->assertStringContainsString( 'Acme Reviews', (string) file_get_contents( $this->tmp . '/acme-reviews.php' ) );
		$this->assertStringContainsString( 'namespace Acme\\Reviews;', (string) file_get_contents( $this->tmp . '/src/Thing.php' ) );
	}

	public function test_apply_skips_vendor_binaries_and_tooling(): void {
		$this->fixture();
		( new Renamer( $this->values() ) )->apply( $this->tmp );

		$this->assertStringContainsString( 'wppbp', (string) file_get_contents( $this->tmp . '/vendor/x/Lib.php' ) );
		$this->assertStringContainsString( 'wppbp', (string) file_get_contents( $this->tmp . '/bin/Renamer.php' ) );
		$this->assertSame( 'wppbp binary', file_get_contents( $this->tmp . '/logo.png' ) );
	}

	public function test_renaming_a_copy_of_the_whole_project_leaves_no_old_identifiers(): void {
		$project = dirname( __DIR__, 2 );
		$copy    = $this->tmp . '/copy';
		self::copyTree( $project, $copy );

		( new Renamer( $this->values() ) )->apply( $copy );

		$this->assertFileExists( $copy . '/acme-reviews.php' );
		$this->assertFileDoesNotExist( $copy . '/wp-plugin-boilerplate-pro.php' );
		$this->assertFileExists( $copy . '/languages/acme-reviews.pot' );

		$needles   = array( 'wp-plugin-boilerplate-pro', 'wppbp', 'WPPBP', 'PluginBoilerplate', 'WP Plugin Boilerplate Pro', 'Saizul' );
		$offenders = array();
		$iterator  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $copy, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file ) {
			$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $copy ) + 1 ) );
			if ( in_array( $relative, Renamer::SKIP_FILES, true ) || str_starts_with( $relative, '.github/' ) ) {
				continue;
			}
			$content = (string) file_get_contents( $file->getPathname() );
			foreach ( $needles as $needle ) {
				if ( false !== stripos( $content, $needle ) ) {
					$offenders[] = $relative . ' contains ' . $needle;
				}
			}
		}
		$this->assertSame( array(), $offenders );
	}

	public function test_renamed_php_files_still_lint(): void {
		$project = dirname( __DIR__, 2 );
		$copy    = $this->tmp . '/copy';
		self::copyTree( $project, $copy );
		( new Renamer( $this->values() ) )->apply( $copy );

		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $copy, \FilesystemIterator::SKIP_DOTS ) );
		$checked  = 0;
		foreach ( $iterator as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}
			exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $file->getPathname() ) . ' 2>&1', $output, $code );
			$this->assertSame( 0, $code, $file->getPathname() . ': ' . implode( "\n", $output ) );
			++$checked;
		}
		$this->assertGreaterThan( 20, $checked );
	}
}
