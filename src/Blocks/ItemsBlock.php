<?php
/**
 * The wp-plugin-boilerplate-pro/items block (dynamic, rendered on the server).
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Blocks;

use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Shortcodes\ItemsShortcode;
use SaizulAmin\PluginBoilerplate\Support\ItemsArgs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the block from blocks/items/block.json and renders it with the same list markup as the shortcode.
 * There is no build step: the small editor script uses the wp.* globals.
 */
class ItemsBlock {

	/**
	 * Settings repository.
	 */
	private Repository $settings;

	/**
	 * Shortcode renderer, reused for the list markup.
	 */
	private ItemsShortcode $shortcode;

	/**
	 * Create the block.
	 *
	 * @param Repository     $settings  Settings repository.
	 * @param ItemsShortcode $shortcode Shortcode renderer.
	 */
	public function __construct( Repository $settings, ItemsShortcode $shortcode ) {
		$this->settings  = $settings;
		$this->shortcode = $shortcode;
	}

	/**
	 * Attach hooks.
	 */
	public function hook(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register the block type from its metadata file.
	 */
	public function register(): void {
		register_block_type(
			WPPBP_DIR . 'blocks/items',
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render( array $attributes = array() ): string {
		if ( ! $this->settings->get( 'enabled', true ) ) {
			return '';
		}

		$args = ItemsArgs::normalize(
			$attributes['count'] ?? null,
			$attributes['layout'] ?? null,
			$attributes['search'] ?? '',
			$this->settings->get( 'items_per_page', 10 ),
			$this->settings->get( 'layout', 'list' )
		);

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(),
			$this->shortcode->render_list( $args )
		);
	}
}
