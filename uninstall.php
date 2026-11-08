<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * Removes options, scheduled events and (optionally) content. Content is only removed when the
 * site owner enabled "Delete data on uninstall" in the settings.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$wppbp_settings = get_option( 'wppbp_settings', array() );

wp_clear_scheduled_hook( 'wppbp_daily_cleanup' );

if ( is_array( $wppbp_settings ) && ! empty( $wppbp_settings['delete_on_uninstall'] ) ) {
	$wppbp_items = get_posts(
		array(
			'post_type'      => 'wppbp_item',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $wppbp_items as $wppbp_item_id ) {
		wp_delete_post( (int) $wppbp_item_id, true );
	}
}

delete_option( 'wppbp_settings' );
delete_option( 'wppbp_last_cleanup' );
delete_option( 'wppbp_version' );
