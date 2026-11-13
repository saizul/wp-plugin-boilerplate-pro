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
use SaizulAmin\PluginBoilerplate\Settings\Transfer;
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
	 * Plugin version written into settings exports.
	 */
	private string $version;

	/**
	 * Create the command.
	 *
	 * @param Repository  $settings Settings repository.
	 * @param CleanupTask $cleanup  Cleanup task.
	 * @param string      $version  Plugin version.
	 */
	public function __construct( Repository $settings, CleanupTask $cleanup, string $version = '0.0.0' ) {
		$this->settings = $settings;
		$this->cleanup  = $cleanup;
		$this->version  = $version;
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

	/**
	 * Exports the settings as a versioned JSON document.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<file>]
	 * : Write the export to this file instead of standard output.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppbp export-settings > settings.json
	 *     wp wppbp export-settings --file=settings.json
	 *
	 * @subcommand export-settings
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function export_settings( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		$json = (string) wp_json_encode( Transfer::export( $this->settings->all(), $this->version ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$file = (string) ( $assoc_args['file'] ?? '' );

		if ( '' === $file ) {
			WP_CLI::line( $json );
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP-CLI writes to a path chosen by the operator.
		if ( false === file_put_contents( $file, $json . "\n" ) ) {
			WP_CLI::error( sprintf( 'Could not write to %s.', $file ) );
		}

		WP_CLI::success( sprintf( 'Exported settings to %s.', $file ) );
	}

	/**
	 * Imports settings from an export file or a partial JSON object of settings.
	 *
	 * Values are validated strictly against the settings schema. Settings that are left out keep
	 * their current value. If any value is invalid, nothing is saved.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the JSON file, or "-" to read from standard input.
	 *
	 * [--dry-run]
	 * : Validate and list the changes without saving them.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppbp import-settings settings.json --dry-run
	 *     echo '{"layout":"grid"}' | wp wppbp import-settings -
	 *
	 * @subcommand import-settings
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function import_settings( array $args, array $assoc_args ): void {
		$source = (string) ( $args[0] ?? '' );
		if ( '' === $source ) {
			WP_CLI::error( 'Please provide a file path, or "-" for standard input.' );
		}
		if ( '-' !== $source && ! is_readable( $source ) ) {
			WP_CLI::error( sprintf( 'Cannot read %s.', $source ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file or STDIN chosen by the operator.
		$json   = (string) file_get_contents( '-' === $source ? 'php://stdin' : $source );
		$result = Transfer::fromJson( $json, $this->settings->all() );

		if ( ! $result->isValid() ) {
			foreach ( $result->errors() as $key => $message ) {
				WP_CLI::warning( sprintf( '%s: %s', $key, $message ) );
			}
			WP_CLI::error( 'The settings could not be imported. Nothing was changed.' );
		}

		if ( ! $result->hasChanges() ) {
			WP_CLI::success( 'Settings are already up to date.' );
			return;
		}

		$rows = array();
		foreach ( $result->changes() as $key => $change ) {
			$rows[] = array(
				'setting' => $key,
				'from'    => (string) wp_json_encode( $change['from'] ),
				'to'      => (string) wp_json_encode( $change['to'] ),
			);
		}
		Utils\format_items( 'table', $rows, array( 'setting', 'from', 'to' ) );

		if ( Utils\get_flag_value( $assoc_args, 'dry-run', false ) ) {
			WP_CLI::success( sprintf( 'Dry run: %d setting(s) would change. Nothing was saved.', count( $rows ) ) );
			return;
		}

		$this->settings->replace( $result->settings() );
		WP_CLI::success( sprintf( 'Imported settings: %d setting(s) changed.', count( $rows ) ) );
	}
}
