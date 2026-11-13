<?php
/**
 * REST controller for exporting and importing settings.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Rest;

use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Settings\Transfer;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes under /wp-json/wp-plugin-boilerplate-pro/v1.
 *
 * - GET  /settings             export envelope (see Settings\Transfer)
 * - POST /settings[?dry_run=1] import an envelope or a partial settings object
 *
 * Both routes require the manage_options capability. Validation and merging live in the pure
 * Settings\Transfer class; this controller only handles HTTP and storage.
 */
class SettingsController {

	public const REST_NAMESPACE = 'wp-plugin-boilerplate-pro/v1';
	public const ROUTE          = '/settings';

	/**
	 * Settings repository.
	 */
	private Repository $settings;

	/**
	 * Plugin version written into exports.
	 */
	private string $version;

	/**
	 * Create the controller.
	 *
	 * @param Repository $settings Settings repository.
	 * @param string     $version  Plugin version.
	 */
	public function __construct( Repository $settings, string $version ) {
		$this->settings = $settings;
		$this->version  = $version;
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
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'dry_run' => array(
							'description'       => __( 'Validate and report changes without saving them.', 'wp-plugin-boilerplate-pro' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);
	}

	/**
	 * Settings can only be read or changed by administrators.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to manage these settings.', 'wp-plugin-boilerplate-pro' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Export the current settings.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function export_settings( $request ): WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return rest_ensure_response( Transfer::export( $this->settings->all(), $this->version ) );
	}

	/**
	 * Import settings from the JSON body.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_settings( $request ) {
		$body = (string) $request->get_body();
		if ( '' === trim( $body ) ) {
			return $this->invalid( array( 'json' => 'The request body is empty.' ) );
		}

		$result = Transfer::fromJson( $body, $this->settings->all() );
		if ( ! $result->isValid() ) {
			return $this->invalid( $result->errors() );
		}

		$dry_run = (bool) $request['dry_run'];
		if ( ! $dry_run && $result->hasChanges() ) {
			$this->settings->replace( $result->settings() );
		}

		return rest_ensure_response(
			array(
				'dry_run'  => $dry_run,
				'updated'  => ! $dry_run && $result->hasChanges(),
				'changes'  => (object) $result->changes(),
				'settings' => $result->settings(),
			)
		);
	}

	/**
	 * A 400 error carrying the validation messages.
	 *
	 * @param array<string, string> $errors Error message keyed by field.
	 */
	private function invalid( array $errors ): WP_Error {
		return new WP_Error(
			'wppbp_invalid_settings',
			__( 'The settings could not be imported. Nothing was changed.', 'wp-plugin-boilerplate-pro' ),
			array(
				'status' => 400,
				'errors' => $errors,
			)
		);
	}
}
