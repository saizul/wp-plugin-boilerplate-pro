<?php
/**
 * Boot, post type, settings, shortcode and cron integration tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Integration;

use SaizulAmin\PluginBoilerplate\Cron\CleanupTask;
use SaizulAmin\PluginBoilerplate\Lifecycle;
use SaizulAmin\PluginBoilerplate\PostTypes\ItemPostType;
use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Shortcodes\ItemsShortcode;
use WP_UnitTestCase;

/**
 * Runs against a real WordPress.
 */
final class PluginBootTest extends WP_UnitTestCase {

	public function test_post_type_is_registered_and_exposed_to_rest(): void {
		$this->assertTrue( post_type_exists( ItemPostType::POST_TYPE ) );

		$object = get_post_type_object( ItemPostType::POST_TYPE );
		$this->assertTrue( $object->show_in_rest );
		$this->assertSame( 'wppbp-items', $object->rest_base );
	}

	public function test_item_url_meta_is_registered_and_sanitised(): void {
		$this->assertTrue( registered_meta_key_exists( 'post', ItemPostType::META_URL, ItemPostType::POST_TYPE ) );

		$post_id = self::factory()->post->create( array( 'post_type' => ItemPostType::POST_TYPE ) );
		update_post_meta( $post_id, ItemPostType::META_URL, 'javascript:alert(1)' );

		$this->assertStringNotContainsString( 'javascript:', (string) get_post_meta( $post_id, ItemPostType::META_URL, true ) );
	}

	public function test_settings_repository_returns_defaults_and_sanitises_stored_values(): void {
		delete_option( Repository::OPTION );
		$repo = new Repository();
		$this->assertSame( Repository::defaults(), $repo->all() );

		update_option(
			Repository::OPTION,
			array(
				'items_per_page' => 99999,
				'layout'         => 'carousel',
			)
		);
		$all = $repo->all();
		$this->assertSame( 100, $all['items_per_page'] );
		$this->assertSame( 'list', $all['layout'] );

		$repo->reset();
		$this->assertSame( Repository::defaults(), $repo->all() );
	}

	public function test_shortcode_lists_published_items_only(): void {
		$this->assertTrue( shortcode_exists( ItemsShortcode::TAG ) );

		self::factory()->post->create(
			array(
				'post_type'   => ItemPostType::POST_TYPE,
				'post_title'  => 'Visible Item',
				'post_status' => 'publish',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => ItemPostType::POST_TYPE,
				'post_title'  => 'Draft Item',
				'post_status' => 'draft',
			)
		);

		$html = do_shortcode( '[' . ItemsShortcode::TAG . ']' );
		$this->assertStringContainsString( 'Visible Item', $html );
		$this->assertStringNotContainsString( 'Draft Item', $html );
	}

	public function test_shortcode_escapes_item_titles(): void {
		self::factory()->post->create(
			array(
				'post_type'   => ItemPostType::POST_TYPE,
				'post_title'  => 'Fish & <em>Chips</em>',
				'post_status' => 'publish',
			)
		);

		$html = do_shortcode( '[' . ItemsShortcode::TAG . ' count="50"]' );
		$this->assertStringNotContainsString( '<em>Chips</em>', $html );
	}

	public function test_activation_stores_defaults_and_schedules_cleanup(): void {
		delete_option( Repository::OPTION );
		CleanupTask::unschedule();

		Lifecycle::activate();
		$this->assertSame( Repository::defaults(), get_option( Repository::OPTION ) );
		$this->assertNotFalse( wp_next_scheduled( CleanupTask::HOOK ) );

		Lifecycle::deactivate();
		$this->assertFalse( wp_next_scheduled( CleanupTask::HOOK ) );
	}
}
