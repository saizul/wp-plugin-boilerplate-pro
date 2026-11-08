<?php
/**
 * Custom post type "Example Item".
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\PostTypes;

use SaizulAmin\PluginBoilerplate\Support\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the post type and its meta.
 */
class ItemPostType {

	public const POST_TYPE = 'wppbp_item';
	public const META_URL  = 'wppbp_item_url';

	/**
	 * Attach hooks.
	 */
	public function hook(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register the post type and meta field.
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => _x( 'Items', 'post type general name', 'wp-plugin-boilerplate-pro' ),
					'singular_name'      => _x( 'Item', 'post type singular name', 'wp-plugin-boilerplate-pro' ),
					'add_new_item'       => __( 'Add New Item', 'wp-plugin-boilerplate-pro' ),
					'edit_item'          => __( 'Edit Item', 'wp-plugin-boilerplate-pro' ),
					'new_item'           => __( 'New Item', 'wp-plugin-boilerplate-pro' ),
					'view_item'          => __( 'View Item', 'wp-plugin-boilerplate-pro' ),
					'search_items'       => __( 'Search Items', 'wp-plugin-boilerplate-pro' ),
					'not_found'          => __( 'No items found.', 'wp-plugin-boilerplate-pro' ),
					'not_found_in_trash' => __( 'No items found in Trash.', 'wp-plugin-boilerplate-pro' ),
				),
				'public'          => true,
				'has_archive'     => true,
				'show_in_rest'    => true,
				'rest_base'       => 'wppbp-items',
				'menu_icon'       => 'dashicons-star-filled',
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
				'rewrite'         => array( 'slug' => 'items' ),
				'capability_type' => 'post',
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_URL,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => static fn( $value ): string => Sanitizer::url( $value ),
				'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			)
		);
	}
}
