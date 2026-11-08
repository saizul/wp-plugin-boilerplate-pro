<?php
/**
 * The [wppbp_items] shortcode.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Shortcodes;

use SaizulAmin\PluginBoilerplate\PostTypes\ItemPostType;
use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Support\Sanitizer;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Usage: [wppbp_items count="5" layout="grid" search="term"]
 *
 * Attributes come from content authors, so they are sanitised before use and the output is escaped.
 */
class ItemsShortcode {

	public const TAG = 'wppbp_items';

	/**
	 * Settings repository.
	 */
	private Repository $settings;

	/**
	 * Create the shortcode.
	 *
	 * @param Repository $settings Settings repository.
	 */
	public function __construct( Repository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Attach hooks.
	 */
	public function hook(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Normalise attributes.
	 *
	 * @param array<string, mixed>|string $atts Raw attributes.
	 * @return array{count: int, layout: string, search: string}
	 */
	public function parse_atts( array|string $atts ): array {
		$atts = shortcode_atts(
			array(
				'count'  => $this->settings->get( 'items_per_page', 10 ),
				'layout' => $this->settings->get( 'layout', 'list' ),
				'search' => '',
			),
			$atts,
			self::TAG
		);

		return array(
			'count'  => Sanitizer::int( $atts['count'], 1, 100, 10 ),
			'layout' => Sanitizer::enum( $atts['layout'], \SaizulAmin\PluginBoilerplate\Settings\Schema::LAYOUTS, 'list' ),
			'search' => Sanitizer::text( $atts['search'] ),
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, mixed>|string $atts Raw attributes.
	 */
	public function render( array|string $atts = array() ): string {
		if ( ! $this->settings->get( 'enabled', true ) ) {
			return '';
		}

		$args  = $this->parse_atts( $atts );
		$query = new WP_Query(
			array(
				'post_type'      => ItemPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $args['count'],
				's'              => $args['search'],
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="wppbp-empty">' . esc_html__( 'No items found.', 'wp-plugin-boilerplate-pro' ) . '</p>';
		}

		$html = '<ul class="' . esc_attr( 'wppbp-items wppbp-items--' . $args['layout'] ) . '">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= sprintf(
				'<li class="wppbp-item"><a href="%1$s">%2$s</a></li>',
				esc_url( get_permalink() ),
				esc_html( get_the_title() )
			);
		}
		$html .= '</ul>';
		wp_reset_postdata();

		return $html;
	}
}
