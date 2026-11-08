<?php
/**
 * Daily cleanup event.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Cron;

use SaizulAmin\PluginBoilerplate\PostTypes\ItemPostType;
use SaizulAmin\PluginBoilerplate\Settings\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permanently deletes this plugin's own trashed items older than the configured retention.
 * It never touches other post types.
 */
class CleanupTask {

	public const HOOK = 'wppbp_daily_cleanup';

	/**
	 * Settings repository.
	 */
	private Repository $settings;

	/**
	 * Create the task.
	 *
	 * @param Repository $settings Settings repository.
	 */
	public function __construct( Repository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Attach hooks and self-heal a missing schedule.
	 */
	public function hook(): void {
		add_action( self::HOOK, array( $this, 'run' ) );
		add_action( 'init', array( self::class, 'schedule' ) );
	}

	/**
	 * Schedule the event if it is not scheduled yet.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	/**
	 * Remove the event.
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Delete expired trashed items and remember when it ran.
	 *
	 * @return int Number of deleted items.
	 */
	public function run(): int {
		$days = (int) $this->settings->get( 'retention_days', 30 );

		$expired = get_posts(
			array(
				'post_type'      => ItemPostType::POST_TYPE,
				'post_status'    => 'trash',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'column' => 'post_modified_gmt',
						'before' => gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) ),
					),
				),
			)
		);

		$deleted = 0;
		foreach ( $expired as $post_id ) {
			if ( wp_delete_post( (int) $post_id, true ) ) {
				++$deleted;
			}
		}

		update_option( 'wppbp_last_cleanup', time(), false );

		return $deleted;
	}
}
