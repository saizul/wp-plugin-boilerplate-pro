<?php
/**
 * Activation and deactivation handlers.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate;

use SaizulAmin\PluginBoilerplate\Cron\CleanupTask;
use SaizulAmin\PluginBoilerplate\PostTypes\ItemPostType;
use SaizulAmin\PluginBoilerplate\Settings\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static callbacks for register_activation_hook() and register_deactivation_hook().
 */
final class Lifecycle {

	/**
	 * Runs on activation: default options, rewrite rules, cron.
	 */
	public static function activate(): void {
		if ( false === get_option( Repository::OPTION ) ) {
			add_option( Repository::OPTION, Repository::defaults() );
		}
		update_option( 'wppbp_version', defined( 'WPPBP_VERSION' ) ? WPPBP_VERSION : '0.0.0' );

		// The post type must exist before flushing so its permalinks work immediately.
		( new ItemPostType() )->register();
		flush_rewrite_rules();

		CleanupTask::schedule();
	}

	/**
	 * Runs on deactivation: remove the scheduled event and rewrite rules. Data is kept.
	 */
	public static function deactivate(): void {
		CleanupTask::unschedule();
		flush_rewrite_rules();
	}
}
