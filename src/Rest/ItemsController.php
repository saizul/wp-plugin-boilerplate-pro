<?php
/**
 * REST controller for items.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Rest;

use SaizulAmin\PluginBoilerplate\PostTypes\ItemPostType;
use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Support\Sanitizer;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes under /wp-json/wp-plugin-boilerplate-pro/v1.
 *
 * - GET  /items        public, paginated and searchable
 * - GET  /items/<id>   public
 * - POST /items        requires the publish_posts capability
 *
 * Every route declares a permission_callback, validates and sanitises its arguments and returns
 * data shaped by get_item_schema().
 */
class ItemsController extends WP_REST_Controller {

	/**
	 * Settings repository.
	 */
	private Repository $settings;

	/**
	 * Create the controller.
	 *
	 * @param Repository $settings Settings repository.
	 */
	public function __construct( Repository $settings ) {
		$this->settings  = $settings;
		$this->namespace = 'wp-plugin-boilerplate-pro/v1';
		$this->rest_base = 'items';
	}

	/**
	 * Attach hooks.
	 */
	public function hook(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'Unique identifier for the item.', 'wp-plugin-boilerplate-pro' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Reading published items is public.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function get_items_permissions_check( $request ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return true;
	}

	/**
	 * Creating items needs a logged-in user who can publish posts. Cookie authentication
	 * additionally requires the X-WP-Nonce header, which WordPress verifies before this runs.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( current_user_can( 'publish_posts' ) ) {
			return true;
		}
		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to create items.', 'wp-plugin-boilerplate-pro' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Query parameters for the collection.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_collection_params(): array {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'wp-plugin-boilerplate-pro' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to return.', 'wp-plugin-boilerplate-pro' ),
				'type'              => 'integer',
				'default'           => $this->settings->get( 'items_per_page', 10 ),
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'   => array(
				'description'       => __( 'Limit results to those matching a string.', 'wp-plugin-boilerplate-pro' ),
				'type'              => 'string',
				'sanitize_callback' => array( Sanitizer::class, 'text' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * List items.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function get_items( $request ): WP_REST_Response {
		$query = new WP_Query(
			array(
				'post_type'      => ItemPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $request['per_page'],
				'paged'          => (int) $request['page'],
				's'              => (string) $request['search'],
			)
		);

		$data = array();
		foreach ( $query->posts as $post ) {
			$data[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $post, $request ) );
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Single item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$post = get_post( (int) $request['id'] );

		if ( ! $post instanceof WP_Post || ItemPostType::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error(
				'wppbp_item_not_found',
				__( 'Item not found.', 'wp-plugin-boilerplate-pro' ),
				array( 'status' => 404 )
			);
		}

		return $this->prepare_item_for_response( $post, $request );
	}

	/**
	 * Create an item.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => ItemPostType::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => Sanitizer::text( $request['title'] ),
				'post_content' => wp_kses_post( (string) $request['content'] ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$url = Sanitizer::url( $request['url'] );
		if ( '' !== $url ) {
			update_post_meta( $post_id, ItemPostType::META_URL, $url );
		}

		$response = $this->prepare_item_for_response( get_post( $post_id ), $request );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $post_id ) ) );

		return $response;
	}

	/**
	 * Shape a post according to the schema.
	 *
	 * @param WP_Post         $item    Post.
	 * @param WP_REST_Request $request Request.
	 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$data = array(
			'id'      => (int) $item->ID,
			'title'   => get_the_title( $item ),
			'content' => apply_filters( 'the_content', $item->post_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
			'url'     => (string) get_post_meta( $item->ID, ItemPostType::META_URL, true ),
			'link'    => get_permalink( $item ),
			'date'    => mysql_to_rfc3339( $item->post_date_gmt ),
		);

		return rest_ensure_response( $data );
	}

	/**
	 * JSON schema for an item.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'wppbp-item',
			'type'       => 'object',
			'properties' => array(
				'id'      => array(
					'description' => __( 'Unique identifier for the item.', 'wp-plugin-boilerplate-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'title'   => array(
					'description' => __( 'The item title.', 'wp-plugin-boilerplate-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed', 'edit' ),
					'minLength'   => 1,
					'maxLength'   => 200,
					'required'    => true,
				),
				'content' => array(
					'description' => __( 'The item content (HTML in responses, limited HTML accepted on create).', 'wp-plugin-boilerplate-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'url'     => array(
					'description' => __( 'An external URL related to the item.', 'wp-plugin-boilerplate-pro' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'link'    => array(
					'description' => __( 'URL of the item on the site.', 'wp-plugin-boilerplate-pro' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'date'    => array(
					'description' => __( 'The date the item was published, in GMT.', 'wp-plugin-boilerplate-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}
}
