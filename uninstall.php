<?php
/**
 * Uninstall script for Log Changes plugin.
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all plugin data from the database.
 *
 * @package LogChanges
 */

// Exit if not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete the change log table.
$table_name = $wpdb->prefix . 'change_log';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Delete plugin options.
delete_option( 'log_changes_version' );

// For multisite installations, delete options from all sites.
if ( is_multisite() ) {
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		
		$table_name = $wpdb->prefix . 'change_log';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		delete_option( 'log_changes_version' );
		
		restore_current_blog();
	}
}
