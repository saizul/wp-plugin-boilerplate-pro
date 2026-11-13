<?php
/**
 * Settings export/import REST integration tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Integration;

use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Settings\Transfer;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * Exercises GET/POST /settings through the real REST server.
 */
final class RestSettingsTest extends WP_UnitTestCase {

	private const ROUTE = '/wp-plugin-boilerplate-pro/v1/settings';

	/**
	 * REST server used by the test.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	public function set_up(): void {
		parent::set_up();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init', $this->server );
		update_option( Repository::OPTION, Repository::defaults() );
	}

	public function tear_down(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * POST request with a JSON body.
	 *
	 * @param mixed                $body  Body to encode.
	 * @param array<string, mixed> $query Query parameters.
	 */
	private function import_request( mixed $body, array $query = array() ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $body ) );
		$request->set_query_params( $query );
		return $request;
	}

	private function login_as( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	public function test_route_is_registered(): void {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
	}

	public function test_anonymous_users_cannot_export(): void {
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$this->assertSame( 401, $response->get_status() );
	}

	public function test_editors_cannot_export_or_import(): void {
		$this->login_as( 'editor' );

		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) )->get_status() );
		$this->assertSame( 403, $this->server->dispatch( $this->import_request( array( 'layout' => 'grid' ) ) )->get_status() );
		$this->assertSame( 'list', ( new Repository() )->get( 'layout' ) );
	}

	public function test_administrators_can_export(): void {
		$this->login_as( 'administrator' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( Transfer::FORMAT, $data['format'] );
		$this->assertSame( Repository::defaults(), $data['settings'] );
	}

	public function test_administrators_can_import_a_partial_object(): void {
		$this->login_as( 'administrator' );

		$response = $this->server->dispatch( $this->import_request( array( 'layout' => 'grid' ) ) );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['updated'] );
		$this->assertSame( 'grid', ( new Repository() )->get( 'layout' ) );
		$this->assertSame( 10, ( new Repository() )->get( 'items_per_page' ) );
	}

	public function test_dry_run_does_not_save(): void {
		$this->login_as( 'administrator' );

		$response = $this->server->dispatch( $this->import_request( array( 'layout' => 'grid' ), array( 'dry_run' => 'true' ) ) );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['dry_run'] );
		$this->assertFalse( $data['updated'] );
		$this->assertArrayHasKey( 'layout', (array) $data['changes'] );
		$this->assertSame( 'list', ( new Repository() )->get( 'layout' ) );
	}

	public function test_invalid_import_is_rejected_and_nothing_is_saved(): void {
		$this->login_as( 'administrator' );

		$response = $this->server->dispatch(
			$this->import_request(
				array(
					'layout'         => 'grid',
					'items_per_page' => 9000,
				)
			)
		);
		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'wppbp_invalid_settings', $data['code'] );
		$this->assertArrayHasKey( 'items_per_page', $data['data']['errors'] );
		$this->assertSame( 'list', ( new Repository() )->get( 'layout' ) );
	}

	public function test_export_can_be_imported_back(): void {
		$this->login_as( 'administrator' );
		update_option( Repository::OPTION, array( 'layout' => 'grid' ) + Repository::defaults() );

		$export = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) )->get_data();
		update_option( Repository::OPTION, Repository::defaults() );

		$response = $this->server->dispatch( $this->import_request( $export ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'grid', ( new Repository() )->get( 'layout' ) );
	}
}
