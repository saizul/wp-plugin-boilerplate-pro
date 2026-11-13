<?php
/**
 * Settings export and import tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SaizulAmin\PluginBoilerplate\Settings\Schema;
use SaizulAmin\PluginBoilerplate\Settings\Transfer;

final class TransferTest extends TestCase {

	public function test_export_builds_a_versioned_envelope(): void {
		$export = Transfer::export( Schema::defaults(), '1.2.3' );

		$this->assertSame( Transfer::FORMAT, $export['format'] );
		$this->assertSame( Transfer::FORMAT_VERSION, $export['format_version'] );
		$this->assertSame( '1.2.3', $export['plugin_version'] );
		$this->assertSame( Schema::defaults(), $export['settings'] );
	}

	public function test_export_completes_and_sanitises_settings(): void {
		$export = Transfer::export(
			array(
				'layout' => 'grid',
				'evil'   => 'x',
			),
			'1.0.0'
		);

		$this->assertSame( array_keys( Schema::fields() ), array_keys( $export['settings'] ) );
		$this->assertSame( 'grid', $export['settings']['layout'] );
		$this->assertArrayNotHasKey( 'evil', $export['settings'] );
	}

	public function test_export_round_trips_through_json(): void {
		$settings = array(
			'layout'         => 'grid',
			'items_per_page' => 25,
			'contact_email'  => 'owner@example.com',
			'enabled'        => false,
		) + Schema::defaults();

		$json   = (string) json_encode( Transfer::export( $settings, '1.0.0' ) );
		$result = Transfer::fromJson( $json, Schema::defaults() );

		$this->assertTrue( $result->isValid() );
		$this->assertSame( Schema::sanitize( $settings ), $result->settings() );
		$this->assertSame( array( 'enabled', 'contact_email', 'items_per_page', 'layout' ), array_keys( $result->changes() ) );
	}

	public function test_importing_the_current_settings_changes_nothing(): void {
		$result = Transfer::import( Transfer::export( Schema::defaults(), '1.0.0' ), Schema::defaults() );

		$this->assertTrue( $result->isValid() );
		$this->assertFalse( $result->hasChanges() );
		$this->assertSame( Schema::defaults(), $result->settings() );
	}

	public function test_bare_partial_object_is_merged_over_current_settings(): void {
		$current = array( 'retention_days' => 90 ) + Schema::defaults();

		$result = Transfer::import( array( 'layout' => 'grid' ), $current );

		$this->assertTrue( $result->isValid() );
		$this->assertSame( 'grid', $result->settings()['layout'] );
		$this->assertSame( 90, $result->settings()['retention_days'] );
		$this->assertTrue( $result->settings()['enabled'], 'Booleans left out keep their current value.' );
		$this->assertSame(
			array(
				'layout' => array(
					'from' => 'list',
					'to'   => 'grid',
				),
			),
			$result->changes()
		);
	}

	public function test_partial_envelope_is_merged_too(): void {
		$result = Transfer::import(
			array(
				'format'         => Transfer::FORMAT,
				'format_version' => 1,
				'settings'       => array( 'delete_on_uninstall' => true ),
			),
			Schema::defaults()
		);

		$this->assertTrue( $result->isValid() );
		$this->assertSame( array( 'delete_on_uninstall' ), array_keys( $result->changes() ) );
	}

	public function test_text_is_sanitised_on_import(): void {
		$result = Transfer::import( array( 'greeting' => '<b>Hi</b>   there' ), Schema::defaults() );

		$this->assertTrue( $result->isValid() );
		$this->assertSame( 'Hi there', $result->settings()['greeting'] );
	}

	public function test_one_invalid_value_rejects_the_whole_import(): void {
		$current = Schema::defaults();

		$result = Transfer::import(
			array(
				'layout'         => 'grid',
				'items_per_page' => 9000,
				'unknown'        => true,
			),
			$current
		);

		$this->assertFalse( $result->isValid() );
		$this->assertSame( array( 'items_per_page', 'unknown' ), array_keys( $result->errors() ) );
		$this->assertSame( $current, $result->settings() );
		$this->assertFalse( $result->hasChanges() );
	}

	public function test_current_settings_are_completed_before_merging(): void {
		$result = Transfer::import( array(), array( 'layout' => 'grid' ) );

		$this->assertTrue( $result->isValid() );
		$this->assertSame( array_keys( Schema::fields() ), array_keys( $result->settings() ) );
		$this->assertSame( 'grid', $result->settings()['layout'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function invalidPayloadProvider(): array {
		$envelope = array(
			'format'         => Transfer::FORMAT,
			'format_version' => 1,
			'settings'       => array(),
		);

		return array(
			'null'                   => array( null, 'settings' ),
			'string'                 => array( 'layout=grid', 'settings' ),
			'list'                   => array( array( 'grid', 'list' ), 'settings' ),
			'foreign format'         => array( array( 'format' => 'other-plugin' ) + $envelope, 'format' ),
			'missing format version' => array( array_diff_key( $envelope, array( 'format_version' => 0 ) ), 'format_version' ),
			'string format version'  => array( array( 'format_version' => '1' ) + $envelope, 'format_version' ),
			'zero format version'    => array( array( 'format_version' => 0 ) + $envelope, 'format_version' ),
			'newer format version'   => array( array( 'format_version' => Transfer::FORMAT_VERSION + 1 ) + $envelope, 'format_version' ),
			'missing settings'       => array( array_diff_key( $envelope, array( 'settings' => 0 ) ), 'settings' ),
			'settings is a list'     => array( array( 'settings' => array( 1, 2 ) ) + $envelope, 'settings' ),
			'settings is a string'   => array( array( 'settings' => 'x' ) + $envelope, 'settings' ),
		);
	}

	#[DataProvider( 'invalidPayloadProvider' )]
	public function test_invalid_payloads_are_rejected( mixed $payload, string $error_key ): void {
		$result = Transfer::import( $payload, Schema::defaults() );

		$this->assertFalse( $result->isValid() );
		$this->assertArrayHasKey( $error_key, $result->errors() );
		$this->assertSame( Schema::defaults(), $result->settings() );
	}

	public function test_invalid_json_is_reported(): void {
		$result = Transfer::fromJson( '{"layout": "grid"', Schema::defaults() );

		$this->assertFalse( $result->isValid() );
		$this->assertStringStartsWith( 'Invalid JSON', $result->errors()['json'] );
	}

	public function test_deeply_nested_json_is_rejected(): void {
		$json = str_repeat( '[', 40 ) . str_repeat( ']', 40 );

		$this->assertArrayHasKey( 'json', Transfer::fromJson( $json, Schema::defaults() )->errors() );
	}

	public function test_oversized_documents_are_rejected_before_decoding(): void {
		$json = '{"greeting": "' . str_repeat( 'a', Transfer::MAX_BYTES ) . '"}';

		$result = Transfer::fromJson( $json, Schema::defaults() );

		$this->assertFalse( $result->isValid() );
		$this->assertArrayHasKey( 'json', $result->errors() );
	}

	public function test_empty_json_object_is_a_no_op(): void {
		$result = Transfer::fromJson( '{}', Schema::defaults() );

		$this->assertTrue( $result->isValid() );
		$this->assertFalse( $result->hasChanges() );
	}
}
