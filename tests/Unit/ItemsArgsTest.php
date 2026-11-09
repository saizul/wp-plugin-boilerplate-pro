<?php
/**
 * ItemsArgs tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SaizulAmin\PluginBoilerplate\Support\ItemsArgs;

final class ItemsArgsTest extends TestCase {

	/**
	 * @return array<string, array{0: array<int, mixed>, 1: array{count: int, layout: string, search: string}}>
	 */
	public static function normalizeProvider(): array {
		return array(
			'valid values'                   => array(
				array( '5', 'grid', 'guide' ),
				array(
					'count'  => 5,
					'layout' => 'grid',
					'search' => 'guide',
				),
			),
			'count is clamped high'          => array(
				array( 5000, 'list', '' ),
				array(
					'count'  => 100,
					'layout' => 'list',
					'search' => '',
				),
			),
			'count is clamped low'           => array(
				array( -3, 'list', '' ),
				array(
					'count'  => 1,
					'layout' => 'list',
					'search' => '',
				),
			),
			'missing falls back to defaults' => array(
				array( null, null, null, 12, 'grid' ),
				array(
					'count'  => 12,
					'layout' => 'grid',
					'search' => '',
				),
			),
			'invalid layout uses default'    => array(
				array( 3, 'table', '', 10, 'grid' ),
				array(
					'count'  => 3,
					'layout' => 'grid',
					'search' => '',
				),
			),
			'broken defaults are repaired'   => array(
				array( null, null, '', 'many', 'masonry' ),
				array(
					'count'  => 10,
					'layout' => 'list',
					'search' => '',
				),
			),
			'search is sanitised'            => array(
				array( 1, 'list', '  <b>tea</b>   time ' ),
				array(
					'count'  => 1,
					'layout' => 'list',
					'search' => 'tea time',
				),
			),
			'array search rejected'          => array(
				array( 1, 'list', array( 'x' ) ),
				array(
					'count'  => 1,
					'layout' => 'list',
					'search' => '',
				),
			),
		);
	}

	/**
	 * @param array<int, mixed>                                  $input    Arguments for normalize().
	 * @param array{count: int, layout: string, search: string} $expected Expected result.
	 */
	#[DataProvider( 'normalizeProvider' )]
	public function test_normalize( array $input, array $expected ): void {
		$this->assertSame( $expected, ItemsArgs::normalize( ...$input ) );
	}

	public function test_block_metadata_matches_the_server_side_rules(): void {
		$meta = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/blocks/items/block.json' ), true );

		$this->assertIsArray( $meta );
		$this->assertSame( 'wp-plugin-boilerplate-pro/items', $meta['name'] );
		$this->assertSame( \SaizulAmin\PluginBoilerplate\Settings\Schema::LAYOUTS, $meta['attributes']['layout']['enum'] );
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/blocks/items/editor.js' );
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/blocks/items/editor.asset.php' );
	}
}
