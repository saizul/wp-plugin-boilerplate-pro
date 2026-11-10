<?php
/**
 * REST API integration tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Integration;

use SaizulAmin\PluginBoilerplate\PostTypes\ItemPostType;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * Exercises the plugin routes through the real REST server.
 */
final class RestItemsTest extends WP_UnitTestCase {

	private const ROUTE = '/wp-plugin-boilerplate-pro/v1/items';

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
	}

	public function tear_down(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	public function test_routes_are_registered(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( self::ROUTE, $routes );
		$this->assertArrayHasKey( self::ROUTE . '/(?P<id>[\d]+)', $routes );
	}

	public function test_anonymous_users_can_list_published_items(): void {
		self::factory()->post->create(
			array(
				'post_type'  => ItemPostType::POST_TYPE,
				'post_title' => 'Public Item',
			)
		);

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertContains( 'Public Item', wp_list_pluck( (array) $response->get_data(), 'title' ) );
	}

	public function test_anonymous_users_cannot_create_items(): void {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_param( 'title', 'Nope' );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 401, $response->get_status() );
	}

	public function test_subscribers_cannot_create_items(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_param( 'title', 'Nope' );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_editors_can_create_items_and_input_is_sanitised(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_param( 'title', '<script>alert(1)</script>Created' );
		$request->set_param( 'content', '<p>Body</p><script>alert(1)</script>' );
		$request->set_param( 'url', 'https://example.com/page' );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertStringNotContainsString( '<script', (string) $data['title'] );
		$this->assertStringNotContainsString( '<script', (string) $data['content'] );
		$this->assertSame( 'https://example.com/page', $data['url'] );
		$this->assertSame( ItemPostType::POST_TYPE, get_post_type( (int) $data['id'] ) );
		$this->assertNotEmpty( $response->get_headers()['Location'] );
	}
}
