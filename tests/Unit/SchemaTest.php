<?php
/**
 * Settings schema tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
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

	public function test_validate_accepts_defaults(): void {
		$this->assertSame( array(), Schema::validate( Schema::defaults() ) );
	}

	public function test_validate_accepts_a_partial_set(): void {
		$this->assertSame( array(), Schema::validate( array( 'layout' => 'grid' ) ) );
		$this->assertSame( array(), Schema::validate( array() ) );
	}

	public function test_validate_rejects_unknown_keys(): void {
		$this->assertSame( array( 'evil' => 'Unknown setting.' ), Schema::validate( array( 'evil' => 'x' ) ) );
	}

	/**
	 * @return array<string, array{0: string, 1: mixed}>
	 */
	public static function invalidValueProvider(): array {
		return array(
			'bool as string'     => array( 'enabled', '1' ),
			'bool as int'        => array( 'enabled', 1 ),
			'int as string'      => array( 'items_per_page', '10' ),
			'int as float'       => array( 'items_per_page', 10.0 ),
			'int below minimum'  => array( 'items_per_page', 0 ),
			'int above maximum'  => array( 'retention_days', 366 ),
			'enum not a choice'  => array( 'layout', 'carousel' ),
			'enum wrong case'    => array( 'layout', 'Grid' ),
			'enum not a string'  => array( 'layout', 1 ),
			'email invalid'      => array( 'contact_email', 'nope' ),
			'email with spaces'  => array( 'contact_email', ' a@example.com ' ),
			'email not a string' => array( 'contact_email', array( 'a@example.com' ) ),
			'text not a string'  => array( 'greeting', 42 ),
			'text null'          => array( 'greeting', null ),
		);
	}

	#[DataProvider( 'invalidValueProvider' )]
	public function test_validate_rejects_invalid_values( string $key, mixed $value ): void {
		$errors = Schema::validate( array( $key => $value ) );

		$this->assertArrayHasKey( $key, $errors );
		$this->assertCount( 1, $errors );
	}

	public function test_validate_reports_ranges_and_choices(): void {
		$errors = Schema::validate(
			array(
				'items_per_page' => 500,
				'layout'         => 'carousel',
			)
		);

		$this->assertSame( 'Must be between 1 and 100.', $errors['items_per_page'] );
		$this->assertSame( 'Must be one of: list, grid.', $errors['layout'] );
	}

	public function test_validate_accepts_boundaries_and_empty_email(): void {
		$this->assertSame(
			array(),
			Schema::validate(
				array(
					'items_per_page' => 100,
					'retention_days' => 1,
					'contact_email'  => '',
					'enabled'        => false,
				)
			)
		);
		$this->assertSame( array(), Schema::validate( array( 'contact_email' => 'owner@example.com' ) ) );
	}
}
