<?php
/**
 * Sanitizer tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SaizulAmin\PluginBoilerplate\Support\Sanitizer;

final class SanitizerTest extends TestCase {

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function textProvider(): array {
		return array(
			'plain'              => array( 'Hello', 'Hello' ),
			'trims'              => array( "  Hello  \n", 'Hello' ),
			'strips tags'        => array( '<b>Bold</b> <script>alert(1)</script>text', 'Bold alert(1)text' ),
			'collapses spaces'   => array( "a   b\t\tc\nd", 'a b c d' ),
			'control characters' => array( "a\x01b\x07c", 'a b c' ),
			'int becomes string' => array( 42, '42' ),
			'array is rejected'  => array( array( 'x' ), '' ),
			'null is rejected'   => array( null, '' ),
			'unicode is kept'    => array( 'কফি  ☕', 'কফি ☕' ),
		);
	}

	#[DataProvider( 'textProvider' )]
	public function test_text( mixed $input, string $expected ): void {
		$this->assertSame( $expected, Sanitizer::text( $input ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function emailProvider(): array {
		return array(
			'valid'       => array( 'jane@example.com', 'jane@example.com' ),
			'trimmed'     => array( '  jane@example.com ', 'jane@example.com' ),
			'invalid'     => array( 'not-an-email', '' ),
			'missing tld' => array( 'jane@', '' ),
			'with tags'   => array( '<b>jane@example.com</b>', 'jane@example.com' ),
			'array'       => array( array(), '' ),
		);
	}

	#[DataProvider( 'emailProvider' )]
	public function test_email( mixed $input, string $expected ): void {
		$this->assertSame( $expected, Sanitizer::email( $input ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function urlProvider(): array {
		return array(
			'https'           => array( 'https://example.com/a?b=1', 'https://example.com/a?b=1' ),
			'http'            => array( 'http://example.com', 'http://example.com' ),
			'javascript'      => array( 'javascript:alert(1)', '' ),
			'data uri'        => array( 'data:text/html;base64,AAAA', '' ),
			'ftp not allowed' => array( 'ftp://example.com/file', '' ),
			'relative'        => array( '/relative/path', '' ),
			'empty'           => array( '', '' ),
			'not scalar'      => array( null, '' ),
		);
	}

	#[DataProvider( 'urlProvider' )]
	public function test_url( mixed $input, string $expected ): void {
		$this->assertSame( $expected, Sanitizer::url( $input ) );
	}

	public function test_url_custom_schemes(): void {
		$this->assertSame( 'ftp://example.com/f', Sanitizer::url( 'ftp://example.com/f', array( 'ftp' ) ) );
		$this->assertSame( '', Sanitizer::url( 'https://example.com', array( 'ftp' ) ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: bool}>
	 */
	public static function boolProvider(): array {
		return array(
			'true'        => array( true, true ),
			'false'       => array( false, false ),
			'one'         => array( 1, true ),
			'zero'        => array( 0, false ),
			'string one'  => array( '1', true ),
			'string zero' => array( '0', false ),
			'on'          => array( 'on', true ),
			'yes upper'   => array( 'YES', true ),
			'true string' => array( ' true ', true ),
			'off'         => array( 'off', false ),
			'empty'       => array( '', false ),
			'null'        => array( null, false ),
			'array'       => array( array( 1 ), false ),
		);
	}

	#[DataProvider( 'boolProvider' )]
	public function test_bool( mixed $input, bool $expected ): void {
		$this->assertSame( $expected, Sanitizer::bool( $input ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: int}>
	 */
	public static function intProvider(): array {
		return array(
			'in range'     => array( '10', 10 ),
			'below min'    => array( -5, 1 ),
			'above max'    => array( 500, 100 ),
			'float string' => array( '12.9', 12 ),
			'not numeric'  => array( 'abc', 7 ),
			'null'         => array( null, 7 ),
			'array'        => array( array( 3 ), 7 ),
		);
	}

	#[DataProvider( 'intProvider' )]
	public function test_int( mixed $input, int $expected ): void {
		$this->assertSame( $expected, Sanitizer::int( $input, 1, 100, 7 ) );
	}

	public function test_int_clamps_the_default_too(): void {
		$this->assertSame( 10, Sanitizer::int( 'x', 10, 20, 0 ) );
	}

	public function test_enum(): void {
		$this->assertSame( 'grid', Sanitizer::enum( 'grid', array( 'list', 'grid' ), 'list' ) );
		$this->assertSame( 'list', Sanitizer::enum( 'table', array( 'list', 'grid' ), 'list' ) );
		$this->assertSame( 'list', Sanitizer::enum( null, array( 'list', 'grid' ), 'list' ) );
		$this->assertSame( 'list', Sanitizer::enum( 'GRID', array( 'list', 'grid' ), 'list' ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function slugProvider(): array {
		return array(
			'simple'       => array( 'Hello World', 'hello-world' ),
			'symbols'      => array( '  Foo__Bar!! ', 'foo-bar' ),
			'already'      => array( 'my-slug-2', 'my-slug-2' ),
			'tags'         => array( '<i>Italic</i> Name', 'italic-name' ),
			'only symbols' => array( '***', '' ),
		);
	}

	#[DataProvider( 'slugProvider' )]
	public function test_slug( mixed $input, string $expected ): void {
		$this->assertSame( $expected, Sanitizer::slug( $input ) );
	}
}
