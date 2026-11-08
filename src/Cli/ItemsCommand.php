<?php
/**
 * WP-CLI commands.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Cli;

use SaizulAmin\PluginBoilerplate\Cron\CleanupTask;
use SaizulAmin\PluginBoilerplate\PostTypes\ItemPostType;
use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Support\Sanitizer;
use WP_CLI;
use WP_CLI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage example items.
 */
class ItemsCommand {

	/**
	 * Settings repository.
	 */
	private Repository $settings;

	/**
	 * Cleanup task.
	 */
	private CleanupTask $cleanup;

	/**
	 * Create the command.
	 *
	 * @param Repository  $settings Settings repository.
	 * @param CleanupTask $cleanup  Cleanup task.
	 */
	public function __construct( Repository $settings, CleanupTask $cleanup ) {
		$this->settings = $settings;
		$this->cleanup  = $cleanup;
	}

	/**
	 * Lists items.
	 *
	 * ## OPTIONS
	 *
	 * [--number=<number>]
	 * : Maximum number of items.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppbp list --number=5 --format=json
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function list( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$number = Sanitizer::int( $assoc_args['number'] ?? 20, 1, 500, 20 );
		$format = (string) ( $assoc_args['format'] ?? 'table' );

		$posts = get_posts(
			array(
				'post_type'      => ItemPostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => $number,
			)
		);

		$rows = array();
		foreach ( $posts as $post ) {
			$rows[] = array(
				'ID'     => $post->ID,
				'title'  => $post->post_title,
				'status' => $post->post_status,
				'date'   => $post->post_date,
			);
		}

		if ( 'ids' === $format ) {
			WP_CLI::line( implode( ' ', wp_list_pluck( $rows, 'ID' ) ) );
			return;
		}

		Utils\format_items( $format, $rows, array( 'ID', 'title', 'status', 'date' ) );
	}

	/**
	 * Creates an item.
	 *
	 * ## OPTIONS
	 *
	 * <title>
	 * : The item title.
	 *
	 * [--porcelain]
	 * : Output just the new item ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppbp create "My first item"
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function create( array $args, array $assoc_args ): void {
		$title = Sanitizer::text( $args[0] ?? '' );
		if ( '' === $title ) {
			WP_CLI::error( 'Please provide a non-empty title.' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => ItemPostType::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( $post_id->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain', false ) ) {
			WP_CLI::line( (string) $post_id );
			return;
		}

		WP_CLI::success( sprintf( 'Created item %d.', $post_id ) );
	}

	/**
	 * Runs the cleanup task now.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppbp cleanup
	 */
	public function cleanup(): void {
		$deleted = $this->cleanup->run();
		WP_CLI::success( sprintf( 'Deleted %d expired item(s).', $deleted ) );
	}

	/**
	 * Prints the current settings as JSON.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppbp settings
	 */
	public function settings(): void {
		WP_CLI::line( (string) wp_json_encode( $this->settings->all(), JSON_PRETTY_PRINT ) );
	}
}
