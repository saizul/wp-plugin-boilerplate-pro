<?php
/**
 * Settings schema tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SaizulAmin\PluginBoilerplate\Settings\Schema;

final class SchemaTest extends TestCase {

	public function test_defaults_cover_every_field(): void {
		$this->assertSame( array_keys( Schema::fields() ), array_keys( Schema::defaults() ) );
	}

	public function test_sanitize_of_defaults_is_stable(): void {
		$defaults = Schema::defaults();

		$this->assertSame( $defaults, Schema::sanitize( $defaults ) );
	}

	public function test_non_array_input_gives_safe_values(): void {
		$clean = Schema::sanitize( 'garbage' );

		$this->assertFalse( $clean['enabled'] );
		$this->assertSame( 10, $clean['items_per_page'] );
		$this->assertSame( 'Hello from the boilerplate', $clean['greeting'] );
	}

	public function test_unknown_keys_are_dropped(): void {
		$clean = Schema::sanitize( array( 'evil' => 'x' ) + Schema::defaults() );

		$this->assertArrayNotHasKey( 'evil', $clean );
	}

	public function test_values_are_sanitised_by_type(): void {
		$clean = Schema::sanitize(
			array(
				'enabled'        => '1',
				'greeting'       => '<b>Hi</b>  there',
				'contact_email'  => 'nope',
				'items_per_page' => '9000',
				'layout'         => 'carousel',
				'retention_days' => '-4',
			)
		);

		$this->assertTrue( $clean['enabled'] );
		$this->assertSame( 'Hi there', $clean['greeting'] );
		$this->assertSame( '', $clean['contact_email'] );
		$this->assertSame( 100, $clean['items_per_page'] );
		$this->assertSame( 'list', $clean['layout'] );
		$this->assertSame( 1, $clean['retention_days'] );
	}

	public function test_unchecked_checkbox_becomes_false(): void {
		$input = Schema::defaults();
		unset( $input['enabled'] );

		$this->assertFalse( Schema::sanitize( $input )['enabled'] );
	}

	public function test_valid_layout_is_kept(): void {
		$this->assertSame( 'grid', Schema::sanitize( array( 'layout' => 'grid' ) )['layout'] );
	}

	public function test_missing_text_uses_default(): void {
		$this->assertSame( 'Hello from the boilerplate', Schema::sanitize( array() )['greeting'] );
	}

	public function test_explicit_empty_text_stays_empty(): void {
		$this->assertSame( '', Schema::sanitize( array( 'greeting' => '' ) )['greeting'] );
	}
}
